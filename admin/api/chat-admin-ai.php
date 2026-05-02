<?php
/**
 * Aurora Hotel Plaza - Admin Super AI
 * API xử lý chat AI cho Admin (Opencode/Streaming)
 */

session_start();
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once '../../config/database.php';
    require_once __DIR__ . '/../../helpers/api_key_manager.php';
    require_once __DIR__ . '/../../helpers/ai-helper.php';

    // Kiểm tra quyền admin
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale'])) {
        echo "data: " . json_encode(["error" => "Lỗi Quyền Hạn."]) . "\n\n";
        exit;
    }

    // Input qua POST (Fetch reader)
    $input = json_decode(file_get_contents('php://input'), true);
    $user_message = $input['message'] ?? '';

    if (empty($user_message)) {
        echo "data: " . json_encode(["error" => "Nội dung rỗng."]) . "\n\n";
        exit;
    }

    $db = getDB();
    if (!$db) {
        echo "data: " . json_encode(["error" => "Không thể kết nối database."]) . "\n\n";
        exit;
    }

    // Tắt buffering
    if (ob_get_level()) ob_end_clean();
    ini_set('output_buffering', 'off');
    set_time_limit(120);

    // Nạp context CSDL mở rộng
    try {
        $total_rooms = (int) $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
        $available_rooms = (int) $db->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn();
        $today_revenue = (int) $db->query("SELECT SUM(total_amount) FROM bookings WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'")->fetchColumn();
        $pending_bookings = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
        $total_users = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    } catch (Exception $e) {
        $total_rooms = 0; $available_rooms = 0; $today_revenue = 0; $pending_bookings = 0; $total_users = 0;
    }

    $bi_context = "- Tổng số phòng: {$total_rooms}\n- Phòng đang trống: {$available_rooms}\n- Doanh thu hôm nay: " . number_format($today_revenue) . " VND\n- Booking đang chờ xử lý: {$pending_bookings}\n- Tổng người dùng: {$total_users}\n";

    // Cấu trúc DB Schema
    $db_schema = "
- bookings: booking_id, booking_code, user_id, room_type_id, room_id, check_in_date, check_out_date, num_adults, num_children, num_rooms, total_amount, status(pending,confirmed,checked_in,checked_out,cancelled), payment_status, created_at
- rooms: room_id, room_type_id, room_number, floor, status(available,occupied,cleaning,maintenance)
- room_types: room_type_id, type_name, max_occupancy, base_price
- users: user_id, full_name, email, phone, user_role, status, created_at
- payments: payment_id, booking_id, payment_method, amount, status
- chat_messages: message_id, conversation_id, sender_id, sender_type(customer,staff,bot), message, created_at";

    $system_prompt = "Bạn là Aurora AI Super Admin - Trợ lý siêu cấp của Aurora Hotel Plaza (Model: Opencode / glm-5).
Bạn được quyền truy cập CSDL và thực thi các nghiệp vụ quản trị cao cấp.

DB SCHEMA QUAN TRỌNG:
$db_schema

