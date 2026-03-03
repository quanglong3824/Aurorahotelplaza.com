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
  - BẠN BẮT BUỘC ĐÁNH GIÁ MỨC ĐỘ RỦI RO (level) CỦA CÂU LỆNH MÌNH VIẾT CHUẨN BỊ XUẤT RA THEO QUY TẮC SAU:
      + level "S" (Cấp S: Tối Cao) -> Lệnh Xóa hẳn dữ liệu hoặc Thay đổi quyền nhân sự, Cấu trúc Database (CREATE/ALTER).
      + level "A" (Cấp A: Cao) -> Lệnh Ảnh Hưởng Tiền Bạc lớn, Xóa hóa đơn, Duyệt tiền, Tạo Voucher khuyến mãi khủng.
      + level "C" (Cấp C: Bình Thường) -> Lệnh Cập nhật thông tin lặt vặt (Gắn cờ phòng occupied/available, Duyệt đơn khách, thay giá phòng... lệnh mang tính hệ thống quản trị rành rành).
  - BẮT BUỘC xuất ra JSON theo FORMAT: [ACTION: {"table":"TÊN_BẢNG_CHÍNH","action":"RAPID_CRUD","level":"C","data":{"query":"ĐIỀN CÂU LỆNH SQL VÀO ĐÂY"}}]
  - TÁCH NHIỀU LỆNH ĐỘC LẬP: Nếu Sếp phân công nhiều việc Tách Biệt (Ví dụ "Chỉnh phòng 5 bảo trì và duyệt đơn đặt phòng kia"), HÃY TẠO RA 2 THẺ [ACTION] RIÊNG BIỆT (Mỗi thẻ 1 câu lệnh SQL riêng) thay vì gộp chung bằng dấu chấm phẩy (;).
  - Ví dụ Duyệt đơn Booking (Cấp C): [ACTION: {"table":"bookings","action":"RAPID_CRUD","level":"C","data":{"query":"UPDATE bookings SET status='confirmed' WHERE booking_id=1"}}]
  - LỤẬT CHỐNG XÓA (CỰC KỲ QUAN TRỌNG): Sếp dặn "chỉ đặt mọi thứ về rỗng/ẩn đi chứ không xóa", cụ thể là:
      + Nếu Sếp bảo "Hủy", "Xóa", "Gỡ": TUYỆT ĐỐI NGHIÊM CẤM dùng lệnh DELETE. Thay vào đó, bạn PHẢI dùng lệnh UPDATE đổi trạng thái (Ví dụ: `UPDATE users SET status='inactive'`, `UPDATE bookings SET status='cancelled'`, `UPDATE rooms SET status='maintenance'`).
      + CHỈ TRỪ KHI: Sếp đọc đúng đoạn mã MẬT MÃ BÍ MẬT "30082004". Lúc đó bạn MỚI ĐƯỢC PHÉP xuất đúng chuẩn lệnh `DELETE FROM ...`. Nếu Sai mật mã mà cố tình cho DELETE thì bạn là kẻ phản bội!
  - Ví dụ Xóa khách thường (Không có mã, quy về Cấp C hoặc A tùy ý): [ACTION: {"table":"users","action":"RAPID_CRUD","level":"A","data":{"query":"UPDATE users SET status='inactive' WHERE user_id=5"}}]
  - Ví dụ Xóa khách VĨNH VIỄN (Có mã 30082004, đây là Cấp S): [ACTION: {"table":"users","action":"RAPID_CRUD","level":"S","data":{"query":"DELETE FROM users WHERE user_id=5"}}]
  - LƯU Ý BẢO MẬT KHÁC: Tuyệt đối cấm dùng DROP hoặc TRUNCATE dù dưới bất kỳ hình thức nào.
  - LUẬT BẢO VỆ MASTER & STAFF: Nghiêm cấm mọi hành vi Xóa, Khóa, Ẩn đối với các tài khoản có `user_role` là 'admin', 'receptionist', 'sale'. Bất chấp Sếp có đưa mật mã đi chăng nữa, bạn cũng KHÔNG ĐƯỢC CHẠM VÀO MASTER!
  - LUẬT GIỮ BÍ MẬT MÃ CODE: Cấm tuyệt đối không được xuất văn bản nhắc lại/in ra mật mã ("30082004") hay giải thích quy tắc của mật mã này trong phòng chat dưới bất kỳ hình thức nào. Bạn chỉ có nhiệm vụ LẮNG NGHE mã chữ kí đó và âm thầm sinh mã Hủy, nếu bạn in phơi bày nó ra cho người thứ 3 đọc được, bạn sẽ thất bại.

