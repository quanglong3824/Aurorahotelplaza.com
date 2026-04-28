<?php
/**
 * API: Set/Delete Telegram Webhook
 * Admin only
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../helpers/telegram.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'set';

if ($action === 'set') {
    $webhookUrl = BASE_URL . '/api/telegram-webhook.php';
    $result = TelegramHelper::setWebhook($webhookUrl);
    
    echo json_encode([
        'success' => $result['success'],
        'webhook_url' => $webhookUrl,
        'data' => $result['data']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'delete') {
    $result = TelegramHelper::deleteWebhook();
    echo json_encode([
        'success' => $result['success'],
        'data' => $result['data']
    ]);
    exit;
}

if ($action === 'info') {
    $result = TelegramHelper::getWebhookInfo();
    echo json_encode([
        'success' => $result['success'],
        'data' => $result['data']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);