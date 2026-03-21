<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/ai-helper.php';
require_once __DIR__ . '/../../helpers/api_key_manager.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>DEBUG THÔNG TIN AI PROVIDER (QWEN)</h2>";
echo "<pre>";

$provider = get_active_ai_provider();
$qwen_key = get_active_qwen_key();
$qwen_model = get_active_qwen_model();

echo "🔍 1. Kiểm tra Cấu hình Hệ thống:\n";
echo "- Provider hiện tại: " . ($provider ?: 'Trống') . "\n";
echo "- Qwen Model: " . ($qwen_model ?: 'Trống') . "\n";

if (!empty($qwen_key)) {
    echo "- Qwen API Key: " . substr($qwen_key, 0, 5) . "****************" . substr($qwen_key, -5) . " (Độ dài: " . strlen($qwen_key) . ")\n";
} else {
    echo "- ❌ LỖI: Qwen API Key TRỐNG hoặc chưa được load!\n";
}

echo "\n🔍 2. Kiểm tra các đường dẫn tìm kiếm .env:\n";
$current_dir = realpath(__DIR__ . '/../../config');
$paths = [];
for ($i = 0; $i < 6; $i++) {
    $paths[] = $current_dir . '/.env';
    $paths[] = $current_dir . '/env';
    $parent = dirname($current_dir);
    if ($parent === $current_dir) break;
    $current_dir = $parent;
}
foreach ($paths as $p) {
    $exists = file_exists($p) ? "✅ TỒN TẠI" : "❌ Không thấy";
    $readable = $exists && is_readable($p) ? " (Đọc được)" : "";
    echo "- Thử đường dẫn: $p -> $exists$readable\n";
}

echo "\n🔍 3. Thử gọi API Qwen (Đồng bộ):\n";
if (!empty($qwen_key)) {
    $ch = curl_init("https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions");
    $test_data = [
        "model" => $qwen_model,
        "messages" => [
            ["role" => "user", "content" => "Xin chào, bạn là ai?"]
        ]
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $qwen_key
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "- HTTP Code: $http_code\n";
    if ($curl_error) {
        echo "- ❌ Lỗi CURL: $curl_error\n";
    } else {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            echo "✅ Gọi API Thành công!\n";
            echo "- AI trả lời: " . $result['choices'][0]['message']['content'] . "\n";
        } else {
            echo "❌ API trả về lỗi hoặc định dạng không đúng:\n";
            print_r($result);
        }
    }
} else {
    echo "⚠️ Bỏ qua test API do không có Key.\n";
}

echo "</pre>";
?>
