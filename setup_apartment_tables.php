<?php
// Mock server variables for CLI execution
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_ADDR'] = '127.0.0.1';

require_once 'config/database.php';

try {
    $db = getDB();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create apartment_inquiries table
    $sql1 = "CREATE TABLE IF NOT EXISTS apartment_inquiries (
        inquiry_id INT AUTO_INCREMENT PRIMARY KEY,
        inquiry_code VARCHAR(50) NOT NULL UNIQUE,
        user_id INT NULL,
        room_type_id INT NOT NULL,
        guest_name VARCHAR(100) NOT NULL,
        guest_email VARCHAR(100) NOT NULL,
        guest_phone VARCHAR(20) NOT NULL,
        preferred_check_in DATE NULL,
        preferred_check_out DATE NULL,
        duration_type VARCHAR(20) DEFAULT 'short_term',
        num_adults INT DEFAULT 1,
        num_children INT DEFAULT 0,
        message TEXT NULL,
        special_requests TEXT NULL,
        status ENUM('new', 'contacted', 'in_progress', 'converted', 'closed', 'cancelled') DEFAULT 'new',
        priority ENUM('normal', 'low', 'high', 'urgent') DEFAULT 'normal',
        assigned_to INT NULL,
        admin_notes TEXT NULL,
        converted_booking_id INT NULL,
        conversion_date DATETIME NULL,
        contacted_at DATETIME NULL,
        closed_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql1);
    echo "Table 'apartment_inquiries' created successfully.<br>";

    // Create apartment_inquiry_history table
    $sql2 = "CREATE TABLE IF NOT EXISTS apartment_inquiry_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        inquiry_id INT NOT NULL,
        old_status VARCHAR(50) NULL,
        new_status VARCHAR(50) NULL,
        changed_by INT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (inquiry_id) REFERENCES apartment_inquiries(inquiry_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql2);
    echo "Table 'apartment_inquiry_history' created successfully.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>