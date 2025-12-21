<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$floor = $_POST['floor'] ?? null;
$status = $_POST['status'] ?? null;

if (!$floor || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// User requested statuses: Trống (available), Đang ở (occupied), Bảo trì (maintenance), Dọn dẹp (cleaning), Đã đặt (reserved)
$allowed_statuses = ['available', 'occupied', 'maintenance', 'cleaning', 'reserved'];

if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $db = getDB();

    // Update all rooms on the specified floor
    $stmt = $db->prepare("UPDATE rooms SET status = :status, updated_at = NOW() WHERE floor = :floor");
    $stmt->execute([
        ':status' => $status,
        ':floor' => $floor
    ]);

    $count = $stmt->rowCount();

    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at)
        VALUES (:user_id, 'bulk_update_floor', 'floor', :entity_id, :description, :ip_address, NOW())
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':entity_id' => $floor, // Storing floor number as entity_id
        ':description' => "Updated all rooms on floor $floor to $status",
        ':ip_address' => $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Đã cập nhật trạng thái $count phòng ở tầng $floor thành '$status'",
        'updated_count' => $count
    ]);

} catch (Exception $e) {
    error_log("Update floor status error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
