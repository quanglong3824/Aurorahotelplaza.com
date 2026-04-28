<?php
/**
 * Tệp tin quản lý nạp API KEY bí mật từ biến môi trường.
 * KHÔNG ĐƯỢC ĐIỀN TRỰC TIẾP KEY VÀO ĐÂY ĐỂ ĐẢM BẢO BẢO MẬT.
 * Tất cả Key phải được cấu hình trong file .env
 * 
 * AI Provider: Google Gemini (100%)
 */

require_once __DIR__ . '/load_env.php';

/**
 * QUẢN LÝ GOOGLE GEMINI KEYS
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
 * QUẢN LÝ OPENCODE KEYS (Dự phòng)
 */
$OPENCODE_API_KEY = env('OPENCODE_API_KEY', '');
$OPENCODE_API_URL = env('OPENCODE_API_URL', 'https://opencode.ai/zen/go/v1');
$OPENCODE_MODEL = env('OPENCODE_MODEL', 'deepseek-v4-flash');

/**
 * ĐỊNH NGHĨA HẰNG SỐ
 */
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', !empty($GEMINI_API_KEYS[0]) ? $GEMINI_API_KEYS[0] : '');
}
if (!defined('OPENCODE_API_KEY')) {
    define('OPENCODE_API_KEY', $OPENCODE_API_KEY);
}
if (!defined('OPENCODE_API_URL')) {
    define('OPENCODE_API_URL', $OPENCODE_API_URL);
}
if (!defined('OPENCODE_MODEL')) {
    define('OPENCODE_MODEL', $OPENCODE_MODEL);
}
?>
