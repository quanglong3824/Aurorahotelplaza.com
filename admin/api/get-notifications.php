<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$limit = $_GET['limit'] ?? 10;
$unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

try {
    $db = getDB();
    
    // Get notifications
    $where = "WHERE user_id = :user_id";
    if ($unread_only) {
        $where .= " AND is_read = 0";
    }
    
    $stmt = $db->prepare("
        SELECT * FROM notifications
        $where
        ORDER BY created_at DESC
        LIMIT :limit
    ");
    
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count
    $stmt = $db->prepare("
        SELECT COUNT(*) as unread_count
        FROM notifications
        WHERE user_id = :user_id AND is_read = 0
    ");
    $stmt->execute([':user_id' => $user_id]);
    $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ]);
    
} catch (Exception $e) {
    error_log("Get notifications error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
