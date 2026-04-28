<?php
/**
 * API: Xóa Banner
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$banner_id = (int) ($_POST['banner_id'] ?? $_GET['banner_id'] ?? 0);

if ($banner_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("DELETE FROM banners WHERE banner_id = :id");
    $stmt->execute([':id' => $banner_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Xóa banner thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Banner không tồn tại']);
    }
    
} catch (PDOException $e) {
    error_log('Delete banner error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database']);
}