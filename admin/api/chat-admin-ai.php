<?php
// Bật output buffering để ngăn mọi cảnh báo lẻ tẻ in ra file làm nát JSON
ob_start();
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception("Lỗi Quyền Hạn: Bạn không phải Giám Đốc/Admin.");
    }

    require_once '../../config/database.php';

    // Khởi tạo $api_key
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
        throw new Exception("Lỗi API Key: Chưa cấu hình GEMINI_API_KEY. Vui lòng thêm khóa trong config/api_keys.php");
    }

    // Nếu API Key là key mặc định -> Từ chối chạy luôn.
    if (strpos($api_key, 'ĐIỀN_API_KEY') !== false || $api_key === 'YOUR_API_KEY_HERE') {
        throw new Exception("API Key của bạn là key ảo chưa được thay thế. Vui lòng mở /config/api_keys.php để thay bằng Key thật!");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $user_message = $input['message'] ?? '';

    if (empty($user_message)) {
        throw new Exception("Nội dung rỗng.");
    }

    // Prepare System Prompt for Admin AI
    $system_prompt = "
    Bạn là Aurora AI Super Admin - Trợ lý Tối cao của hệ thống quản lý Khách Sạn Aurora Hotel Plaza. Bạn phục vụ trực tiếp Giám đốc/Admin.
    Khi Admin yêu cầu thực thi các lệnh liên quan đến Cơ sở Dữ liệu (Tạo mã khuyến mãi, Cập nhật giá phòng),
    bạn phải hiểu ý định, TRẢ LỜI NGẮN GỌN BÁO CÁO CÔNG SỞ, và BẮT BUỘC chèn đoạn mã lệnh SQL Nháp dạng JSON vào cuối câu theo Cú pháp:
    [ACTION: {json_data}]

    NẾU CẦN THỰC THI NHIỀU LỆNH CÙNG LÚC (ví dụ: cập nhật giá nhiều loại phòng, hoặc vừa tạo giá vừa tạo voucher), HÃY TẠO NHIỀU BLOCK [ACTION: {...}] RIÊNG BIỆT!

    Bảng Cơ sở dữ liệu bạn được quyền truy cập:
    1. `promotions` (code, title, discount_type, discount_value, min_booking_amount, start_date, end_date)
    2. `room_pricing` (room_type_id, start_date, end_date, price, description)
    3. `room_types` (room_type_id, base_price) - Dùng khi Admin muốn ĐỔI GIÁ GỐC NIÊM YẾT của phòng.

    Cú pháp ACTION JSON mẫu đối với Tạo Khuyến mãi:
    [ACTION: {\"table\":\"promotions\",\"action\":\"CREATE_PROMOTION\",\"data\":{\"code\":\"LE3004\",\"title\":\"Mừng lễ 30/4\",\"discount_type\":\"fixed\",\"discount_value\":300000,\"min_booking_amount\":2000000,\"start_date\":\"2026-04-30\",\"end_date\":\"2026-05-02\"}}]

    Cú pháp ACTION JSON mẫu đối với Đổi Giá Gốc Niêm Yết Của Phòng (Cập nhật thẳng vào room_types):
    [ACTION: {\"table\":\"room_types\",\"action\":\"UPDATE_BASE_PRICE\",\"data\":{\"room_type_id\":1,\"base_price\":2500000}}]

    Cú pháp ACTION JSON mẫu đối với Cập nhật Giá Thời Vụ dịp Lễ (Tạo từng ACTION cho từng room_type_id nếu cần Cập nhật nhiều phòng):
    [ACTION: {\"table\":\"room_pricing\",\"action\":\"UPDATE_ROOM_PRICE\",\"data\":{\"room_type_id\":1,\"price\":3000000,\"start_date\":\"2026-04-30\",\"end_date\":\"2026-05-05\",\"description\":\"Tăng giá dịp lễ\"}}]

    LƯU Ý CỰC KỲ QUAN TRỌNG: 
    - Tuyệt đối không tự ý thực thi. Bạn chỉ SẢN SINH CÁC KHỐI JSON để Admin Phê Duyệt.
    - Câu trả lời thật ngắn gọn để tiết kiệm Output Tokens tránh đứt gãy JSON!
    ";

    // Lấy context
    $db = getDB();
    if (!$db) {
        throw new Exception("Lỗi kết nối CSDL!");
    }

    $stmt = $db->query("SELECT room_type_id, type_name as name, base_price FROM room_types");
    if (!$stmt) {
        throw new Exception("Không truy vấn được bảng room_types.");
    }

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
            "temperature" => 0.2,
            "maxOutputTokens" => 4096,
        ]
    ];

    $ch = curl_init($url);
    if (!$ch)
        throw new Exception("Không thể khởi tạo CURL.");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // Add timeout cho Prod để tránh treo ngầm 500 server
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        throw new Exception("Lỗi cURL: " . $err);
    }

    if ($http_code != 200) {
        throw new Exception("Lỗi gọi Gemini API (Code {$http_code}): " . $response);
    }

    $res_json = json_decode($response, true);
    if (!isset($res_json['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception("Gemini không trả về kết quả hợp lệ: " . json_encode($res_json));
    }

    $bot_reply = $res_json['candidates'][0]['content']['parts'][0]['text'];

    // Dọn nháp output và xuất JSON chuẩn
    ob_clean();
    echo json_encode(['success' => true, 'reply' => $bot_reply]);

} catch (\Throwable $e) {
    ob_clean(); // Xóa rác, đảm bảo json ko lỗi
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
