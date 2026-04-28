<?php
/**
 * API: Lấy thông tin Banner để edit
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$banner_id = (int) ($_GET['banner_id'] ?? 0);

if ($banner_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM banners WHERE banner_id = :id");
    $stmt->execute([':id' => $banner_id]);
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($banner) {
        echo json_encode(['success' => true, 'banner' => $banner]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Banner không tồn tại']);
    }
    
} catch (PDOException $e) {
    error_log('Get banner error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database']);
}