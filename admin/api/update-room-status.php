<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$room_id = $_POST['room_id'] ?? null;
$status = $_POST['status'] ?? null;
$notes = $_POST['notes'] ?? '';
$mark_cleaned = isset($_POST['mark_cleaned']);

if (!$room_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$allowed_statuses = ['available', 'occupied', 'maintenance', 'cleaning'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $db = getDB();
    
    $update_fields = ['status = :status'];
    $params = [
        ':status' => $status,
        ':room_id' => $room_id
    ];
    
    if ($notes) {
        $update_fields[] = 'notes = :notes';
        $params[':notes'] = $notes;
    }
    
    if ($mark_cleaned) {
        $update_fields[] = 'last_cleaned = NOW()';
    }
    
    $update_fields[] = 'updated_at = NOW()';
    
    $sql = "UPDATE rooms SET " . implode(', ', $update_fields) . " WHERE room_id = :room_id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at)
        VALUES (:user_id, 'update_room_status', 'room', :entity_id, :description, :ip_address, NOW())
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':entity_id' => $room_id,
        ':description' => "Updated room status to $status",
        ':ip_address' => $_SERVER['REMOTE_ADDR']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật trạng thái phòng thành công'
    ]);
    
} catch (Exception $e) {
    error_log("Update room status error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
