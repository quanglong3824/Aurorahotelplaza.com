<?php
require_once 'config/database.php';
$db = getDB();
if (!$db) {
    echo "DB Connection Failed\n";
    exit;
}

$stmt = $db->query("SELECT prompt_text, reply_text, status, error_message, http_code, model_name FROM ai_logs WHERE ai_type = 'client' ORDER BY log_id DESC LIMIT 5");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($logs as $log) {
    echo "Model: {$log['model_name']} | Status: {$log['status']} | HTTP: {$log['http_code']}\n";
    echo "Error: " . ($log['error_message'] ?: 'None') . "\n";
    echo "Prompt: " . substr($log['prompt_text'], 0, 100) . "...\n";
    echo "Reply: " . substr($log['reply_text'], 0, 200) . "...\n";
    echo "-----------------------------------\n";
}
