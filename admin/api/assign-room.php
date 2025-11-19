<?php
/**
 * API: Assign Room to Booking
 * Admin phân phòng cụ thể cho đơn đặt phòng
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/security.php';
require_once '../../helpers/activity-logger.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $db = getDB();
    
    $booking_id = Security::sanitizeInt($_POST['booking_id'] ?? 0);
    $room_id = Security::sanitizeInt($_POST['room_id'] ?? 0);
    
    if (!$booking_id || !$room_id) {
        throw new Exception('Thông tin không hợp lệ');
    }
    
    $db->beginTransaction();
    
    // Get booking info
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name, rt.room_type_id
        FROM bookings b
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        WHERE b.booking_id = :booking_id
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Đơn đặt phòng không tồn tại');
    }
    
    // Check if booking already has a room
    if ($booking['room_id']) {
        throw new Exception('Đơn này đã được phân phòng');
    }
    
    // Check booking status
    if (!in_array($booking['status'], ['confirmed', 'pending'])) {
        throw new Exception('Chỉ có thể phân phòng cho đơn đã xác nhận hoặc chờ xác nhận');
    }
    
    // Get room info
    $stmt = $db->prepare("
        SELECT r.*, rt.type_name
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE r.room_id = :room_id
    ");
    $stmt->execute([':room_id' => $room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        throw new Exception('Phòng không tồn tại');
    }
    
    // Validate room type matches booking
    if ($room['room_type_id'] != $booking['room_type_id']) {
        throw new Exception('Loại phòng không khớp với đơn đặt phòng');
    }
    
    // Check room availability
    if ($room['status'] !== 'available') {
        throw new Exception('Phòng không khả dụng. Trạng thái hiện tại: ' . $room['status']);
    }
    
    // Check if room is already booked for these dates
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM bookings
        WHERE room_id = :room_id
        AND status IN ('confirmed', 'checked_in')
        AND (
            (check_in_date <= :check_in AND check_out_date > :check_in)
            OR (check_in_date < :check_out AND check_out_date >= :check_out)
            OR (check_in_date >= :check_in AND check_out_date <= :check_out)
        )
        AND booking_id != :booking_id
    ");
    
    $stmt->execute([
        ':room_id' => $room_id,
        ':check_in' => $booking['check_in_date'],
        ':check_out' => $booking['check_out_date'],
        ':booking_id' => $booking_id
    ]);
    
    $conflict = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($conflict > 0) {
        throw new Exception('Phòng đã được đặt trong khoảng thời gian này');
    }
    
    // Assign room to booking
    $stmt = $db->prepare("
        UPDATE bookings 
        SET room_id = :room_id,
            status = 'confirmed',
            updated_at = NOW()
        WHERE booking_id = :booking_id
    ");
    
    $stmt->execute([
        ':room_id' => $room_id,
        ':booking_id' => $booking_id
    ]);
    
    // Update room status
    $stmt = $db->prepare("
        UPDATE rooms 
        SET status = 'occupied',
            updated_at = NOW()
        WHERE room_id = :room_id
    ");
    
    $stmt->execute([':room_id' => $room_id]);
    
    // Add to booking history
    $stmt = $db->prepare("
        INSERT INTO booking_history (
            booking_id, old_status, new_status,
            changed_by, notes, created_at
        ) VALUES (
            :booking_id, :old_status, 'confirmed',
            :changed_by, :notes, NOW()
        )
    ");
    
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':old_status' => $booking['status'],
        ':changed_by' => $_SESSION['user_id'],
        ':notes' => "Phân phòng {$room['room_number']}"
    ]);
    
    // Log activity
    ActivityLogger::logRoomAssign(
        $_SESSION['user_id'],
        $booking_id,
        $booking['booking_code'],
        $room['room_number']
    );
    
    // Create notification for customer
    $stmt = $db->prepare("
        INSERT INTO notifications (
            user_id, type, title, message, link, icon, created_at
        ) VALUES (
            :user_id, 'room_assigned', :title, :message, :link, 'meeting_room', NOW()
        )
    ");
    
    $notif_message = sprintf(
        'Đơn đặt phòng %s đã được phân phòng %s. Check-in: %s',
        $booking['booking_code'],
        $room['room_number'],
        date('d/m/Y', strtotime($booking['check_in_date']))
    );
    
    $stmt->execute([
        ':user_id' => $booking['user_id'],
        ':title' => 'Đã phân phòng',
        ':message' => $notif_message,
        ':link' => '/profile/bookings.php?id=' . $booking_id
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Phân phòng thành công',
        'data' => [
            'booking_id' => $booking_id,
            'room_id' => $room_id,
            'room_number' => $room['room_number'],
            'floor' => $room['floor'],
            'building' => $room['building']
        ]
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Assign room error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
