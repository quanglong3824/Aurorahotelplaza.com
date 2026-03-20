<?php
/**
 * API: Stream AI Reply (SSE)
 * GET /api/chat/ai-stream.php?conversation_id=5&message=...
 */

session_start();
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

require_once '../../config/database.php';
require_once '../../helpers/ai-helper.php';

// Auth check
if (!isset($_SESSION['user_id']) && !isset($_SESSION['chat_guest_id'])) {
    echo "data: " . json_encode(["error" => "Unauthorized"]) . "\n\n";
    exit;
}

$conv_id = (int) ($_GET['conversation_id'] ?? 0);
$user_msg_id = (int) ($_GET['user_message_id'] ?? 0);

if (!$conv_id || !$user_msg_id) {
    echo "data: " . json_encode(["error" => "Missing parameters"]) . "\n\n";
    exit;
}

$db = getDB();

// Lấy nội dung tin nhắn của khách
$stmt = $db->prepare("SELECT message FROM chat_messages WHERE message_id = ? AND conversation_id = ?");
$stmt->execute([$user_msg_id, $conv_id]);
$message = $stmt->fetchColumn();

if (!$message) {
    echo "data: " . json_encode(["error" => "Message not found"]) . "\n\n";
    exit;
}

// Tắt buffering
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');
set_time_limit(120);

// Gọi stream từ helper
$full_reply = stream_ai_reply($message, $db, $conv_id);

// Sau khi stream xong, lưu vào DB nếu có kết quả
$new_msg_id = 0;
if (!empty($full_reply)) {
    try {
        $stmt = $db->prepare("
            INSERT INTO chat_messages
                (conversation_id, sender_id, sender_type, message, message_type, is_internal, is_read, created_at)
            VALUES
                (:cid, 0, 'bot', :msg, 'text', 0, 0, NOW())
        ");
        $stmt->execute([
            ':cid' => $conv_id,
            ':msg' => $full_reply
        ]);
        $new_msg_id = $db->lastInsertId();

        $db->prepare("
            UPDATE chat_conversations
            SET unread_customer = unread_customer + 1,
                unread_staff = 0,
                last_message_at = NOW(),
                last_message_preview = :preview,
                updated_at = NOW()
            WHERE conversation_id = :cid
        ")->execute([
            ':preview' => mb_substr($full_reply, 0, 100),
            ':cid' => $conv_id
        ]);
    } catch (Exception $e) {
        error_log("Failed to save AI reply: " . $e->getMessage());
    }
}

echo "data: " . json_encode(["done" => true, "message_id" => $new_msg_id]) . "\n\n";
if (ob_get_level() > 0) ob_flush(); flush();
