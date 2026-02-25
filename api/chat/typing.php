<?php
/**
 * API: Cập nhật trạng thái typing
 * POST /api/chat/typing.php
 * Body: { "conversation_id": 5, "is_typing": true }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$conv_id = (int) ($input['conversation_id'] ?? 0);
$is_typing = (bool) ($input['is_typing'] ?? false);

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'customer';
$user_type = in_array($user_role, ['admin', 'receptionist', 'sale']) ? 'staff' : 'customer';

if (!$conv_id) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $db = getDB();

    // UPSERT typing status
    $db->prepare("
        INSERT INTO chat_typing (conversation_id, user_id, user_type, is_typing, updated_at)
        VALUES (:cid, :uid, :utype, :typing, NOW())
        ON DUPLICATE KEY UPDATE
            is_typing  = VALUES(is_typing),
            updated_at = NOW()
    ")->execute([
                ':cid' => $conv_id,
                ':uid' => $user_id,
                ':utype' => $user_type,
                ':typing' => $is_typing ? 1 : 0,
            ]);

    // Dọn dẹp typing record cũ (> 10 giây) để tránh bảng phình to
    $db->exec("DELETE FROM chat_typing WHERE updated_at < NOW() - INTERVAL 10 SECOND");

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
