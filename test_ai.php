<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'helpers/ai-helper.php';

try {
    $db = getDB();
    $reply = generate_ai_reply("Cho mình đặt một phòng Deluxe 4 đêm từ 1-5/3/2026 2 người", $db, 0);
    echo "REPLY: " . $reply . "\n";
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
