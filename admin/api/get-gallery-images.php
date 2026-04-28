<?php
/**
 * API: Lấy danh sách ảnh từ Gallery để chọn
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
    
    $category = $_GET['category'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    
    $where = "WHERE status = 'active'";
    $params = [];
    
    if ($category !== 'all' && !empty($category)) {
        $where .= " AND category = :category";
        $params[':category'] = $category;
    }
    
    if (!empty($search)) {
        $where .= " AND (title LIKE :search OR alt_text LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    $stmt = $db->prepare("
        SELECT gallery_id, title, image_url, description as alt_text, category, thumbnail_url
        FROM gallery
        $where
        ORDER BY sort_order ASC, created_at DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Normalize image URLs
    foreach ($images as &$img) {
        $img['image_url'] = imgUrl($img['image_url']);
        if ($img['thumbnail_url']) {
            $img['thumbnail_url'] = imgUrl($img['thumbnail_url']);
        }
    }
    
    $stmt = $db->query("SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL AND status = 'active' ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'images' => $images,
        'categories' => $categories
    ]);
    
} catch (PDOException $e) {
    error_log('Get gallery images error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database']);
}