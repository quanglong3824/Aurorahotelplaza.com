<?php
/**
 * API: Quick View Booking
 * Xem nhanh thông tin booking và khách hàng
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/auth-middleware.php';
require_once '../../helpers/booking-helper.php';

header('Content-Type: application/json');

AuthMiddleware::requireStaff();

try {
    $booking_id = $_GET['booking_id'] ?? 0;
    
    if (!$booking_id) {
        throw new Exception('Booking ID is required');
    }
    
    $db = getDB();
    
    // Get booking with customer info
    $stmt = $db->prepare("
        SELECT 
            b.*,
            u.user_id,
            u.full_name,
            u.email,
            u.phone,
            u.created_at as customer_since,
            rt.type_name,
            r.room_number,
            ul.current_points,
            ul.lifetime_points,
            mt.tier_name,
            mt.color_code,
            mt.discount_percentage
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
        LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
        WHERE b.booking_id = :booking_id
    ");
    
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Get customer booking stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status IN ('confirmed', 'checked_in', 'checked_out') THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_spent,
            MAX(created_at) as last_booking_date
        FROM bookings
        WHERE user_id = :user_id
    ");
    
    $stmt->execute([':user_id' => $booking['user_id']]);
    $customer_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent bookings of this customer
    $stmt = $db->prepare("
        SELECT 
            b.booking_id,
            b.booking_code,
            b.check_in_date,
            b.check_out_date,
            b.total_amount,
            b.status,
            b.payment_status,
            rt.type_name
        FROM bookings b
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        WHERE b.user_id = :user_id
        AND b.booking_id != :current_booking_id
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    
    $stmt->execute([
        ':user_id' => $booking['user_id'],
        ':current_booking_id' => $booking_id
    ]);
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment history for this booking
    $stmt = $db->prepare("
        SELECT * FROM payments
        WHERE booking_id = :booking_id
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([':booking_id' => $booking_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    $response = [
        'success' => true,
        'booking' => [
            'booking_id' => $booking['booking_id'],
            'booking_code' => $booking['booking_code'],
            'short_code' => BookingHelper::getShortCode($booking['booking_code']),
            'booking_date' => BookingHelper::getDateFromCode($booking['booking_code']),
            'status' => $booking['status'],
            'payment_status' => $booking['payment_status'],
            'check_in_date' => $booking['check_in_date'],
            'check_out_date' => $booking['check_out_date'],
            'num_adults' => $booking['num_adults'],
            'num_children' => $booking['num_children'],
            'num_rooms' => $booking['num_rooms'],
            'total_nights' => $booking['total_nights'],
            'room_price' => $booking['room_price'],
            'discount_amount' => $booking['discount_amount'],
            'total_amount' => $booking['total_amount'],
            'type_name' => $booking['type_name'],
            'room_number' => $booking['room_number'],
            'special_requests' => $booking['special_requests'],
            'created_at' => $booking['created_at']
        ],
        'customer' => [
            'user_id' => $booking['user_id'],
            'full_name' => $booking['full_name'],
            'email' => $booking['email'],
            'phone' => $booking['phone'],
            'customer_since' => $booking['customer_since'],
            'current_points' => $booking['current_points'] ?? 0,
            'lifetime_points' => $booking['lifetime_points'] ?? 0,
            'tier_name' => $booking['tier_name'],
            'tier_color' => $booking['color_code'],
            'discount_percentage' => $booking['discount_percentage']
        ],
        'customer_stats' => $customer_stats,
        'recent_bookings' => $recent_bookings,
        'payments' => $payments
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
