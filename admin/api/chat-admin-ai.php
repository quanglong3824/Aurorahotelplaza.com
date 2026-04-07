<?php
/**
 * Aurora Hotel Plaza - Admin Super AI v2.2
 * API xử lý chat AI cho Admin
 */

// Start output buffering để bắt lỗi
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

// Tắt error display để không làm hỏng JSON
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
1. Nếu cần dữ liệu để phân tích: Trả về DUY NHẤT [READ_DB: SELECT * FROM ...]
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

                if (strlen($data_str) > 12000) {
                    $data_str = substr($data_str, 0, 12000) . '...[TRUNCATED]';
                }

                $db_result_msg = "HỆ THỐNG GỬI KẾT QUẢ SQL THÀNH CÔNG: " . $data_str . "\n\nTừ kết quả trên, hãy trình bày báo cáo phân tích và trả lời câu hỏi ban đầu của sếp.";

                // Gọi AI lần 2 với kết quả
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
    echo json_encode([
        'success' => true,
        'reply' => $bot_reply,
        'provider' => 'gemini',
        'key_info' => "Gemini (" . env('AI_MODEL', 'gemini-2.0-flash') . ")",
        'tokens' => 0,
        'key_idx' => get_active_key_index(),
        'stats' => get_key_usage_stats()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // Clear any output
    ob_clean();

    // Log error
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
 * Gọi AI đồng bộ cho Admin
 */
function call_ai_admin($system_prompt, $messages)
{
    $api_key = get_active_gemini_key();

    if (empty($api_key)) {
        throw new Exception("Chưa cấu hình Gemini API Key. Kiểm tra file .env");
    }

    $model_name = env('AI_MODEL', 'gemini-2.0-flash');

    // Build prompt
    $gemini_history = "";
    foreach ($messages as $m) {
        $gemini_history .= ($m['role'] == 'user' ? "Sếp: " : "AI: ") . $m['content'] . "\n\n";
    }

    $full_prompt = $system_prompt . "\n\n" . $gemini_history;

    try {
        $client = Gemini::client($api_key);
        $response = $client->generativeModel($model_name)->generateContent($full_prompt);
        $text = $response->text();

        if (empty($text)) {
            throw new Exception("AI trả về phản hồi rỗng");
        }

        return $text;

    } catch (Exception $e) {
        $errorMsg = $e->getMessage();

        // Xử lý 429 Rate Limit
        if (strpos($errorMsg, '429') !== false || strpos($errorMsg, 'quota') !== false || strpos($errorMsg, 'rate limit') !== false) {
            // Thử rotate key
            if (rotate_gemini_key()) {
                // Retry với key mới
                return call_ai_admin($system_prompt, $messages);
            }
            throw new Exception("API Key bị giới hạn (429). Đang thử key khác...");
        }

        throw new Exception("Gemini API Error: " . $errorMsg);
    }
}