<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Không thể xóa chính mình']);
    exit;
}

try {
    $db = getDB();
    
    // Check if user has bookings
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa nhân viên đã có dữ liệu liên quan']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Xóa nhân viên thành công']);
    
} catch (Exception $e) {
    error_log("Delete user error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
