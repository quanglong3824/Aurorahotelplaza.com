<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../helpers/logger.php';

// Get POST data
$input_data = $_POST;

// Handle JSON input
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($content_type, 'application/json') !== false) {
    $input_json = file_get_contents('php://input');
    $input_data = json_decode($input_json, true) ?? [];
}

$room_type_id = $input_data['room_type_id'] ?? null;
$check_in_date = $input_data['check_in_date'] ?? null;
$check_out_date = $input_data['check_out_date'] ?? null;
$num_guests = $input_data['num_guests'] ?? 1;
$num_adults = intval($input_data['num_adults'] ?? $num_guests);
$num_children = intval($input_data['num_children'] ?? 0);
$guest_name = trim($input_data['guest_name'] ?? '');
$guest_email = trim($input_data['guest_email'] ?? '');
$guest_phone = trim($input_data['guest_phone'] ?? '');
$special_requests = $input_data['special_requests'] ?? '';
$payment_method = $input_data['payment_method'] ?? 'cash';

// Booking type: 'standard', 'short_stay', or 'inquiry'
$booking_type_input = $input_data['booking_type'] ?? 'standard';
$is_inquiry_mode = $booking_type_input === 'inquiry';
$is_short_stay = $booking_type_input === 'short_stay';

// Apartment Inquiry specific fields
$inquiry_message = $input_data['message'] ?? $input_data['inquiry_message'] ?? '';
$duration_type = $input_data['duration_type'] ?? 'short_term';

// Extra guests and beds
$extra_beds = intval($input_data['extra_beds'] ?? 0);
$extra_guest_fee = floatval($input_data['extra_guest_fee'] ?? 0);
$extra_bed_fee = floatval($input_data['extra_bed_fee'] ?? 0);
$extra_guests_data = $input_data['extra_guests_data'] ?? '[]';

