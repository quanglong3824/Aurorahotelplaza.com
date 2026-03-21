<?php
/**
 * Aurora Hotel Plaza - AI Chat Engine v2.0
 * ========================================
 * Quản lý hội thoại, nạp ngữ cảnh và gọi API Gemini
 */

require_once __DIR__ . '/api_key_manager.php';

// Tắt buffering để hỗ trợ Server-Sent Events (SSE)
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');

if (!defined('AI_MODEL')) define('AI_MODEL', 'gemini-2.0-flash');

/**
 * Lấy System Prompt đầy đủ cho Aurora AI
 */
function get_aurora_system_prompt($db, $conv_id = 0)
{
    $current_date = date('d/m/Y', time() + 7 * 3600);
    $current_time = date('H:i', time() + 7 * 3600);
    
    // Tự động nạp lịch sử 10 tin nhắn gần nhất để AI nhớ hội thoại
    $history_context = "";
    if ($db && $conv_id > 0) {
        try {
            $stmt = $db->prepare("
                SELECT sender_type, message 
                FROM chat_messages 
                WHERE conversation_id = ? 
                  AND message_type = 'text' 
                  AND is_internal = 0
                ORDER BY message_id DESC 
                LIMIT 10
            ");
            $stmt->execute([$conv_id]);
            $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

            if (!empty($rows)) {
                $history_context .= "\n[LỊCH SỬ HỘI THOẠI GẦN NHẤT]\n";
                foreach ($rows as $r) {
                    $role = ($r['sender_type'] === 'customer') ? 'Khách' : (($r['sender_type'] === 'bot') ? 'Aurora' : 'Nhân viên');
                    $history_context .= "{$role}: {$r['message']}\n";
                }
                $history_context .= "[KẾT THÚC LỊCH SỬ]\n";
            }
        } catch (Exception $e) {}
    }

    return "Bạn là Aurora - Trợ lý ảo lễ tân 5 sao của Aurora Hotel Plaza.
Giới tính: Nữ. Tính cách: Sang trọng, nồng hậu, tinh tế và cực kỳ chuyên nghiệp.
Vị trí: 253 Phạm Văn Thuận, Biên Hòa, Đồng Nai. Hotline: 0251 3918 888.

NHIỆM VỤ CHÍNH:
1. Chào đón khách nồng hậu (dùng tên nếu biết).
2. Tư vấn các loại phòng (Deluxe, Premium, Suite) và dịch vụ (Hồ bơi, Nhà hàng, Gym).
3. Hỗ trợ kiểm tra giá và đặt phòng trực tiếp qua công cụ run_sql.
4. Trả lời các câu hỏi về du lịch Biên Hòa, Đồng Nai một cách tự tin.

QUY TẮC PHỤC VỤ:
- Luôn giữ thái độ 'Khách hàng là thượng đế'.
- Trình bày câu trả lời ngắn gọn, có cấu trúc (dùng bullet points nếu cần).
- Sử dụng Markdown để làm nổi bật thông tin quan trọng (Giá tiền, Tên phòng).
- Định dạng giá: 1,500,000 VND.

CÔNG CỤ HỖ TRỢ:
- LUÔN dùng `run_sql` để lấy dữ liệu thực tế từ database khi khách hỏi về giá hoặc phòng trống.
- KHÔNG BAO GIỜ bịa đặt số liệu nếu database không có.
- Chỉ thực hiện SELECT (xem dữ liệu) hoặc INSERT (tạo đơn đặt phòng/liên hệ). TUYỆT ĐỐI KHÔNG DELETE/DROP.

Ngày giờ hiện tại: {$current_date} {$current_time}.
{$history_context}";
}

/**
 * Định nghĩa các Tool (Function Calling)
 */
function get_ai_tools()
{
    return [
        [
            "functionDeclarations" => [
                [
                    "name" => "run_sql",
                    "description" => "Truy vấn CSDL khách sạn để lấy giá phòng, kiểm tra phòng trống hoặc lưu đơn đặt phòng.",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "sql" => ["type" => "STRING", "description" => "Câu lệnh SQL MySQL hợp lệ (SELECT/INSERT)."]
                        ],
                        "required" => ["sql"]
                    ]
                ]
            ]
        ]
    ];
}

