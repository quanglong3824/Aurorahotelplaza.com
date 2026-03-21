<?php
/**
 * Tệp tin quản lý nạp API KEY bí mật từ biến môi trường.
 * KHÔNG ĐƯỢC ĐIỀN TRỰC TIẾP KEY VÀO ĐÂY ĐỂ ĐẢM BẢO BẢO MẬT.
 * Tất cả Key phải được cấu hình trong file .env hoặc env nằm ngoài Document Root.
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
 * 2. ĐỊNH NGHĨA HẰNG SỐ (TƯƠNG THÍCH NGƯỢC)
 */
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', !empty($GEMINI_API_KEYS[0]) ? $GEMINI_API_KEYS[0] : '');
}

/**
 * 3. AI PROVIDER (Cố định là gemini)
 */
if (!defined('AI_PROVIDER')) {
    define('AI_PROVIDER', env('AI_PROVIDER', 'gemini'));
}
?>
