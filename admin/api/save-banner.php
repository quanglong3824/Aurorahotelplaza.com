<?php
/**
 * API: Lưu Banner (Thêm/Sửa)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

try {
    $db = getDB();
    
    $banner_id = (int) ($_POST['banner_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $is_active = (int) ($_POST['is_active'] ?? 0);
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Tiêu đề không được trống']);
        exit;
    }
    
    if (empty($image_url)) {
        echo json_encode(['success' => false, 'message' => 'URL hình ảnh không được trống']);
        exit;
    }
    
    if ($banner_id > 0) {
        $stmt = $db->prepare("
            UPDATE banners 
            SET title = :title,
                subtitle = :subtitle,
                image_url = :image_url,
                link_url = :link_url,
                sort_order = :sort_order,
                is_active = :is_active,
                updated_at = NOW(),
                updated_by = :user_id
            WHERE banner_id = :banner_id
        ");
        $stmt->execute([
            ':title' => $title,
            ':subtitle' => $subtitle,
            ':image_url' => $image_url,
            ':link_url' => $link_url,
            ':sort_order' => $sort_order,
            ':is_active' => $is_active,
            ':user_id' => $_SESSION['user_id'],
            ':banner_id' => $banner_id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật banner thành công', 'banner_id' => $banner_id]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO banners (title, subtitle, image_url, link_url, sort_order, is_active, created_at, created_by)
            VALUES (:title, :subtitle, :image_url, :link_url, :sort_order, :is_active, NOW(), :user_id)
        ");
        $stmt->execute([
            ':title' => $title,
            ':subtitle' => $subtitle,
            ':image_url' => $image_url,
            ':link_url' => $link_url,
            ':sort_order' => $sort_order,
            ':is_active' => $is_active,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        $new_id = $db->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Thêm banner thành công', 'banner_id' => $new_id]);
    }
    
} catch (PDOException $e) {
    error_log('Save banner error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Save banner error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}