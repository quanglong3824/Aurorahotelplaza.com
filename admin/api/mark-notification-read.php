<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$notification_id = $_POST['notification_id'] ?? null;
$mark_all = isset($_POST['mark_all']) && $_POST['mark_all'] === 'true';

try {
    $db = getDB();
    
    if ($mark_all) {
        // Mark all as read
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1, read_at = NOW()
            WHERE user_id = :user_id AND is_read = 0
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        
    } else if ($notification_id) {
        // Mark specific notification as read
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1, read_at = NOW()
            WHERE notification_id = :notification_id AND user_id = :user_id
        ");
        $stmt->execute([
            ':notification_id' => $notification_id,
            ':user_id' => $_SESSION['user_id']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
