<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDB();
    
    $package_id = intval($_POST['package_id'] ?? 0);
    
    if ($package_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid package ID']);
        exit;
    }
    
    // Check if package has bookings
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM service_bookings WHERE package_id = :package_id");
    $stmt->execute([':package_id' => $package_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa gói đã có booking. Vui lòng tắt khả dụng thay vì xóa.']);
        exit;
    }
    
    // Delete package
    $stmt = $db->prepare("DELETE FROM service_packages WHERE package_id = :package_id");
    $stmt->execute([':package_id' => $package_id]);
    
    echo json_encode(['success' => true, 'message' => 'Xóa gói thành công']);
    
} catch (Exception $e) {
    error_log("Delete package error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
