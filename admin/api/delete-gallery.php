<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$gallery_id = $_POST['gallery_id'] ?? null;

if (!$gallery_id) {
    echo json_encode(['success' => false, 'message' => 'Missing gallery_id']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("DELETE FROM gallery WHERE gallery_id = :gallery_id");
    $stmt->execute([':gallery_id' => $gallery_id]);
    
    echo json_encode(['success' => true, 'message' => 'Xóa hình ảnh thành công']);
    
} catch (Exception $e) {
    error_log("Delete gallery error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
