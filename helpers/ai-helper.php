<?php
/**
 * Aurora Hotel Plaza - AI Chat Engine v2.1 (International Qwen & Gemini)
 * ====================================================================
 */

require_once __DIR__ . '/api_key_manager.php';

if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');

/**
 * Lấy System Prompt đầy đủ cho Aurora AI
 */
function get_aurora_system_prompt($db, $conv_id = 0)
{
    $current_date = date('d/m/Y', time() + 7 * 3600);
    $current_time = date('H:i', time() + 7 * 3600);
    
    $history_context = "";
    if ($db && $conv_id > 0) {
        try {
            $stmt = $db->prepare("SELECT sender_type, message FROM chat_messages WHERE conversation_id = ? AND message_type = 'text' AND is_internal = 0 ORDER BY message_id DESC LIMIT 10");
            $stmt->execute([$conv_id]);
            $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
            if (!empty($rows)) {
                $history_context .= "\n[LỊCH SỬ HỘI THOẠI GẦN NHẤT]\n";
                foreach ($rows as $r) {
                    $role = ($r['sender_type'] === 'customer') ? 'Khách' : 'Aurora';
                    $history_context .= "{$role}: {$r['message']}\n";
                }
                $history_context .= "[KẾT THÚC LỊCH SỬ]\n";
            }
        } catch (Exception $e) {}
    }

    $schema = "
CẤU TRÚC DỮ LIỆU ĐỂ TRỢ LÝ (AURORA) PHỤC VỤ KHÁCH CHI TIẾT:
- services: service_id, service_name, category(room_service,spa,restaurant,event,transport,laundry), price, description
- room_types: room_type_id, type_name, max_occupancy, bed_type
- rooms: room_id, room_type_id, status(available,occupied,maintenance)
- service_bookings: service_booking_id, user_id, service_id, service_date, service_time, quantity, total_price, status
- promotions: promotion_code, discount_value, description
- bookings (Hỗ trợ tra cứu đơn): booking_code, status, total_amount, payment_status, check_in_date
- contact_submissions (Yêu cầu liên hệ của khách): name, email, phone, subject, message, submission_id. (Lưu ý: Bắt buộc truyền submission_id = FLOOR(RAND() * 1000000))
";

    return "Bạn là Aurora - Trợ lý ảo lễ tân 5 sao của Aurora Hotel Plaza.
Giới tính: Nữ. Tính cách: Sang trọng, nồng hậu, tinh tế, thông minh và phục vụ tận tâm.
Bạn có quyền hạn tra cứu Database để phục vụ khách hàng.

$schema

QUY TẮC ĐẶC BIỆT KHI CẦN DATA - TỰ ĐỘNG PHỤC VỤ (DB TOOLS):
- Nếu khách yêu cầu TRA CỨU thông tin thực tế (Còn phòng không, tìm dịch vụ, giá, tra cứu lịch trình/mã đặt phòng...), bạn BẮT BUỘC CHỈ trả về duy nhất chuỗi bắt đầu bằng: [TOOL_SQL: SELECT ...]
- Nếu khách cần ĐỂ LẠI THÔNG TIN LIÊN HỆ / Yêu cầu gọi lại, tự động chèn vào bảng contact_submissions: [TOOL_SQL: INSERT INTO contact_submissions (name, email, phone, subject, message, submission_id) VALUES (...)]
- VD: [TOOL_SQL: SELECT status, total_amount FROM bookings WHERE booking_code = 'XYZ123']
- NẾU DÙNG TOOL_SQL: Bạn CHỈ in ra câu lệnh SQL (không xin chào, không in gì khác). Hệ thống sẽ tự chạy và gửi lại KẾT QUẢ HỆ THỐNG cho bạn nhìn thấy ở lần hội thoại kế tiếp.
- Ở lần gọi thứ 2 (Sau khi nhận KẾT QUẢ HỆ THỐNG): Bạn hãy đọc hệ thống trả về và viết câu trả lời cuối cùng thật chuyên nghiệp, ngọt ngào cho khách (lúc này cấm đưa ra TOOL_SQL nữa).
- Nghiêm cấm thao tác rủi ro liên quan tới password, tài khoản nội bộ. 

NHIỆM VỤ:
1. Chào đón khách nồng hậu. Bám sát yêu cầu khách đưa ra.
2. Trả lời ngắn gọn, dùng Markdown làm nổi bật thông tin quan trọng.
3. Định dạng giá: 1,500,000 VND.

Ngày giờ: {$current_date} {$current_time}.
{$history_context}";
}

/**
 * Điều phối gọi AI
 */
