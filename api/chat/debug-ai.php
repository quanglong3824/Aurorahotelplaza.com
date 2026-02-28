<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/ai-helper.php';

// File này dùng để test lỗi AI gọi từ Google về (Mở trên trình duyệt)
$db = getDB();

echo "<h2>DEBUG THÔNG TIN GOOGLE GEMINI API</h2>";
echo "<pre>";

$api_key = '';
$key_file = __DIR__ . '/../../config/api_keys.php';
echo "🔍 1. Kiểm tra File Config:\n";
if (file_exists($key_file)) {
    echo "- File: ĐÃ TÌM THẤY ($key_file)\n";
    require_once $key_file;
    if (defined('GEMINI_API_KEY')) {
        $api_key = GEMINI_API_KEY;
        echo "- Khóa API (Ẩn 1 phần): " . substr($api_key, 0, 10) . "...........\n";
    } else {
        echo "- LỖI: File có tồn tại nhưng chưa define('GEMINI_API_KEY', ...)\n";
    }
} else {
    echo "- LỖI: Không tìm thấy file $key_file trên thư mục Host!\n";
}

echo "\n🔍 2. Bắt đầu Test gọi Lên Server AI...\n";
$user_message = "Xin chào";
echo "- Câu hỏi Test: '$user_message'\n";

// Mình tự gọi nội tuyến để in thẳng kết quả Raw JSON Request ra màn hình dễ debug
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;
$data = [
    "contents" => [
        ["role" => "user", "parts" => [["text" => "Xin chào"]]]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "\n❌ LỖI CURL: " . curl_error($ch) . "\n";
} else {
    echo "\n✅ KẾT QUẢ TỪ GOOGLE TRẢ VỀ:\n\n";
    $result = json_decode($response, true);
    print_r($result);
}

echo "</pre>";
?>