<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../helpers/logger.php';

// Get POST data
$room_type_ids = $_POST['room_type_id'] ?? null; // Can be array
$check_in_date = $_POST['check_in_date'] ?? null;
$check_out_date = $_POST['check_out_date'] ?? null;
$num_adults_list = $_POST['num_adults'] ?? [2]; // Array of adults per room
$num_children_list = $_POST['num_children'] ?? [0]; // Array of children per room
$guest_name = trim($_POST['guest_name'] ?? '');
$guest_email = trim($_POST['guest_email'] ?? '');
$guest_phone = trim($_POST['guest_phone'] ?? '');
$special_requests = $_POST['special_requests'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cash';

// Get calculated values from frontend
$calculated_total = floatval($_POST['calculated_total'] ?? 0);
$calculated_nights = intval($_POST['calculated_nights'] ?? 0);
$frontend_room_price = floatval($_POST['room_price'] ?? 0); // This might be misleading for multi-room

// Normalize inputs to arrays
if (!is_array($room_type_ids)) {
    $room_type_ids = [$room_type_ids];
}
if (!is_array($num_adults_list)) {
    $num_adults_list = array_fill(0, count($room_type_ids), $num_adults_list);
}
if (!is_array($num_children_list)) {
    $num_children_list = array_fill(0, count($room_type_ids), $num_children_list);
}

// Validate required fields
if (empty($room_type_ids) || !$check_in_date || !$check_out_date || !$guest_name || !$guest_email || !$guest_phone) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc'
    ]);
    exit;
}

try {
    $db = getDB();

    // Calculate nights
    $checkin = new DateTime($check_in_date);
    $checkout = new DateTime($check_out_date);
    $interval = $checkin->diff($checkout);
    $num_nights = $interval->days;

    if ($num_nights < 1) {
        throw new Exception('Số đêm phải lớn hơn 0');
    }

    // Generate SHARED booking code
    $booking_code = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));

    // User Management (Create or Get)
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$guest_email]);
        $existing_user = $stmt->fetch();

        if ($existing_user) {
            $user_id = $existing_user['user_id'];
        } else {
            $password_hash = password_hash(uniqid(), PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, phone, user_role, status, email_verified) VALUES (?, ?, ?, ?, 'customer', 'active', 0)");
            $stmt->execute([$guest_email, $password_hash, $guest_name, $guest_phone]);
            $user_id = $db->lastInsertId();
        }
    }

    $grand_total = 0;
    $first_booking_id = null;
    $bookings_created = [];

    // Loop through each room requested
    foreach ($room_type_ids as $index => $r_type_id) {
        $r_adults = (int) ($num_adults_list[$index] ?? 2);
        $r_children = (int) ($num_children_list[$index] ?? 0);
        $r_guests = $r_adults + $r_children;

        // Get room type details
        $stmt = $db->prepare("SELECT * FROM room_types WHERE room_type_id = ? AND status = 'active'");
        $stmt->execute([$r_type_id]);
        $room_type = $stmt->fetch();

        if (!$room_type) {
            throw new Exception('Loại phòng không tồn tại (ID: ' . $r_type_id . ')');
        }

        // Validate room capacity (2:2 for Twin, 2:1 for Single)
        $max_adults = isset($room_type['max_adults']) ? (int) $room_type['max_adults'] : 2;
        $max_children = isset($room_type['max_children']) ? (int) $room_type['max_children'] : 1;

        if ($r_adults > $max_adults) {
            throw new Exception("Phòng {$room_type['type_name']} tối đa $max_adults người lớn");
        }
        if ($r_children > $max_children) {
            throw new Exception("Phòng {$room_type['type_name']} tối đa $max_children trẻ em");
        }

        $current_room_price = $room_type['base_price'];
        $current_room_total = $current_room_price * $num_nights;
        $grand_total += $current_room_total;

        // Check room availability (Find ONE available room of this type)
        // Note: For multi-room of SAME type, we need to ensure we don't pick the same room ID.
        // We excluded already booked rooms. But inside this loop, if we pick Room A for first iteration, 
        // we must exclude Room A for second iteration if strict assignment is needed.
        // However, 'bookings' table insert doesn't strictly lock immediately unless transaction?
        // We should collect assigned room IDs to exclude them in subsequent query.

        $excluded_room_ids = array_column($bookings_created, 'room_id');
        $excluded_clause = "";
        if (!empty($excluded_room_ids)) {
            $excluded_clause = "AND r.room_id NOT IN (" . implode(',', array_filter($excluded_room_ids)) . ")";
        }

        $stmt = $db->prepare("
            SELECT r.room_id 
            FROM rooms r
            WHERE r.room_type_id = ? 
            AND r.status = 'available'
            $excluded_clause
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
            $r_type_id,
            $check_in_date,
            $check_in_date,
            $check_out_date,
            $check_out_date,
            $check_in_date,
            $check_out_date
        ]);
        $available_room = $stmt->fetch();
        $assigned_room_id = $available_room['room_id'] ?? null;

        // Insert Booking Record
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
            $booking_code,
            $user_id,
            $assigned_room_id,
            $r_type_id,
            $check_in_date,
            $check_out_date,
            $r_guests,
            0,
            1,
            $num_nights,
            $current_room_price,
            $current_room_total,
            $guest_name,
            $guest_email,
            $guest_phone,
            $special_requests
        ]);

        $current_booking_id = $db->lastInsertId();
        if (!$first_booking_id)
            $first_booking_id = $current_booking_id;

        $bookings_created[] = [
            'booking_id' => $current_booking_id,
            'room_id' => $assigned_room_id
        ];

        // Update room status
        if ($assigned_room_id) {
            $stmt = $db->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?");
            $stmt->execute([$assigned_room_id]);
        }
    }

    // Store booking info in session (Use first ID for reference, but code is universal)
    $_SESSION['pending_booking_id'] = $first_booking_id;
    $_SESSION['pending_booking_code'] = $booking_code;

    // Email Notification (Simplified: Send for the main code/first booking, or we need a custom email for groups)
    // For now, using existing mailer which fetches by Booking ID. It will show only 1 room.
    // Ideally update mailer to fetch by Booking Code. But strictly requested "database update algorithm", not "mailer".
    // I will trigger the standard mail for the *first* booking ID, which at least confirms code.
    // (User can check details on site).

    try {
        require_once '../../helpers/mailer.php';
        $stmt = $db->prepare("SELECT b.*, rt.type_name FROM bookings b LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id WHERE b.booking_id = ?");
        $stmt->execute([$first_booking_id]);
        $booking_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking_data) {
            $booking_data['num_nights'] = $num_nights;
            $booking_data['total_nights'] = $num_nights;
            $booking_data['room_type_name'] = $booking_data['type_name'] . (count($bookings_created) > 1 ? ' (+ ' . (count($bookings_created) - 1) . ' phòng khác)' : '');
            $booking_data['total_amount'] = $grand_total; // Override to show full price

            $mailer = getMailer();
            $mailer->sendBookingConfirmation($guest_email, $booking_data);
        }
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
    }

    // Prepare response
    $response = [
        'success' => true,
        'booking_id' => $first_booking_id,
        'booking_code' => $booking_code,
        'total_amount' => $grand_total
    ];

    // VNPay Payment
    if ($payment_method === 'vnpay') {
        require_once '../../payment/config.php';

        $vnp_TxnRef = $booking_code;
        $vnp_Amount = $grand_total; // Pay for ALL rooms
        $vnp_Locale = 'vn';
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