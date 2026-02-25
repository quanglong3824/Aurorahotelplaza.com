<?php
/**
 * API Admin: Lấy danh sách quick replies
 * GET /admin/api/get-quick-replies.php?search=/hello
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$search = trim($_GET['search'] ?? '');

try {
    $db = getDB();
    $params = [];
    $where = ['is_active = 1'];

    if ($search !== '') {
        $where[] = "(shortcut LIKE :s OR title LIKE :s OR content LIKE :s)";
        $params[':s'] = "%$search%";
    }

    $stmt = $db->prepare("
        SELECT reply_id, category, shortcut, title, content
        FROM chat_quick_replies
        WHERE " . implode(' AND ', $where) . "
        ORDER BY sort_order ASC, reply_id ASC
        LIMIT 20
    ");
    $stmt->execute($params);

    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'data' => []]);
}
