<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$service_id = $_POST['service_id'] ?? null;

if (!$service_id) {
    echo json_encode(['success' => false, 'message' => 'Missing service_id']);
    exit;
}

try {
    $db = getDB();
    
    // Check if service has bookings
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM service_bookings WHERE service_id = :service_id");
    $stmt->execute([':service_id' => $service_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa dịch vụ đã có đơn đặt. Vui lòng tắt khả dụng thay vì xóa.']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM services WHERE service_id = :service_id");
    $stmt->execute([':service_id' => $service_id]);
    
    echo json_encode(['success' => true, 'message' => 'Xóa dịch vụ thành công']);
    
} catch (Exception $e) {
    error_log("Delete service error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
