<?php
/**
 * API: Cập nhật field của booking (room_type_id, total_amount)
 */
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $booking_id = (int)($input['booking_id'] ?? 0);
    $field = $input['field'] ?? '';
    $value = $input['value'] ?? null;

    if (!$booking_id || !$field || $value === null) {
        echo json_encode(['success' => false, 'message' => 'Thiếu tham số']);
        exit;
    }

    // Chỉ cho phép cập nhật các field này
    $allowed_fields = ['room_type_id', 'room_price'];
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['success' => false, 'message' => 'Field không được phép']);
        exit;
    }

    $db = getDB();

    // Lấy booking hiện tại
    $stmt = $db->prepare("SELECT status FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn']);
        exit;
    }

    // Không cho sửa khi đã check-out hoặc hủy
    if (in_array($booking['status'], ['checked_out', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Không thể sửa đơn đã ' . $booking['status']]);
        exit;
    }

    // Validate room_type_id
    if ($field === 'room_type_id') {
        $value = (int)$value;
        $stmt = $db->prepare("SELECT type_name FROM room_types WHERE room_type_id = ? AND status = 'active'");
        $stmt->execute([$value]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Loại phòng không hợp lệ']);
            exit;
        }
    }

    // Validate room_price
    if ($field === 'room_price') {
        $value = (int)$value;
        if ($value < 0) {
            echo json_encode(['success' => false, 'message' => 'Giá không hợp lệ']);
            exit;
        }
    }

    // Cập nhật
    if ($field === 'room_price') {
        // Khi cập nhật room_price, cũng cập nhật total_amount bằng giá trị tương tự
        $stmt = $db->prepare("UPDATE bookings SET room_price = ?, total_amount = ?, updated_at = NOW() WHERE booking_id = ?");
        $stmt->execute([$value, $value, $booking_id]);
    } else {
        $stmt = $db->prepare("UPDATE bookings SET $field = ?, updated_at = NOW() WHERE booking_id = ?");
        $stmt->execute([$value, $booking_id]);
    }

    // Log history
    $labels = [
        'room_type_id' => 'Loại phòng',
        'room_price' => 'Đơn giá phòng'
    ];
    $label = $labels[$field] ?? $field;
    $stmt = $db->prepare("
        INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $booking_id,
        $label . ' cũ',
        $label . ' mới',
        $_SESSION['user_id'],
        "Admin cập nhật $label: $value"
    ]);

    echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