/**
 * Xử lý gọi Function Call
 */
function handle_tool_call($functionCall, $db)
{
    $name = $functionCall['name'] ?? '';
    $args = $functionCall['args'] ?? [];

    if ($name === 'run_sql' && isset($args['sql'])) {
        $sql = $args['sql'];
        // Chặn các lệnh nguy hiểm
        if (preg_match('/^\s*(DROP|DELETE|TRUNCATE|ALTER|UPDATE|GRANT|REVOKE)/i', $sql)) {
            return ["error" => "Xin lỗi, tôi không có quyền thực hiện thao tác xóa hoặc thay đổi cấu trúc dữ liệu."];
        }

        try {
            $stmt = $db->query($sql);
            if (preg_match('/^\s*SELECT/i', $sql)) {
                return ["result" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            } else {
                return ["ok" => true, "affected_rows" => $stmt->rowCount(), "last_id" => $db->lastInsertId()];
            }
        } catch (Exception $e) {
            return ["error" => "Lỗi truy vấn: " . $e->getMessage()];
        }
    }
    return ["error" => "Công cụ không tồn tại."];
}

/**
 * Stream phản hồi từ Gemini (SSE)
 */
function stream_ai_reply($user_message, $db, $conv_id = 0)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Hệ thống AI chưa được cấu hình Key."]) . "\n\n";
        return "";
    }

    $model = AI_MODEL;
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    $contents = [["role" => "user", "parts" => [["text" => $system_prompt . "\n\nKhách: " . $user_message]]]];
    
    $full_response_text = "";

    // Thử tối đa 3 lần nếu gặp lỗi Quota (xoay vòng key)
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?alt=sse&key={$api_key}";
        $data = [
            "contents" => $contents,
            "tools" => get_ai_tools(),
            "generationConfig" => ["temperature" => 0.4, "maxOutputTokens" => 2048]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $current_fc = null;
        $buffer = "";

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$full_response_text, &$current_fc, &$buffer) {
            $buffer .= $data;
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $event = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);
                
                $lines = explode("\n", $event);
                foreach ($lines as $line) {
                    if (strpos($line, 'data: ') === 0) {
                        $json = json_decode(substr($line, 6), true);
                        if (isset($json['candidates'][0]['content']['parts'])) {
                            foreach ($json['candidates'][0]['content']['parts'] as $part) {
                                if (isset($part['text'])) {
                                    $text = $part['text'];
                                    $full_response_text .= $text;
                                    echo "data: " . json_encode(["text" => $text]) . "\n\n";
                                    if (ob_get_level() > 0) ob_flush(); flush();
                                }
                                if (isset($part['functionCall'])) {
                                    $current_fc = $part['functionCall'];
                                }
                            }
                        }
                    }
                }
            }
            return strlen($data);
        });

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            // Ghi log sử dụng
            log_key_usage(get_active_key_index(), strlen($full_response_text) / 4, (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'admin' : 'client');
            
            // Nếu có Function Call, xử lý và gọi lại AI
            if ($current_fc) {
                $contents[] = ["role" => "model", "parts" => [["functionCall" => $current_fc]]];
                $tool_result = handle_tool_call($current_fc, $db);
                
                echo "data: " . json_encode(["status" => "processing", "tool" => $current_fc['name']]) . "\n\n";
                if (ob_get_level() > 0) ob_flush(); flush();
                
                $contents[] = ["role" => "user", "parts" => [["functionResponse" => ["name" => $current_fc['name'], "response" => ["content" => $tool_result]]]]];
                $current_fc = null;
                continue; // Gửi lại dữ liệu tool result cho AI
            }
            break; // Thành công hoàn toàn
        } else {
            // Xử lý lỗi Rate Limit
            if (in_array($http_code, [429, 403, 400, 401])) {
                mark_key_rate_limited(get_active_key_index(), 3600);
                $api_key = rotate_gemini_key();
                if ($api_key) continue;
            }
            
            if (empty($full_response_text)) {
                echo "data: " . json_encode(["error" => "Xin lỗi, Aurora hiện đang bận một chút. Vui lòng quay lại sau giây lát."]) . "\n\n";
            }
            break;
        }
    }
    
    return $full_response_text;
}
