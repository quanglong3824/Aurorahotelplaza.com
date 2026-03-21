<?php
// Tệp tin load danh sách API KEY bí mật từ môi trường hệ thống bên ngoài.
require_once __DIR__ . '/load_env.php';

/**
 * QUẢN LÝ GOOGLE GEMINI KEYS
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
        "AIzaSyCDRM_0X_zba9EQM-miVCRKJdL-mpwzYUk",
        "AIzaSyCVy5C1fkptsCuLVHITActvrekRw6O4Mg4"
    ];
}

// Giữ lại tương thích ngược hằng số
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', !empty($GEMINI_API_KEYS[0]) ? $GEMINI_API_KEYS[0] : '');
}

/**
 * AI PROVIDER (Cố định là gemini)
 */
if (!defined('AI_PROVIDER')) {
    define('AI_PROVIDER', 'gemini');
}
?>
