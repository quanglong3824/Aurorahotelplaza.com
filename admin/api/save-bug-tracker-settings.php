<?php
/**
 * API: Lưu cài đặt Bug Tracker
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

require_once '../../config/database.php';
$db = getDB();

$fields = ['telegram_bot_token', 'telegram_chat_id', 'bug_tracker_enabled', 'bug_tracker_min_severity'];

foreach ($fields as $key) {
    if (!isset($_POST[$key]))
        continue;
    $value = trim($_POST[$key]);

    // Kiểm tra key đã tồn tại chưa
    $check = $db->prepare("SELECT setting_id FROM system_settings WHERE setting_key = ?");
    $check->execute([$key]);
    if ($check->fetch()) {
        $db->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?")
            ->execute([$value, $_SESSION['user_id'], $key]);
    } else {
        $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, updated_by) VALUES (?, ?, 'string', ?, ?)")
            ->execute([$key, $value, $key, $_SESSION['user_id']]);
    }
}

header('Location: ../ai-bug.php?msg=settings_saved');
exit;
