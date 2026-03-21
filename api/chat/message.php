<?php
/**
 * API Xử lý tin nhắn Chat - Aurora Hotel Plaza
 */
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Tắt buffering trên Nginx

require_once '../../config/database.php';
require_once '../../helpers/ai-helper.php';

$db = getDB();

// 1. Lấy dữ liệu đầu vào
$user_message = $_POST['message'] ?? '';
$conversation_id = (int)($_POST['conversation_id'] ?? 0);

if (empty($user_message)) {
    echo "data: " . json_encode(["error" => "Tin nhắn không được để trống."]) . "\n\n";
    exit;
}

// 2. Lưu tin nhắn của khách vào DB
try {
    if ($conversation_id === 0) {
        // Tạo cuộc hội thoại mới nếu chưa có
        $stmt = $db->prepare("INSERT INTO chat_conversations (customer_name, status, created_at) VALUES (?, 'active', NOW())");
        $stmt->execute(['Khách vãng lai']);
        $conversation_id = $db->lastInsertId();
    }
    
    $stmt = $db->prepare("INSERT INTO chat_messages (conversation_id, sender_type, message, message_type, created_at) VALUES (?, 'customer', ?, 'text', NOW())");
    $stmt->execute([$conversation_id, $user_message]);
} catch (Exception $e) {
    // Silent fail cho DB để AI vẫn chạy được
}

// 3. Gọi AI Engine để phản hồi
echo "data: " . json_encode(["conversation_id" => $conversation_id]) . "\n\n";
if (ob_get_level() > 0) ob_flush(); flush();

$ai_response = stream_ai_reply($user_message, $db, $conversation_id);

// 4. Lưu phản hồi của AI vào DB
if (!empty($ai_response)) {
    try {
        $stmt = $db->prepare("INSERT INTO chat_messages (conversation_id, sender_type, message, message_type, created_at) VALUES (?, 'bot', ?, 'text', NOW())");
        $stmt->execute([$conversation_id, $ai_response]);
        
        // Cập nhật thời gian phản hồi cuối cho cuộc hội thoại
        $db->prepare("UPDATE chat_conversations SET last_message_at = NOW() WHERE conversation_id = ?")->execute([$conversation_id]);
    } catch (Exception $e) {}
}

echo "data: [DONE]\n\n";
if (ob_get_level() > 0) ob_flush(); flush();
