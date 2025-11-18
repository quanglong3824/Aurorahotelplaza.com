<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_POST['user_id'] ?? null;
$status = $_POST['status'] ?? null;
$reason = $_POST['reason'] ?? '';

if (!$user_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE users SET status = :status, updated_at = NOW() WHERE user_id = :user_id");
    $stmt->execute([
        ':status' => $status,
        ':user_id' => $user_id
    ]);
    
    // Log activity
    if ($reason) {
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at)
            VALUES (:admin_id, 'update_customer_status', 'user', :user_id, :description, :ip_address, NOW())
        ");
        $stmt->execute([
            ':admin_id' => $_SESSION['user_id'],
            ':user_id' => $user_id,
            ':description' => "Changed status to $status. Reason: $reason",
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
    
} catch (Exception $e) {
    error_log("Update customer status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
