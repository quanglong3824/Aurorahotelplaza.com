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
    $notification_id = intval($_POST['notification_id'] ?? 0);
    
    if ($notification_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
        exit;
    }
    
    // Delete only user's own notification
    $stmt = $db->prepare("
        DELETE FROM notifications 
        WHERE notification_id = :notification_id AND user_id = :user_id
    ");
    
    $stmt->execute([
        ':notification_id' => $notification_id,
        ':user_id' => $user_id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Xóa thành công']);
    
} catch (Exception $e) {
    error_log("Delete notification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
