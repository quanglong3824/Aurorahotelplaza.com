<?php
/**
 * Background Task: Xử lý AI Analysis và Telegram Alert
 * Script này được gọi qua cURL non-blocking từ ErrorTracker,
 * đảm bảo không làm chậm trang của người dùng.
 */
// Tắt giới hạn thời gian và cho phép chạy ngầm kể cả khi cURL đóng kết nối
ignore_user_abort(true);
set_time_limit(120); // Cho phép tối đa 2 phút để gọi Gemini

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/error-tracker.php';

// Bảo mật: chỉ cho phép local server gọi hoặc xác thực bằng secret key
$secret = $_POST['secret'] ?? '';
$expectedSecret = md5('aurora_bg_secret_key'); // Trong thực tế nên để ở config

if ($secret !== $expectedSecret) {
    http_response_code(403);
    exit('Forbidden');
}

$errorId = (int) ($_POST['error_id'] ?? 0);
$errorDataJson = $_POST['error_data'] ?? '';

if (!$errorId || !$errorDataJson) {
    exit('Invalid data');
}

$errorData = json_decode($errorDataJson, true);

// Khởi tạo tracker để load settings
AuroraErrorTracker::init();

// Tiến hành phân tích và gửi
AuroraErrorTracker::analyzeWithAiAndNotify($errorId, $errorData);

echo "OK";
