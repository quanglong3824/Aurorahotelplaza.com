<?php
/**
 * API: Smart Search Booking
 * Tìm kiếm booking thông minh với mã ngắn
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/booking-helper.php';
require_once '../../helpers/auth-middleware.php';

header('Content-Type: application/json');

AuthMiddleware::requireStaff();

try {
    $search = $_GET['search'] ?? '';
    
    if (empty($search)) {
        echo json_encode([
            'success' => true,
            'bookings' => [],
            'hints' => BookingHelper::getSearchHints()
        ]);
        exit;
    }
    
    $db = getDB();
    
    // Search using smart code
    $bookings = BookingHelper::searchBySmartCode($db, $search);
    
    // Format results
    $results = [];
    foreach ($bookings as $booking) {
        $results[] = [
            'booking_id' => $booking['booking_id'],
            'booking_code' => $booking['booking_code'],
            'short_code' => BookingHelper::getShortCode($booking['booking_code']),
            'booking_date' => BookingHelper::getDateFromCode($booking['booking_code']),
            'guest_name' => $booking['guest_name'],
            'guest_email' => $booking['guest_email'],
            'guest_phone' => $booking['guest_phone'],
            'type_name' => $booking['type_name'],
            'room_number' => $booking['room_number'],
            'check_in_date' => $booking['check_in_date'],
            'check_out_date' => $booking['check_out_date'],
            'total_amount' => $booking['total_amount'],
            'status' => $booking['status'],
            'payment_status' => $booking['payment_status'],
            'created_at' => $booking['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'bookings' => $results,
        'count' => count($results),
        'search_term' => $search,
        'parsed_codes' => BookingHelper::parseSmartCode($search)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
