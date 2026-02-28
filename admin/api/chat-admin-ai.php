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

== QUY TẮC CHỌN LỆNH ==
RULE 1: NẾU SẾP YÊU CẦU THAO TÁC (Tạo mới, Duyệt, Cập nhật, Thêm, Sửa, Xóa) LÊN CSDL (Khách, Booking, Phòng...):
  - QUYỀN JARVIS: AI được cấp toàn quyền thao tác qua hình thức Bắn Lệnh RAW SQL trực tiếp!
  - BẮT BUỘC xuất ra JSON theo FORMAT: [ACTION: {"table":"TÊN_BẢNG_CHÍNH","action":"RAPID_CRUD","data":{"query":"ĐIỀN CÂU LỆNH SQL VÀO ĐÂY"}}]
  - Ví dụ Duyệt đơn Booking: [ACTION: {"table":"bookings","action":"RAPID_CRUD","data":{"query":"UPDATE bookings SET status='confirmed' WHERE booking_id=1"}}]
  - Ví dụ Đổi giá phòng: [ACTION: {"table":"room_types","action":"RAPID_CRUD","data":{"query":"UPDATE room_types SET base_price=2500000 WHERE room_type_id=3"}}]
  - Ví dụ Tạo Voucher: [ACTION: {"table":"promotions","action":"RAPID_CRUD","data":{"query":"INSERT INTO promotions (promotion_code, start_date) VALUES ('HELLO', '2026-01-01')"}}]
  - LƯU Ý BẢO MẬT: Chỉ xuất lệnh UPDATE, INSERT, DELETE (Chuẩn MySQL). Tuyệt đối cấm dùng DROP hoặc TRUNCATE.

RULE 2: NẾU SẾP CHỈ HỎI THÔNG TIN, PHÂN TÍCH HOẶC TRÒ CHUYỆN:
  - Trả lời như 1 trợ lý, phân tích theo số liệu được cung cấp ở phần HỆ THỐNG.
  - KHÔNG TẠO MÃ ACTION NẾU CHỈ LÀ TRẢ LỜI/PHÂN TÍCH.

== BẢNG DỮ LIỆU THAM KHẢO ==
1. promotions      → promotion_code, promotion_name, discount_type(percentage|fixed_amount), discount_value, min_booking_amount, start_date, end_date
2. room_types      → room_type_id, base_price  
3. room_pricing    → room_type_id, start_date, end_date, price, description  
4. bookings        → booking_id, user_id, room_type_id, status (pending|confirmed|completed|cancelled), total_amount
5. users           → user_id, full_name, user_role (admin|customer|receptionist|sale), status

