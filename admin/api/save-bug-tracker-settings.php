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

    // Bỏ qua token/chat_id rỗng (giữ nguyên giá trị cũ)
    if ($value === '' && in_array($key, ['telegram_bot_token', 'telegram_chat_id'])) {
        continue;
    }

    try {
        // Xóa TẤT CẢ rows của key này (kể cả duplicate rỗng) rồi insert mới
        // Đây là cách duy nhất đảm bảo không có duplicate gây đọc nhầm
        $db->prepare("DELETE FROM system_settings WHERE setting_key = ?")
            ->execute([$key]);

        $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, 'string', ?)")
            ->execute([$key, $value, $key]);

    } catch (\Throwable $e) {
        error_log('[BugTracker] Save setting failed for ' . $key . ': ' . $e->getMessage());
    }
}

header('Location: ../ai-bug.php?msg=settings_saved#settingsCard');
exit;
