<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../helpers/logger.php';

// Get POST data
$room_type_id = $_POST['room_type_id'] ?? null;
$check_in_date = $_POST['check_in_date'] ?? null;
$check_out_date = $_POST['check_out_date'] ?? null;
$num_guests = $_POST['num_guests'] ?? 1;
$guest_name = trim($_POST['guest_name'] ?? '');
$guest_email = trim($_POST['guest_email'] ?? '');
$guest_phone = trim($_POST['guest_phone'] ?? '');
$special_requests = $_POST['special_requests'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cash';

// Get calculated values from frontend
$calculated_total = floatval($_POST['calculated_total'] ?? 0);
$calculated_nights = intval($_POST['calculated_nights'] ?? 0);
$frontend_room_price = floatval($_POST['room_price'] ?? 0);

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
    $stmt = $db->prepare("SELECT * FROM room_types WHERE room_type_id = ? AND status = 'active'");
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
    
    // Validate calculated values from frontend
    if ($calculated_nights !== $num_nights) {
        error_log("Nights mismatch: frontend=$calculated_nights, backend=$num_nights");
    }
    
    $room_price = $room_type['base_price'];
    
    // Validate frontend price matches backend
    if (abs($frontend_room_price - $room_price) > 0.01) {
        error_log("Price mismatch: frontend=$frontend_room_price, backend=$room_price");
    }
    
    // Use backend calculation for security, but log discrepancies
    $total_amount = $room_price * $num_nights;
    
    if (abs($calculated_total - $total_amount) > 0.01) {
        error_log("Total mismatch: frontend=$calculated_total, backend=$total_amount");
    }
    
    // Generate booking code
    $booking_code = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    
    // Get user_id if logged in
    $user_id = $_SESSION['user_id'] ?? null;
    
    // If not logged in, create guest account or use guest user
    if (!$user_id) {
        // Check if email exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$guest_email]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            $user_id = $existing_user['user_id'];
        } else {
            // Create guest user
            $password_hash = password_hash(uniqid(), PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (email, password_hash, full_name, phone, user_role, status, email_verified) 
                VALUES (?, ?, ?, ?, 'customer', 'active', 0)
            ");
            $stmt->execute([$guest_email, $password_hash, $guest_name, $guest_phone]);
            $user_id = $db->lastInsertId();
        }
    }
    
    // Check room availability
    $stmt = $db->prepare("
        SELECT r.room_id 
        FROM rooms r
        WHERE r.room_type_id = ? 
        AND r.status = 'available'
        AND r.room_id NOT IN (
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
    
    $room_id = $available_room['room_id'] ?? null;
    
    // Create booking
    $stmt = $db->prepare("
        INSERT INTO bookings (
            booking_code, user_id, room_id, room_type_id,
            check_in_date, check_out_date, num_adults, num_children, num_rooms, total_nights,
            room_price, total_amount,
            guest_name, guest_email, guest_phone, special_requests,
            status, payment_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')
    ");
    
    $stmt->execute([
        $booking_code, $user_id, $room_id, $room_type_id,
        $check_in_date, $check_out_date, $num_guests, 0, 1, $num_nights,
        $room_price, $total_amount,
        $guest_name, $guest_email, $guest_phone, $special_requests
    ]);
    
    $booking_id = $db->lastInsertId();
    
    // Update room status if assigned
    if ($room_id) {
        $stmt = $db->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?");
        $stmt->execute([$room_id]);
    }
    
    // Store booking info in session
    $_SESSION['pending_booking_id'] = $booking_id;
    $_SESSION['pending_booking_code'] = $booking_code;
    
    // Send booking confirmation email (don't block booking if email fails)
    $emailSent = false;
    try {
        require_once '../../helpers/mailer.php';
        
        // Get complete booking data for email
        $stmt = $db->prepare("
            SELECT b.*, rt.type_name, rt.category 
            FROM bookings b 
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id 
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($booking_data) {
            // Ensure required fields are set for email template
            $booking_data['num_nights'] = $num_nights; // For old template compatibility
            $booking_data['total_nights'] = $num_nights; // For new template
            $booking_data['num_adults'] = $num_guests;
            $booking_data['room_type_name'] = $booking_data['type_name']; // Alias for template
            
            // Send email using Mailer class
            $mailer = getMailer();
            $emailSent = $mailer->sendBookingConfirmation($guest_email, $booking_data);
            
            if ($emailSent) {
                error_log("✅ Booking confirmation email sent successfully to: $guest_email for booking: $booking_code");
            } else {
                error_log("❌ Failed to send booking confirmation email to: $guest_email for booking: $booking_code");
            }
        } else {
            error_log("❌ Could not fetch booking data for email: $booking_code");
        }
    } catch (Exception $emailError) {
        error_log("Email sending error for booking $booking_code: " . $emailError->getMessage());
        // Don't fail the booking if email fails
    }
    
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
