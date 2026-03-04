<?php
/**
 * API: Lưu cài đặt Bug Tracker (Telegram)
 * Chỉ cập nhật các field có trong POST và không rỗng
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

require_once '../../config/database.php';

try {
    $db = getDB();
} catch (\Throwable $e) {
    header('Location: ../ai-bug.php?msg=db_error');
    exit;
}

$fields = ['telegram_bot_token', 'telegram_chat_id', 'bug_tracker_enabled', 'bug_tracker_min_severity'];

foreach ($fields as $key) {
    if (!isset($_POST[$key]))
        continue;
    $value = trim($_POST[$key]);

    // Nếu field trống (người dùng không nhập lại), bỏ qua — giữ nguyên giá trị cũ
    // Ngoại trừ bug_tracker_enabled và min_severity luôn cập nhật
    if ($value === '' && !in_array($key, ['bug_tracker_enabled', 'bug_tracker_min_severity'])) {
        continue;
    }

    try {
        // Kiểm tra key đã tồn tại chưa
        $checkStmt = $db->prepare("SELECT setting_id FROM system_settings WHERE setting_key = ?");
        $checkStmt->execute([$key]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // UPDATE — chỉ dùng các cột chắc chắn tồn tại
            $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")
                ->execute([$value, $key]);
        } else {
            // INSERT — dùng các cột chắc chắn tồn tại
            $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, 'string', ?)")
                ->execute([$key, $value, $key]);
        }
    } catch (\Throwable $e) {
        // Fallback: INSERT IGNORE nếu cột nào đó thiếu
        try {
            $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")
                ->execute([$key, $value, $value]);
        } catch (\Throwable $e2) {
            error_log('[BugTracker] Save setting failed for ' . $key . ': ' . $e2->getMessage());
        }
    }
}

header('Location: ../ai-bug.php?msg=settings_saved#settingsCard');
exit;
