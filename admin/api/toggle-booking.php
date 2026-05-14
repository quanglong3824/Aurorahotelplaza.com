<?php
/**
 * API: Bật/tắt chặn đặt phòng (quick toggle từ admin header)
 */
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    // Lấy trạng thái hiện tại
    $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'booking_disabled'");
    $current = $stmt->fetchColumn();
    $new_value = ($current === '1') ? '0' : '1';

    // Cập nhật
    $stmt = $db->prepare("
        INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at)
        VALUES ('booking_disabled', :value, :user_id, NOW())
        ON DUPLICATE KEY UPDATE 
            setting_value = :value,
            updated_by = :user_id,
            updated_at = NOW()
    ");
    $stmt->execute([':value' => $new_value, ':user_id' => $_SESSION['user_id']]);

    echo json_encode([
        'success' => true,
        'disabled' => $new_value === '1',
        'message' => $new_value === '1'
            ? '⛔ Đã chặn đặt phòng. Nút booking trên website sẽ bị xám đi.'
            : '✅ Đã mở lại đặt phòng.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