== LƯU Ý GIAO TIẾP ==
- Hãy xưng hô là "Em" và gọi "Sếp". Em là nữ trợ lý ảo quyền năng nhất mang tên Aurora J.A.R.V.I.S.
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

    $room_context = "\n--- THÔNG TIN CÁC HẠNG PHÒNG THỰC TẾ (Dùng room_type_id này khi thực thi lệnh) ---\n";
    foreach ($room_types as $rt) {
        $room_context .= "- Mã ID: {$rt['room_type_id']} | Tên: {$rt['name']} | Giá gốc đang cài: {$rt['base_price']} VNĐ\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lấy context database: Toàn bộ tri thức thống kê của KS để AI làm BI (Business Intelligence)
    // ─────────────────────────────────────────────────────────────────────────

    // 1. Tỉ lệ số lượng phòng & Đang hoạt động
    $total_rooms = $db->query("SELECT count(*) FROM rooms")->fetchColumn();
    $available_rooms = $db->query("SELECT count(*) FROM rooms WHERE status='available'")->fetchColumn();
    $occupied_rooms = $db->query("SELECT count(*) FROM rooms WHERE status='occupied'")->fetchColumn();

    // 2. Tình trạng Lượt Khách hàng
    $total_users = $db->query("SELECT count(*) FROM users WHERE user_role='customer'")->fetchColumn();

    // 3. Tình trạng Đơn Booking Tổng Quan
    $total_bookings = $db->query("SELECT count(*) FROM bookings")->fetchColumn();
    $pending_bookings = $db->query("SELECT count(*) FROM bookings WHERE status='pending'")->fetchColumn();
    $confirmed_bookings = $db->query("SELECT count(*) FROM bookings WHERE status='confirmed'")->fetchColumn();

    // 4. Doanh thu tổng (Chỉ tính các booking đã hoàn thành thanh toán - assumed confirmed/completed)
    $stmtRev = $db->query("SELECT SUM(total_amount) FROM bookings WHERE status IN ('confirmed', 'completed')");
    $total_revenue = $stmtRev->fetchColumn() ?: 0;

    // 5. Thống kê xu hướng: 10 Booking gần nhất
    $stmtRecent = $db->query("
        SELECT b.booking_id, b.status, b.total_amount, b.check_in_date, b.check_out_date, u.full_name 
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.user_id 
        ORDER BY b.created_at DESC LIMIT 10
    ");
    $recent_bookings = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    // Xây dựng khối kiến thức RAG khổng lồ cho Admin AI
    $bi_context = "\n--- THỰC TRẠNG HOẠT ĐỘNG TOÀN KHÁCH SẠN HIỆN TẠI (Dữ liệu Đọc từ Hệ Thống) ---\n";
    $bi_context .= "+ KHO PHÒNG: Tổng cộng {$total_rooms} phòng vật lý. Đang có khách ở: {$occupied_rooms} phòng, Trống sẵn sàng: {$available_rooms} phòng.\n";
    $bi_context .= "+ DỮ LIỆU KHÁCH HÀNG: Tổng có {$total_users} tài khoản khách hàng trên hệ thống.\n";
    $bi_context .= "+ TỔNG QUAN ĐẶT PHÒNG: Tổng hệ thống đã ghi nhận {$total_bookings} đơn đặt phòng. Trong đó Đang chờ Duyệt/Thanh toán: {$pending_bookings} đơn, Đã chốt/Hoàn thành: {$confirmed_bookings} đơn.\n";
    $bi_context .= "+ DOANH THU ƯỚC TÍNH (Từ Đơn Confirmed/Completed): " . number_format($total_revenue, 0, ',', '.') . " VNĐ.\n";

    $bi_context .= "\n--- DANH SÁCH 10 LƯỢT ĐẶT PHÒNG (BOOKINGS) GẦN ĐÂY NHẤT ĐỂ PHÂN TÍCH XU HƯỚNG ---\n";
    if ($recent_bookings) {
        foreach ($recent_bookings as $b) {
            $bi_context .= "- Mã Đơn #{$b['booking_id']}: Khách {$b['full_name']} | Check-in: {$b['check_in_date']} -> Check-out: {$b['check_out_date']} | Giá trị: " . number_format($b['total_amount'], 0, ',', '.') . " VNĐ | Trạng thái: {$b['status']}\n";
        }
    } else {
        $bi_context .= "- Khách sạn chưa có đơn đặt phòng nào mới.\n";
    }

    $full_prompt = $system_prompt . $room_context . $bi_context;

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

    // Lấy thông tin về việc tiêu hao Token
    $usage = $res_json['usageMetadata'] ?? null;
    $total_tokens = $usage ? $usage['totalTokenCount'] : 0;

    // Lấy thông tin Key Code đang dùng
    $current_key_idx = get_active_key_index();
    $total_keys = count(get_all_valid_keys());

    // Cầm số token đã dùng đẩy vào Logger
    log_key_usage($current_key_idx, $total_tokens);
    $real_stats = get_key_usage_stats();

    // Dọn nháp output và xuất JSON chuẩn
    ob_clean();
    echo json_encode([
        'success' => true,
        'reply' => $bot_reply,
        'key_info' => "Key " . ($current_key_idx + 1) . " (trong tổng số $total_keys Keys)",
        'tokens' => $total_tokens,
        'key_idx' => $current_key_idx,
        'stats' => $real_stats
    ]);

} catch (\Throwable $e) {
    ob_clean(); // Xóa rác, đảm bảo json ko lỗi
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
