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

    // Khởi tạo và Quản lý Tự động Chọn/Rotate API Key Mới Nhất
    require_once __DIR__ . '/../../helpers/api_key_manager.php';
    $api_key = get_active_gemini_key();

    if (empty($api_key)) {
        throw new Exception("Lỗi API Key: Chưa cấu hình GEMINI_API_KEYS. Vui lòng thêm khóa trong config/api_keys.php");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $user_message = $input['message'] ?? '';

    if (empty($user_message)) {
        throw new Exception("Nội dung rỗng.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SYSTEM PROMPT - Bắt buộc AI phải chọn đúng loại lệnh
    // ─────────────────────────────────────────────────────────────────────────
    $system_prompt = <<<'PROMPT'
Bạn là Aurora AI Super Admin - Trợ lý siêu cấp của Khách Sạn Aurora Hotel Plaza.

== QUY TẮC CHỌN LỆNH (QUAN TRỌNG NHẤT) ==
RULE 1: Admin nói "cập nhật giá phòng X lên Y" mà KHÔNG đề cập ngày/dịp cụ thể
  → LUÔN LUÔN dùng UPDATE_BASE_PRICE (table: room_types) để đổi giá gốc niêm yết.

RULE 2: Admin nói "dịp lễ...", "tháng ...đến tháng...", "từ ngày...đến ngày..."
  → Dùng UPDATE_ROOM_PRICE (table: room_pricing) với start_date + end_date đầy đủ.

RULE 3: Admin muốn tạo mã khuyến mãi / voucher giảm giá
  → Dùng CREATE_PROMOTION (table: promotions).

== BẢNG DỮ LIỆU ==
1. promotions      → promotion_code, promotion_name, discount_type(percentage|fixed_amount), discount_value, min_booking_amount, start_date, end_date
2. room_types      → room_type_id, base_price  [Chỉ dùng UPDATE_BASE_PRICE để đổi giá gốc]
3. room_pricing    → room_type_id, start_date, end_date, price, description  [Chỉ dùng UPDATE_ROOM_PRICE cho giá thời vụ]

== FORMAT JSON BẮT BUỘC ==
[ACTION: {"table":"TÊN_BẢNG","action":"TÊN_HÀNH_ĐỘNG","data":{...}}]

Nếu nhiều lệnh: xuất NHIỀU block [ACTION] riêng biệt nhau.

== VÍ DỤ CHUẨN ==
Admin: "Cập nhật giá Deluxe lên 2.5 triệu" (không có ngày cụ thể)
→ BẮT BUỘC dùng UPDATE_BASE_PRICE:
[ACTION: {"table":"room_types","action":"UPDATE_BASE_PRICE","data":{"room_type_id":ID_PHONG,"base_price":2500000}}]

Admin: "Nâng giá Deluxe 10% dịp 30/4" (có dịp cụ thể)
→ Dùng UPDATE_ROOM_PRICE với ngày:
[ACTION: {"table":"room_pricing","action":"UPDATE_ROOM_PRICE","data":{"room_type_id":ID_PHONG,"price":2200000,"start_date":"2026-04-30","end_date":"2026-05-02","description":"Lễ 30/4"}}]

Admin: "Tạo voucher giảm 20% cho đơn từ 2tr" 
→ Dùng CREATE_PROMOTION (dùng đúng tên cột promotion_code, promotion_name, discount_type là 'percentage' hoặc 'fixed_amount'):
[ACTION: {"table":"promotions","action":"CREATE_PROMOTION","data":{"code":"NOEL26","promotion_code":"NOEL26","promotion_name":"Noel 2026","discount_type":"percentage","discount_value":20,"min_booking_amount":2000000,"start_date":"2026-12-01","end_date":"2026-12-31"}}]

== LƯU Ý ==
- Trả lời ngắn gọn kiểu báo cáo công sở.
- Tuyệt đối chỉ xuất JSON để Admin phê duyệt, KHÔNG tự thực thi.
- Thay ID_PHONG bằng room_type_id thật từ danh sách phòng phía dưới.
PROMPT;

    // ─────────────────────────────────────────────────────────────────────────
    // Lấy context database: Danh sách phòng thực tế
    // ─────────────────────────────────────────────────────────────────────────
    $db = getDB();
    if (!$db) {
        throw new Exception("Lỗi kết nối CSDL!");
    }

    $stmt = $db->query("SELECT room_type_id, type_name as name, base_price FROM room_types");
    if (!$stmt) {
        throw new Exception("Không truy vấn được bảng room_types.");
    }

    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $room_context = "\n\nDANH SÁCH PHÒNG THỰC TẾ (Dùng room_type_id này khi tạo JSON):\n";
    foreach ($room_types as $rt) {
        $room_context .= "  room_type_id={$rt['room_type_id']} | Tên: {$rt['name']} | Giá gốc hiện tại: {$rt['base_price']} VND\n";
    }

    $full_prompt = $system_prompt . $room_context;

    // ─────────────────────────────────────────────────────────────────────────
    // Gọi Gemini API
    // ─────────────────────────────────────────────────────────────────────────
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    $reqData = [
        "system_instruction" => [
            "parts" => [["text" => $full_prompt]]
        ],
        "contents" => [
            ["role" => "user", "parts" => [["text" => $user_message]]]
        ],
        "generationConfig" => [
            "temperature" => 0.1,
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Kích hoạt tự động Switch Key khi Quota Của Key Hiển Tại đã hết
    if ($http_code === 429) {
        $new_key = rotate_gemini_key();
        if ($new_key && $new_key !== $api_key) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $new_key;
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            $err = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
    }

    curl_close($ch);

    if ($err) {
        throw new Exception("Lỗi cURL: " . $err);
    }

    if ($http_code === 429) {
        // Quota exceeded - parse details for frontend countdown
        $errData = json_decode($response, true);
        $retryDelay = '60s';
        $quotaLimit = 'N/A';
        $quotaId = 'N/A';

        if (isset($errData['error']['details'])) {
            foreach ($errData['error']['details'] as $detail) {
                if (isset($detail['retryDelay'])) {
                    $retryDelay = $detail['retryDelay'];
                }
                if (isset($detail['violations'][0])) {
                    $v = $detail['violations'][0];
                    $quotaLimit = $v['quotaValue'] ?? 'N/A';
                    $quotaId = $v['quotaId'] ?? 'N/A';
                }
            }
        }
        $retrySeconds = (int) filter_var($retryDelay, FILTER_SANITIZE_NUMBER_INT);

        ob_clean();
        echo json_encode([
            'success' => false,
            'error_type' => 'QUOTA_EXCEEDED',
            'retry_after' => $retrySeconds ?: 60,
            'quota_limit' => $quotaLimit,
            'quota_id' => $quotaId,
            'message' => "Quota: {$quotaLimit} req/day. Retry in {$retryDelay}.",
        ]);
        exit;
    }

    if ($http_code != 200) {
        throw new Exception("Lỗi gọi Gemini API (HTTP {$http_code}): " . $response);
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
