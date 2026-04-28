<?php
/**
 * Test Banner API Response
 */
session_start();
require_once '../config/database.php';
require_once '../helpers/image-helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDB();
    $stmt = $db->query("SELECT banner_id, title, subtitle, image_desktop, image_mobile, link_url, position, sort_order, status FROM banners ORDER BY sort_order ASC, created_at DESC");
    $banners_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $banners = [];
    foreach ($banners_raw as $b) {
        $b['image_url'] = $b['image_desktop'];
        $b['is_active'] = $b['status'] === 'active' ? 1 : 0;
        $banners[] = $b;
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($banners),
        'banners' => $banners
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}