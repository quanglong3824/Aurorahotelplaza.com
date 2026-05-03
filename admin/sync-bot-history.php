<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized');
}

require_once '../config/database.php';
require_once '../helpers/bot-detector.php';

$db = getDB();
if (!$db) die('Database connection failed');

echo "<h2>Bắt đầu đồng bộ dữ liệu Bot cho lịch sử lưu lượng...</h2>";

try {
    // Lấy các dòng log chưa có thông tin bot
    $stmt = $db->query("SELECT id, ip_address, user_agent FROM traffic_logs WHERE bot_type IS NULL OR bot_name IS NULL LIMIT 500");
    $logs = $stmt->fetchAll();

    $count = 0;
    $updated = 0;

    foreach ($logs as $log) {
        $count++;
        // Giả lập $_SERVER để BotDetector hoạt động
        $_SERVER['HTTP_USER_AGENT'] = $log['user_agent'];
        $_SERVER['REMOTE_ADDR'] = $log['ip_address'];

        $botInfo = BotDetector::detect();
        
        if ($botInfo['is_bot']) {
            $updateStmt = $db->prepare("UPDATE traffic_logs SET bot_type = ?, bot_name = ?, device_type = 'bot' WHERE id = ?");
            $updateStmt->execute([$botInfo['type'], $botInfo['name'], $log['id']]);
            $updated++;
        } else {
            // Nếu không phải bot, đảm bảo bot_type là null (hoặc none nếu bạn muốn)
            $updateStmt = $db->prepare("UPDATE traffic_logs SET bot_type = 'none', bot_name = '' WHERE id = ?");
            $updateStmt->execute([$log['id']]);
        }
    }

    echo "<p>Đã quét: $count dòng.</p>";
    echo "<p>Đã nhận diện và cập nhật: $updated Bot.</p>";
    echo "<p><strong>Đồng bộ hoàn tất!</strong> Quay lại trang <a href='traffic-logs.php'>Nhật ký lưu lượng</a>.</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}
