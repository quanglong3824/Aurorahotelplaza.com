<?php
/**
 * API: Check Staff Online - Widget gọi để biết có ai online không
 * GET /api/chat/check-staff-online.php
 * 
 * Không cần đăng nhập (widget dùng cho tất cả khách).
 * Trả về số nhân viên online và tên (nếu có).
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();
    $now = time();

    // Lấy danh sách staff online
    $stmt = $db->prepare("SELECT setting_value FROM chat_settings WHERE setting_key = 'staff_online'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $online_staff = [];
    if ($row && $row['setting_value']) {
        $all_staff = json_decode($row['setting_value'], true) ?: [];

        // Chỉ lấy staff còn online (ping trong 60 giây qua)
        foreach ($all_staff as $staff) {
            if ($now - ($staff['last_seen'] ?? 0) <= 60) {
                $online_staff[] = [
                    'name' => $staff['name'] ?? 'Nhân viên',
                    'role' => $staff['role'] ?? 'staff'
                ];
            }
        }
    }

    $count = count($online_staff);

    // Tạo text hiển thị phù hợp
    if ($count === 0) {
        $status_text = 'Aurora AI (Online 24/7)';
        $is_online = true; // Luôn Online nhờ AI
    } elseif ($count === 1) {
        $status_text = $online_staff[0]['name'] . ' đang trực';
        $is_online = true;
    } else {
        $status_text = $count . ' nhân viên đang trực';
        $is_online = true;
    }

    echo json_encode([
        'success' => true,
        'is_online' => $is_online,
        'online_count' => $count,
        'status_text' => $status_text,
        'staff' => $online_staff
    ]);

} catch (Exception $e) {
    error_log("Check staff online error: " . $e->getMessage());
    echo json_encode([
        'success' => true,
        'is_online' => false,
        'online_count' => 0,
        'status_text' => 'Hỗ trợ trực tuyến'
    ]);
}
