<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$review_id = $_POST['review_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$review_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE reviews SET status = :status, updated_at = NOW() WHERE review_id = :review_id");
    $stmt->execute([
        ':status' => $status,
        ':review_id' => $review_id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
    
} catch (Exception $e) {
    error_log("Update review status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
