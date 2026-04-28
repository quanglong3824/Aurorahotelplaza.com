<?php
/**
 * API: Tạo bảng telegram_message_mapping
 * Admin only - Run once
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    $sql = "
        CREATE TABLE IF NOT EXISTS telegram_message_mapping (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            telegram_message_id BIGINT NOT NULL,
            message_type ENUM('notification', 'reply') DEFAULT 'notification',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_conversation (conversation_id),
            INDEX idx_telegram_msg (telegram_message_id),
            UNIQUE KEY unique_conv_msg (conversation_id, telegram_message_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($sql);
    
    echo json_encode([
        'success' => true,
        'message' => 'Table telegram_message_mapping created successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error creating table: ' . $e->getMessage()
    ]);
}