function stream_ai_reply($user_message, $db, $conv_id = 0)
{
    $history = [];
    $provider = get_active_ai_provider();
    if ($provider === 'qwen') {
        return stream_qwen_reply_v1($user_message, $db, $conv_id, $history, 1);
    } else {
        return stream_gemini_reply($user_message, $db, $conv_id, $history, 1);
    }
}

/**
 * Xử lý logic Tool Call (Multi-turn loop) sau khi stream
 */
function process_tool_call_after_stream($full_response_text, $user_message, $db, $conv_id, &$history, $turn, $provider) {
    if (preg_match('/\[TOOL_SQL:\s*(.*?)\]/s', $full_response_text, $matches)) {
        $sql = trim($matches[1]);
        $is_safe = true;
        $dangerous = ['DROP', 'TRUNCATE', 'ALTER', 'GRANT', 'REVOKE', 'users', 'password', 'role_permissions', 'system_settings'];
        foreach($dangerous as $d) {
            if (stripos($sql, $d) !== false) {
                $is_safe = false; break;
            }
        }
        
        if ($is_safe) {
            try {
                $stmtRead = $db->query($sql);
                if (stripos($sql, 'SELECT') === 0) {
                    $res = $stmtRead->fetchAll(PDO::FETCH_ASSOC);
                    $res_str = json_encode($res, JSON_UNESCAPED_UNICODE);
                } else {
                    $res_str = "Hành động thành công. Lời báo: Đã thay đổi " . $stmtRead->rowCount() . " dòng.";
                }
                $msg = "KẾT QUẢ HỆ THỐNG TRẢ VỀ: " . substr($res_str, 0, 5000) . "\n\nDựa vào kết quả trên, hãy trả lời khách.";
            } catch (\Throwable $e) {
                // Báo lỗi cho AI sửa
                $msg = "HỆ THỐNG BÁO LỖI SQL: " . $e->getMessage() . "\n\nGiải thích sự cố hoặc thay thế bằng từ ngữ lịch sự với khách.";
            }
        } else {
            $msg = "HỆ THỐNG: Lệnh bị từ chối do bảo mật. Hãy giải thích với khách tính năng này quá quyền hạn.";
        }
        
        $history[] = ["role" => "user", "content" => $user_message];
        $history[] = ["role" => "assistant", "content" => $full_response_text];
        
        if ($provider === 'qwen') {
            return stream_qwen_reply_v1($msg, $db, $conv_id, $history, $turn + 1);
        } else {
            return stream_gemini_reply($msg, $db, $conv_id, $history, $turn + 1);
        }
    }
    return $full_response_text;
}

/**
 * Stream phản hồi từ Qwen V1 OpenAI Compatible API
 */
function stream_qwen_reply_v1($user_message, $db, $conv_id, &$history = [], $turn = 1)
{
    $api_key = get_active_qwen_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Chưa cấu hình Qwen API Key."]) . "\n\n";
        return "";
    }

    $base_url = get_active_ai_base_url();
    $model = get_active_qwen_model();
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    
    $url = rtrim($base_url, '/');
    if (strpos($url, '/chat/completions') === false) {
        $url .= "/chat/completions";
    }
    
    $messages_payload = [["role" => "system", "content" => $system_prompt]];
    foreach($history as $msg) {
        $messages_payload[] = $msg;
    }
    $messages_payload[] = ["role" => "user", "content" => $user_message];
    
    $data = [
        "model" => $model,
        "messages" => $messages_payload,
        "stream" => true,
        "temperature" => 0.7
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
            'X-DashScope-SSE: enable',
            'Accept: text/event-stream'
        ],
        CURLOPT_TIMEOUT => 90,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TCP_KEEPALIVE => 1,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
    ]);

    if ($turn === 1) {
        echo ":" . str_repeat(' ', 2048) . "\n\n";
        if (ob_get_level() > 0) ob_flush(); flush();
    }

    $full_response_text = "";
    $buffer = "";
    $is_tool_call = false;
    $is_decided = false;

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$full_response_text, &$buffer, &$is_tool_call, &$is_decided) {
        $buffer .= $data;
        while (($pos = strpos($buffer, "\n")) !== false) {
            $line = trim(substr($buffer, 0, $pos));
            $buffer = substr($buffer, $pos + 1);
            if (empty($line)) continue;
            
            if (strpos($line, 'data: ') === 0) {
                $content = substr($line, 6);
                if ($content === "[DONE]") break;
                
                $json = json_decode($content, true);
                if (isset($json['choices'][0]['delta']['content'])) {
                    $text = $json['choices'][0]['delta']['content'];
                    $full_response_text .= $text;
                    
                    if (!$is_decided) {
                        if (strlen($full_response_text) >= 1) {
                            if ($full_response_text[0] !== '[') {
                                $is_decided = true;
                                echo "data: " . json_encode(["text" => $full_response_text]) . "\n\n";
                                if (ob_get_level() > 0) ob_flush(); flush();
                            } else if (strlen($full_response_text) >= 6) {
                                if (strpos($full_response_text, '[TOOL_') === 0) {
                                    $is_tool_call = true;
                                }
                                $is_decided = true;
                                if (!$is_tool_call) {
                                    echo "data: " . json_encode(["text" => $full_response_text]) . "\n\n";
                                    if (ob_get_level() > 0) ob_flush(); flush();
                                }
                            }
                        }
                    } else {
                        if (!$is_tool_call) {
                            echo "data: " . json_encode(["text" => $text]) . "\n\n";
                            if (ob_get_level() > 0) ob_flush(); flush();
                        }
                    }
                }
            }
        }
        return strlen($data);
    });

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code !== 200 && empty($full_response_text)) {
        echo "data: " . json_encode(["error" => "Mã lỗi: $http_code. Hệ thống AI đang bận."]) . "\n\n";
    }

    if ($is_tool_call && $turn <= 2) {
        return process_tool_call_after_stream($full_response_text, $user_message, $db, $conv_id, $history, $turn, 'qwen');
    }

    if (!empty($full_response_text) && $turn === 1) { // Chỉ tính log lần cuối để tránh lặp
        log_key_usage('qwen', strlen($full_response_text) / 2, (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'admin' : 'client');
    }
    
    return $full_response_text;
}