// Get calculated values from frontend
$calculated_total = floatval($input_data['calculated_total'] ?? 0);
$calculated_nights = intval($input_data['calculated_nights'] ?? 0);
$frontend_room_price = floatval($input_data['room_price'] ?? 0);
$price_type_used = $input_data['price_type_used'] ?? 'double';

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

    // For short stay, we count as 1 night but use short stay price
    if ($is_short_stay) {
        $num_nights = 1;
    } elseif ($num_nights < 1) {
        throw new Exception('Số đêm phải lớn hơn 0');
    }

    // Validate calculated values from frontend
    if ($calculated_nights !== $num_nights && !$is_short_stay) {
        error_log("Nights mismatch: frontend=$calculated_nights, backend=$num_nights");
    }

    // Determine room price based on number of guests, booking type, and category
    $category = $room_type['category'] ?? 'room';

    // Check for short stay first
    if ($is_short_stay && $category === 'room') {
        if (!empty($room_type['price_short_stay'])) {
            $room_price = (float) $room_type['price_short_stay'];
            $price_type_used = 'short_stay';
        } else {
            throw new Exception('Loại phòng này không hỗ trợ nghỉ ngắn hạn');
        }
    } elseif ($category === 'room') {
        // Hotel Room: use single/double pricing
        if ($num_adults == 1 && !empty($room_type['price_single_occupancy'])) {
            $room_price = (float) $room_type['price_single_occupancy'];
            $price_type_used = 'single';
        } else {
            $room_price = !empty($room_type['price_double_occupancy'])
                ? (float) $room_type['price_double_occupancy']
                : (float) $room_type['base_price'];
            $price_type_used = 'double';
        }
    } else {
        // Apartment: use daily/weekly pricing
        if ($num_nights >= 7) {
            // Weekly rate
            if ($num_adults == 1 && !empty($room_type['price_avg_weekly_single'])) {
                $room_price = (float) $room_type['price_avg_weekly_single'];
                $price_type_used = 'weekly';
            } elseif (!empty($room_type['price_avg_weekly_double'])) {
                $room_price = (float) $room_type['price_avg_weekly_double'];
                $price_type_used = 'weekly';
            } else {
                $room_price = (float) $room_type['base_price'];
                $price_type_used = 'daily';
            }
        } else {
            // Daily rate
            if ($num_adults == 1 && !empty($room_type['price_daily_single'])) {
                $room_price = (float) $room_type['price_daily_single'];
                $price_type_used = 'daily';
            } elseif (!empty($room_type['price_daily_double'])) {
                $room_price = (float) $room_type['price_daily_double'];
                $price_type_used = 'daily';
            } else {
                $room_price = (float) $room_type['base_price'];
                $price_type_used = 'daily';
            }
        }
    }

    // Validate frontend price matches backend (with tolerance)
    if (abs($frontend_room_price - $room_price) > 1000) {
        error_log("Price mismatch: frontend=$frontend_room_price, backend=$room_price (type=$price_type_used)");
    }

    // Calculate room subtotal
    $room_subtotal = $room_price * $num_nights;

    // Validate extra fees from frontend (trust frontend calculation for now)
    // In production, recalculate on backend for security
    $backend_extra_guest_fee = $extra_guest_fee; // Use frontend value
    $backend_extra_bed_fee = $extra_bed_fee;     // Use frontend value

    // Calculate total amount
    $total_amount = $room_subtotal + $backend_extra_guest_fee + $backend_extra_bed_fee;

    if (abs($calculated_total - $total_amount) > 1000) {
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

    // Check if this is an inquiry-type booking (apartments)
    // Determine booking type based on room_type settings
    $booking_type = $room_type['booking_type'] ?? 'instant';
    if ($booking_type === 'inquiry' || $is_inquiry_mode) {
        $booking_type = 'inquiry';
    }

    // For inquiry bookings (apartments), skip room availability check
    $room_id = null;
    if ($booking_type === 'instant') {
        // Check room availability only for instant bookings
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
            $check_in_date,
            $check_in_date,
            $check_out_date,
            $check_out_date,
            $check_in_date,
            $check_out_date
        ]);
        $available_room = $stmt->fetch();
        $room_id = $available_room['room_id'] ?? null;
    }

    // Determine initial status based on booking type
    $initial_status = $booking_type === 'inquiry' ? 'pending' : 'pending';

    // Create booking with booking_type and inquiry fields
    $stmt = $db->prepare("
        INSERT INTO bookings (
            booking_code, booking_type, user_id, room_id, room_type_id,
            check_in_date, check_out_date, num_adults, num_children, num_rooms, total_nights,
            room_price, total_amount,
            guest_name, guest_email, guest_phone, special_requests,
            inquiry_message, duration_type,
            status, payment_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");

    // For inquiry bookings, payment status is N/A
    $payment_status = $booking_type === 'inquiry' ? 'unpaid' : 'unpaid';

    $stmt->execute([
        $booking_code,
        $booking_type,
        $user_id,
        $room_id,
        $room_type_id,
        $check_in_date,
        $check_out_date,
        $num_guests,
        0,
        1,
        $num_nights,
        $room_price,
        $total_amount,
        $guest_name,
        $guest_email,
        $guest_phone,
        $special_requests,
        $inquiry_message,
        $duration_type,
        $payment_status
    ]);

    $booking_id = $db->lastInsertId();

    // Update room status if assigned (only for instant bookings)
    if ($room_id && $booking_type === 'instant') {
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
                error_log("Booking confirmation email sent successfully to: $guest_email for booking: $booking_code");
            } else {
                error_log("Failed to send booking confirmation email to: $guest_email for booking: $booking_code");
            }
        } else {
            error_log("Could not fetch booking data for email: $booking_code");
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
        'booking_type' => $booking_type,
        'total_amount' => $total_amount
    ];

    // For inquiry bookings, return a success message (no payment processing)
    if ($booking_type === 'inquiry') {
        $response['message'] = 'Yêu cầu tư vấn của bạn đã được gửi thành công! Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất.';
        echo json_encode($response);
        exit;
    }

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