<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDB();
    
    $service_id = $_POST['service_id'] ?? null;
    $service_name = trim($_POST['service_name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $icon = trim($_POST['icon'] ?? 'room_service');
    $description = trim($_POST['description'] ?? '');
    $thumbnail = trim($_POST['thumbnail'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $is_available = intval($_POST['is_available'] ?? 1);
    
    // Validate required fields
    if (empty($service_name) || empty($slug)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }
    
    // Check if slug already exists (for other services)
    $stmt = $db->prepare("SELECT service_id FROM services WHERE slug = :slug AND service_id != :service_id");
    $stmt->execute([':slug' => $slug, ':service_id' => $service_id ?? 0]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Slug đã tồn tại']);
        exit;
    }
    
    if ($service_id) {
        // Update existing service
        $stmt = $db->prepare("
            UPDATE services SET
                service_name = :service_name,
                slug = :slug,
                icon = :icon,
                description = :description,
                thumbnail = :thumbnail,
                sort_order = :sort_order,
                is_available = :is_available,
                updated_at = NOW()
            WHERE service_id = :service_id
        ");
        
        $stmt->execute([
            ':service_id' => $service_id,
            ':service_name' => $service_name,
            ':slug' => $slug,
            ':icon' => $icon,
            ':description' => $description,
            ':thumbnail' => $thumbnail,
            ':sort_order' => $sort_order,
            ':is_available' => $is_available
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật dịch vụ thành công']);
    } else {
        // Insert new service
        $stmt = $db->prepare("
            INSERT INTO services (service_name, slug, icon, description, thumbnail, sort_order, is_available, created_at, updated_at)
            VALUES (:service_name, :slug, :icon, :description, :thumbnail, :sort_order, :is_available, NOW(), NOW())
        ");
        
        $stmt->execute([
            ':service_name' => $service_name,
            ':slug' => $slug,
            ':icon' => $icon,
            ':description' => $description,
            ':thumbnail' => $thumbnail,
            ':sort_order' => $sort_order,
            ':is_available' => $is_available
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Thêm dịch vụ thành công']);
    }
    
} catch (Exception $e) {
    error_log("Save service error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