/**
 * Stream phản hồi từ Google Gemini
 */
function stream_gemini_reply($user_message, $db, $conv_id, &$history = [], $turn = 1)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Chưa cấu hình Gemini API Key."]) . "\n\n";
        return "";
    }

    $model = env('AI_MODEL', 'gemini-2.0-flash');
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    
    $gemini_hist = "";
    foreach($history as $msg) {
        $prefix = ($msg['role'] == "user") ? "Khởi tạo/Khách: " : "Model (Vòng trước): ";
        $gemini_hist .= $prefix . $msg['content'] . "\n\n";
    }
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?alt=sse&key={$api_key}";
    $data = [
        "contents" => [["role" => "user", "parts" => [["text" => $system_prompt . "\n\n" . $gemini_hist . "Khách/Hệ thống (Hiện tại): " . $user_message]]]],
        "generationConfig" => ["temperature" => 0.4, "maxOutputTokens" => 2048]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $full_response_text = "";
    $buffer = "";
    $is_tool_call = false;
    $is_decided = false;

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$full_response_text, &$buffer, &$is_tool_call, &$is_decided) {
        $buffer .= $data;
        while (($pos = strpos($buffer, "\n\n")) !== false) {
            $event = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + 2);
            foreach (explode("\n", $event) as $line) {
                if (strpos($line, 'data: ') === 0) {
                    $json = json_decode(substr($line, 6), true);
                    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                        $text = $json['candidates'][0]['content']['parts'][0]['text'];
                        $full_response_text .= $text;
                        
                        if (!$is_decided) {
                            if (strlen($full_response_text) >= 1) {
                                if ($full_response_text[0] !== '[') {
                                    $is_decided = true;
                                    echo "data: " . json_encode(["text" => $full_response_text]) . "\n\n";
                                    if (ob_get_level() > 0) ob_flush(); flush();
                                } else if (strlen($full_response_text) >= 6) {
                                    if (strpos($full_response_text, '[TOOL_') === 0) {
                                        $is_tool_call = true;
                                    }
                                    $is_decided = true;
                                    if (!$is_tool_call) {
                                        echo "data: " . json_encode(["text" => $full_response_text]) . "\n\n";
                                        if (ob_get_level() > 0) ob_flush(); flush();
                                    }
                                }
                            }
                        } else {
                            if (!$is_tool_call) {
                                echo "data: " . json_encode(["text" => $text]) . "\n\n";
                                if (ob_get_level() > 0) ob_flush(); flush();
                            }
                        }
                    }
                }
            }
        }
        return strlen($data);
    });

    curl_exec($ch);
    curl_close($ch);

    if ($is_tool_call && $turn <= 2) {
        return process_tool_call_after_stream($full_response_text, $user_message, $db, $conv_id, $history, $turn, 'gemini');
    }

    return $full_response_text;
}
