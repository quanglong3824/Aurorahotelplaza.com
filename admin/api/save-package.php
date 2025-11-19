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
    
    $package_id = $_POST['package_id'] ?? null;
    $service_id = intval($_POST['service_id'] ?? 0);
    $package_name = trim($_POST['package_name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $price_unit = trim($_POST['price_unit'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $is_featured = intval($_POST['is_featured'] ?? 0);
    $is_available = intval($_POST['is_available'] ?? 1);
    
    // Validate required fields
    if (empty($package_name) || empty($slug) || $service_id <= 0 || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }
    
    // Check if slug already exists (for other packages in same service)
    $stmt = $db->prepare("SELECT package_id FROM service_packages WHERE slug = :slug AND service_id = :service_id AND package_id != :package_id");
    $stmt->execute([':slug' => $slug, ':service_id' => $service_id, ':package_id' => $package_id ?? 0]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Slug đã tồn tại trong dịch vụ này']);
        exit;
    }
    
    if ($package_id) {
        // Update existing package
        $stmt = $db->prepare("
            UPDATE service_packages SET
                package_name = :package_name,
                slug = :slug,
                price = :price,
                price_unit = :price_unit,
                features = :features,
                sort_order = :sort_order,
                is_featured = :is_featured,
                is_available = :is_available,
                updated_at = NOW()
            WHERE package_id = :package_id
        ");
        
        $stmt->execute([
            ':package_id' => $package_id,
            ':package_name' => $package_name,
            ':slug' => $slug,
            ':price' => $price,
            ':price_unit' => $price_unit,
            ':features' => $features,
            ':sort_order' => $sort_order,
            ':is_featured' => $is_featured,
            ':is_available' => $is_available
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật gói thành công']);
    } else {
        // Insert new package
        $stmt = $db->prepare("
            INSERT INTO service_packages (service_id, package_name, slug, price, price_unit, features, sort_order, is_featured, is_available, created_at, updated_at)
            VALUES (:service_id, :package_name, :slug, :price, :price_unit, :features, :sort_order, :is_featured, :is_available, NOW(), NOW())
        ");
        
        $stmt->execute([
            ':service_id' => $service_id,
            ':package_name' => $package_name,
            ':slug' => $slug,
            ':price' => $price,
            ':price_unit' => $price_unit,
            ':features' => $features,
            ':sort_order' => $sort_order,
            ':is_featured' => $is_featured,
            ':is_available' => $is_available
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Thêm gói thành công']);
    }
    
} catch (Exception $e) {
    error_log("Save package error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