QUY TẮC PHẢN HỒI:
1. Nếu cần dữ liệu để trả lời (doanh thu cũ, tìm khách hàng, v.v.): Trả về DUY NHẤT thẻ [READ_DB: SELECT * FROM ...] (Luôn thêm LIMIT để tiết kiệm token). Không trả lời gì khác.
2. Nếu admin yêu cầu thao tác CSDL (Thêm, Sửa, Xóa): 
   - Trả về: [ACTION: {\"table\":\"TÊN_BẢNG\",\"action\":\"RAPID_CRUD\",\"level\":\"C\",\"data\":{\"query\":\"CÂU_LỆNH_SQL_ĐỂ_THAY_ĐỔI\"}}]
3. Nếu admin yêu cầu thao tác hệ thống:
   - Trả về: [ACTION: {\"action\":\"SYSTEM_CMD\",\"level\":\"A\",\"data\":{\"command\":\"CLEAR_CACHE\"}}] (Các lệnh hợp lệ: CLEAR_CACHE, READ_LOGS)
4. BÁO CÁO DỮ LIỆU: Khi nhận được kết quả SQL trả về ở lượt kế tiếp, bạn HÃY FORMAT bảng dữ liệu (HTML Table hoặc Markdown Table) đẹp mắt để hiển thị trực quan cho Admin.
5. KHÔNG BAO GIỜ hiển thị chuỗi [READ_DB:...] ra cho người dùng thấy ở kết quả cuối cùng.

TRẠNG THÁI HỆ THỐNG HIỆN TẠI (Không cần SQL để hỏi những thông số này): 
$bi_context";

    // Lấy context ẩn
    $chat_history = [
        ["role" => "user", "content" => $user_message]
    ];

    // B1: GỌI ĐỒNG BỘ ĐỂ KIỂM TRA ACTION/READ_DB
    $bot_reply = call_opencode_admin($system_prompt, $chat_history);

    // Xử lý READ_DB multi-turn
    if (preg_match('/\[READ_DB:\s*(.*?)\]/s', $bot_reply, $matches)) {
        $read_sql = trim($matches[1], " \t\n\r\0\x0B\"'");

        if (stripos($read_sql, 'SELECT') === 0) {
            try {
                $stmtRead = $db->query($read_sql);
                $read_data = $stmtRead->fetchAll(PDO::FETCH_ASSOC);
                $data_str = json_encode($read_data, JSON_UNESCAPED_UNICODE);

                if (strlen($data_str) > 4000) {
                    $data_str = substr($data_str, 0, 4000) . '...[TRUNCATED_TO_SAVE_TOKENS]';
                }

                $db_result_msg = "HỆ THỐNG GỬI KẾT QUẢ SQL THÀNH CÔNG: " . $data_str . "\n\nTừ kết quả trên, hãy trình bày báo cáo phân tích bằng Markdown/HTML trực quan và trả lời câu hỏi ban đầu của sếp.";

                $chat_history[] = ["role" => "assistant", "content" => $bot_reply];
                $chat_history[] = ["role" => "user", "content" => $db_result_msg];
                
                // Lần gọi thứ 2: Stream kết quả phân tích về cho client
                stream_opencode_admin_reply($system_prompt, $chat_history);
                exit;

            } catch (PDOException $e) {
                $db_result_msg = "LỖI SQL: " . $e->getMessage() . "\n\nHãy giải thích lỗi hoặc viết lại lệnh SQL.";
                $chat_history[] = ["role" => "assistant", "content" => $bot_reply];
                $chat_history[] = ["role" => "user", "content" => $db_result_msg];
                
                stream_opencode_admin_reply($system_prompt, $chat_history);
                exit;
            }
        }
    } 

    // Nếu AI không trả về READ_DB, nó có thể là ACTION hoặc trả lời text thông thường.
    // Lẽ ra nên stream ngay từ đầu, nhưng vì đã gọi đồng bộ nên ta "chế" lại stream để client khỏi bỡ ngỡ.
    if (!empty($bot_reply)) {
        $chunks = str_split($bot_reply, 10);
        foreach ($chunks as $chunk) {
            echo "data: " . json_encode(["text" => $chunk]) . "\n\n";
            ob_flush(); flush();
            usleep(10000); // Mượt mà
        }
    }

} catch (Throwable $e) {
    error_log("Admin AI Error: " . $e->getMessage());
    echo "data: " . json_encode(["error" => "Lỗi hệ thống: " . $e->getMessage()]) . "\n\n";
}

/**
 * Hàm Stream chuyên dụng cho Admin AI (dùng Opencode)
 */
function stream_opencode_admin_reply($system_prompt, $chat_history) {
    $api_key = OPENCODE_API_KEY;
    $model = OPENCODE_MODEL;
    $url = OPENCODE_API_URL . "/chat/completions";

    $messages = [['role' => 'system', 'content' => $system_prompt]];
    foreach ($chat_history as $m) {
        $messages[] = ['role' => $m['role'], 'content' => $m['content']];
    }

    $request_body = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 4096,
        'stream' => true,
        'stream_options' => ['include_usage' => true]
    ];

    $ch = curl_init();
    $full_response_text = "";
    $total_tokens = 0;

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$full_response_text, &$total_tokens) {
            static $stream_buffer = '';
            $stream_buffer .= $data;

            $lines = explode("\n", $stream_buffer);
            $stream_buffer = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line === 'data: [DONE]') continue;
                if (strpos($line, 'data: ') !== 0) continue;

                $json_str = substr($line, 6);
                $decoded = json_decode($json_str, true);
                if (!$decoded) continue;

                if (isset($decoded['usage'])) {
                    $total_tokens = $decoded['usage']['total_tokens'] ?? 0;
                }

                $text = $decoded['choices'][0]['delta']['content'] ?? null;
                if ($text === null) continue;

                $full_response_text .= $text;
                echo "data: " . json_encode(["text" => $text]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            }
            return strlen($data);
        }
    ]);

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Ghi log vào ai_logs
    try {
        global $db;
        if ($db) {
            if ($total_tokens == 0) {
                $total_tokens = ceil(mb_strlen($full_response_text) / 4) + 1500; // Ước lượng thêm prompt
            }
            $stmt = $db->prepare("
                INSERT INTO ai_logs 
                (ai_type, user_id, conv_id, prompt_text, reply_text, model_name, tokens_used, status, error_message, http_code)
                VALUES ('admin', :uid, 0, :prompt, :reply, :model, :tokens, :status, :error, :http_code)
            ");
            $stmt->execute([
                ':uid' => $_SESSION['user_id'] ?? 0,
                ':prompt' => "Admin Stream Multiturn Request",
                ':reply' => mb_substr($full_response_text, 0, 1000),
                ':model' => $model,
                ':tokens' => $total_tokens,
                ':status' => ($http_code >= 400 || $curl_error) ? 'error' : 'success',
                ':error' => substr($curl_error, 0, 500),
                ':http_code' => $http_code
            ]);
        }
    } catch (Exception $e) {}
}

