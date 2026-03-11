<?php
/**
 * Aurora Hotel Plaza - Session Cleanup Script
 * ============================================
 * Run this script periodically to clean up stale guest sessions
 * 
 * Usage:
 *   php helpers/session-cleanup.php
 * 
 * Cron job suggestion (run daily at 3 AM):
 *   0 3 * * * /Applications/XAMPP/xamppfiles/bin/php /Applications/XAMPP/xamppfiles/htdocs/Github/AURORA HOTEL PLAZA/Aurorahotelplaza.com/helpers/session-cleanup.php >> /var/log/session_cleanup.log
 */

// Load environment
require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/database.php';

// Get database connection
$db = getDB();

if (!$db) {
    echo "Error: Cannot connect to database.\n";
    exit(1);
}

// Define session expiration time (30 days)
$expiration_days = 30;
$expiration_timestamp = time() - ($expiration_days * 24 * 60 * 60);

echo "=== Aurora Hotel Plaza - Session Cleanup ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Cleanup threshold: " . date('Y-m-d H:i:s', $expiration_timestamp) . " ({$expiration_days} days ago)\n\n";

$deleted_count = 0;

// Cleanup strategy: Delete old pending sessions (user_id is NULL for guests)
// Sessions table may not exist, so handle gracefully
try {
    // Check if sessions table exists
    $stmt = $db->query("SHOW TABLES LIKE 'sessions'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->prepare("
            DELETE FROM sessions 
            WHERE last_activity < :timestamp 
            AND user_id IS NULL
        ");
        $stmt->execute([':timestamp' => $expiration_timestamp]);
        $deleted_sessions = $stmt->rowCount();
        $deleted_count += $deleted_sessions;
        echo "[OK] Deleted {$deleted_sessions} stale sessions from sessions table\n";
    } else {
        echo "[INFO]sessions table does not exist - skipping cleanup\n";
    }
} catch (PDOException $e) {
    echo "[WARN] Failed to cleanup sessions: " . $e->getMessage() . "\n";
}

// Cleanup strategy: Delete old chat guest messages
try {
    $stmt = $db->query("SHOW TABLES LIKE 'chat_messages'");
    if ($stmt->rowCount() > 0) {
        // Get count before delete
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM chat_messages 
            WHERE user_id IS NULL OR user_id = 0
            AND created_at < FROM_UNIXTIME(:timestamp)
        ");
        $stmt->execute([':timestamp' => $expiration_timestamp]);
        $before_count = $stmt->fetchColumn();
        
        // Delete old messages
        $stmt = $db->prepare("
            DELETE FROM chat_messages 
            WHERE (user_id IS NULL OR user_id = 0)
            AND created_at < FROM_UNIXTIME(:timestamp)
        ");
        $stmt->execute([':timestamp' => $expiration_timestamp]);
        $deleted_messages = $stmt->rowCount();
        $deleted_count += $deleted_messages;
        
        echo "[OK] Deleted {$deleted_messages} old chat messages (was: {$before_count})\n";
    } else {
        echo "[INFO] chat_messages table does not exist - skipping cleanup\n";
    }
} catch (PDOException $e) {
    echo "[WARN] Failed to cleanup chat_messages: " . $e->getMessage() . "\n";
}

// Cleanup strategy: Delete old pending bookings (guest bookings older than threshold)
try {
    $stmt = $db->prepare("
        DELETE FROM bookings 
        WHERE status = 'pending' 
        AND created_at < FROM_UNIXTIME(:timestamp)
    ");
    $stmt->execute([':timestamp' => $expiration_timestamp]);
    $deleted_bookings = $stmt->rowCount();
    $deleted_count += $deleted_bookings;
    
    echo "[OK] Deleted {$deleted_bookings} abandoned pending bookings\n";
} catch (PDOException $e) {
    echo "[WARN] Failed to cleanup bookings: " . $e->getMessage() . "\n";
}

// Cleanup strategy: Delete old guest reviews
try {
    $stmt = $db->prepare("
        DELETE FROM reviews 
        WHERE user_id IS NULL OR user_id = 0
        AND created_at < FROM_UNIXTIME(:timestamp)
    ");
    $stmt->execute([':timestamp' => $expiration_timestamp]);
    $deleted_reviews = $stmt->rowCount();
    $deleted_count += $deleted_reviews;
    
    echo "[OK] Deleted {$deleted_reviews} old guest reviews\n";
} catch (PDOException $e) {
    echo "[INFO] No old guest reviews to cleanup\n";
}

// Summary
echo "\n=== Cleanup Summary ===\n";
echo "Maximum session age: {$expiration_days} days\n";
echo "Timestamp threshold: {$expiration_timestamp}\n";
echo "Total records deleted: {$deleted_count}\n";
echo "\nCleanup completed successfully at " . date('Y-m-d H:i:s') . "\n";

exit(0);
