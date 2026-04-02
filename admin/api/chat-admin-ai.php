<?php
/**
 * Aurora Hotel Plaza - Admin Super AI v2.1 (Hybrid Support)
 * ========================================================
 */
ob_start();
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once __DIR__ . '/../../helpers/api_key_manager.php';
require_once __DIR__ . '/../../helpers/ai-helper.php';

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception("Lỗi Quyền Hạn: Bạn không phải Giám Đốc/Admin.");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $user_message = $input['message'] ?? '';

    if (empty($user_message)) {
        throw new Exception("Nội dung rỗng.");
    }

    $db = getDB();
    $provider = get_active_ai_provider();
    
    // Nạp context CSDL
    $total_rooms = $db->query("SELECT count(*) FROM rooms")->fetchColumn();
    $available_rooms = $db->query("SELECT count(*) FROM rooms WHERE status='available'")->fetchColumn();
    $bi_context = "- Tổng số phòng: {$total_rooms}\n- Phòng đang trống: {$available_rooms}\n";

    // Cấu trúc DB Schema quan trọng để AI không bị nhầm cột
    $db_schema = "
- bookings: booking_id, booking_code, user_id, room_type_id, room_id, check_in_date, check_out_date, num_adults, num_children, num_rooms, total_amount, status(pending,confirmed,checked_in,checked_out,cancelled), payment_status, created_at
- rooms: room_id, room_type_id, room_number, floor, status(available,occupied,cleaning,maintenance)
- room_types: room_type_id, type_name, max_occupancy, price
- users: user_id, full_name, email, phone, user_role, status, created_at
- payments: payment_id, booking_id, payment_method, amount, status
- chat_messages: message_id, conversation_id, sender_id, sender_type(customer,staff,bot), message, created_at";

    $system_prompt = "Bạn là Aurora AI Super Admin - Trợ lý siêu cấp của Aurora Hotel Plaza.
Bạn được quyền truy cập CSDL và thực thi các nghiệp vụ quản trị cao cấp.

DB SCHEMA QUAN TRỌNG:
$db_schema

QUY TẮC PHẢN HỒI:
1. Nếu cần dữ liệu để phân tích (Ví dụ: Thống kê số lượng, tìm phòng vô lý, xem doanh thu):
   - Hãy TRẢ VỀ DUY NHẤT chuỗi sau để hệ thống tự chạy SQL cho bạn: [READ_DB: SELECT * FROM ...]
   - Không được nói thêm văn bản nào khác nếu dùng từ khóa này. Hệ thống sẽ tự chạy câu lệnh và gọi lại bạn lần 2 kèm dữ liệu để bạn đọc.
2. Nếu bạn ĐÃ NHẬN ĐƯỢC KẾT QUẢ TỪ LẦN GỌI BƯỚC 1 (Khi hệ thống gửi JSON kết quả hoặc thông báo lỗi SQL):
   - Đọc kết quả, phân tích dữ liệu một cách chuyên sâu, sau đó trình bày câu trả lời hoàn chỉnh bằng ngôn ngữ tự nhiên cho sếp.
   - Hoặc nếu gặp lỗi SQL, hãy nói cho sếp biết và tự viết lại lệnh SQL chuẩn hơn (hoặc gọi [READ_DB] lần 2).
3. Khi Admin yêu cầu thao tác biến đổi CSDL (Thêm, Sửa, Xóa): 
   - BẮT BUỘC xuất: [ACTION: {\"table\":\"TÊN_BẢNG\",\"action\":\"RAPID_CRUD\",\"level\":\"C\",\"data\":{\"query\":\"CÂU_LỆNH_SQL\"}}]
   - Luôn yêu cầu phê duyệt cho các lệnh có rủi ro cao.

TRẠNG THÁI HỆ THỐNG: $bi_context";

        // Hàm gọi AI
    function call_ai_sync($provider, $sys_prompt, $messages) {
        $bot_reply = "";
        $api_key = get_active_gemini_key();
        $model = env('AI_MODEL', 'gemini-2.0-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $api_key;
        
        // Chuyển messages thành format gemini
        $gemini_history = "";
        foreach($messages as $m) {
            $gemini_history .= ($m['role']=='user' ? "Sếp: " : "AI: ") . $m['content'] . "\n\n";
        }
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                "contents" => [["role" => "user", "parts" => [["text" => $sys_prompt . "\n\n" . $gemini_history]]]]
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 40
        ]);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $json = json_decode($res, true);
            $bot_reply = $json['candidates'][0]['content']['parts'][0]['text'] ?? "";
        } else {
            throw new Exception("Gemini API Error (Code: $http_code): " . $res);
        }
        return $bot_reply;
    }

    $chat_history = [
        ["role" => "user", "content" => $user_message]
    ];
    
    // Lần gọi 1
    $bot_reply = call_ai_sync($provider, $system_prompt, $chat_history);
    
    // Nếu AI cần READ_DB (Xử lý đa vòng)
    if (preg_match('/\[READ_DB:\s*(.*?)\]/s', $bot_reply, $matches)) {
        $read_sql = trim($matches[1], " \t\n\r\0\x0B\"'");
        if (stripos($read_sql, 'SELECT') === 0) {
            try {
                $stmtRead = $db->query($read_sql);
                $read_data = $stmtRead->fetchAll(PDO::FETCH_ASSOC);
                $data_str = json_encode($read_data, JSON_UNESCAPED_UNICODE);
                // Giới hạn max string length để tránh tràn context LLM
                if (strlen($data_str) > 12000) $data_str = substr($data_str, 0, 12000) . '...[TRUNCATED]';
                $db_result_msg = "HỆ THỐNG GỬI KẾT QUẢ SQL THÀNH CÔNG: " . $data_str . "\n\nTừ kết quả trên, hãy trình bày báo cáo phân tích và trả lời câu hỏi ban đầu của sếp ở trên.";
            } catch (PDOException $e) {
                $db_result_msg = "LỖI SQL KHI CỐ TÌNH CHẠY: " . $e->getMessage() . "\n\nHãy giải thích nguyên nhân lỗi hoặc sửa lại lệnh SQL và trả lời cho sếp biết nhé.";
            }
            
            // Đưa kết quả vào history và gọi AI lần 2
            $chat_history[] = ["role" => "assistant", "content" => $bot_reply];
            $chat_history[] = ["role" => "user", "content" => $db_result_msg];
            
            $bot_reply = call_ai_sync($provider, $system_prompt, $chat_history);
        }
    }

    log_key_usage(get_active_key_index(), 1500, 'admin');

    ob_clean();
    echo json_encode([
        'success' => true,
        'reply' => $bot_reply,
        'provider' => $provider,
        'key_info' => "Gemini (" . env('AI_MODEL', 'gemini-2.0-flash') . ")",
        'tokens' => 0,
        'key_idx' => get_active_key_index(),
        'stats' => get_key_usage_stats()
    ]);

} catch (\Throwable $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
