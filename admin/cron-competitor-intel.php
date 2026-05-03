<?php
/**
 * Aurora Hotel Plaza - Competitor Intelligence Cronjob
 * Xử lý hàng đợi phân tích đối thủ tự động
 */

// Chạy CLI hoặc HTTP
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        die('Unauthorized');
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/competitor-analyzer.php';

$db = getDB();
if (!$db) die('Database connection failed');

// Mỗi lần chạy xử lý tối đa 1-2 đối thủ để tránh timeout hosting
$limit = 2;

echo "<h2>Cronjob: Bắt đầu phân tích đối thủ...</h2>";

try {
    $stmt = $db->prepare("SELECT id, name FROM competitor_intelligence WHERE status IN ('pending', 'error') ORDER BY created_at ASC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $queue = $stmt->fetchAll();

    if (empty($queue)) {
        echo "<p>Không có đối thủ nào trong hàng đợi.</p>";
        exit;
    }

    foreach ($queue as $item) {
        echo "<p>Đang xử lý: <strong>{$item['name']}</strong>... ";
        if (CompetitorAnalyzer::processOne($item['id'])) {
            echo "<span style='color:green'>Thành công!</span></p>";
        } else {
            echo "<span style='color:red'>Thất bại. Kiểm tra log lỗi.</span></p>";
        }
        // Nghỉ một chút giữa các lần gọi API để tránh rate limit
        sleep(5);
    }

    echo "<p><strong>Hoàn tất phiên xử lý.</strong></p>";

} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi Cron: " . $e->getMessage() . "</p>";
}
