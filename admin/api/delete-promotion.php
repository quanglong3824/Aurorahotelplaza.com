<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$promotion_id = $_POST['promotion_id'] ?? null;

if (!$promotion_id) {
    echo json_encode(['success' => false, 'message' => 'Missing promotion_id']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("DELETE FROM promotions WHERE promotion_id = :promotion_id");
    $stmt->execute([':promotion_id' => $promotion_id]);
    
    echo json_encode(['success' => true, 'message' => 'Xóa khuyến mãi thành công']);
    
} catch (Exception $e) {
    error_log("Delete promotion error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
