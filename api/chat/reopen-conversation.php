<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$conv_id = (int) ($input['conversation_id'] ?? 0);
$user_id = (int) $_SESSION['user_id'];

if (!$conv_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu conversation_id']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE chat_conversations 
        SET status = IF(staff_id IS NULL, 'open', 'assigned'),
            updated_at = NOW() 
        WHERE conversation_id = :cid AND customer_id = :uid AND status = 'closed'
    ");
    $stmt->execute([':cid' => $conv_id, ':uid' => $user_id]);

    // Check if it was updated
    if ($stmt->rowCount() > 0) {
        // Ghi log session mới vào chat_messages
        $insertSys = $db->prepare("
            INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message_type, message, is_internal, is_read, created_at)
            VALUES (:cid, 0, 'system', 'text', '───── Bắt đầu phiên trò chuyện mới ─────', 0, 0, NOW())
        ");
        $insertSys->execute([':cid' => $conv_id]);

        $db->prepare("
            UPDATE chat_conversations
            SET last_message_at = NOW(),
                last_message_preview = 'Bắt đầu phiên trò chuyện mới'
            WHERE conversation_id = :cid
        ")->execute([':cid' => $conv_id]);

        echo json_encode(['success' => true, 'message' => 'Đã mở lại cuộc trò chuyện']);
    } else {
        // Có thể KH không có quyền, hoặc conv không closed
        echo json_encode(['success' => false, 'message' => 'Không thể mở lại cuộc trò chuyện này']);
    }

} catch (Exception $e) {
    error_log('Reopen error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi server']);
}
