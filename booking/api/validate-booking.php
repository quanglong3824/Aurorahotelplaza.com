<?php
/**
 * Booking Validation API
 * Kiểm tra chống spam trước khi submit booking
 * SIẾT CHẶT: Check user đã đăng ký và guest (email)
 */

session_start();
header('Content-Type: application/json');

require_once '../../config/environment.php';
require_once '../../config/database.php';
require_once '../../helpers/booking-validator.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['allowed' => true, 'message' => '']);
    exit;
}

// Get input data
$input_data = $_POST;
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($content_type, 'application/json') !== false) {
    $input_json = file_get_contents('php://input');
    $input_data = json_decode($input_json, true) ?? [];
}

$check_in_date = $input_data['check_in_date'] ?? null;
$check_out_date = $input_data['check_out_date'] ?? null;

// Get user info
$user_id = $_SESSION['user_id'] ?? null;
$guest_email = $input_data['guest_email'] ?? null;
$guest_phone = $input_data['guest_phone'] ?? null;

// 1. Check rate limiting (CHO TẤT CẢ - user và guest)
$rate_limit_id = getRateLimitIdentifier();
$rate_limit = checkRateLimit($rate_limit_id, $max_requests = 5, $time_window = 60); // 5 requests/phút

if (!$rate_limit['allowed']) {
    echo json_encode([
        'allowed' => false,
        'message' => $rate_limit['message'],
        'retry_after' => $rate_limit['retry_after'],
        'type' => 'rate_limit'
    ]);
    exit;
}

// 2. CHECK SPAM (Chặn nếu có booking chưa hoàn tất)
$spam_check = null;

if ($user_id) {
    // User đã đăng nhập
    $spam_check = checkBookingSpam($user_id, null, null);
} else {
    // Guest: Ưu tiên email/phone đang nhập vào form, nhưng bao gồm cả các identifiers đã dùng trên thiết bị này
    $check_emails = [];
    $check_phones = [];
    
    if ($guest_email) $check_emails[] = $guest_email;
    if ($guest_phone) $check_phones[] = $guest_phone;
    
    if (isset($_SESSION['guest_identifiers'])) {
        $check_emails = array_unique(array_merge($check_emails, $_SESSION['guest_identifiers']['emails']));
        $check_phones = array_unique(array_merge($check_phones, $_SESSION['guest_identifiers']['phones']));
    }
    
    if (!empty($check_emails) || !empty($check_phones)) {
        $spam_check = checkBookingSpam(null, $check_emails, $check_phones);
    }
}

if ($spam_check && !$spam_check['allowed']) {
    echo json_encode([
        'allowed' => false,
        'message' => $spam_check['message'],
        'pending_bookings' => $spam_check['pending_bookings'],
        'type' => 'spam'
    ]);
    exit;
}

// 3. Check for overlapping bookings (nếu có ngày) - CHO TẤT CẢ
if ($check_in_date && $check_out_date && ($user_id || $guest_email)) {
    $overlap_check = checkBookingOverlap($user_id, $guest_email, $guest_phone, $check_in_date, $check_out_date);
    
    if (!$overlap_check['allowed']) {
        echo json_encode([
            'allowed' => false,
            'message' => $overlap_check['message'],
            'overlapping_bookings' => $overlap_check['overlapping_bookings'],
            'type' => 'overlap'
        ]);
        exit;
    }
}

// All checks passed
echo json_encode([
    'allowed' => true,
    'message' => ''
]);
