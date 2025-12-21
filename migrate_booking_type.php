<?php
/**
 * Migration: Add booking_type column to bookings table
 * This allows storing both instant bookings (rooms) and inquiry bookings (apartments)
 */

// Mock server variables for CLI execution
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_ADDR'] = '127.0.0.1';

require_once 'config/database.php';

try {
    $db = getDB();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if booking_type column exists
    $stmt = $db->query("SHOW COLUMNS FROM bookings LIKE 'booking_type'");
    $columnExists = $stmt->rowCount() > 0;

    if (!$columnExists) {
        // Add booking_type column
        $sql = "ALTER TABLE bookings ADD COLUMN booking_type ENUM('instant', 'inquiry') DEFAULT 'instant' AFTER booking_code";
        $db->exec($sql);
        echo "Column 'booking_type' added to bookings table.<br>";
    } else {
        echo "Column 'booking_type' already exists.<br>";
    }

    // Check if inquiry_message column exists
    $stmt = $db->query("SHOW COLUMNS FROM bookings LIKE 'inquiry_message'");
    $messageColumnExists = $stmt->rowCount() > 0;

    if (!$messageColumnExists) {
        // Add inquiry_message column for apartment inquiries
        $sql = "ALTER TABLE bookings ADD COLUMN inquiry_message TEXT NULL AFTER special_requests";
        $db->exec($sql);
        echo "Column 'inquiry_message' added to bookings table.<br>";
    } else {
        echo "Column 'inquiry_message' already exists.<br>";
    }

    // Check if duration_type column exists  
    $stmt = $db->query("SHOW COLUMNS FROM bookings LIKE 'duration_type'");
    $durationColumnExists = $stmt->rowCount() > 0;

    if (!$durationColumnExists) {
        // Add duration_type column for apartment inquiries
        $sql = "ALTER TABLE bookings ADD COLUMN duration_type VARCHAR(50) NULL AFTER inquiry_message";
        $db->exec($sql);
        echo "Column 'duration_type' added to bookings table.<br>";
    } else {
        echo "Column 'duration_type' already exists.<br>";
    }

    echo "<br><strong>Migration completed successfully!</strong>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>