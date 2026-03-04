<?php
/**
 * API: Trả về số lỗi open chưa xử lý (cho badge sidebar)
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['count' => 0]);
    exit;
}

require_once '../../config/database.php';
try {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) FROM error_logs WHERE status = 'open' AND severity IN ('critical','error')");
    $count = (int) $stmt->fetchColumn();
    echo json_encode(['count' => $count]);
} catch (\Throwable $e) {
    echo json_encode(['count' => 0]);
}