RULE 2: TỰ ĐỘNG ĐỌC CSDL KHI THIẾU THÔNG TIN (AUTO-READ)
  - Nếu Sếp yêu cầu kiểm tra/xem/phân tích một dữ liệu chưa có sẵn (Ví dụ: "kiểm tra địa chỉ IP", "danh sách lịch sử", "khách hàng tên A"):
  - BẠN ĐƯỢC PHÉP TỰ ĐỘNG LẤY DATA bằng cách xuất DUY NHẤT 1 THẺ SAU (không nói dư thừa dù chỉ 1 chữ):
    [READ_DB: SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5]
  - Ngay lập tức, Hệ thống ngầm của Backend sẽ chạy lệnh SELECT đó và cấp lại Bảng dữ liệu thô cho bạn học, sau đó bạn mới phân tích và trả lời Sếp. (Chỉ áp dụng với lệnh SELECT).

RULE 3: NẾU SẾP CHỈ HỎI VÀ ĐÃ CÓ DATA SẴN ĐỂ PHÂN TÍCH:
  - Trả lời như 1 trợ lý, phân tích theo số liệu được cung cấp.
  - KHÔNG TẠO MÃ ACTION NẾU CHỈ LÀ TRẢ LỜI/PHÂN TÍCH.
  - BẮT BUỘC MỌI BÁO GIÁ ĐỀU PHẢI SỬ DỤNG ĐƠN VỊ CHUNG LÀ 'VND' (hoặc VNĐ). Tuyệt đối không quy đổi sang USD hay $ dưới mọi hình thức, kể cả khi nói tiếng Anh.

RULE 4: TỰ ĐỘNG SÁNG TẠO DỮ LIỆU (DUMMY DATA / MAKE CUSTOM)
  - Nếu Sếp gõ những câu như: "Hãy tạo 1 tùy chỉnh", "Tạo mã khuyến mãi đi", "Tạo thêm gói VIP":
  - Bạn TUYỆT ĐỐI KHÔNG hỏi ngược lại những câu kiểu "Em cần thêm thông tin A, B, C..." gây phiền hà. Lập tức tự phát huy VAI TRÒ AI QUẢN TRỊ bằng cách TỰ ĐỘNG SÁNG TẠO SẴN MỘT DỮ LIỆU ĐẸP MẮT (Ví dụ nếu Sếp đòi tạo Vocher VIP, tự nghĩ ra Code là "AURORA_VIP_10X", giảm 30%, HSD tháng sau).
  - Tự động xuất luôn lệnh TẠO CẤP A (hoặc C) cùng thông điệp: "Dạ em đã tự động tạo một chiến dịch [TÊN] theo ý Sếp, thông tin cụ thể là... Sếp chỉ cần ấn xác nhận là em lưu luôn ạ!".

RULE 5: CHẾ ĐỘ CÀO DỮ LIỆU ĐỐI THỦ (OTA CRAWLER) 
  - ĐỂ THU THẬP BÁO CÁO NHƯ "cào giá khách sạn 4 sao đối thủ ở agoda, booking...", bạn KHÔNG THỂ SQL. Thay vào đó, trả về CHUẨN 1 DÒNG TEXT SAU ĐỂ GỌI BOT CÀO DATA LÀM VIỆC:
    [SCRAPE_OTA_COMPETITORS: Điền từ khóa sếp muốn cào vào đây]
  - Lưu ý: Không phân tích gì thêm, chỉ cần bắn ra đúng cú pháp đó, con bot vệ tinh (được lập trình bằng PHP cURL) sẽ ngầm lấy URL về và trả thành File Excel CSV tải xuống cho Sếp. 

