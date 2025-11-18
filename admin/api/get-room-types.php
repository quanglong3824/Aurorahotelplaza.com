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
    
    $stmt = $db->query("
        SELECT room_type_id, type_name, slug, category, base_price, status
        FROM room_types
        WHERE status = 'active'
        ORDER BY category, sort_order ASC, type_name ASC
    ");
    
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'room_types' => $room_types
    ]);
    
} catch (Exception $e) {
    error_log("Get room types error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
