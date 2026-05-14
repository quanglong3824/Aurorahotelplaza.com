<?php
/**
 * API: Kiểm tra trạng thái đặt phòng
 * Trả về JSON: { "disabled": true/false, "message": "..." }
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('booking_disabled', 'booking_disabled_message')");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode([
        'disabled' => ($rows['booking_disabled'] ?? '0') === '1',
        'message' => $rows['booking_disabled_message'] ?? 'Hệ thống tạm thời không nhận đặt phòng mới. Vui lòng liên hệ hotline để được hỗ trợ.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Không thể kiểm tra trạng thái đặt phòng']);
}
