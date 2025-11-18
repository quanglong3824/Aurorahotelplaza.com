<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$review_id = $_POST['review_id'] ?? null;

if (!$review_id) {
    echo json_encode(['success' => false, 'message' => 'Missing review_id']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("DELETE FROM reviews WHERE review_id = :review_id");
    $stmt->execute([':review_id' => $review_id]);
    
    echo json_encode(['success' => true, 'message' => 'Xóa đánh giá thành công']);
    
} catch (Exception $e) {
    error_log("Delete review error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
