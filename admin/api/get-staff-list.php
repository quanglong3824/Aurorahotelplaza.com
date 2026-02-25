<?php
/**
 * API Admin: Lấy danh sách staff đang hoạt động để assign
 * GET /admin/api/get-staff-list.php
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->query("
        SELECT
            u.user_id,
            u.full_name,
            u.user_role,
            COALESCE(c.load, 0) AS load
        FROM users u
        LEFT JOIN (
            SELECT staff_id, COUNT(*) AS load
            FROM chat_conversations
            WHERE status = 'assigned'
            GROUP BY staff_id
        ) c ON c.staff_id = u.user_id
        WHERE u.user_role IN ('admin','receptionist','sale')
          AND u.status = 'active'
        ORDER BY c.load ASC, u.full_name ASC
    ");
    $staff = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $staff]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'data' => []]);
}
