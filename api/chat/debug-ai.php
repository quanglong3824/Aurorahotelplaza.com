<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/ai-helper.php';

// File này dùng để test lỗi AI gọi từ Google về (Mở trên trình duyệt)
$db = getDB();

echo "<h2>DEBUG THÔNG TIN GOOGLE GEMINI API</h2>";
echo "<pre>";

require_once __DIR__ . '/../../helpers/api_key_manager.php';

echo "🔍 1. Kiểm tra Hệ thống Quản trị API Key:\n";
$api_key = get_active_gemini_key();
if (!empty($api_key)) {
    echo "- ĐANG SỬ DỤNG KEY (Ẩn 1 phần): " . substr($api_key, 0, 10) . "...........\n";
    $total_keys = count(get_all_valid_keys());
    echo "- Tổng số Key hợp lệ đang Load được: $total_keys\n";
} else {
    echo "- LỖI: Cấu hình chưa hợp lệ hoặc Trống API Keys!\n";
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
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($http_code === 429) {
    echo "\n⚠️ LỖI QUOTA EXCEEDED (HTTP 429)! Đang thử Rotate sang Key Dự Phòng...\n";
    $new_key = rotate_gemini_key();
    if ($new_key && $new_key !== $api_key) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $new_key;
        curl_setopt($ch, CURLOPT_URL, $url);
        echo "- THỬ LẠI VỚI KEY: " . substr($new_key, 0, 10) . "...........\n";
        $response = curl_exec($ch);
    } else {
        echo "- KHÔNG CÓ KEY NÀO DỰ PHÒNG HOẶC ĐỀU HẾT QUOTA!\n";
    }
}

if (curl_errno($ch)) {
    echo "\n❌ LỖI CURL: " . curl_error($ch) . "\n";
} else {
    echo "\n✅ KẾT QUẢ TỪ GOOGLE TRẢ VỀ:\n\n";
    $result = json_decode($response, true);
    print_r($result);
}

echo "</pre>";
?>