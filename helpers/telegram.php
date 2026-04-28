<?php
/**
 * Aurora Hotel Plaza - Telegram Helper
 * Quản lý gửi/nhận tin nhắn Telegram Bot
 * 
 * HARDCODED CONFIG - All in one, không cần cấu hình
 */

class TelegramHelper
{
    private static $botToken = '8772642373:AAG0ubAS246uCzUEZOJNimRuA5UxY6LpUrg';
    private static $chatId = '5513249927';
    private static $initialized = true;

    public static function init()
    {
        // Already hardcoded - no init needed
    }

    public static function getConfig()
    {
        return [
            'bot_token' => self::$botToken,
            'chat_id' => self::$chatId,
            'is_configured' => !empty(self::$botToken) && !empty(self::$chatId)
        ];
    }

    public static function getBotToken()
    {
        return self::$botToken;
    }

    public static function getChatId()
    {
        return self::$chatId;
    }

    public static function sendMessage($text, $parseMode = 'HTML', $disablePreview = true)
    {
        self::init();

        if (empty(self::$botToken) || empty(self::$chatId)) {
            return ['success' => false, 'error' => 'Telegram not configured'];
        }

        $payload = json_encode([
            'chat_id' => self::$chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => $disablePreview
        ]);

        $ch = curl_init("https://api.telegram.org/bot" . self::$botToken . "/sendMessage");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $data = json_decode($result, true);

        if ($httpCode === 200 && ($data['ok'] ?? false)) {
            return [
                'success' => true,
                'message_id' => $data['result']['message_id'] ?? null,
                'data' => $data
            ];
        }

        return [
            'success' => false,
            'error' => $data['description'] ?? $curlError ?? 'Unknown error',
            'http_code' => $httpCode
        ];
    }

    public static function sendBookingNotification($bookingData)
    {
        self::init();

        $bookingCode = $bookingData['booking_code'] ?? 'N/A';
        $typeName = $bookingData['type_name'] ?? $bookingData['room_type'] ?? 'N/A';
        $guestName = $bookingData['guest_name'] ?? $bookingData['customer_name'] ?? 'N/A';
        $guestPhone = $bookingData['guest_phone'] ?? $bookingData['phone'] ?? 'N/A';
        $guestEmail = $bookingData['guest_email'] ?? $bookingData['email'] ?? 'N/A';
        $checkIn = $bookingData['check_in'] ?? 'N/A';
        $checkOut = $bookingData['check_out'] ?? 'N/A';
        $nights = $bookingData['nights'] ?? 1;
        $total = $bookingData['total_amount'] ?? 0;
        $totalFormatted = number_format($total, 0, ',', '.') . ' VND';

        $status = $bookingData['status'] ?? 'pending';
        $statusEmoji = match ($status) {
            'pending' => '⏳',
            'confirmed' => '✅',
            'checked_in' => '🏨',
            'checked_out' => '👋',
            'cancelled' => '❌',
            default => '📋'
        };

        $time = date('d/m/Y H:i');

        $text = "<b>🔔 BOOKING MỚI - Aurora Hotel Plaza</b>\n"
            . "<b>Mã:</b> #{$bookingCode}\n"
            . "<b>Trạng thái:</b> {$statusEmoji} {$status}\n"
            . "\n"
            . "<b>━━━ THÔNG TIN KHÁCH ━━━</b>\n"
            . "<b>👤 Họ tên:</b> {$guestName}\n"
            . "<b>📱 Điện thoại:</b> {$guestPhone}\n"
            . "<b>📧 Email:</b> {$guestEmail}\n"
            . "\n"
            . "<b>━━━ THÔNG TIN ĐẶT PHÒNG ━━━</b>\n"
            . "<b>🏠 Loại phòng:</b> {$typeName}\n"
            . "<b>📅 Check-in:</b> {$checkIn}\n"
            . "<b>📅 Check-out:</b> {$checkOut}\n"
            . "<b>🌙 Số đêm:</b> {$nights}\n"
            . "<b>💰 Tổng tiền:</b> {$totalFormatted}\n"
            . "\n"
            . "<b>⏰ Thời gian đặt:</b> {$time}\n";

        if (defined('BASE_URL')) {
            $adminUrl = BASE_URL . "/admin/booking-detail.php?id=" . ($bookingData['booking_id'] ?? $bookingData['id'] ?? '');
            $text .= "\n<a href=\"{$adminUrl}\">📋 Xem chi tiết trên Admin</a>";
        }

        return self::sendMessage($text);
    }

