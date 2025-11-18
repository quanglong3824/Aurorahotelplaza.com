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

$service_id = $_POST['service_id'] ?? null;
$service_name = trim($_POST['service_name'] ?? '');
$category = $_POST['category'] ?? '';
$price = $_POST['price'] ?? 0;
$unit = trim($_POST['unit'] ?? '');
$sort_order = $_POST['sort_order'] ?? 0;
$short_description = trim($_POST['short_description'] ?? '');
$description = trim($_POST['description'] ?? '');
$image = trim($_POST['image'] ?? '');
$available = $_POST['available'] ?? 0;

if (empty($service_name) || empty($category) || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

try {
    $db = getDB();
    
    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $service_name)));
    
    if ($service_id) {
        // Update existing service
        $stmt = $db->prepare("
            UPDATE services SET
                service_name = :service_name,
                slug = :slug,
                category = :category,
                description = :description,
                short_description = :short_description,
                price = :price,
                unit = :unit,
                image = :image,
                available = :available,
                sort_order = :sort_order,
                updated_at = NOW()
            WHERE service_id = :service_id
        ");
        
        $stmt->execute([
            ':service_name' => $service_name,
            ':slug' => $slug,
            ':category' => $category,
            ':description' => $description,
            ':short_description' => $short_description,
            ':price' => $price,
            ':unit' => $unit,
            ':image' => $image,
            ':available' => $available,
            ':sort_order' => $sort_order,
            ':service_id' => $service_id
        ]);
        
        $message = 'Cập nhật dịch vụ thành công';
        $action = 'update_service';
        
    } else {
        // Insert new service
        $stmt = $db->prepare("
            INSERT INTO services (
                service_name, slug, category, description, short_description,
                price, unit, image, available, sort_order, created_at
            ) VALUES (
                :service_name, :slug, :category, :description, :short_description,
                :price, :unit, :image, :available, :sort_order, NOW()
            )
        ");
        
        $stmt->execute([
            ':service_name' => $service_name,
            ':slug' => $slug,
            ':category' => $category,
            ':description' => $description,
            ':short_description' => $short_description,
            ':price' => $price,
            ':unit' => $unit,
            ':image' => $image,
            ':available' => $available,
            ':sort_order' => $sort_order
        ]);
        
        $service_id = $db->lastInsertId();
        $message = 'Thêm dịch vụ thành công';
        $action = 'create_service';
    }
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at)
        VALUES (:user_id, :action, 'service', :entity_id, :description, :ip_address, NOW())
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':action' => $action,
        ':entity_id' => $service_id,
        ':description' => "$action: $service_name",
        ':ip_address' => $_SERVER['REMOTE_ADDR']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'service_id' => $service_id
    ]);
    
} catch (Exception $e) {
    error_log("Save service error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
