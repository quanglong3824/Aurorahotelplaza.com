<?php
// Tệp tin load danh sách API KEY bí mật từ môi trường hệ thống bên ngoài.
require_once __DIR__ . '/load_env.php';

/**
 * 1. QUẢN LÝ GOOGLE GEMINI KEYS
 */
$env_keys_str = env('GEMINI_API_KEYS', '');
if (empty($env_keys_str)) {
    // Thử lấy từ biến GEMINI_API_KEY đơn lẻ
    $env_keys_str = env('GEMINI_API_KEY', '');
}

if (!empty($env_keys_str)) {
    $GEMINI_API_KEYS = array_values(array_filter(array_map('trim', explode(',', $env_keys_str))));
} else {
    // ĐIỀN TRỰC TIẾP VÀO ĐÂY NẾU .ENV KHÔNG HOẠT ĐỘNG
    $GEMINI_API_KEYS = [
        "AIzaSyALXYEI3AqcLcZxsw-JojFx-1C-TwrfpD0", 
    ];
}

// Giữ lại tương thích ngược hằng số
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', !empty($GEMINI_API_KEYS[0]) ? $GEMINI_API_KEYS[0] : '');
}

/**
 * 2. QUẢN LÝ ALIBABA QWEN KEYS
 */
$q_key = env('QWEN_API_KEY', '');
if (!defined('QWEN_API_KEY')) {
    // ĐIỀN TRỰC TIẾP KEY VÀO ĐÂY NẾU .ENV KHÔNG HOẠT ĐỘNG
    define('QWEN_API_KEY', !empty($q_key) ? $q_key : 'sk-e9954f97d62944ebac204dbc21eca25c');
}

if (!defined('QWEN_MODEL')) {
    define('QWEN_MODEL', env('QWEN_MODEL', 'qwen-max'));
}

/**
 * 3. AI PROVIDER (gemini | qwen)
 */
if (!defined('AI_PROVIDER')) {
    define('AI_PROVIDER', env('AI_PROVIDER', 'qwen'));
}
?>
