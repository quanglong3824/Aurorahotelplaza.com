<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized');
}

require_once '../config/database.php';
$db = getDB();

echo "<h2>Cập nhật cấu trúc bảng traffic_logs...</h2>";

try {
    // Thêm cột bot_type
    $db->exec("ALTER TABLE traffic_logs ADD COLUMN IF NOT EXISTS bot_type ENUM('good', 'bad', 'none') DEFAULT NULL AFTER is_unique");
    echo "<p>Đã kiểm tra/thêm cột <strong>bot_type</strong>.</p>";

    // Thêm cột bot_name
    $db->exec("ALTER TABLE traffic_logs ADD COLUMN IF NOT EXISTS bot_name VARCHAR(255) DEFAULT NULL AFTER bot_type");
    echo "<p>Đã kiểm tra/thêm cột <strong>bot_name</strong>.</p>";

    echo "<p><strong>Thành công!</strong> Bây giờ bạn có thể chạy <a href='sync-bot-history.php'>Đồng bộ lịch sử</a>.</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}
