<?php
/**
 * Aurora Hotel Plaza - Security Migration
 * Thiết lập bảng Blacklist và Cấu hình bảo mật
 */

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized');
}

require_once '../config/database.php';
$db = getDB();

echo "<h2>Thiết lập Hệ thống Bảo mật Anti-Bot</h2>";

try {
    // 1. Bảng lưu danh sách đen IP
    $sql_blacklist = "CREATE TABLE IF NOT EXISTS security_blacklist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL UNIQUE,
        reason TEXT,
        attempts INT DEFAULT 1,
        is_permanent TINYINT(1) DEFAULT 0,
        expires_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql_blacklist);
    echo "<p>✔️ Đã tạo bảng <strong>security_blacklist</strong>.</p>";

    // 2. Bảng lưu vết Honeypot (để đối soát)
    $sql_honeypot = "CREATE TABLE IF NOT EXISTS security_honeypot_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        request_uri TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql_honeypot);
    echo "<p>✔️ Đã tạo bảng <strong>security_honeypot_logs</strong>.</p>";

    echo "<p style='color:green; font-weight:bold;'>Thiết lập Database hoàn tất!</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi thiết lập: " . $e->getMessage() . "</p>";
}
