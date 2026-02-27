<?php
/**
 * API: Staff Heartbeat - Cập nhật trạng thái online
 * POST /api/chat/staff-heartbeat.php
 * 
 * Gọi mỗi 30s từ admin panel để báo nhân viên đang online.
 * Dùng bảng `chat_settings` để lưu JSON danh sách staff online.
 */
session_start();
header('Content-Type: application/json');

// Chỉ cho phép staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();
    $user_id = (int) $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? 'Nhân viên';
    $now = time();

    // Lấy danh sách staff online hiện tại từ chat_settings
    $stmt = $db->prepare("SELECT setting_value FROM chat_settings WHERE setting_key = 'staff_online'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $online_staff = [];
    if ($row && $row['setting_value']) {
        $online_staff = json_decode($row['setting_value'], true) ?: [];
    }

    // Cập nhật/thêm nhân viên hiện tại
    $online_staff[$user_id] = [
        'user_id' => $user_id,
        'name' => $user_name,
        'role' => $_SESSION['user_role'],
        'last_seen' => $now
    ];

    // Loại bỏ nhân viên offline (không ping trong 60 giây)
    foreach ($online_staff as $id => $staff) {
        if ($now - $staff['last_seen'] > 60) {
            unset($online_staff[$id]);
        }
    }

    $json = json_encode($online_staff);

    // Upsert vào chat_settings
    if ($row) {
        $stmt = $db->prepare("UPDATE chat_settings SET setting_value = ? WHERE setting_key = 'staff_online'");
        $stmt->execute([$json]);
    } else {
        $stmt = $db->prepare("INSERT INTO chat_settings (setting_key, setting_value, setting_type) VALUES ('staff_online', ?, 'json')");
        $stmt->execute([$json]);
    }

    echo json_encode([
        'success' => true,
        'online_count' => count($online_staff),
        'staff' => array_values($online_staff)
    ]);

} catch (Exception $e) {
    error_log("Staff heartbeat error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
