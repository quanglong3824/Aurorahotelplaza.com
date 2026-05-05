<?php
/**
 * Delete Booking API - Xóa cứng đặt phòng
 * Xóa vĩnh viễn booking và TẤT CẢ dữ liệu liên quan
 * CHỈ ADMIN MỚI CÓ QUYỀN
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';
require_once '../../helpers/activity-logger.php';

// Chỉ admin mới được xóa booking
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Chỉ admin mới có quyền xóa đặt phòng']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$booking_id = (int) ($_POST['booking_id'] ?? 0);

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Booking ID không hợp lệ']);
    exit;
}

try {
    $db = getDB();

    // Kiểm tra booking tồn tại
    $stmt = $db->prepare("SELECT booking_id, booking_code, guest_name, status, room_id FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đặt phòng']);
        exit;
    }

    // Bắt đầu transaction
    $db->beginTransaction();

    // Tắt foreign key checks để xóa theo thứ tự bất kỳ
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    $deleted_counts = [];

    // 1. Xóa service_bookings liên quan
    try {
        $stmt = $db->prepare("DELETE FROM service_bookings WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $deleted_counts['service_bookings'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['service_bookings'] = 0;
    }

    // 2. Xóa booking_services liên quan (nếu bảng tồn tại)
    try {
        $stmt = $db->prepare("DELETE FROM booking_services WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $deleted_counts['booking_services'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['booking_services'] = 0;
    }

    // 3. Xóa payments liên quan
    try {
        $stmt = $db->prepare("DELETE FROM payments WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $deleted_counts['payments'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['payments'] = 0;
    }

    // 4. Xóa booking_history liên quan
    try {
        $stmt = $db->prepare("DELETE FROM booking_history WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $deleted_counts['booking_history'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['booking_history'] = 0;
    }

    // 5. Xóa booking_extra_guests liên quan
    try {
        $stmt = $db->prepare("DELETE FROM booking_extra_guests WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $deleted_counts['booking_extra_guests'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['booking_extra_guests'] = 0;
    }

    // 6. Xóa notifications liên quan đến booking
    try {
        $stmt = $db->prepare("DELETE FROM notifications WHERE entity_type = 'booking' AND entity_id = ?");
        $stmt->execute([$booking_id]);
        $deleted_counts['notifications'] = $stmt->rowCount();
    } catch (Exception $e) {
        $deleted_counts['notifications'] = 0;
    }

    // 7. Xóa activity_logs liên quan đến booking
    try {
        $stmt = $db->prepare("DELETE FROM activity_logs WHERE entity_type = 'booking' AND entity_id = ?");
        $stmt->execute([$booking_id]);
        $deleted_counts['activity_logs'] = $stmt->rowCount();
    } catch (Exception $e) {
        // Hoặc set NULL nếu không xóa được
        try {
            $stmt = $db->prepare("UPDATE activity_logs SET entity_id = NULL WHERE entity_type = 'booking' AND entity_id = ?");
            $stmt->execute([$booking_id]);
        } catch (Exception $e2) {}
        $deleted_counts['activity_logs'] = 0;
    }

    // 8. Nếu phòng đang ở trạng thái 'occupied' do booking này, trả về 'available'
    if ($booking['room_id'] && in_array($booking['status'], ['confirmed', 'checked_in'])) {
        try {
            $stmt = $db->prepare("UPDATE rooms SET status = 'available', updated_at = NOW() WHERE room_id = ?");
            $stmt->execute([$booking['room_id']]);
            $deleted_counts['room_released'] = 1;
        } catch (Exception $e) {
            $deleted_counts['room_released'] = 0;
        }
    }

    // 9. Cuối cùng - Xóa booking chính
    $stmt = $db->prepare("DELETE FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $deleted_counts['bookings'] = $stmt->rowCount();

    // Bật lại foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Commit transaction
    $db->commit();

    // Log activity
    if (function_exists('logActivity')) {
        logActivity(
            'delete_booking',
            'booking',
            $booking_id,
            "Xóa vĩnh viễn đặt phòng: {$booking['booking_code']} - Khách: {$booking['guest_name']}"
        );
    }

    echo json_encode([
        'success' => true,
        'message' => "Đã xóa vĩnh viễn đặt phòng #{$booking['booking_code']}",
        'deleted_counts' => $deleted_counts
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    error_log("Delete booking error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xóa đặt phòng: ' . $e->getMessage()
    ]);
}