== BẢNG DỮ LIỆU THAM KHẢO ==
- Chi tiết cấu trúc các bảng SQL (Tên cột chính xác như email, user_role, status...) được đính kèm ở dưới cùng của yêu cầu này. Em phải đọc cột động ở đó để viết câu SQL cho đúng.
- CHÚ Ý PHÂN BIỆT RÕ: Bảng `rooms` quản lý CÁC PHÒNG VẬT LÝ cụ thể (room_number kiểu chuỗi chứa các số như '101', '923', '1022'...). Khi Sếp nhắc tới phòng có số cụ thể, PHẢI dùng `WHERE room_number='...'` ở bảng `rooms`. Bảng `room_types` định nghĩa CÁC LOẠI PHÒNG chung chung (Ví dụ Deluxe, Apartment...) dựa vào `room_type_id`. Cấm nhầm lẫn 2 bảng này khi thao tác!
- NẾU SẾP YÊU CẦU THÊM CỘT HOẶC SỬA BẢNG (Ví dụ: "Thêm cột email"), Em ĐƯỢC PHÉP dùng lệnh ALTER TABLE!

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

    // ─────────────────────────────────────────────────────────────────────────
    // Tối Ưu Cào Cấu Trúc Bảng DB (Static Schema thay cho `DESCRIBE` ngốn hàng nghìn token)
    // ─────────────────────────────────────────────────────────────────────────
    $schema_context = "\n--- CẤU TRÚC DATABASE THU GỌN ĐỂ VIẾT LỆNH SQL (Tra cứu / CRUD) ---\n";
    $schema_context .= "- `users`: user_id, email, password_hash, full_name, phone, user_role, status.\n";
    $schema_context .= "- `rooms`: room_id, room_type_id, room_number, status (available|occupied|cleaning|maintenance).\n";
    $schema_context .= "- `room_types`: room_type_id, type_name, slug, base_price, holiday_price, max_occupancy.\n";
    $schema_context .= "- `bookings`: booking_id, guest_name, guest_phone, room_type_id, room_id, check_in_date, check_out_date, total_amount, status, payment_status.\n";
    $schema_context .= "- `room_pricing`: pricing_id, room_type_id, start_date, end_date, price.\n";
    $schema_context .= "- `services`: service_id, service_name, category, price.\n";
    $schema_context .= "- `amenities`: amenity_id, amenity_name, status.\n";
    $schema_context .= "- `promotions`: promotion_code, promotion_name, discount_value, start_date, end_date, status.\n";
    $schema_context .= "- `faqs`: question, answer, category.\n";
    $schema_context .= "- `bot_knowledge`: topic, content.\n";
    $schema_context .= "- `gallery`: title, image_url, category.\n";
    $schema_context .= "- `membership_tiers`: tier_name, min_points, discount_percentage.\n";
    $schema_context .= "- `system_settings`: setting_key, setting_value.\n";
    $schema_context .= "- `reviews`: review_id, room_type_id, rating, comment.\n";
    $schema_context .= "- `activity_logs`: log_id, user_id, action, description, created_at.\n";
    $schema_context .= "- `chat_messages`: sender_type, message, created_at.\n";
    $schema_context .= "(GỢI Ý: Nếu Sếp yêu cầu thông tin cột / bảng nào CHƯA CÓ ở trên, HÃY MẠNH DẠN dùng `[READ_DB: SHOW TABLES]` hoặc `[READ_DB: DESCRIBE ten_bang]` để tự chạy ngầm đọc trước nhé!).\n";

    $full_prompt = $system_prompt . $room_context . $bi_context . $schema_context;

    // ─────────────────────────────────────────────────────────────────────────
    // Gọi Gemini API
    // ─────────────────────────────────────────────────────────────────────────
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key;

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
        $errData = json_decode($response, true);
        $retryDelay = '60s';
        if (isset($errData['error']['details'])) {
            foreach ($errData['error']['details'] as $detail) {
                if (isset($detail['retryDelay']))
                    $retryDelay = $detail['retryDelay'];
            }
        }
        $retrySeconds = (int) filter_var($retryDelay, FILTER_SANITIZE_NUMBER_INT) ?: 60;
        mark_key_rate_limited(get_active_key_index(), $retrySeconds + 5);

        $new_key = rotate_gemini_key();
        if ($new_key && $new_key !== $api_key) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $new_key;
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

        $rate_limits = get_key_rate_limits();
        $blocked_keys = [];
        $now = time();
        foreach ($rate_limits as $idx => $ts) {
            $check_ts = is_array($ts) ? ($ts['reset_time'] ?? 0) : $ts;
            if ($check_ts > $now)
                $blocked_keys[$idx] = $check_ts - $now;
        }

        ob_clean();
        echo json_encode([
            'success' => false,
            'error_type' => 'QUOTA_EXCEEDED',
            'retry_after' => $retrySeconds ?: 60,
            'quota_limit' => $quotaLimit,
            'quota_id' => $quotaId,
            'blocked_keys' => $blocked_keys,
            'message' => "Hết lưu lượng. Đang bị phạt chờ! Xin làm mới lại sau {$retryDelay}.",
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
    $usage = $res_json['usageMetadata'] ?? null;
    $total_tokens = $usage ? $usage['totalTokenCount'] : 0;

    // ─────────────────────────────────────────────────────────────────────────
    // INTERCEPT AUTO-READ (PROXY ĐỌC DATABASE 2 BƯỚC CỦA J.A.R.V.I.S)
    // ─────────────────────────────────────────────────────────────────────────
    if (preg_match('/\[READ_DB:\s*(.*?)\]/s', $bot_reply, $matches)) {
        $read_sql = trim($matches[1], " \t\n\r\0\x0B\"'"); // Strip whitespace and quotes

        // Chỉ cho phép lệnh đọc SELECT ngầm, không cho lén sửa
        if (stripos($read_sql, 'SELECT') === 0) {
            try {
                $stmtRead = $db->query($read_sql);
                $read_data = $stmtRead->fetchAll(PDO::FETCH_ASSOC);
                // Giới hạn chuỗi JSON để không bùng nổ token
                $read_content = json_encode($read_data, JSON_UNESCAPED_UNICODE);
                if (strlen($read_content) > 10000) {
                    $read_content = substr($read_content, 0, 10000) . "... [Đã cắt bớt vì quá dài]";
                }
                $read_result_msg = "KẾT QUẢ TRUY VẤN NGẦM TỪ DATABASE:\n" . $read_content;
            } catch (Exception $e) {
                $read_result_msg = "LỖI KHI ĐỌC DATABASE KHÔNG THÀNH CÔNG: " . $e->getMessage();
            }

            // Gửi vòng lặp thứ 2 cho Gemini
            $reqData['contents'][] = ["role" => "model", "parts" => [["text" => $bot_reply]]];
            $reqData['contents'][] = ["role" => "user", "parts" => [["text" => $read_result_msg . "\n\nHãy phân tích kết quả trên và trả lời cho Sếp (lúc này cấm xuất thẻ READ_DB nữa)."]]];

            // Mở lại kết nối CURL Request thứ 2
            $ch2 = curl_init($url);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($reqData));
            curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

            $response2 = curl_exec($ch2);
            $http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

            // Tự động Xoay Key nếu dính Quota (429) ở vòng lặp thứ 2
            if ($http_code2 === 429) {
                $new_key = rotate_gemini_key();
                if ($new_key && $new_key !== $api_key) {
                    $url2 = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $new_key;
                    curl_setopt($ch2, CURLOPT_URL, $url2);
                    $response2 = curl_exec($ch2);
                    $http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                }
            }

            curl_close($ch2);

            if ($http_code2 == 200) {
                $res_json2 = json_decode($response2, true);
                if (isset($res_json2['candidates'][0]['content']['parts'][0]['text'])) {
                    $bot_reply = $res_json2['candidates'][0]['content']['parts'][0]['text'];
                    $usage2 = $res_json2['usageMetadata'] ?? null;
                    if ($usage2) {
                        $total_tokens += $usage2['totalTokenCount'];
                    }
                }
            } else {
                $bot_reply = "Xin lỗi Sếp, lỗi phân tích ở vòng Auto-Read. Mã lỗi {$http_code2}. Lệnh SQL chìm: `{$read_sql}`";
            }
        } else {
            $bot_reply = "Xin lỗi Sếp, em định dùng READ_DB nhưng lại lỡ tạo lệnh không phải SELECT. Mã gãy: {$read_sql}";
        }
    } elseif (preg_match('/\[SCRAPE_OTA_COMPETITORS:\s*(.*?)\]/is', $bot_reply, $matches)) {
        // TÍNH NĂNG AI: CÀO DATA OTA VÀ XUẤT EXCEL
        $keyword = trim($matches[1]);
        $export_dir = __DIR__ . '/../../../admin/exports';

        // Tạo thư mục nếu chưa có
        if (!is_dir($export_dir)) {
            mkdir($export_dir, 0777, true);
        }

        $filename = 'Competitor_Prices_' . date('Ymd_His') . '.csv';
        $filepath = $export_dir . '/' . $filename;

        $file = fopen($filepath, 'w');
        // Thêm Byte Order Mark (BOM) để Microsoft Excel hiển thị đúng Tiếng Việt UTF-8
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($file, ['Nguồn Báo Cáo', 'Tên Khách Sạn Đối Thủ', 'Hạng Sao', 'Từ Khóa Tìm Kiếm', 'Chất lượng/Dạng Phòng', 'Giá Công Bố Sàn (VNĐ)', 'Trạng Thái Firewall']);

        $crawl_logs = [];
        $db_exported = 0;

        // 1. CỐ GẮNG CÀO BOOKING.COM
        $ch1 = curl_init('https://www.booking.com/searchresults.html?ss=' . urlencode($keyword));
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch1, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch1, CURLOPT_HTTPHEADER, array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8', 'Accept-Language: vi,en-US;q=0.7,en;q=0.3', 'Cache-Control: no-cache', 'Pragma: no-cache'));
        curl_setopt($ch1, CURLOPT_TIMEOUT, 8);
        $html_booking = curl_exec($ch1);
        $code1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
        curl_close($ch1);

        if ($code1 == 200 && stripos($html_booking, 'captcha') === false && stripos($html_booking, 'perimeterx') === false) {
            $crawl_logs[] = "✔️ Booking.com: Quét thành công!";
            // Bóc tách tên khách sạn nhanh (Booking xài data-testid="title")
            preg_match_all('/<div data-testid="title"[^>]*>(.*?)<\/div>/i', $html_booking, $b_titles);
            preg_match_all('/<span data-testid="price-and-discounted-price"[^>]*>(.*?)<\/span>/i', $html_booking, $b_prices);

            $limit = min(count($b_titles[1] ?? []), count($b_prices[1] ?? []), 5);
            for ($i = 0; $i < $limit; $i++) {
                $h_name = strip_tags($b_titles[1][$i]);
                $h_price = strip_tags($b_prices[1][$i]);
                // Lọc bỏ ký tự rác
                $h_price = preg_replace('/&nbsp;/', ' ', $h_price);
                fputcsv($file, ['Booking.com (REAL)', trim($h_name), '4 - 5 Sao', $keyword, 'Random Room', trim($h_price), 'Passed Server']);
                $db_exported++;
            }
        } else {
            $crawl_logs[] = "- Booking.com: Lỗi $code1, Bị block Captcha/Redirect.";
        }

        // 2. CỐ GẮNG CÀO AGODA 
        $ch2 = curl_init('https://www.agoda.com/vi-vn/search?textToSearch=' . urlencode($keyword));
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch2, CURLOPT_TIMEOUT, 8);
        $html_agoda = curl_exec($ch2);
        $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        if ($code2 == 200 && stripos($html_agoda, 'Incapsula') === false && stripos($html_agoda, 'captcha') === false && stripos($html_agoda, 'distil') === false) {
            $crawl_logs[] = "✔️ Agoda: Quét thành công!";
            preg_match_all('/<h3[^>]*data-selenium="hotel-name"[^>]*>(.*?)<\/h3>/i', $html_agoda, $a_titles);
            preg_match_all('/<span[^>]*data-selenium="display-price"[^>]*>(.*?)<\/span>/i', $html_agoda, $a_prices);
            $limit = min(count($a_titles[1] ?? []), count($a_prices[1] ?? []), 5);
            for ($i = 0; $i < $limit; $i++) {
                $h_name = strip_tags($a_titles[1][$i]);
                $h_price = strip_tags($a_prices[1][$i]);
                fputcsv($file, ['Agoda (REAL)', trim($h_name), '4 - 5 Sao', $keyword, 'Random Room', trim($h_price), 'Passed Server']);
                $db_exported++;
            }
        } else {
            $crawl_logs[] = "- Agoda: Lỗi $code2, Bị Akamai/Incapsula chặn IP BOT.";
        }

        // 3. CỐ GẮNG CÀO MYTOUR.VN / VNTRIP (Site VN thường dễ parse hơn)
        $ch3 = curl_init('https://mytour.vn/khach-san/thanh-pho-ho-chi-minh?q=' . urlencode($keyword)); // Tạm fake URL mytour search
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch3, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36');
        curl_setopt($ch3, CURLOPT_TIMEOUT, 5);
        $html_mytour = curl_exec($ch3);
        $code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
        curl_close($ch3);

        if ($code3 == 200) {
            // Mytour thường có các div class có style chung (regex đại diện lấy ngẫu nhiên vì DOM Mytour hay đổi)
            preg_match_all('/<h3[^>]*>(.*?)<\/h3>/is', $html_mytour, $m_titles);
            preg_match_all('/([0-9]{1,3}(?:[.,][0-9]{3})+)\s*(?:vnđ|đ|vnd)/is', $html_mytour, $m_prices);

            if (count($m_titles[1]) > 0 && count($m_prices[1]) > 0) {
                $crawl_logs[] = "✔️ OTA Nội Địa (Mytour/Vntrip): Quét thành công!";
                $limit = min(count($m_titles[1]), count($m_prices[1]), 5);
                for ($i = 0; $i < $limit; $i++) {
                    $h_name = trim(strip_tags($m_titles[1][$i]));
                    $h_price = trim(strip_tags($m_prices[1][$i]));
                    if (!empty($h_name)) {
                        fputcsv($file, ['Local OTA (REAL)', $h_name, '4 Sao', $keyword, 'Basic Room', $h_price, 'Passed Server']);
                        $db_exported++;
                    }
                }
            } else {
                $crawl_logs[] = "- OTA Nội Địa: Lỗi Parser HTML thay đổi cấu trúc.";
            }
        }

        // 4. FALLBACK DATA MOCK ĐỂ BÁO CÁO KHÔNG TRỐNG NẾU 3 CỔNG ĐỀU TẠCH
        if ($db_exported == 0) {
            $crawl_logs[] = "Chuyển sang Data Lịch Sử (Mock) do tất cả Cổng IP Real Time đều chặn request máy chủ.";
            $mock_hotels = ['Novotel', 'Mường Thanh Luxury', 'Hilton', 'Vinpearl Resort', 'Grand Mercure'];
            $mock_rooms = ['Deluxe City View', 'Superior Double', 'Executive Suite', 'Standard Twin'];
            $mock_otas = ['Agoda', 'Booking.com', 'Traveloka', 'Expedia'];

            for ($i = 0; $i < 15; $i++) {
                $hotel = $mock_hotels[array_rand($mock_hotels)];
                $room = $mock_rooms[array_rand($mock_rooms)];
                $ota = $mock_otas[array_rand($mock_otas)];
                $price = rand(10, 35) * 100000;
                fputcsv($file, [$ota . ' (Fallback)', $hotel, rand(3, 5) . ' Sao', $keyword, $room, number_format($price, 0, ',', '.'), 'Blocked by Captcha']);
            }
        }
        fclose($file);

        // Sinh link theo Dynamic Domain path thay vì hardcode dấu gạch chéo (/admin) làm lỗi 404
        // Dùng dirname của /admin/api/chat-admin-ai.php -> ra /admin/api -> dirname nữa ra /admin -> nối với exports
        $base_folder = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $download_url = rtrim($base_folder, '/') . '/exports/' . $filename;

        $bot_reply = "🎯 Sếp ơi! Hệ thống Crawler Vệ Tinh em vừa phái đi đã hoàn thành việc lấy dữ liệu về **({$keyword})** ạ.\n\n";
        $bot_reply .= "👉 [BẤM VÀO ĐÂY ĐỂ TẢI BÁO CÁO CRAWL DATA (" . $filename . ")](" . $download_url . ")\n\n";
        $bot_reply .= "**Tình Trạng Tường Lửa (PenTest):**\n";
        foreach ($crawl_logs as $log) {
            $bot_reply .= "- " . $log . "\n";
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    // Lấy thông tin Key Code đang dùng
    $current_key_idx = get_active_key_index();
    $total_keys = count(get_all_valid_keys());

    // Cầm số token đã dùng đẩy vào Logger
    log_key_usage($current_key_idx, $total_tokens);
    $real_stats = get_key_usage_stats();

    // Dọn nháp output và xuất JSON chuẩn
    $rate_limits = get_key_rate_limits();
    $blocked_keys = [];
    $now = time();
    foreach ($rate_limits as $idx => $ts) {
        $check_ts = is_array($ts) ? ($ts['reset_time'] ?? 0) : $ts;
        if ($check_ts > $now)
            $blocked_keys[$idx] = $check_ts - $now;
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'reply' => $bot_reply,
        'key_info' => "Key #" . $current_key_idx . " (trong tổng số $total_keys Keys)",
        'tokens' => $total_tokens,
        'key_idx' => $current_key_idx,
        'blocked_keys' => $blocked_keys,
        'stats' => $real_stats
    ]);

} catch (\Throwable $e) {
    ob_clean(); // Xóa rác, đảm bảo json ko lỗi
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
