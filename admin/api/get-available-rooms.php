<?php
/**
 * API: Get Available Rooms
 * Lấy danh sách phòng khả dụng cho booking
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/security.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $db = getDB();
    
    $booking_id = Security::sanitizeInt($_GET['booking_id'] ?? 0);
    
    if (!$booking_id) {
        throw new Exception('Booking ID không hợp lệ');
    }
    
    // Get booking info
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name
        FROM bookings b
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        WHERE b.booking_id = :booking_id
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Đơn đặt phòng không tồn tại');
    }
    
    // Get available rooms of the same type
    $stmt = $db->prepare("
        SELECT 
            r.*,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM bookings b2
                    WHERE b2.room_id = r.room_id
                    AND b2.status IN ('confirmed', 'checked_in')
                    AND (
                        (b2.check_in_date <= :check_in AND b2.check_out_date > :check_in)
                        OR (b2.check_in_date < :check_out AND b2.check_out_date >= :check_out)
                        OR (b2.check_in_date >= :check_in AND b2.check_out_date <= :check_out)
                    )
                    AND b2.booking_id != :booking_id
                ) THEN 0
                ELSE 1
            END as is_available
        FROM rooms r
        WHERE r.room_type_id = :room_type_id
        AND r.status IN ('available', 'occupied')
        ORDER BY is_available DESC, r.floor ASC, r.room_number ASC
    ");
    
    $stmt->execute([
        ':room_type_id' => $booking['room_type_id'],
        ':check_in' => $booking['check_in_date'],
        ':check_out' => $booking['check_out_date'],
        ':booking_id' => $booking_id
    ]);
    
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'booking' => [
            'booking_code' => $booking['booking_code'],
            'type_name' => $booking['type_name'],
            'check_in_date' => $booking['check_in_date'],
            'check_out_date' => $booking['check_out_date']
        ],
        'rooms' => $rooms
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
