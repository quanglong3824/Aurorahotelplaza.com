<?php
// Tệp tin load danh sách API KEY bí mật của Google Gemini từ môi trường hệ thống bên ngoài.
// Các core keys này đã được đem ra khỏi thư mục public_html để chống bị tool scan nhận diện.

require_once __DIR__ . '/load_env.php';

// Nếu file .env có chứa cấu hình nhiều key, nằm tách nhau bởi dấu phẩy
// Ví dụ: GEMINI_API_KEYS="key1,key2,key3"
$env_keys = env('GEMINI_API_KEYS', '');

if (!empty($env_keys)) {
    // Tách bằng dấu phẩy và làm sạch khoảng trắng để array được nạp đúng
    $GEMINI_API_KEYS = array_map('trim', explode(',', $env_keys));
} else {
    // Fallback: Mảng trống dự phòng để logic bot chat không báo fatal error nếu chưa kịp cấu hình
    $GEMINI_API_KEYS = [
        "", // Quota Key 1
        "", // Quota Key 2
        "", // Quota Key 3
    ];
}

// Giữ lại tương thích ngược, load biến trực tiếp
define('GEMINI_API_KEY', env('GEMINI_API_KEY', 'ĐIỀN_API_KEY_MỚI_VÀO_ĐÂY'));
?>