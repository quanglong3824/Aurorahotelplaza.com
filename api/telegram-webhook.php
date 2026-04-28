<?php
/**
 * Telegram Webhook API
 * Nhận tin nhắn từ Telegram Bot và xử lý (2 chiều)
 * 
 * Cơ chế hoạt động:
 * 1. Quản trị viên nhắn tin qua Telegram Bot
 * 2. Telegram gửi tin nhắn đến webhook này
 * 3. Webhook phân tích tin nhắn và tìm conversation liên quan
 * 4. Gửi tin nhắn vào hệ thống chat của khách sạn
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/telegram.php';

$EXPECTED_CHAT_ID = '5513249927';

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

logTelegramUpdate($update);

$message = $update['message'] ?? null;
if (!$message) {
    echo json_encode(['ok' => true, 'handled' => false, 'reason' => 'No message']);
    exit;
}

$chatId = $message['chat']['id'] ?? 0;
$fromId = $message['from']['id'] ?? 0;
$fromName = $message['from']['first_name'] ?? 'Admin';
$fromUsername = $message['from']['username'] ?? '';
$text = $message['text'] ?? '';
$messageId = $message['message_id'] ?? 0;
$replyToMessageId = $message['reply_to_message']['message_id'] ?? null;

if ((string) $chatId !== $EXPECTED_CHAT_ID) {
    echo json_encode(['ok' => true, 'handled' => false, 'reason' => 'Chat ID mismatch']);
    exit;
}

if (empty($text)) {
    echo json_encode(['ok' => true, 'handled' => false, 'reason' => 'Empty text']);
    exit;
}

try {
    $db = getDB();
    if (!$db) {
        throw new Exception('Database error');
    }

    if (strpos($text, '/start') !== false) {
        $welcomeMsg = "<b>Aurora Hotel Plaza Bot</b>\n"
            . "Chào mừng quản trị viên!\n"
            . "\n"
            . "<b>Cách sử dụng:</b>\n"
            . "• Để phản hồi khách: Reply tin nhắn notification từ bot\n"
            . "• Tin nhắn mới sẽ được gửi tự động khi:\n"
            . "  - Có booking mới\n"
            . "  - Khách gửi tin nhắn qua web chat\n"
            . "\n"
            . "<b>Lệnh:</b>\n"
            . "/start - Hiển thị hướng dẫn\n"
            . "/status - Kiểm tra trạng thái bot\n"
            . "/help - Danh sách lệnh";

        TelegramHelper::replyToChatMessage($chatId, $welcomeMsg, $messageId);
        echo json_encode(['ok' => true, 'handled' => true, 'action' => 'welcome']);
        exit;
    }

    if (strpos($text, '/status') !== false) {
        $statusMsg = "<b>Bot Status</b>\n"
            . "✅ Bot đang hoạt động\n"
            . "⏰ " . date('d/m/Y H:i:s') . "\n"
            . "🔗 Webhook: Active\n"
            . "👤 Chat ID: " . $chatId;

        TelegramHelper::replyToChatMessage($chatId, $statusMsg, $messageId);
        echo json_encode(['ok' => true, 'handled' => true, 'action' => 'status']);
        exit;
    }

    if (strpos($text, '/help') !== false) {
        $helpMsg = "<b>Danh sách lệnh</b>\n"
            . "/start - Bắt đầu sử dụng bot\n"
            . "/status - Kiểm tra trạng thái\n"
            . "/help - Hiển thị hướng dẫn\n"
            . "\n"
            . "<b>Phản hồi khách:</b>\n"
            . "Reply tin nhắn notification từ bot để phản hồi khách.";

        TelegramHelper::replyToChatMessage($chatId, $helpMsg, $messageId);
        echo json_encode(['ok' => true, 'handled' => true, 'action' => 'help']);
        exit;
    }

    $convId = findConversationByTelegramMessageId($db, $replyToMessageId);

    if ($convId) {
        $result = sendStaffReplyToConversation($db, $convId, $text, $fromName);
        
        $confirmMsg = "✅ Tin nhắn đã gửi đến khách\n"
            . "💬 Conversation #" . $convId . "\n"
            . "⏰ " . date('H:i:s');

        TelegramHelper::replyToChatMessage($chatId, $confirmMsg, $messageId);

        echo json_encode([
            'ok' => true,
            'handled' => true,
            'action' => 'reply_sent',
            'conversation_id' => $convId
        ]);
        exit;
    }

    $convId = findActiveConversationByContext($db, $text);

    if ($convId) {
        $result = sendStaffReplyToConversation($db, $convId, $text, $fromName);

        $confirmMsg = "✅ Tin nhắn đã gửi đến khách\n"
            . "💬 Conversation #" . $convId . "\n"
            . "⏰ " . date('H:i:s');

        TelegramHelper::replyToChatMessage($chatId, $confirmMsg, $messageId);

        echo json_encode([
            'ok' => true,
            'handled' => true,
            'action' => 'reply_sent_by_context',
            'conversation_id' => $convId
        ]);
        exit;
    }

    TelegramHelper::replyToChatMessage($chatId, 
        "⚠️ Không tìm thấy conversation để phản hồi.\n"
        . "Reply tin nhắn notification từ bot để phản hồi khách.",
        $messageId);

    echo json_encode(['ok' => true, 'handled' => true, 'action' => 'no_conversation']);

} catch (Exception $e) {
    error_log('Telegram webhook error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

function logTelegramUpdate($update)
{
    $logFile = __DIR__ . '/../logs/telegram_webhook.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logEntry = date('Y-m-d H:i:s') . " | " . json_encode($update, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function findConversationByTelegramMessageId($db, $telegramMessageId)
{
    if (!$telegramMessageId) return null;

    try {
        $stmt = $db->prepare(
            "SELECT conversation_id FROM telegram_message_mapping 
             WHERE telegram_message_id = :msg_id 
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([':msg_id' => $telegramMessageId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int) $result['conversation_id'] : null;
    } catch (Throwable $e) {
        return null;
    }
}

function findActiveConversationByContext($db, $text)
{
    $patterns = [
        '/\[#(\d+)\]/',
        '/conversation\s*#?(\d+)/i',
        '/conv\s*#?(\d+)/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $convId = (int) $matches[1];
            $check = $db->prepare("SELECT conversation_id FROM chat_conversations WHERE conversation_id = :id AND status != 'closed'");
            $check->execute([':id' => $convId]);
            if ($check->fetch()) {
                return $convId;
            }
        }
    }

    return null;
}

function sendStaffReplyToConversation($db, $convId, $message, $staffName)
{
    $staffId = getStaffIdByName($db, $staffName);
    if (!$staffId) {
        $staffId = 1;
    }

    $insertMsg = $db->prepare(
        "INSERT INTO chat_messages 
            (conversation_id, sender_id, sender_type, message, message_type, is_read, created_at)
         VALUES 
            (:cid, :sid, 'staff', :msg, 'text', 0, NOW())"
    );
    $insertMsg->execute([
        ':cid' => $convId,
        ':sid' => $staffId,
        ':msg' => $message
    ]);

    $db->prepare(
        "UPDATE chat_conversations 
         SET unread_customer = unread_customer + 1,
             unread_staff = 0,
             last_message_at = NOW(),
             last_message_preview = :preview,
             updated_at = NOW()
         WHERE conversation_id = :cid"
    )->execute([
        ':preview' => mb_substr($message, 0, 100),
        ':cid' => $convId
    ]);

    return true;
}

function getStaffIdByName($db, $name)
{
    try {
        $stmt = $db->prepare(
            "SELECT user_id FROM users WHERE role IN ('admin', 'receptionist', 'sale') AND name LIKE :name LIMIT 1"
        );
        $stmt->execute([':name' => '%' . $name . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int) $result['user_id'] : null;
    } catch (Throwable $e) {
        return null;
    }
}