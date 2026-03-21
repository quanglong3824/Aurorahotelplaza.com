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

    $api_key = get_active_gemini_key();
    $model = 'gemini-2.0-flash';

    if (empty($api_key)) {
        throw new Exception("Lỗi API Key: Chưa cấu hình GEMINI_API_KEY trong file .env");
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

    // Call Gemini
    function call_gemini_admin($api_key, $model, $system_prompt, $user_message, $history = []) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $api_key;
        
        $contents = [
            ["role" => "user", "parts" => [["text" => $system_prompt . "\n\nSếp: " . $user_message]]]
        ];

        if (!empty($history)) {
            // Transform history for Gemini if needed, but for simplicity here we just use the prompt
            // Gemini doesn't use the same role structure as OpenAI/Qwen easily in a single call without specialized array
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(["contents" => $contents]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => $code, 'body' => $res];
    }

    $res = call_gemini_admin($api_key, $model, $full_prompt, $user_message);
    if ($res['code'] !== 200) throw new Exception("Gemini API Error: " . $res['body']);

    $data = json_decode($res['body'], true);
    $bot_reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? "";
    $total_tokens = 0; // Gemini response doesn't always include usage in this format

    // Handle Auto-Read (Simplified for Admin Bot)
    if (preg_match('/\[READ_DB:\s*(.*?)\]/s', $bot_reply, $matches)) {
        $read_sql = trim($matches[1], " \t\n\r\0\x0B\"'");
        if (stripos($read_sql, 'SELECT') === 0) {
            $stmtRead = $db->query($read_sql);
            $read_data = $stmtRead->fetchAll(PDO::FETCH_ASSOC);
            $new_prompt = $bot_reply . "\nDữ liệu DB: " . json_encode($read_data) . "\nPhân tích và trả lời sếp.";
            $res2 = call_gemini_admin($api_key, $model, $full_prompt, $new_prompt);
            if ($res2['code'] === 200) {
                $data2 = json_decode($res2['body'], true);
                $bot_reply = $data2['candidates'][0]['content']['parts'][0]['text'] ?? $bot_reply;
            }
        }
    }

    log_key_usage(get_active_key_index(), 500); // Admin calls are usually heavier

    ob_clean();
    echo json_encode([
        'success' => true,
        'reply' => $bot_reply,
        'provider' => 'gemini',
        'key_info' => "Gemini (" . $model . ")",
        'tokens' => 0,
        'key_idx' => get_active_key_index(),
        'stats' => get_key_usage_stats()
    ]);

} catch (\Throwable $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
