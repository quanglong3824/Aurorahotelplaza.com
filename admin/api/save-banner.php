<?php
/**
 * API: Lưu Banner (Thêm/Sửa)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function sendError($message, $debug = []) {
    echo json_encode(['success' => false, 'message' => $message, 'debug' => $debug]);
    exit;
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    sendError('Unauthorized');
}

require_once '../../config/database.php';

try {
    $db = getDB();
    
    $banner_id = (int) ($_POST['banner_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $link_text = trim($_POST['link_text'] ?? '');
    $sort_order = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : null;
    $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : null;
    $position = trim($_POST['position'] ?? 'popup');
    
    $status = $is_active === 1 ? 'active' : 'inactive';
    
    if ($banner_id > 0 && empty($title) && empty($image_url) && $is_active !== null) {
        $stmt = $db->prepare("UPDATE banners SET status = :status, updated_at = NOW() WHERE banner_id = :banner_id");
        $stmt->execute([':status' => $status, ':banner_id' => $banner_id]);
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công', 'banner_id' => $banner_id]);
        exit;
    }
    
    if (empty($title)) {
        sendError('Tiêu đề không được trống');
    }
    
    if (empty($image_url)) {
        sendError('URL hình ảnh không được trống');
    }
    
    if ($banner_id > 0) {
        $stmt = $db->prepare("
            UPDATE banners 
            SET title = :title,
                subtitle = :subtitle,
                image_desktop = :image_desktop,
                image_mobile = :image_mobile,
                link_url = :link_url,
                link_text = :link_text,
                sort_order = :sort_order,
                status = :status,
                position = :position,
                updated_at = NOW()
            WHERE banner_id = :banner_id
        ");
        $stmt->execute([
            ':title' => $title,
            ':subtitle' => $subtitle,
            ':image_desktop' => $image_url,
            ':image_mobile' => $image_url,
            ':link_url' => $link_url,
            ':link_text' => $link_text,
            ':sort_order' => $sort_order ?? 0,
            ':status' => $status,
            ':position' => $position,
            ':banner_id' => $banner_id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật banner thành công', 'banner_id' => $banner_id]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO banners (title, subtitle, image_desktop, image_mobile, link_url, link_text, position, sort_order, status, created_at, updated_at)
            VALUES (:title, :subtitle, :image_desktop, :image_mobile, :link_url, :link_text, :position, :sort_order, :status, NOW(), NOW())
        ");
        $stmt->execute([
            ':title' => $title,
            ':subtitle' => $subtitle,
            ':image_desktop' => $image_url,
            ':image_mobile' => $image_url,
            ':link_url' => $link_url,
            ':link_text' => $link_text,
            ':position' => $position,
            ':sort_order' => $sort_order ?? 0,
            ':status' => $status
        ]);
        
        $new_id = $db->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Thêm banner thành công', 'banner_id' => $new_id]);
    }
    
} catch (PDOException $e) {
    error_log('Save banner PDO error: ' . $e->getMessage() . ' | SQLSTATE: ' . ($e->errorInfo[0] ?? 'N/A'));
    sendError('Lỗi database: ' . $e->getMessage(), [
        'code' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Exception $e) {
    error_log('Save banner error: ' . $e->getMessage());
    sendError('Lỗi: ' . $e->getMessage());
}