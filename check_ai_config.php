<?php
/**
 * Chẩn đoán cấu hình AI - Aurora Hotel Plaza
 */
require_once 'config/load_env.php';
require_once 'helpers/api_key_manager.php';

echo "<h1>Aurora AI Diagnostic</h1>";
echo "<pre>";

echo "1. Kiểm tra hằng số AI_CONFIG_PATH:\n";
echo "   PATH: " . (defined('AI_CONFIG_PATH') ? AI_CONFIG_PATH : "CHƯA ĐỊNH NGHĨA") . "\n";
echo "   Tồn tại? " . (is_dir(AI_CONFIG_PATH) ? "CÓ" : "KHÔNG") . "\n\n";

echo "2. Kiểm tra biến môi trường (.env):\n";
echo "   AI_PROVIDER: " . env('AI_PROVIDER') . "\n";
echo "   AI_MODEL: " . env('AI_MODEL') . "\n";
echo "   QWEN_MODEL: " . env('QWEN_MODEL') . "\n";
echo "   QWEN_API_KEY: " . (env('QWEN_API_KEY') ? "ĐÃ CÓ (Dài " . strlen(env('QWEN_API_KEY')) . " ký tự)" : "TRỐNG") . "\n";
echo "   GEMINI_API_KEY: " . (env('GEMINI_API_KEY') ? "ĐÃ CÓ" : "TRỐNG") . "\n\n";

echo "3. Kiểm tra Logic Provider:\n";
$provider = get_active_ai_provider();
echo "   Provider hiện tại: " . $provider . "\n";

if ($provider === 'qwen') {
    $key = get_active_qwen_key();
    $model = get_active_qwen_model();
    echo "   Model Qwen: " . $model . "\n";
    echo "   Key Qwen: " . ($key ? "SẴN SÀNG" : "LỖI: KHÔNG LẤY ĐƯỢC KEY") . "\n";
} else {
    $key = get_active_gemini_key();
    echo "   Key Gemini: " . ($key ? "SẴN SÀNG" : "LỖI: KHÔNG LẤY ĐƯỢC KEY") . "\n";
}

echo "\n4. Kiểm tra quyền ghi thư mục config:\n";
$test_file = AI_CONFIG_PATH . '/test_write.txt';
if (@file_put_contents($test_file, "test")) {
    echo "   Quyền ghi: OK\n";
    @unlink($test_file);
} else {
    echo "   Quyền ghi: THẤT BẠI (AI có thể không xoay vòng key được)\n";
}

echo "</pre>";
