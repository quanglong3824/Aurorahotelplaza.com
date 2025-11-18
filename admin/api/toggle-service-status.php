<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$service_id = $_POST['service_id'] ?? null;
$available = $_POST['available'] ?? 0;

if (!$service_id) {
    echo json_encode(['success' => false, 'message' => 'Missing service_id']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE services SET available = :available, updated_at = NOW() WHERE service_id = :service_id");
    $stmt->execute([
        ':available' => $available,
        ':service_id' => $service_id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
    
} catch (Exception $e) {
    error_log("Toggle service status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