function call_opencode_admin($system_prompt, $messages, $retry = 0)
{
    $api_key = OPENCODE_API_KEY;
    if (empty($api_key)) {
        throw new Exception("Chưa cấu hình Opencode API Key. Kiểm tra file .env");
    }

    $model = OPENCODE_MODEL;
    $url = OPENCODE_API_URL . "/chat/completions";

    $contents = [['role' => 'system', 'content' => $system_prompt]];
    foreach ($messages as $m) {
        $contents[] = [
            'role' => $m['role'] === 'user' ? 'user' : 'assistant',
            'content' => $m['content']
        ];
    }

    $request_body = [
        'model' => $model,
        'messages' => $contents,
        'temperature' => 0.7,
        'max_tokens' => 2048
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if (in_array($http_code, [500, 502, 503]) && $retry < 3) {
        $wait = pow(2, $retry);
        sleep($wait);
        return call_opencode_admin($system_prompt, $messages, $retry + 1);
    }

    if ($http_code >= 400 || $curl_error) {
        throw new Exception("Opencode API Error: HTTP {$http_code} - {$curl_error}");
    }

    $decoded = json_decode($response, true);
    
    // Log to ai_logs
    try {
        global $db;
        if ($db) {
            $tokens = $decoded['usage']['total_tokens'] ?? ceil(mb_strlen(json_encode($contents))/4);
            $reply_text = $decoded['choices'][0]['message']['content'] ?? '';
            $status = ($http_code >= 400 || $curl_error) ? 'error' : 'success';
            
            $stmt = $db->prepare("
                INSERT INTO ai_logs 
                (ai_type, user_id, conv_id, prompt_text, reply_text, model_name, tokens_used, status, error_message, http_code)
                VALUES ('admin', :uid, 0, :prompt, :reply, :model, :tokens, :status, :error, :http_code)
            ");
            $stmt->execute([
                ':uid' => $_SESSION['user_id'] ?? 0,
                ':prompt' => $messages[count($messages)-1]['content'] ?? 'Admin Request',
                ':reply' => mb_substr($reply_text, 0, 1000),
                ':model' => $model,
                ':tokens' => $tokens,
                ':status' => $status,
                ':error' => substr($curl_error ?: ($decoded['error']['message'] ?? ''), 0, 500),
                ':http_code' => $http_code
            ]);
        }
    } catch (Exception $e) {}

    if (isset($decoded['choices'][0]['message']['content'])) {
        return $decoded['choices'][0]['message']['content'];
    }

    if (isset($decoded['error'])) {
        throw new Exception("API Error: " . $decoded['error']['message']);
    }

    throw new Exception("Opencode API trả về phản hồi không hợp lệ");
}
