<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$gallery_id = $_POST['gallery_id'] ?? null;
$title = trim($_POST['title'] ?? '');
$image_url = trim($_POST['image_url'] ?? '');
$thumbnail_url = trim($_POST['thumbnail_url'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$sort_order = $_POST['sort_order'] ?? 0;
$status = $_POST['status'] ?? 'active';

if (empty($title) || empty($image_url)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

try {
    $db = getDB();
    
    if ($gallery_id) {
        // Update
        $stmt = $db->prepare("
            UPDATE gallery SET
                title = :title,
                image_url = :image_url,
                thumbnail_url = :thumbnail_url,
                category = :category,
                description = :description,
                sort_order = :sort_order,
                status = :status
            WHERE gallery_id = :gallery_id
        ");
        
        $stmt->execute([
            ':title' => $title,
            ':image_url' => $image_url,
            ':thumbnail_url' => $thumbnail_url,
            ':category' => $category,
            ':description' => $description,
            ':sort_order' => $sort_order,
            ':status' => $status,
            ':gallery_id' => $gallery_id
        ]);
        
        $message = 'Cập nhật hình ảnh thành công';
        
    } else {
        // Insert
        $stmt = $db->prepare("
            INSERT INTO gallery (
                title, image_url, thumbnail_url, category, description,
                sort_order, status, uploaded_by, created_at
            ) VALUES (
                :title, :image_url, :thumbnail_url, :category, :description,
                :sort_order, :status, :uploaded_by, NOW()
            )
        ");
        
        $stmt->execute([
            ':title' => $title,
            ':image_url' => $image_url,
            ':thumbnail_url' => $thumbnail_url,
            ':category' => $category,
            ':description' => $description,
            ':sort_order' => $sort_order,
            ':status' => $status,
            ':uploaded_by' => $_SESSION['user_id']
        ]);
        
        $gallery_id = $db->lastInsertId();
        $message = 'Thêm hình ảnh thành công';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'gallery_id' => $gallery_id
    ]);
    
} catch (Exception $e) {
    error_log("Save gallery error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
