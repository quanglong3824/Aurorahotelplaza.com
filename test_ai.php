<?php
/**
 * AI Feature Test Script
 * Kiểm tra tính năng AI chat của Aurora Hotel Plaza
 * Hỗ trợ: Alibaba GLM-5, Google Gemini
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

// 2. Test AI Provider
$ai_provider = env('AI_PROVIDER', 'alibaba');
testResult("AI_PROVIDER", true, "Provider: $ai_provider");

// 3. Test API Keys
echo "<h2>2. Test API Keys</h2>";

if ($ai_provider === 'alibaba') {
    $envKey = env('ALIBABA_API_KEY');
    testResult("ALIBABA_API_KEY from env", !empty($envKey), "Value: " . (empty($envKey) ? "EMPTY" : substr($envKey, 0, 15) . "..."));
    
    $envKeys = env('ALIBABA_API_KEYS');
    testResult("ALIBABA_API_KEYS from env", true, "Value: " . (empty($envKeys) ? "NOT SET" : "CONFIGURED"));
} else {
    $envKey = env('GEMINI_API_KEY');
    testResult("GEMINI_API_KEY from env", !empty($envKey), "Value: " . (empty($envKey) ? "EMPTY" : substr($envKey, 0, 10) . "..."));
    
    $envKeys = env('GEMINI_API_KEYS');
    testResult("GEMINI_API_KEYS from env", true, "Value: " . (empty($envKeys) ? "NOT SET" : "CONFIGURED"));
}

// 4. Test AI_CONFIG_PATH
testResult("AI_CONFIG_PATH defined", defined('AI_CONFIG_PATH'), "Path: " . (defined('AI_CONFIG_PATH') ? AI_CONFIG_PATH : sys_get_temp_dir()));

// 5. Test api_key_manager.php
echo "<h2>3. Test API Key Manager</h2>";
require_once __DIR__ . '/helpers/api_key_manager.php';

testResult("get_active_ai_provider()", true, "Provider: " . get_active_ai_provider());

if ($ai_provider === 'alibaba') {
    $allKeys = get_all_valid_alibaba_keys();
    testResult("get_all_valid_alibaba_keys()", count($allKeys) > 0, "Found " . count($allKeys) . " valid key(s)");
    
    $activeKey = get_active_alibaba_key();
    testResult("get_active_alibaba_key()", !empty($activeKey), "Key: " . (empty($activeKey) ? "EMPTY" : substr($activeKey, 0, 15) . "..."));
} else {
    $allKeys = get_all_valid_keys();
    testResult("get_all_valid_keys()", count($allKeys) > 0, "Found " . count($allKeys) . " valid key(s)");
    
    $activeKey = get_active_gemini_key();
    testResult("get_active_gemini_key()", !empty($activeKey), "Key: " . (empty($activeKey) ? "EMPTY" : substr($activeKey, 0, 10) . "..."));
}

$keyLimits = get_key_rate_limits($ai_provider);
testResult("get_key_rate_limits()", true, "Limits: " . json_encode($keyLimits));

// 6. Test ai-helper.php
echo "<h2>4. Test AI Helper</h2>";
require_once __DIR__ . '/helpers/ai-helper.php';

testResult("ai-helper.php loaded", true);
testResult("get_aurora_system_prompt exists", function_exists('get_aurora_system_prompt'));
testResult("stream_ai_reply exists", function_exists('stream_ai_reply'));
testResult("stream_alibaba_reply exists", function_exists('stream_alibaba_reply'));
testResult("stream_gemini_reply exists", function_exists('stream_gemini_reply'));
testResult("call_ai_sync exists", function_exists('call_ai_sync'));

// 7. Test Composer / Gemini Client (only for Gemini)
echo "<h2>5. Test AI Libraries</h2>";
if ($ai_provider === 'gemini') {
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
} else {
    testResult("cURL extension", function_exists('curl_init'), "Required for Alibaba API");
}

// 8. Test database connection
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

// 9. Test actual AI call
echo "<h2>7. Test AI Sync Call ($ai_provider)</h2>";
if (!empty($activeKey)) {
    try {
        $test_msg = "Hello, this is a test. Reply 'OK' only.";
        $reply = call_ai_sync($test_msg, $db ?? null, null, "Reply 'OK' only.");
        testResult("AI Sync Call ($ai_provider)", !empty($reply) && strpos($reply, 'Lỗi') === false, "Reply: " . substr($reply, 0, 100));
    } catch (Exception $e) {
        testResult("AI Sync Call ($ai_provider)", false, $e->getMessage());
    }
} else {
    testResult("AI Sync Call ($ai_provider)", false, "Skipping - No API key configured");
}

// 10. Check file permissions
echo "<h2>8. Test File Permissions</h2>";
$configPath = defined('AI_CONFIG_PATH') ? AI_CONFIG_PATH : sys_get_temp_dir();
testResult("Config directory writable", is_writable($configPath), "Path: $configPath");

// Summary
echo "<hr>";
echo "<h2>SUMMARY</h2>";
echo "<p><strong>Passed:</strong> $testsPassed</p>";
echo "<p><strong>Failed:</strong> $testsFailed</p>";

if ($testsFailed > 0) {
    echo "<p style='color:red'><strong>⚠ SOME TESTS FAILED</strong></p>";
    echo "<h3>Configuration needed in config/.env:</h3>";
    echo "<pre>";
    echo "# AI Provider (alibaba or gemini)\n";
    echo "AI_PROVIDER=alibaba\n\n";
    echo "# Alibaba GLM-5 API Key\n";
    echo "ALIBABA_API_KEY=sk-your-api-key-here\n\n";
    echo "# Or for Gemini:\n";
    echo "# AI_PROVIDER=gemini\n";
    echo "# GEMINI_API_KEY=AIza-your-key-here\n";
    echo "</pre>";
} else {
    echo "<p style='color:green'><strong>✓ ALL TESTS PASSED!</strong></p>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h1 { color: #d4af37; }
h2 { color: #333; border-bottom: 2px solid #d4af37; padding-bottom: 5px; margin-top: 30px; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>