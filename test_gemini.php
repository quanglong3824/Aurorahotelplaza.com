<?php
/**
 * File test Gemini API
 * Truy cập: https://your-domain.com/test_gemini.php
 * 
 * Lưu ý: Xóa file này sau khi test xong để bảo mật!
 */

// Hiển thị lỗi để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>TEST GEMINI API</h1>";
echo "<hr>";

// Step 1: Check vendor/autoload.php
echo "<h2>1. Kiểm tra Vendor Autoload</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p style='color:green'>✓ File vendor/autoload.php tồn tại</p>";
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<p style='color:green'>✓ Đã load autoload thành công</p>";
} else {
    echo "<p style='color:red'><b>✗ LỖI: File vendor/autoload.php KHÔNG tồn tại!</b></p>";
    echo "<p>Bạn cần chạy <code>composer install</code> để cài đặt dependencies.</p>";
    echo "<p>Xem file INSTALL_COMPOSER.md để biết cách cài đặt.</p>";
    exit;
}

// Step 2: Check load_env.php
echo "<h2>2. Kiểm tra Load Env</h2>";
if (file_exists(__DIR__ . '/config/load_env.php')) {
    echo "<p style='color:green'>✓ File config/load_env.php tồn tại</p>";
    require_once __DIR__ . '/config/load_env.php';
    echo "<p style='color:green'>✓ Đã load env thành công</p>";
} else {
    echo "<p style='color:orange'>⚠ File config/load_env.php không tồn tại (không bắt buộc)</p>";
}

// Step 3: Check API Key
echo "<h2>3. Kiểm tra API Key</h2>";
$apiKey = null;

// Try multiple sources
if (function_exists('env') && env('GEMINI_API_KEY')) {
    $apiKey = env('GEMINI_API_KEY');
    echo "<p style='color:green'>✓ Lấy API Key từ env() thành công</p>";
} elseif (getenv('GEMINI_API_KEY')) {
    $apiKey = getenv('GEMINI_API_KEY');
    echo "<p style='color:green'>✓ Lấy API Key từ getenv() thành công</p>";
} elseif (isset($_ENV['GEMINI_API_KEY'])) {
    $apiKey = $_ENV['GEMINI_API_KEY'];
    echo "<p style='color:green'>✓ Lấy API Key từ \$_ENV thành công</p>";
} else {
    echo "<p style='color:red'><b>✗ LỖI: KHÔNG TÌM THẤY API KEY!</b></p>";
    echo "<p>Tạo file <code>config/.env</code> với nội dung:</p>";
    echo "<pre style='background:#f0f0f0;padding:10px;'>GEMINI_API_KEY=AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX</pre>";
    echo "<p>Lấy API Key tại: <a href='https://aistudio.google.com/app/apikey' target='_blank'>https://aistudio.google.com/app/apikey</a></p>";
    exit;
}

echo "<p style='color:green'>✓ API Key: <code>" . substr($apiKey, 0, 10) . '...</code></p>';

// Step 4: Test Gemini API
echo "<h2>4. Test Gemini API Call</h2>";
echo "<p>Đang gọi API...</p>";

try {
    $client = Gemini::client($apiKey);
    $response = $client->generativeModel('gemini-2.0-flash')->generateContent('Xin chào, hãy giới thiệu ngắn về bản thân bạn bằng tiếng Việt.');
    $reply = $response->text();

    echo "<p style='color:green'><b>✓ THÀNH CÔNG!</b></p>";
    echo "<p><b>AI trả lời:</b></p>";
    echo "<div style='background:#e8f5e9;padding:15px;border-radius:5px;border:1px solid #4caf50;'>";
    echo "<p style='margin:0'>" . nl2br(htmlspecialchars($reply)) . "</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<p style='color:red'><b>✗ LỖI API CALL!</b></p>";
    echo "<p><b>Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><b>File:</b> " . $e->getFile() . ":" . $e->getLine() . "</p>";

    $errorMsg = $e->getMessage();
    if (strpos($errorMsg, '429') !== false) {
        echo "<p style='color:orange'><b>Ghi chú:</b> API Key bị giới hạn quota (429). Dùng nhiều key hơn hoặc chờ reset.</p>";
    } elseif (strpos($errorMsg, '403') !== false || strpos($errorMsg, '401') !== false) {
        echo "<p style='color:orange'><b>Ghi chú:</b> API Key không hợp lệ hoặc bị từ chối. Kiểm tra lại key.</p>";
    }
}

echo "<hr>";
echo "<h2>5. Kiểm tra các hàm AI Helper</h2>";
if (file_exists(__DIR__ . '/helpers/ai-helper.php')) {
    require_once __DIR__ . '/helpers/ai-helper.php';

    $functions = [
        'get_aurora_system_prompt',
        'stream_gemini_reply',
        'process_tool_call_after_stream',
        'call_ai_sync'
    ];

    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "<p style='color:green'>✓ Hàm <code>$func</code> tồn tại</p>";
        } else {
            echo "<p style='color:red'>✗ Hàm <code>$func</code> KHÔNG tồn tại</p>";
        }
    }
} else {
    echo "<p style='color:red'>✗ File helpers/ai-helper.php không tồn tại</p>";
}

echo "<hr>";
echo "<p style='color:blue'><i>Lưu ý: Xóa file test_gemini.php sau khi test xong để bảo mật!</i></p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background: #f5f5f5;
    }

    h1 {
        color: #1a73e8;
    }

    h2 {
        color: #333;
        border-bottom: 2px solid #1a73e8;
        padding-bottom: 5px;
    }

    pre {
        background: #263238;
        color: #aed581;
        padding: 10px;
        border-radius: 5px;
        overflow-x: auto;
    }

    code {
        background: #f0f0f0;
        padding: 2px 5px;
        border-radius: 3px;
    }
</style>