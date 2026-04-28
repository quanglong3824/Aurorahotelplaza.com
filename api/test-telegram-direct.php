<?php
require_once __DIR__ . '/../helpers/telegram.php';

error_log('=== Test Telegram Direct ===');

$result = TelegramHelper::sendMessage('Test từ PHP - Aurora Hotel Plaza Admin');

error_log('Result: ' . json_encode($result, JSON_UNESCAPED_UNICODE));

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);