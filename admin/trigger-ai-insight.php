<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized');
}

require_once '../config/database.php';
require_once '../helpers/ai-traffic-analyzer.php';

echo "<h2>Kích hoạt AI Phân tích Động thái Website...</h2>";

if (AITrafficAnalyzer::logDailyInsight()) {
    echo "<p style='color:green; font-weight:bold;'>Thành công! AI đã phân tích dữ liệu hôm nay và ghi vào Nhật ký hoạt động.</p>";
    echo "<p><a href='activity-logs.php'>Xem Nhật ký hoạt động (Activity Logs)</a></p>";
} else {
    echo "<p style='color:red;'>Lỗi trong quá trình phân tích dữ liệu.</p>";
}
