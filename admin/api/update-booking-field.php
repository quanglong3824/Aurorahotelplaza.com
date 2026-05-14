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
    $allowed_fields = ['room_type_id', 'total_amount', 'check_in_date', 'check_out_date'];
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

    // Validate total_amount
    if ($field === 'total_amount') {
        $value = (int)$value;
        if ($value < 0) {
            echo json_encode(['success' => false, 'message' => 'Giá không hợp lệ']);
            exit;
        }
    }

    // Validate dates
    if (in_array($field, ['check_in_date', 'check_out_date'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            echo json_encode(['success' => false, 'message' => 'Định dạng ngày không hợp lệ']);
            exit;
        }

        // Lấy booking đầy đủ để kiểm tra logic ngày
        $stmt = $db->prepare("SELECT check_in_date, check_out_date FROM bookings WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $dates = $stmt->fetch();

        $currentCheckIn = $dates['check_in_date'];
        $currentCheckOut = $dates['check_out_date'];

        if ($field === 'check_in_date') {
            $newCheckIn = $value;
            $newCheckOut = $currentCheckOut;
        } else {
            $newCheckIn = $currentCheckIn;
            $newCheckOut = $value;
        }

        if (strtotime($newCheckIn) >= strtotime($newCheckOut)) {
            echo json_encode(['success' => false, 'message' => 'Ngày check-in phải trước ngày check-out']);
            exit;
        }
    }

    // Cập nhật
    $stmt = $db->prepare("UPDATE bookings SET $field = ?, updated_at = NOW() WHERE booking_id = ?");
    $stmt->execute([$value, $booking_id]);

    // Log history
    $labels = [
        'room_type_id' => 'Loại phòng',
        'total_amount' => 'Tổng tiền',
        'check_in_date' => 'Ngày check-in',
        'check_out_date' => 'Ngày check-out'
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
