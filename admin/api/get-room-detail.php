<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$room_id = $_GET['room_id'] ?? null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Room ID required']);
    exit;
}

try {
    $db = getDB();
    
    // Get room details
    $stmt = $db->prepare("
        SELECT r.*, rt.type_name, rt.category, rt.base_price, rt.max_occupancy
        FROM rooms r
        LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE r.room_id = :room_id
    ");
    $stmt->execute([':room_id' => $room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }
    
    // Get current booking with full guest info
    $stmt = $db->prepare("
        SELECT b.*, 
               COALESCE(u.full_name, b.guest_name) as guest_name, 
               COALESCE(u.email, b.guest_email) as email, 
               COALESCE(u.phone, b.guest_phone) as phone,
               b.guest_id_number as id_number,
               u.address, u.date_of_birth, u.gender,
               u.created_at as member_since, rt.type_name as room_type_name
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        WHERE b.room_id = :room_id
        AND b.check_in_date <= CURDATE()
        AND b.check_out_date > CURDATE()
        AND b.status IN ('confirmed', 'checked_in')
        ORDER BY b.check_in_date DESC
        LIMIT 1
    ");
    $stmt->execute([':room_id' => $room_id]);
    $current_booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get booking history (last 10 bookings)
    $stmt = $db->prepare("
        SELECT b.*, 
               COALESCE(u.full_name, b.guest_name) as guest_name,
               COALESCE(u.phone, b.guest_phone) as phone,
               COALESCE(u.email, b.guest_email) as email,
               rt.type_name as room_type_name
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        WHERE b.room_id = :room_id
        AND b.status IN ('completed', 'checked_out', 'cancelled')
        ORDER BY b.check_out_date DESC
        LIMIT 10
    ");
    $stmt->execute([':room_id' => $room_id]);
    $booking_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get activity logs for this room's bookings
    $stmt = $db->prepare("
        SELECT al.*, u.full_name as admin_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        WHERE al.entity_type = 'booking'
        AND al.entity_id IN (
            SELECT booking_id FROM bookings WHERE room_id = :room_id
        )
        ORDER BY al.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([':room_id' => $room_id]);
    $activity_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'room' => $room,
        'current_booking' => $current_booking ?: null,
        'booking_history' => $booking_history,
        'activity_logs' => $activity_logs
    ]);
    
} catch (Exception $e) {
    error_log("Get room detail error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
