<?php
/**
 * Aurora Hotel Plaza - Admin Super AI v3.0
 * API xử lý chat AI cho Admin (100% Gemini)
 */

ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once '../../config/database.php';
    require_once __DIR__ . '/../../helpers/api_key_manager.php';
    require_once __DIR__ . '/../../helpers/ai-helper.php';

    // Kiểm tra quyền admin
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception("Lỗi Quyền Hạn: Bạn không phải Giám Đốc/Admin.");
    }

    // Parse input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input: " . json_last_error_msg());
    }

    $user_message = $input['message'] ?? '';
    if (empty($user_message)) {
        throw new Exception("Nội dung rỗng.");
    }

    $db = getDB();
    if (!$db) {
        throw new Exception("Không thể kết nối database.");
    }

    // Nạp context CSDL
    try {
        $total_rooms = (int) $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
        $available_rooms = (int) $db->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn();
    } catch (Exception $e) {
        $total_rooms = 0;
        $available_rooms = 0;
    }

    $bi_context = "- Tổng số phòng: {$total_rooms}\n- Phòng đang trống: {$available_rooms}\n";

    // Cấu trúc DB Schema
    $db_schema = "
- bookings: booking_id, booking_code, user_id, room_type_id, room_id, check_in_date, check_out_date, num_adults, num_children, num_rooms, total_amount, status(pending,confirmed,checked_in,checked_out,cancelled), payment_status, created_at
- rooms: room_id, room_type_id, room_number, floor, status(available,occupied,cleaning,maintenance)
- room_types: room_type_id, type_name, max_occupancy, base_price
- users: user_id, full_name, email, phone, user_role, status, created_at
- payments: payment_id, booking_id, payment_method, amount, status
- chat_messages: message_id, conversation_id, sender_id, sender_type(customer,staff,bot), message, created_at";

    $system_prompt = "Bạn là Aurora AI Super Admin - Trợ lý siêu cấp của Aurora Hotel Plaza.
Bạn được quyền truy cập CSDL và thực thi các nghiệp vụ quản trị cao cấp.

DB SCHEMA QUAN TRỌNG:
$db_schema

QUY TẮC PHẢN HỒI:
1. Nếu cần dữ liệu để phân tích: Trả về DUY NHẤT [READ_DB: SELECT * FROM ...] (Luôn thêm LIMIT 20 để tiết kiệm token nếu không cần toàn bộ)
2. Khi nhận kết quả SQL: Phân tích và trình bày báo cáo hoàn chỉnh
3. Khi admin yêu cầu thao tác CSDL (Thêm, Sửa, Xóa): 
   - Xuất: [ACTION: {\"table\":\"TÊN_BẢNG\",\"action\":\"RAPID_CRUD\",\"level\":\"C\",\"data\":{\"query\":\"CÂU_LỆNH_SQL\"}}]

TRẠNG THÁI HỆ THỐNG: $bi_context";

    // Gọi AI lần 1
    $chat_history = [
        ["role" => "user", "content" => $user_message]
    ];

    $bot_reply = call_ai_admin($system_prompt, $chat_history);

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

                $db_result_msg = "HỆ THỐNG GỬI KẾT QUẢ SQL THÀNH CÔNG: " . $data_str . "\n\nTừ kết quả trên, hãy trình bày báo cáo phân tích và trả lời câu hỏi ban đầu của sếp.";

                $chat_history[] = ["role" => "assistant", "content" => $bot_reply];
                $chat_history[] = ["role" => "user", "content" => $db_result_msg];
                $bot_reply = call_ai_admin($system_prompt, $chat_history);

            } catch (PDOException $e) {
                $db_result_msg = "LỖI SQL: " . $e->getMessage() . "\n\nHãy giải thích lỗi hoặc viết lại lệnh SQL.";
                $chat_history[] = ["role" => "assistant", "content" => $bot_reply];
                $chat_history[] = ["role" => "user", "content" => $db_result_msg];
                $bot_reply = call_ai_admin($system_prompt, $chat_history);
            }
        }
    }

    // Log usage
    log_key_usage(get_active_key_index(), 1500, 'admin');

    // Clear buffer và trả kết quả
    ob_clean();
    $model_name = env('AI_MODEL', 'gemini-2.0-flash');

    echo json_encode([
        'success' => true,
        'reply' => $bot_reply,
        'provider' => 'gemini',
        'key_info' => "Gemini ({$model_name})",
        'tokens' => 0,
        'key_idx' => get_active_key_index(),
        'stats' => get_key_usage_stats()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_clean();

    error_log("Admin AI Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_type' => 'SERVER_ERROR',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Gọi Gemini đồng bộ cho Admin
 */
function call_gemini_admin($system_prompt, $messages, $retry = 0)
{
    $api_key = get_active_gemini_key();

    if (empty($api_key)) {
        throw new Exception("Chưa cấu hình Gemini API Key. Kiểm tra file .env");
    }

    $model = env('AI_MODEL', 'gemini-2.0-flash');

    // Build Gemini multi-turn contents
    $contents = [];
    foreach ($messages as $m) {
        $contents[] = [
            'role' => $m['role'] === 'user' ? 'user' : 'model',
            'parts' => [['text' => $m['content']]]
        ];
    }

    $request_body = [
        'system_instruction' => ['parts' => [['text' => $system_prompt]]],
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 2048
        ]
    ];

    $url = GEMINI_API_BASE . "{$model}:generateContent?key={$api_key}";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Auto-retry cho lỗi 503/500/502 (Gemini server overload)
    if (in_array($http_code, [500, 502, 503]) && $retry < 3) {
        $wait = pow(2, $retry);
        error_log("Admin AI: HTTP $http_code - Auto-retry lần " . ($retry + 1) . " sau {$wait}s...");
        sleep($wait);
        return call_gemini_admin($system_prompt, $messages, $retry + 1);
    }

    if ($http_code === 429) {
        $current_idx = get_active_key_index();
        mark_key_rate_limited($current_idx, 60);
        rotate_gemini_key();
        throw new Exception("Gemini API bị giới hạn (429). Vui lòng thử lại sau.");
    }

    if ($http_code >= 400 || $curl_error) {
        throw new Exception("Gemini API Error: HTTP {$http_code} - {$curl_error}");
    }

    $decoded = json_decode($response, true);

    if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
        return $decoded['candidates'][0]['content']['parts'][0]['text'];
    }

    if (isset($decoded['error'])) {
        throw new Exception("API Error: " . $decoded['error']['message']);
    }

    throw new Exception("Gemini API trả về phản hồi không hợp lệ");
/**
 * Gọi AI đồng bộ cho Admin (Router)
 */
function call_ai_admin($system_prompt, $messages, $retry = 0) {
    if (get_active_ai_provider() === 'opencode') {
        return call_opencode_admin($system_prompt, $messages, $retry);
    }
    return call_gemini_admin($system_prompt, $messages, $retry);
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
            $tokens = $decoded['usage']['total_tokens'] ?? 0;
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
                ':reply' => $reply_text,
                ':model' => $model,
                ':tokens' => $tokens,
                ':status' => $status,
                ':error' => $curl_error ?: ($decoded['error']['message'] ?? ''),
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