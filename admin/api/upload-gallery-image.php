<?php
/**
 * API: Upload Gallery Image
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/environment.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Không có file upload']);
    exit;
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 10 * 1024 * 1024; // 10MB

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận JPG, PNG, GIF, WebP']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File quá lớn (tối đa 10MB)']);
    exit;
}

$category = trim($_POST['category'] ?? 'general');
$title = trim($_POST['title'] ?? '');

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$safeCategory = preg_replace('/[^a-zA-Z0-9_-]/', '', $category);
$filename = $safeCategory . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;

$uploadDir = __DIR__ . '/../../uploads/gallery/' . $safeCategory . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $imageUrl = BASE_URL . '/uploads/gallery/' . $safeCategory . '/' . $filename;
    
    $imageTitle = $title ?: pathinfo($file['name'], PATHINFO_FILENAME);
    
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO gallery (title, image_url, thumbnail_url, category, status, sort_order, uploaded_by, created_at)
        VALUES (:title, :url, :thumb, :category, 'active', 0, :user_id, NOW())
    ");
    $stmt->execute([
        ':title' => $imageTitle,
        ':url' => $imageUrl,
        ':thumb' => $imageUrl,
        ':category' => $category,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    $galleryId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Upload thành công',
        'image_url' => $imageUrl,
        'thumbnail_url' => $imageUrl,
        'filename' => $filename,
        'gallery_id' => $galleryId,
        'title' => $imageTitle,
        'category' => $category
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu file']);
}