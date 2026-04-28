<?php
/**
 * API: Lấy danh sách tất cả Banner
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/environment.php';
require_once '../../helpers/image-helper.php';

try {
    $db = getDB();
    
    $stmt = $db->query("SELECT banner_id, title, subtitle, image_desktop, image_mobile, link_url, link_text, position, sort_order, status FROM banners ORDER BY sort_order ASC, created_at DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Map columns for frontend compatibility
    foreach ($banners as &$banner) {
        $banner['image_url'] = imgUrl($banner['image_desktop']);
        $banner['is_active'] = $banner['status'] === 'active' ? 1 : 0;
    }
    
    echo json_encode([
        'success' => true,
        'banners' => $banners
    ]);
    
} catch (PDOException $e) {
    error_log('Get banners error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database']);
}