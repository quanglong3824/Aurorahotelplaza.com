<?php
/**
 * Update Full Room Info - API cho sơ đồ phòng
 * Cho phép cập nhật toàn bộ thông tin phòng từ modal room-map
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$room_id      = (int)($data['room_id'] ?? 0);
$room_type_id = (int)($data['room_type_id'] ?? 0);
$room_number  = trim($data['room_number'] ?? '');
$floor        = (int)($data['floor'] ?? 0);
$status       = $data['status'] ?? 'available';
$notes        = trim($data['notes'] ?? '');
$last_cleaned = $data['last_cleaned'] ?? null;

$allowed_status = ['available', 'occupied', 'maintenance', 'cleaning', 'reserved', 'inactive'];

if (!$room_id || !$room_type_id || !$room_number) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
    exit;
}

if (!in_array($status, $allowed_status)) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
    exit;
}

if (empty($last_cleaned)) {
    $last_cleaned = null;
}

try {
    $db = getDB();

    // Kiểm tra số phòng trùng (ngoài phòng hiện tại)
    $stmt = $db->prepare("SELECT room_id FROM rooms WHERE room_number = :number AND room_id != :id");
    $stmt->execute([':number' => $room_number, ':id' => $room_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => "Số phòng '{$room_number}' đã tồn tại"]);
        exit;
    }

    // Kiểm tra room_type hợp lệ
    $stmt = $db->prepare("SELECT room_type_id, type_name, category FROM room_types WHERE room_type_id = :id AND status = 'active'");
    $stmt->execute([':id' => $room_type_id]);
    $room_type = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room_type) {
        echo json_encode(['success' => false, 'message' => 'Loại phòng không hợp lệ']);
        exit;
    }

    $stmt = $db->prepare("
        UPDATE rooms SET
            room_type_id = :room_type_id,
            room_number  = :room_number,
            floor        = :floor,
            status       = :status,
            notes        = :notes,
            last_cleaned = :last_cleaned,
            updated_at   = NOW()
        WHERE room_id = :room_id
    ");
    $stmt->execute([
        ':room_type_id' => $room_type_id,
        ':room_number'  => $room_number,
        ':floor'        => $floor,
        ':status'       => $status,
        ':notes'        => $notes,
        ':last_cleaned' => $last_cleaned,
        ':room_id'      => $room_id,
    ]);

    // Log activity
    if (function_exists('logActivity')) {
        logActivity($_SESSION['user_id'], 'update_room', "Cập nhật phòng {$room_number} (loại: {$room_type['type_name']})");
    }

    echo json_encode([
        'success'   => true,
        'message'   => "Đã cập nhật phòng {$room_number} thành công",
        'type_name' => $room_type['type_name'],
    ]);

} catch (Exception $e) {
    error_log("Update room full error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
