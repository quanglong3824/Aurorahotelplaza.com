<?php
/**
 * Toggle Show Prices - Quick API
 * Bật/tắt hiển thị giá trên toàn bộ frontend
 * CHỈ ADMIN
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';
require_once '../../helpers/settings-helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Chỉ admin mới có quyền']);
    exit;
}

try {
    $db = getDB();

    // Lấy trạng thái hiện tại
    $current = getSystemSetting('show_prices', '0');
    $new_value = $current === '1' ? '0' : '1';

    $stmt = $db->prepare("
        INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at)
        VALUES ('show_prices', :val, :uid, NOW())
        ON DUPLICATE KEY UPDATE setting_value = :val, updated_by = :uid, updated_at = NOW()
    ");
    $stmt->execute([':val' => $new_value, ':uid' => $_SESSION['user_id']]);

    $label = $new_value === '1' ? 'BẬT (hiển thị giá)' : 'TẮT (hiển thị Liên hệ)';

    echo json_encode([
        'success'   => true,
        'new_value' => $new_value,
        'message'   => "Giá trên website: {$label}"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
