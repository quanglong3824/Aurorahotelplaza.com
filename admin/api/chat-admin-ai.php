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
    
    // 1. System Prompt đặc thù cho Admin (Thực thi SQL)
    $system_prompt = "Bạn là Aurora AI Super Admin - Trợ lý siêu cấp của Aurora Hotel Plaza.
Bạn được quyền truy cập CSDL và thực thi các nghiệp vụ quản trị cao cấp.

QUY TẮC PHẢN HỒI:
1. Khi Admin yêu cầu thao tác CSDL (Thêm, Sửa, Xóa, Cập nhật): 
   - BẮT BUỘC xuất ra JSON theo FORMAT: [ACTION: {\"table\":\"TÊN_BẢNG\",\"action\":\"RAPID_CRUD\",\"level\":\"C\",\"data\":{\"query\":\"CÂU_LỆNH_SQL\"}}]
2. Nếu cần dữ liệu để phân tích:
   - TỰ ĐỘNG lấy dữ liệu bằng cách xuất: [READ_DB: SELECT * FROM ...]
3. Trả lời súc tích, chuyên nghiệp và nồng hậu.
4. Luôn yêu cầu phê duyệt cho các lệnh có rủi ro cao (Level A, S).

TRẠNG THÁI HỆ THỐNG HIỆN TẠI:
";
    
    // Nạp một chút context CSDL
    $total_rooms = $db->query("SELECT count(*) FROM rooms")->fetchColumn();
    $available_rooms = $db->query("SELECT count(*) FROM rooms WHERE status='available'")->fetchColumn();
    $bi_context = "- Tổng số phòng: {$total_rooms}\n- Phòng đang trống: {$available_rooms}\n";
    
    $full_system_prompt = $system_prompt . $bi_context;

    // 2. Sử dụng Engine Lai (Hybrid) từ ai-helper.php
    // Lưu ý: stream_ai_reply trong ai-helper dùng echo data:, ở đây admin chat đang dùng JSON sync
    // Nên ta sẽ tạo một bản sync hoặc giả lập để lấy kết quả cuối cùng.
    
    $provider = get_active_ai_provider();
    $bot_reply = "";
    
    if ($provider === 'qwen') {
        $api_key = get_active_qwen_key();
        $model = get_active_qwen_model();
        $base_url = get_active_ai_base_url();
        
        // Đảm bảo URL kết thúc bằng /chat/completions
        $url = $base_url;
        if (strpos($url, '/chat/completions') === false) {
            $url = rtrim($url, '/') . "/chat/completions";
        }

        $data = [
            "model" => $model,
            "messages" => [
                ["role" => "system", "content" => $full_system_prompt],
                ["role" => "user", "content" => $user_message]
            ],
            "stream" => false // Admin chat hiện tại xử lý đồng bộ (Sync)
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $json = json_decode($res, true);
            $bot_reply = $json['choices'][0]['message']['content'] ?? "";
        } else {
            throw new Exception("Qwen API Error (Code: $http_code): " . $res);
        }
    } else {
        // Fallback Gemini Sync (Dùng logic cũ nhưng đã được bọc lại)
        $api_key = get_active_gemini_key();
        $model = env('AI_MODEL', 'gemini-2.0-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $api_key;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                "contents" => [["role" => "user", "parts" => [["text" => $full_system_prompt . "\n\nSếp: " . $user_message]]]]
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
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
    }

    // 3. Xử lý [READ_DB] nếu có
    if (preg_match('/\[READ_DB:\s*(.*?)\]/s', $bot_reply, $matches)) {
        $read_sql = trim($matches[1], " \t\n\r\0\x0B\"'");
        if (stripos($read_sql, 'SELECT') === 0) {
            $stmtRead = $db->query($read_sql);
            $read_data = $stmtRead->fetchAll(PDO::FETCH_ASSOC);
            
            // Hỏi lại AI lần 2 với dữ liệu đã đọc (Simplified)
            $bot_reply .= "\n\n(Đã tự động truy vấn dữ liệu: " . count($read_data) . " bản ghi được tìm thấy. Bạn có thể yêu cầu tôi phân tích số liệu này.)";
        }
    }

    log_key_usage($provider === 'qwen' ? 'qwen' : get_active_key_index(), 500, 'admin');

    ob_clean();
    echo json_encode([
        'success' => true,
        'reply' => $bot_reply,
        'provider' => $provider,
        'key_info' => $provider === 'qwen' ? "Qwen ($model)" : "Gemini ($model)",
        'tokens' => 0,
        'key_idx' => get_active_key_index(),
        'stats' => get_key_usage_stats()
    ]);

} catch (\Throwable $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
