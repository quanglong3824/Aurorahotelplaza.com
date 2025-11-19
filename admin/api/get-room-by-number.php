<?php
/**
 * API: Get Room by Number
 * Tìm phòng theo số phòng
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/auth-middleware.php';

header('Content-Type: application/json');

AuthMiddleware::requireStaff();

try {
    $room_number = $_GET['room_number'] ?? '';
    
    if (empty($room_number)) {
        throw new Exception('Room number is required');
    }
    
    $db = getDB();
    
    // Get room with current booking info
    $stmt = $db->prepare("
        SELECT r.*, rt.type_name, rt.category, rt.base_price,
               (SELECT b.booking_id 
                FROM bookings b 
                WHERE b.room_id = r.room_id 
                AND b.check_in_date <= CURDATE() 
                AND b.check_out_date > CURDATE()
                AND b.status IN ('confirmed', 'checked_in')
                ORDER BY b.check_in_date DESC
                LIMIT 1) as current_booking_id,
               (SELECT b.booking_code 
                FROM bookings b 
                WHERE b.room_id = r.room_id 
                AND b.check_in_date <= CURDATE() 
                AND b.check_out_date > CURDATE()
                AND b.status IN ('confirmed', 'checked_in')
                ORDER BY b.check_in_date DESC
                LIMIT 1) as booking_code,
               (SELECT b.status 
                FROM bookings b 
                WHERE b.room_id = r.room_id 
                AND b.check_in_date <= CURDATE() 
                AND b.check_out_date > CURDATE()
                AND b.status IN ('confirmed', 'checked_in')
                ORDER BY b.check_in_date DESC
                LIMIT 1) as booking_status,
               (SELECT u.full_name 
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.user_id
                WHERE b.room_id = r.room_id 
                AND b.check_in_date <= CURDATE() 
                AND b.check_out_date > CURDATE()
                AND b.status IN ('confirmed', 'checked_in')
                ORDER BY b.check_in_date DESC
                LIMIT 1) as guest_name,
               (SELECT b.check_in_date 
                FROM bookings b 
                WHERE b.room_id = r.room_id 
                AND b.check_in_date <= CURDATE() 
                AND b.check_out_date > CURDATE()
                AND b.status IN ('confirmed', 'checked_in')
                ORDER BY b.check_in_date DESC
                LIMIT 1) as check_in_date,
               (SELECT b.check_out_date 
                FROM bookings b 
                WHERE b.room_id = r.room_id 
                AND b.check_in_date <= CURDATE() 
                AND b.check_out_date > CURDATE()
                AND b.status IN ('confirmed', 'checked_in')
                ORDER BY b.check_in_date DESC
                LIMIT 1) as check_out_date
        FROM rooms r
        LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE r.room_number = :room_number
    ");
    
    $stmt->execute([':room_number' => $room_number]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        echo json_encode([
            'success' => false,
            'message' => 'Room not found'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'room' => $room
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
