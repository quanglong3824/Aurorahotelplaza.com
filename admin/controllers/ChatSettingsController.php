<?php
/**
 * Aurora Hotel Plaza - Chat Settings Controller
 */

function getChatSettingsData() {
    $user_role = $_SESSION['user_role'];
    if (!in_array($user_role, ['admin', 'receptionist'])) {
        return ['error' => 'Không có quyền truy cập.'];
    }

    $db = getDB();
    $migrationNeeded = false;
    $qr = [];
    $settingsRaw = [];

    try {
        // Load quick replies
        $qr = $db->query("SELECT * FROM chat_quick_replies ORDER BY category, sort_order, title")->fetchAll();
        // Load chat settings
        $settingsRaw = $db->query("SELECT setting_key, setting_value FROM chat_settings")->fetchAll();
    } catch (PDOException $e) {
        $migrationNeeded = true; // bảng chưa tồn tại
    }

    $settings = [];
    foreach ($settingsRaw as $s) {
        $settings[$s['setting_key']] = $s['setting_value'];
    }

    $defaults = [
        'auto_reply_enabled' => '1',
        'auto_reply_message' => 'Xin chào! Cảm ơn bạn đã liên hệ với Aurora Hotel Plaza. Chúng tôi sẽ phản hồi trong thời gian sớm nhất.',
        'working_hours_start' => '08:00',
        'working_hours_end' => '22:00',
        'offline_message' => 'Chúng tôi hiện ngoài giờ làm việc. Vui lòng để lại tin nhắn, chúng tôi sẽ phản hồi sớm.',
        'max_conversations' => '10',
        'sse_interval_global' => '3',
        'sse_interval_conv' => '2',
        'sound_enabled' => '1',
        'chat_enabled' => '1',
    ];
    $settings = array_merge($defaults, $settings);

    // Group quick replies by category
    $qrByCategory = [];
    foreach ($qr as $r) {
        $qrByCategory[$r['category'] ?? 'Chung'][] = $r;
    }

    return [
        'migrationNeeded' => $migrationNeeded,
        'qr' => $qr,
        'settings' => $settings,
        'qrByCategory' => $qrByCategory,
        'user_role' => $user_role
    ];
}
