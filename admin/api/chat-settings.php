<?php
/**
 * admin/api/chat-settings.php
 * Lưu / đọc cài đặt chat từ bảng chat_settings
 * GET  → trả về tất cả settings
 * POST → upsert từng cặp key/value
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

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = $db->query("SELECT setting_key, setting_value FROM chat_settings")->fetchAll();
        $settings = [];
        foreach ($rows as $r)
            $settings[$r['setting_key']] = $r['setting_value'];
        echo json_encode(['success' => true, 'data' => $settings]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    // Whitelist các keys được phép update
    $allowed = [
        'chat_enabled',
        'auto_reply_enabled',
        'auto_reply_message',
        'offline_message',
        'max_conversations',
        'working_hours_start',
        'working_hours_end',
        'sse_interval_global',
        'sse_interval_conv',
        'sound_enabled'
    ];

    $stmt = $db->prepare("
        INSERT INTO chat_settings (setting_key, setting_value, updated_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
    ");

    $db->beginTransaction();
    foreach ($allowed as $key) {
        if (array_key_exists($key, $data)) {
            $val = is_string($data[$key]) ? trim($data[$key]) : (string) $data[$key];
            $stmt->execute([$key, $val]);
        }
    }
    $db->commit();

    // Log activity
    $changed = implode(', ', array_filter($allowed, fn($key) => array_key_exists($key, $data)));
    if ($changed) {
        try {
            $logStmt = $db->prepare("
                INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
                VALUES (?, 'chat_settings_update', ?, ?, NOW())
            ");
            $logStmt->execute([
                $_SESSION['user_id'],
                "Cập nhật cài đặt chat: $changed",
                $_SERVER['REMOTE_ADDR']
            ]);
        } catch (Exception $e) { /* log không bắt buộc */
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
