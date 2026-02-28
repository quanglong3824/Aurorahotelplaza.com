<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo JSON_encode(['success' => false, 'message' => 'Lỗi Quyền Hạn: Bạn không phải Giám Đốc/Admin. Cút ra ngoài!']);
    exit;
}

require_once '../../config/database.php';
$api_key = '';
$key_file = __DIR__ . '/../../config/api_keys.php';
if (file_exists($key_file)) {
    require_once $key_file;
    if (defined('GEMINI_API_KEY')) {
        $api_key = GEMINI_API_KEY;
    }
} else {
    $api_key = getenv('GEMINI_API_KEY');
}

if (empty($api_key)) {
    echo json_encode(['success' => false, 'message' => 'Lỗi API Key: Chưa cấu hình GEMINI_API_KEY']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_message = $input['message'] ?? '';

if (empty($user_message)) {
    echo json_encode(['success' => false, 'message' => 'Nội dung rỗng.']);
    exit;
}

// Prepare System Prompt for Admin AI
$system_prompt = "
Bạn là Aurora AI Super Admin - Trợ lý Tối cao của hệ thống quản lý Khách Sạn Aurora Hotel Plaza. Bạn phục vụ trực tiếp Giám đốc/Admin.
Khi Admin yêu cầu thực thi các lệnh liên quan đến Cơ sở Dữ liệu (Tạo mã khuyến mãi, Cập nhật giá phòng),
bạn phải hiểu ý định, TRẢ LỜI NGẮN GỌN BÁO CÁO CÔNG SỞ, và BẮT BUỘC chèn đoạn mã lệnh SQL Nháp dạng JSON vào cuối câu theo Cú pháp:
[ACTION: {json_data}]

NẾU CẦN THỰC THI NHIỀU LỆNH CÙNG LÚC (ví dụ: cập nhật giá nhiều loại phòng, hoặc vừa tạo giá vừa tạo voucher), HÃY TẠO NHIỀU BLOCK [ACTION: {...}] RIÊNG BIỆT!

Bảng Cơ sở dữ liệu:
1. `promotions` (code, title, discount_type, discount_value, min_booking_amount, start_date, end_date)
2. `room_pricing` (room_type_id, start_date, end_date, price, description)

Cú pháp ACTION JSON mẫu đối với Tạo Khuyến mãi:
[ACTION: {\"table\":\"promotions\",\"action\":\"CREATE_PROMOTION\",\"data\":{\"code\":\"LE3004\",\"title\":\"Mừng lễ 30/4\",\"discount_type\":\"fixed\",\"discount_value\":300000,\"min_booking_amount\":2000000,\"start_date\":\"2026-04-30\",\"end_date\":\"2026-05-02\"}}]

Cú pháp ACTION JSON mẫu đối với Cập nhật Giá phòng (Tạo từng ACTION cho từng room_type_id nếu cần Cập nhật nhiều phòng):
[ACTION: {\"table\":\"room_pricing\",\"action\":\"UPDATE_ROOM_PRICE\",\"data\":{\"room_type_id\":1,\"price\":3000000,\"start_date\":\"2026-04-30\",\"end_date\":\"2026-05-05\",\"description\":\"Tăng giá dịp lễ 30/4\"}}]
[ACTION: {\"table\":\"room_pricing\",\"action\":\"UPDATE_ROOM_PRICE\",\"data\":{\"room_type_id\":2,\"price\":3500000,\"start_date\":\"2026-04-30\",\"end_date\":\"2026-05-05\",\"description\":\"Tăng giá dịp lễ 30/4\"}}]

LƯU Ý: Tuyệt đối không tự ý thực thi. Bạn chỉ SẢN SINH CÁC KHỐI JSON để Admin Phê Duyệt.
";

try {
    // 1. Get room types real name for reference context
    $stmt = $db->query("SELECT room_type_id, type_name as name, base_price FROM room_types");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $room_context = "Các loại phòng đang có (Sử dụng ID này nếu tạo room_pricing): \n";
    foreach ($room_types as $rt) {
        $room_context .= "ID: {$rt['room_type_id']} | Tên: {$rt['name']} | Giá niêm yết: {$rt['base_price']}\n";
    }

    $system_prompt .= "\n\nHệ thống phòng hiện hữu: \n" . $room_context;

    // Execute Call
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    $reqData = [
        "contents" => [
            ["role" => "user", "parts" => [["text" => $system_prompt . "\n\nSếp chat: " . $user_message]]]
        ],
        "generationConfig" => [
            "temperature" => 0.2, // Low temp for accurate JSON
            "maxOutputTokens" => 1024,
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200) {
        throw new Exception("Lỗi gọi Gemini API (Code {$http_code}): " . $response);
    }

    $res_json = json_decode($response, true);
    if (!isset($res_json['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception("Gemini không trả về kết quả hợp lệ.");
    }

    $bot_reply = $res_json['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode(['success' => true, 'reply' => $bot_reply]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
