<?php
require_once 'config/database.php';
$db = getDB();

echo "--- Recent AI Logs (Last 10) ---\n";
$stmt = $db->query("SELECT * FROM ai_logs ORDER BY created_at DESC LIMIT 10");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($logs as $log) {
    echo "[" . $log['created_at'] . "] " . $log['ai_type'] . " | " . $log['status'] . " | Time: " . $log['processing_time'] . "s\n";
    if ($log['error_message']) {
        echo "Error: " . $log['error_message'] . "\n";
    }
    echo "Prompt: " . substr($log['prompt'], 0, 100) . "...\n";
    echo "Response: " . substr($log['response'], 0, 100) . "...\n";
    echo "-----------------------------------\n";
}

echo "\n--- Error Statistics ---\n";
$stmt = $db->query("SELECT status, COUNT(*) as count FROM ai_logs GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['status'] . ": " . $row['count'] . "\n";
}
?>
