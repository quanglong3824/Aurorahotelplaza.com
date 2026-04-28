<?php
/**
 * API: Lấy danh sách tất cả Banner
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

try {
    $db = getDB();
    
    $stmt = $db->query("SELECT * FROM banners ORDER BY sort_order ASC, created_at DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'banners' => $banners
    ]);
    
} catch (PDOException $e) {
    error_log('Get banners error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database']);
}