    public static function sendChatNotification($conversationData, $messageData)
    {
        self::init();

        $convId = $conversationData['conversation_id'] ?? 0;
        $guestName = $conversationData['guest_name'] ?? 'Khách';
        $message = $messageData['message'] ?? '';
        $time = date('d/m/Y H:i');

        $preview = mb_substr($message, 0, 200);
        if (mb_strlen($message) > 200) {
            $preview .= '...';
        }

        $text = "<b>💬 TIN NHẮN MỚI - Aurora Hotel Plaza</b>\n"
            . "<b>👤 Khách:</b> {$guestName}\n"
            . "<b>⏰ Thời gian:</b> {$time}\n"
            . "\n"
            . "<b>━━━ TIN NHẮN ━━━</b>\n"
            . "<code>" . htmlspecialchars($preview) . "</code>\n";

        if (defined('BASE_URL')) {
            $adminUrl = BASE_URL . "/admin/chat.php?conv=" . $convId;
            $text .= "\n<a href=\"{$adminUrl}\">💬 Trả lời trên Admin</a>";
        }

        return self::sendMessage($text);
    }

    public static function setWebhook($webhookUrl)
    {
        self::init();

        if (empty(self::$botToken)) {
            return ['success' => false, 'error' => 'Bot token not configured'];
        }

        $payload = json_encode([
            'url' => $webhookUrl
        ]);

        $ch = curl_init("https://api.telegram.org/bot" . self::$botToken . "/setWebhook");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($result, true);

        return [
            'success' => $httpCode === 200 && ($data['ok'] ?? false),
            'data' => $data
        ];
    }

    public static function deleteWebhook()
    {
        self::init();

        if (empty(self::$botToken)) {
            return ['success' => false, 'error' => 'Bot token not configured'];
        }

        $ch = curl_init("https://api.telegram.org/bot" . self::$botToken . "/deleteWebhook");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);

        return [
            'success' => ($data['ok'] ?? false),
            'data' => $data
        ];
    }

    public static function getWebhookInfo()
    {
        self::init();

        if (empty(self::$botToken)) {
            return ['success' => false, 'error' => 'Bot token not configured'];
        }

        $ch = curl_init("https://api.telegram.org/bot" . self::$botToken . "/getWebhookInfo");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);

        return [
            'success' => ($data['ok'] ?? false),
            'data' => $data['result'] ?? null
        ];
    }

    public static function replyToChatMessage($chatId, $text, $replyToMessageId = null)
    {
        self::init();

        if (empty(self::$botToken)) {
            return ['success' => false, 'error' => 'Bot token not configured'];
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($replyToMessageId) {
            $payload['reply_to_message_id'] = $replyToMessageId;
        }

        $ch = curl_init("https://api.telegram.org/bot" . self::$botToken . "/sendMessage");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($result, true);

        return [
            'success' => $httpCode === 200 && ($data['ok'] ?? false),
            'data' => $data
        ];
    }
}

function sendTelegramMessage($text)
{
    return TelegramHelper::sendMessage($text);
}

function sendTelegramBookingNotification($bookingData)
{
    return TelegramHelper::sendBookingNotification($bookingData);
}

function sendTelegramChatNotification($conversationData, $messageData)
{
    return TelegramHelper::sendChatNotification($conversationData, $messageData);
}

function getTelegramConfig()
{
    return TelegramHelper::getConfig();
}