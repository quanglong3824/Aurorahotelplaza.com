<?php
/**
 * AI Feature Test Script - Gemini Only
 * Kiểm tra tính năng AI chat của Aurora Hotel Plaza
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AI FEATURE TEST - AURORA HOTEL PLAZA (Gemini)</h1>";
echo "<hr>";

$testsPassed = 0;
$testsFailed = 0;

function testResult($name, $passed, $message = '') {
    global $testsPassed, $testsFailed;
    if ($passed) {
        $testsPassed++;
        echo "✓ <strong>PASS:</strong> $name" . ($message ? " - <em>$message</em>" : "") . "<br>";
    } else {
        $testsFailed++;
        echo "✗ <strong>FAIL:</strong> $name" . ($message ? " - <em>$message</em>" : "") . "<br>";
    }
}

// 1. Test load_env.php
echo "<h2>1. Test Environment Loading</h2>";
require_once __DIR__ . '/config/load_env.php';
testResult("load_env.php loaded", true);

// 2. Test Gemini Keys
echo "<h2>2. Test Gemini API Keys</h2>";

$envKey = env('GEMINI_API_KEY');
testResult("GEMINI_API_KEY from env", !empty($envKey), "Value: " . (empty($envKey) ? "EMPTY" : substr($envKey, 0, 10) . "..."));

$envKeys = env('GEMINI_API_KEYS');
testResult("GEMINI_API_KEYS from env", true, "Value: " . (empty($envKeys) ? "NOT SET" : "CONFIGURED"));

$aiModel = env('AI_MODEL', 'gemini-2.0-flash');
testResult("AI_MODEL", true, "Model: $aiModel");

// 3. Test api_key_manager.php
echo "<h2>3. Test API Key Manager</h2>";
require_once __DIR__ . '/helpers/api_key_manager.php';

$allKeys = get_all_valid_keys();
testResult("get_all_valid_keys()", count($allKeys) > 0, "Found " . count($allKeys) . " valid key(s)");

$activeKey = get_active_gemini_key();
testResult("get_active_gemini_key()", !empty($activeKey), "Key: " . (empty($activeKey) ? "EMPTY" : substr($activeKey, 0, 10) . "..."));

$keyLimits = get_key_rate_limits();
testResult("get_key_rate_limits()", true, "Limits: " . json_encode($keyLimits));

// 4. Test ai-helper.php
echo "<h2>4. Test AI Helper</h2>";
require_once __DIR__ . '/helpers/ai-helper.php';

testResult("ai-helper.php loaded", true);
testResult("GEMINI_API_BASE defined", defined('GEMINI_API_BASE'));
testResult("get_aurora_system_prompt exists", function_exists('get_aurora_system_prompt'));
testResult("stream_gemini_reply exists", function_exists('stream_gemini_reply'));
testResult("call_gemini_sync exists", function_exists('call_gemini_sync'));

// 5. Test cURL
echo "<h2>5. Test cURL Extension</h2>";
testResult("cURL extension", function_exists('curl_init'), "Required for Gemini REST API");

// 6. Test database connection
echo "<h2>6. Test Database Connection</h2>";
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    if ($db) {
        testResult("Database connection", true);

        $stmt = $db->query("SHOW TABLES LIKE 'chat_conversations'");
        $tableExists = $stmt->rowCount() > 0;
        testResult("chat_conversations table", $tableExists, $tableExists ? "EXISTS" : "NOT FOUND");

        $stmt = $db->query("SHOW TABLES LIKE 'chat_messages'");
        $tableExists = $stmt->rowCount() > 0;
        testResult("chat_messages table", $tableExists, $tableExists ? "EXISTS" : "NOT FOUND");
    } else {
        testResult("Database connection", false, "getDB() returned null");
    }
} catch (Exception $e) {
    testResult("Database connection", false, $e->getMessage());
}

// 7. Test actual Gemini API call
echo "<h2>7. Test Gemini Sync Call</h2>";
if (!empty($activeKey)) {
    try {
        $test_msg = "Hello, this is a test. Reply 'OK' only.";
        $reply = call_gemini_sync($test_msg, $db ?? null, null, "Reply 'OK' only.");
        testResult("Gemini Sync Call", !empty($reply) && strpos($reply, 'Lỗi') === false, "Reply: " . substr($reply, 0, 100));
    } catch (Exception $e) {
        testResult("Gemini Sync Call", false, $e->getMessage());
    }
} else {
    testResult("Gemini Sync Call", false, "Skipping - No API key configured");
}

// 8. Check file permissions
echo "<h2>8. Test File Permissions</h2>";
$configPath = defined('AI_CONFIG_PATH') ? AI_CONFIG_PATH : sys_get_temp_dir();
testResult("Config directory writable", is_writable($configPath), "Path: $configPath");

// Summary
echo "<hr>";
echo "<h2>SUMMARY</h2>";
echo "<p><strong>Provider:</strong> Google Gemini (100%)</p>";
echo "<p><strong>Model:</strong> $aiModel</p>";
echo "<p><strong>Passed:</strong> $testsPassed</p>";
echo "<p><strong>Failed:</strong> $testsFailed</p>";

if ($testsFailed > 0) {
    echo "<p style='color:red'><strong>⚠ SOME TESTS FAILED</strong></p>";
    echo "<h3>Configuration needed in config/.env:</h3>";
    echo "<pre>";
    echo "# Google Gemini API Key\n";
    echo "GEMINI_API_KEY=AIza-your-key-here\n\n";
    echo "# Model (optional)\n";
    echo "AI_MODEL=gemini-2.0-flash\n";
    echo "</pre>";
} else {
    echo "<p style='color:green'><strong>✓ ALL TESTS PASSED!</strong></p>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h1 { color: #4285f4; }
h2 { color: #333; border-bottom: 2px solid #4285f4; padding-bottom: 5px; margin-top: 30px; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>