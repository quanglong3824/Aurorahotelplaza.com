<?php
/**
 * AI Feature Test Script
 * Kiểm tra tính năng AI chat của Aurora Hotel Plaza
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AI FEATURE TEST - AURORA HOTEL PLAZA</h1>";
echo "<hr>";

$testsPassed = 0;
$testsFailed = 0;

function testResult($name, $passed, $message = '') {
    global $testsPassed, $testsFailed;
    if ($passed) {
        $testsPassed++;
        echo "✓ <strong>PASS:</strong> $name<br>";
    } else {
        $testsFailed++;
        echo "✗ <strong>FAIL:</strong> $name" . ($message ? " - <em>$message</em>" : "") . "<br>";
    }
}

// 1. Test load_env.php
echo "<h2>1. Test Environment Loading</h2>";
require_once __DIR__ . '/config/load_env.php';
testResult("load_env.php loaded", true);

// 2. Test GEMINI_API_KEY from .env
$envKey = env('GEMINI_API_KEY');
testResult("GEMINI_API_KEY from env", !empty($envKey), "Value: " . (empty($envKey) ? "EMPTY" : substr($envKey, 0, 10) . "..."));

// 3. Test GEMINI_API_KEYS (multiple keys support)
$envKeys = env('GEMINI_API_KEYS');
testResult("GEMINI_API_KEYS from env", !empty($envKeys), "Value: " . (empty($envKeys) ? "NOT SET" : "CONFIGURED"));

// 4. Test AI_CONFIG_PATH
testResult("AI_CONFIG_PATH defined", defined('AI_CONFIG_PATH'), "Path: " . (defined('AI_CONFIG_PATH') ? AI_CONFIG_PATH : "NOT DEFINED"));

// 5. Test api_key_manager.php
echo "<h2>2. Test API Key Manager</h2>";
require_once __DIR__ . '/helpers/api_key_manager.php';

$allKeys = get_all_valid_keys();
testResult("get_all_valid_keys()", count($allKeys) > 0, "Found " . count($allKeys) . " valid key(s)");

$activeKey = get_active_gemini_key();
testResult("get_active_gemini_key()", !empty($activeKey), "Key: " . (empty($activeKey) ? "EMPTY" : substr($activeKey, 0, 10) . "..."));

$keyLimits = get_key_rate_limits();
testResult("get_key_rate_limits()", true, "Limits: " . json_encode($keyLimits));

// 6. Test ai-helper.php
echo "<h2>3. Test AI Helper</h2>";
require_once __DIR__ . '/helpers/ai-helper.php';

testResult("ai-helper.php loaded", true);
testResult("get_aurora_system_prompt exists", function_exists('get_aurora_system_prompt'));
testResult("stream_gemini_reply exists", function_exists('stream_gemini_reply'));
testResult("call_ai_sync exists", function_exists('call_ai_sync'));

// 7. Test Gemini Client class exists
echo "<h2>4. Test Gemini Client Library</h2>";
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
    testResult("Composer autoload loaded", true);
    
    if (class_exists('Gemini\Client')) {
        testResult("Gemini\Client class exists", true);
    } else {
        testResult("Gemini\Client class exists", false, "Class not found - Run: composer install");
    }
} else {
    testResult("Composer autoload", false, "File not found: $composerAutoload");
}

// 8. Test database connection
echo "<h2>5. Test Database Connection</h2>";
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    if ($db) {
        testResult("Database connection", true);
        
        // Test chat_conversations table
        $stmt = $db->query("SHOW TABLES LIKE 'chat_conversations'");
        $tableExists = $stmt->rowCount() > 0;
        testResult("chat_conversations table", $tableExists, $tableExists ? "EXISTS" : "NOT FOUND");
        
        // Test chat_messages table
        $stmt = $db->query("SHOW TABLES LIKE 'chat_messages'");
        $tableExists = $stmt->rowCount() > 0;
        testResult("chat_messages table", $tableExists, $tableExists ? "EXISTS" : "NOT FOUND");
    } else {
        testResult("Database connection", false, "getDB() returned null");
    }
} catch (Exception $e) {
    testResult("Database connection", false, $e->getMessage());
}

// 9. Test actual AI call (sync)
echo "<h2>6. Test AI Sync Call</h2>";
if (!empty($activeKey) && class_exists('Gemini\Client')) {
    try {
        $client = new \Gemini\Client($activeKey);
        $model = $client->generativeModel('gemini-2.0-flash');
        $response = $model->generateContent("Hello, this is a test. Reply 'OK' only.");
        $reply = $response->text();
        testResult("AI Sync Call", true, "Reply: $reply");
    } catch (Exception $e) {
        testResult("AI Sync Call", false, $e->getMessage());
    }
} else {
    testResult("AI Sync Call", false, "Skipping - No API key or Client class");
}

// 10. Check file permissions
echo "<h2>7. Test File Permissions</h2>";
$configDir = dirname(AI_CONFIG_PATH);
testResult("Config directory writable", is_writable($configDir), "Path: $configDir");

$indexFile = AI_CONFIG_PATH . '/current_key_idx.txt';
testResult("current_key_idx.txt writable", is_writable(dirname($indexFile)), "Can create index file");

// Summary
echo "<hr>";
echo "<h2>SUMMARY</h2>";
echo "<p><strong>Passed:</strong> $testsPassed</p>";
echo "<p><strong>Failed:</strong> $testsFailed</p>";

if ($testsFailed > 0) {
    echo "<p style='color:red'><strong>⚠ SOME TESTS FAILED</strong></p>";
    echo "<h3>Các lỗi thường gặp:</h3>";
    echo "<ul>";
    echo "<li><strong>API Key trống:</strong> Kiểm tra file config/.env hoặc ../config/.env</li>";
    echo "<li><strong>Gemini\Client not found:</strong> Chạy <code>composer install</code></li>";
    echo "<li><strong>Database connection failed:</strong> Kiểm tra thông tin DB trong .env</li>";
    echo "<li><strong>Permission denied:</strong> chmod 755 cho thư mục config</li>";
    echo "</ul>";
} else {
    echo "<p style='color:green'><strong>✓ ALL TESTS PASSED!</strong></p>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h1 { color: #d4af37; }
h2 { color: #333; border-bottom: 2px solid #d4af37; padding-bottom: 5px; margin-top: 30px; }
</style>