<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized');
}

require_once '../config/database.php';
require_once '../helpers/bot-detector.php';

$db = getDB();
if (!$db) die('Database connection failed');

$batch_size = 500;
$total_rows_to_check = 0;

try {
    $total_stmt = $db->query("SELECT COUNT(*) FROM traffic_logs WHERE bot_type IS NULL OR bot_name IS NULL");
    $total_rows_to_check = $total_stmt->fetchColumn();
} catch (Exception $e) {}

echo "<h2>Đang đồng bộ dữ liệu Bot...</h2>";
echo "<p>Tổng số dòng còn lại cần xử lý: <strong>$total_rows_to_check</strong></p>";

try {
    // Lấy batch tiếp theo
    $stmt = $db->prepare("SELECT id, ip_address, user_agent FROM traffic_logs WHERE bot_type IS NULL OR bot_name IS NULL LIMIT :limit");
    $stmt->bindValue(':limit', $batch_size, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();

    if (empty($logs)) {
        echo "<p style='color:green; font-weight:bold;'>Đã đồng bộ xong toàn bộ lịch sử!</p>";
        echo "<p><a href='traffic-logs.php'>Quay lại Nhật ký lưu lượng</a></p>";
        exit;
    }

    $processed = 0;
    $bots_found = 0;
    $humans_found = 0;

    foreach ($logs as $log) {
        $processed++;
        $_SERVER['HTTP_USER_AGENT'] = $log['user_agent'];
        $_SERVER['REMOTE_ADDR'] = $log['ip_address'];

        // Tắt verify DNS khi sync lịch sử để tránh timeout, chỉ nhận diện qua UA
        // (Hoặc nếu bạn muốn chính xác tuyệt đối, hãy để nguyên nhưng batch nhỏ lại)
        $botInfo = BotDetector::detect();
        
        if ($botInfo['is_bot']) {
            $updateStmt = $db->prepare("UPDATE traffic_logs SET bot_type = ?, bot_name = ?, device_type = 'bot' WHERE id = ?");
            $updateStmt->execute([$botInfo['type'], $botInfo['name'], $log['id']]);
            $bots_found++;
        } else {
            $updateStmt = $db->prepare("UPDATE traffic_logs SET bot_type = 'none', bot_name = '' WHERE id = ?");
            $updateStmt->execute([$log['id']]);
            $humans_found++;
        }
    }

    echo "<p>Vừa xử lý: $processed dòng (Bots: $bots_found, Human: $humans_found).</p>";
    
    if ($total_rows_to_check > $batch_size) {
        echo "<p>Đang chuyển hướng để xử lý batch tiếp theo sau 2 giây...</p>";
        echo "<script>setTimeout(() => { window.location.reload(); }, 2000);</script>";
    } else {
        echo "<p style='color:green; font-weight:bold;'>Tất cả dữ liệu đã được xử lý xong!</p>";
        echo "<p><a href='traffic-logs.php'>Quay lại Nhật ký lưu lượng</a></p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}
