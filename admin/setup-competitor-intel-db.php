<?php
/**
 * Aurora Hotel Plaza - Competitor Intelligence Migration
 * Tạo cấu trúc bảng MySQL cho hệ thống phân tích đối thủ AI
 */

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized');
}

require_once '../config/database.php';
$db = getDB();

echo "<h2>Thiết lập Database: AI Competitor Intelligence</h2>";

try {
    // 1. Bảng lưu danh sách đối thủ và kết quả phân tích
    $sql_competitors = "CREATE TABLE IF NOT EXISTS competitor_intelligence (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        url VARCHAR(255) NOT NULL,
        raw_markdown LONGTEXT DEFAULT NULL,
        analysis_data JSON DEFAULT NULL,
        status ENUM('pending', 'processing', 'completed', 'error') DEFAULT 'pending',
        error_message TEXT DEFAULT NULL,
        last_analyzed TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_url (url)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql_competitors);
    echo "<p>✔️ Đã tạo bảng <strong>competitor_intelligence</strong>.</p>";

    // 2. Bảng lưu trữ USP và thông số trích xuất nhanh (để search/filter)
    $sql_usp = "CREATE TABLE IF NOT EXISTS competitor_usp (
        id INT AUTO_INCREMENT PRIMARY KEY,
        competitor_id INT NOT NULL,
        usp_title VARCHAR(255),
        usp_description TEXT,
        impact_score INT DEFAULT 0,
        FOREIGN KEY (competitor_id) REFERENCES competitor_intelligence(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql_usp);
    echo "<p>✔️ Đã tạo bảng <strong>competitor_usp</strong>.</p>";

    echo "<p style='color:green; font-weight:bold;'>Thiết lập Database hoàn tất!</p>";
    echo "<p><a href='competitor-intelligence.php'>Đi đến trang Quản lý đối thủ</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi thiết lập: " . $e->getMessage() . "</p>";
}
