<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$faq_id = $_POST['faq_id'] ?? null;

if (!$faq_id) {
    echo json_encode(['success' => false, 'message' => 'Missing faq_id']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("DELETE FROM faqs WHERE faq_id = :faq_id");
    $stmt->execute([':faq_id' => $faq_id]);
    
    echo json_encode(['success' => true, 'message' => 'Xóa câu hỏi thành công']);
    
} catch (Exception $e) {
    error_log("Delete FAQ error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
