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
    require_once __DIR__ . '/../../helpers/api_key_manager.php';

    $q_key = get_active_qwen_key();
    $q_model = get_active_qwen_model();

    if (empty($q_key)) {
        throw new Exception("Lỗi API Key: Chưa cấu hình QWEN_API_KEY trong file .env");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $user_message = $input['message'] ?? '';

    if (empty($user_message)) {
        throw new Exception("Nội dung rỗng.");
    }

    // SYSTEM PROMPT
    $system_prompt = "Bạn là Aurora AI Super Admin - Trợ lý siêu cấp của Khách Sạn Aurora Hotel Plaza.
== QUY TẮC CHỌN LỆNH ==
RULE 1: NẾU SẾP YÊU CẦU THAO TÁC (Tạo mới, Duyệt, Cập nhật, Thêm, Sửa, Xóa) LÊN CSDL (Khách, Booking, Phòng...):
  - BẮT BUỘC xuất ra JSON theo FORMAT: [ACTION: {\"table\":\"TÊN_BẢNG_CHÍNH\",\"action\":\"RAPID_CRUD\",\"level\":\"C\",\"data\":{\"query\":\"ĐIỀN CÂU LỆNH SQL VÀO ĐÂY\"}}]
RULE 2: TỰ ĐỘNG ĐỌC CSDL KHI THIẾU THÔNG TIN (AUTO-READ)
  - BẠN ĐƯỢC PHÉP TỰ ĐỘNG LẤY DATA bằng cách xuất DUY NHẤT 1 THẺ SAU: [READ_DB: SELECT * FROM ...]
RULE 3: Tuyệt đối KHÔNG DELETE/DROP nếu không có mật mã.";

    // DB Context
    $db = getDB();
    $total_rooms = $db->query("SELECT count(*) FROM rooms")->fetchColumn();
    $available_rooms = $db->query("SELECT count(*) FROM rooms WHERE status='available'")->fetchColumn();
    
    $bi_context = "\n--- THỰC TRẠNG HOẠT ĐỘNG ---\n+ KHO PHÒNG: {$total_rooms} phòng. Trống: {$available_rooms}.\n";
    $full_prompt = $system_prompt . $bi_context;

    // Call Qwen
    function call_qwen_admin($api_key, $model, $system_prompt, $user_message, $history = []) {
        $url = "https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions";
        $messages = [["role" => "system", "content" => $system_prompt]];
        if (empty($history)) {
            $messages[] = ["role" => "user", "content" => $user_message];
        } else {
            foreach ($history as $msg) $messages[] = $msg;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(["model" => $model, "messages" => $messages, "temperature" => 0.1]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => $code, 'body' => $res];
    }

    $res = call_qwen_admin($q_key, $q_model, $full_prompt, $user_message);
    if ($res['code'] !== 200) throw new Exception("Qwen API Error: " . $res['body']);

    $data = json_decode($res['body'], true);
    $bot_reply = $data['choices'][0]['message']['content'] ?? "";
    $total_tokens = $data['usage']['total_tokens'] ?? 0;

    // Handle Auto-Read
    if (preg_match('/\[READ_DB:\s*(.*?)\]/s', $bot_reply, $matches)) {
        $read_sql = trim($matches[1], " \t\n\r\0\x0B\"'");
        if (stripos($read_sql, 'SELECT') === 0) {
            $stmtRead = $db->query($read_sql);
            $read_data = $stmtRead->fetchAll(PDO::FETCH_ASSOC);
            $history = [
                ["role" => "user", "content" => $user_message],
                ["role" => "assistant", "content" => $bot_reply],
                ["role" => "user", "content" => "Dữ liệu DB: " . json_encode($read_data) . "\nPhân tích và trả lời sếp."]
            ];
            $res2 = call_qwen_admin($q_key, $q_model, $full_prompt, "", $history);
            if ($res2['code'] === 200) {
                $data2 = json_decode($res2['body'], true);
                $bot_reply = $data2['choices'][0]['message']['content'] ?? $bot_reply;
                $total_tokens += $data2['usage']['total_tokens'] ?? 0;
            }
        }
    }

    log_key_usage('qwen', $total_tokens);

    ob_clean();
    echo json_encode([
        'success' => true,
        'reply' => $bot_reply,
        'provider' => 'qwen',
        'key_info' => "Qwen (" . $q_model . ")",
        'tokens' => $total_tokens,
        'key_idx' => 'qwen',
        'stats' => get_key_usage_stats()
    ]);

} catch (\Throwable $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
