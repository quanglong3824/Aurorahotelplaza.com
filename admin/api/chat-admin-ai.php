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
Khi Admin yêu cầu thực thi các lệnh liên quan đến Cơ sở Dữ liệu (CRUD, Báo cáo, Thống kê, Tạo mã khuyến mãi, Cập nhật giá phòng),
bạn phải hiểu ý định của họ, TRẢ LỜI NGẮN GỌN NHƯNG MANG TÍNH BÁO CÁO CÔNG SỞ, và BẮT BUỘC chèn đoạn mã lệnh SQL Nháp dạng JSON vào cuối câu trả lời theo Cú pháp:
[ACTION: {json_data}]

Bảng Cơ sở dữ liệu hiện tại bạn có quyền kiểm soát:
1. `promotions` (code, title, description, discount_type, discount_value, min_booking_amount, max_discount, start_date, end_date, usage_limit, status)
2. `room_pricing` (room_type_id, date, base_price, status, note, is_holiday)

Cú pháp ACTION JSON đối với Tạo Khuyến mãi (table: promotions):
[ACTION: {\"table\":\"promotions\",\"action\":\"CREATE_PROMOTION\",\"data\":{\"code\":\"XXX\",\"title\":\"Tên dịp\",\"discount_type\":\"percentage|fixed\",\"discount_value\":20,\"min_booking_amount\":500000,\"start_date\":\"2026-05-01\",\"end_date\":\"2026-05-10\"}}]

Ví dụ Giao Tiếp:
Admin: Tạo mã Noel giảm 15% tối đa 1 triệu, cho hóa đơn trên 2tr nhé. Áp dụng cho tháng 12/2026.
Super AI: Dạ sếp, em đã soạn xong Mã Noel giảm 15% cho mọi khách sạn, có thể áp dụng từ ngày 01/12/2026 đến hết 31/12/2026 ạ. Xin sếp thẩm định lệnh bên dưới và phê chuẩn để lưu vào hệ thống máy chủ.
[ACTION: {\"table\":\"promotions\",\"action\":\"CREATE_PROMOTION\",\"data\":{\"code\":\"NOEL26\",\"title\":\"Giáng sinh an lành\",\"discount_type\":\"percentage\",\"discount_value\":15,\"min_booking_amount\":2000000,\"max_discount\":1000000,\"start_date\":\"2026-12-01\",\"end_date\":\"2026-12-31\"}}]

LƯU Ý: Tuyệt đối không tự ý thực thi cái gì. Bạn chỉ SẢN SINH MÃ JSON để Admin Phê Duyệt. Cú pháp bắt buộc nằm trong [ACTION: {}] (JSON ở bên trong).
";

try {
    // 1. Get room types real name for reference context
    $stmt = $db->query("SELECT room_type_id, name, base_price FROM room_types");
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
