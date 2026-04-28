<?php
/**
 * Tệp tin quản lý nạp API KEY bí mật từ biến môi trường.
 * KHÔNG ĐƯỢC ĐIỀN TRỰC TIẾP KEY VÀO ĐÂY ĐỂ ĐẢM BẢO BẢO MẬT.
 * Tất cả Key phải được cấu hình trong file .env hoặc env nằm ngoài Document Root.
 * 
 * Hỗ trợ nhiều AI Provider:
 * - gemini (Google Gemini)
 * - alibaba (Alibaba GLM-5)
 */

require_once __DIR__ . '/load_env.php';

/**
 * 1. QUẢN LÝ GOOGLE GEMINI KEYS
 * Hỗ trợ nạp từ GEMINI_API_KEYS (danh sách cách nhau bởi dấu phẩy) hoặc GEMINI_API_KEY (đơn lẻ)
 */
$env_keys_str = env('GEMINI_API_KEYS', '');
if (empty($env_keys_str)) {
    $env_keys_str = env('GEMINI_API_KEY', '');
}

$GEMINI_API_KEYS = [];
if (!empty($env_keys_str)) {
    $GEMINI_API_KEYS = array_values(array_filter(array_map('trim', explode(',', $env_keys_str))));
}

/**
 * 2. QUẢN LÝ ALIBABA GLM KEYS
 * Hỗ trợ nạp từ ALIBABA_API_KEYS (danh sách cách nhau bởi dấu phẩy) hoặc ALIBABA_API_KEY (đơn lẻ)
 */
$alibaba_keys_str = env('ALIBABA_API_KEYS', '');
if (empty($alibaba_keys_str)) {
    $alibaba_keys_str = env('ALIBABA_API_KEY', '');
}

$ALIBABA_API_KEYS = [];
if (!empty($alibaba_keys_str)) {
    $ALIBABA_API_KEYS = array_values(array_filter(array_map('trim', explode(',', $alibaba_keys_str))));
}

/**
 * 3. ĐỊNH NGHĨA HẰNG SỐ (TƯƠNG THÍCH NGƯỢC)
 */
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', !empty($GEMINI_API_KEYS[0]) ? $GEMINI_API_KEYS[0] : '');
}

if (!defined('ALIBABA_API_KEY')) {
    define('ALIBABA_API_KEY', !empty($ALIBABA_API_KEYS[0]) ? $ALIBABA_API_KEYS[0] : '');
}

/**
 * 4. AI PROVIDER (Mặc định là alibaba, có thể đổi thành gemini)
 * Giá trị: 'alibaba' hoặc 'gemini'
 */
if (!defined('AI_PROVIDER')) {
    define('AI_PROVIDER', env('AI_PROVIDER', 'alibaba'));
}

/**
 * 5. ALIBABA API CONFIG
 * DashScope API (China)
 * URL: https://dashscope.aliyuncs.com/api/v1
 */
if (!defined('ALIBABA_API_URL')) {
    define('ALIBABA_API_URL', env('ALIBABA_API_URL', 'https://dashscope.aliyuncs.com/api/v1'));
}

if (!defined('ALIBABA_MODEL')) {
    define('ALIBABA_MODEL', env('ALIBABA_MODEL', 'qwen-plus'));
}
?>
