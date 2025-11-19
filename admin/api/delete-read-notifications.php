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
        DELETE FROM notifications 
        WHERE user_id = :user_id AND is_read = 1
    ");
    
    $stmt->execute([':user_id' => $user_id]);
    
    $deleted = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'message' => "Đã xóa $deleted thông báo"
    ]);
    
} catch (Exception $e) {
    error_log("Delete read notifications error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
