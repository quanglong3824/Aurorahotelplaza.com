<?php
/**
 * API: Lấy thông tin Banner để edit
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once '../../helpers/image-helper.php';

$banner_id = (int) ($_GET['banner_id'] ?? 0);

if ($banner_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT banner_id, title, subtitle, image_desktop, image_mobile, link_url, link_text, position, sort_order, status FROM banners WHERE banner_id = :id");
    $stmt->execute([':id' => $banner_id]);
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($banner) {
        $banner['image_url'] = imgUrl($banner['image_desktop']);
        $banner['is_active'] = $banner['status'] === 'active' ? 1 : 0;
        
        echo json_encode(['success' => true, 'banner' => $banner]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Banner không tồn tại']);
    }
    
} catch (PDOException $e) {
    error_log('Get banner error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database']);
}