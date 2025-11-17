<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';

// Get POST data
$room_type_id = $_POST['room_type_id'] ?? null;
$check_in_date = $_POST['check_in_date'] ?? null;
$check_out_date = $_POST['check_out_date'] ?? null;
$num_guests = $_POST['num_guests'] ?? 1;
$guest_name = $_POST['guest_name'] ?? '';
$guest_email = $_POST['guest_email'] ?? '';
$guest_phone = $_POST['guest_phone'] ?? '';
$special_requests = $_POST['special_requests'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cash';

// Validate required fields
if (!$room_type_id || !$check_in_date || !$check_out_date || !$guest_name || !$guest_email || !$guest_phone) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc'
    ]);
    exit;
}

try {
    $db = getDB();
    
    // Get room type details
    $stmt = $db->prepare("SELECT * FROM room_types WHERE id = ? AND is_active = 1");
    $stmt->execute([$room_type_id]);
    $room_type = $stmt->fetch();
    
    if (!$room_type) {
        throw new Exception('Loại phòng không tồn tại');
    }
    
    // Calculate nights and total
    $checkin = new DateTime($check_in_date);
    $checkout = new DateTime($check_out_date);
    $interval = $checkin->diff($checkout);
    $num_nights = $interval->days;
    
    if ($num_nights < 1) {
        throw new Exception('Số đêm phải lớn hơn 0');
    }
    
    $room_price = $room_type['base_price'];
    $total_amount = $room_price * $num_nights;
    
    // Generate booking code
    $booking_code = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    
    // Get user_id if logged in
    $user_id = $_SESSION['user_id'] ?? null;
    
    // If not logged in, create guest account or use guest user
    if (!$user_id) {
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$guest_email]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            $user_id = $existing_user['id'];
        } else {
            // Create guest user
            $username = 'guest_' . time() . rand(1000, 9999);
            $password_hash = password_hash(uniqid(), PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password_hash, full_name, phone, role, status) 
                VALUES (?, ?, ?, ?, ?, 'customer', 'active')
            ");
            $stmt->execute([$username, $guest_email, $password_hash, $guest_name, $guest_phone]);
            $user_id = $db->lastInsertId();
        }
    }
    
    // Check room availability
    $stmt = $db->prepare("
        SELECT r.id 
        FROM rooms r
        WHERE r.room_type_id = ? 
        AND r.status = 'available'
        AND r.id NOT IN (
            SELECT room_id 
            FROM bookings 
            WHERE room_id IS NOT NULL
            AND status NOT IN ('cancelled', 'checked_out')
            AND (
                (check_in_date <= ? AND check_out_date > ?)
                OR (check_in_date < ? AND check_out_date >= ?)
                OR (check_in_date >= ? AND check_out_date <= ?)
            )
        )
        LIMIT 1
    ");
    $stmt->execute([
        $room_type_id,
        $check_in_date, $check_in_date,
        $check_out_date, $check_out_date,
        $check_in_date, $check_out_date
    ]);
    $available_room = $stmt->fetch();
    
    $room_id = $available_room['id'] ?? null;
    
    // Create booking
    $stmt = $db->prepare("
        INSERT INTO bookings (
            booking_code, user_id, room_id, room_type_id,
            check_in_date, check_out_date, num_guests, num_nights,
            room_price, total_amount,
            guest_name, guest_email, guest_phone, special_requests,
            status, payment_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')
    ");
    
    $stmt->execute([
        $booking_code, $user_id, $room_id, $room_type_id,
        $check_in_date, $check_out_date, $num_guests, $num_nights,
        $room_price, $total_amount,
        $guest_name, $guest_email, $guest_phone, $special_requests
    ]);
    
    $booking_id = $db->lastInsertId();
    
    // Update room status if assigned
    if ($room_id) {
        $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ?");
        $stmt->execute([$room_id]);
    }
    
    // Store booking info in session
    $_SESSION['pending_booking_id'] = $booking_id;
    $_SESSION['pending_booking_code'] = $booking_code;
    
    // Prepare response
    $response = [
        'success' => true,
        'booking_id' => $booking_id,
        'booking_code' => $booking_code,
        'total_amount' => $total_amount
    ];
    
    // If VNPay payment, create payment URL
    if ($payment_method === 'vnpay') {
        require_once '../../payment/config.php';
        
        $vnp_TxnRef = $booking_code;
        $vnp_Amount = $total_amount;
        $vnp_Locale = 'vn';
        $vnp_BankCode = '';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => "Thanh toan dat phong " . $vnp_TxnRef,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => str_replace('/payment/', '/booking/', $vnp_Returnurl),
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ExpireDate" => $expire
        );
        
        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        
        $vnp_Url_full = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url_full .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        
        $response['payment_url'] = $vnp_Url_full;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
