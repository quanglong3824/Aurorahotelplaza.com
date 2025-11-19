<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    $stmt = $db->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = NOW()
        WHERE user_id = :user_id AND is_read = 0
    ");
    
    $stmt->execute([':user_id' => $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Đã đánh dấu tất cả đã đọc']);
    
} catch (Exception $e) {
    error_log("Mark all notifications read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
