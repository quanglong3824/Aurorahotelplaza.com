<?php
/**
 * Trợ lý ảo AI - Xử lý gọi API Lễ tân (Version 2.5 - Multi-Provider: Gemini & Qwen)
 * ==============================================================================
 */

require_once __DIR__ . '/api_key_manager.php';

// Model mặc định dựa trên Provider
if (!defined('AI_PROVIDER')) define('AI_PROVIDER', get_active_ai_provider());

/**
 * Lấy System Prompt tối ưu cho Aurora AI
 */
function get_aurora_system_prompt($db, $conv_id = 0)
{
    $current_date = date('d/m/Y', time() + 7 * 3600);
    $current_time = date('H:i', time() + 7 * 3600);
    
    $history_context = "";
    if ($db && $conv_id > 0) {
        try {
            $stmtH = $db->prepare("
                SELECT sender_type, message 
                FROM chat_messages 
                WHERE conversation_id = ? 
                  AND message_type = 'text' 
                  AND is_internal = 0
                ORDER BY message_id DESC 
                LIMIT 10
            ");
            $stmtH->execute([$conv_id]);
            $rows = $stmtH->fetchAll(PDO::FETCH_ASSOC);
            $rows = array_reverse($rows);

            if (count($rows) > 0) {
                $history_context .= "\n[LỊCH SỬ TRÒ CHUYỆN GẦN NHẤT]\n";
                foreach ($rows as $r) {
                    $roleName = ($r['sender_type'] === 'customer') ? 'Khách' : (($r['sender_type'] === 'bot') ? 'AI' : 'Lễ tân');
                    $history_context .= "{$roleName}: {$r['message']}\n";
                }
                $history_context .= "[KẾT THÚC LỊCH SỬ]\n";
            }
        } catch (Exception $e) {}
    }

    return "Bạn là Aurora - AI lễ tân 5 sao của Aurora Hotel Plaza. Giới tính: Nữ. Phong cách: Thân thiện, chuyên nghiệp, nhanh nhẹn.
Ngày/giờ hiện tại: {$current_date} {$current_time} (GMT+7).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
[THÔNG TIN KHÁCH SẠN]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• Tên: Aurora Hotel Plaza (4 Sao) | 253 Phạm Văn Thuận, Biên Hòa, Đồng Nai
• Hotline: 0251 3918 888 | Check-in: 14:00 | Check-out: 12:00
• VAT 8% + Phí dịch vụ 5% đã bao gồm trong giá phòng niêm yết.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
[DANH MỤC PHÒNG & GIÁ]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. Deluxe (slug: deluxe): 32m², 1 King. Giá: 1.400.000đ (Đơn) | 1.600.000đ (Đôi)
2. Premium Double (slug: premium-deluxe): 48m², 1 Super King. Giá: 1.700.000đ | 1.900.000đ
3. Premium Twin (slug: premium-twin): 48m², 2 đơn. Giá: 1.700.000đ | 1.900.000đ
4. Aurora Studio/VIP (slug: vip-suite): 54m², Jacuzzi. Giá: 2.200.000đ | 2.300.000đ
* Căn hộ (ID 5-13): Phải liên hệ để báo giá (booking_type=inquiry).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
[QUY TRÌNH ĐẶT PHÒNG (Phòng 1-4)]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. Hỏi: Ngày đến? Ngày đi? Số người?
2. SELECT room_types + rooms để kiểm tra giá và phòng trống.
3. Tổng hợp chi tiết (Loại phòng, Ngày, Tổng tiền) và hỏi XÁC NHẬN.
4. Sau khi khách đồng ý: Gọi `run_sql` để INSERT vào bảng `bookings`.
5. Xuất nút: [BOOK_NOW_BTN: slug=..., name=..., cin=..., cout=...]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
[DB SCHEMA]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
- rooms: room_id, room_type_id, room_number, floor, status(available|occupied|cleaning|maintenance)
- room_types: room_type_id, type_name, slug, base_price, weekend_price, holiday_price, booking_type
- bookings: booking_id, booking_code, room_type_id, check_in_date, check_out_date, total_amount, guest_name, guest_phone, status
- promotions: promotion_code, discount_value, status, end_date
- contact_submissions: name, email, phone, subject, message (Dùng cho yêu cầu căn hộ/hủy phòng)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
[QUY TẮC QUAN TRỌNG]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. LUÔN dùng `run_sql` để lấy dữ liệu thực tế. KHÔNG bịa số liệu.
2. Tuyệt đối KHÔNG DELETE/DROP. Chỉ SELECT và INSERT (bookings, contact_submissions).
3. Trả lời đúng ngôn ngữ khách đang dùng.
4. Hiển thị giá định dạng: 1,600,000 VND.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
[UI COMPONENTS]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
- Nút đặt phòng: [BOOK_NOW_BTN: slug=deluxe, name=Phòng Deluxe, cin=12/03/2026, cout=13/03/2026]
- Nút link: [LINK_BTN: name=Xem ảnh phòng, url=/gallery]
- Nút QR: [VIEW_QR_BTN: code=BKA123, id=456]

{$history_context}";
}

/**
 * Tool definitions cho Gemini/Qwen
 */
function get_ai_tools($provider = 'gemini')
{
    if ($provider === 'gemini') {
        return [
            [
                "functionDeclarations" => [
                    [
                        "name" => "run_sql",
                        "description" => "Truy vấn hoặc cập nhật CSDL khách sạn (SELECT/INSERT).",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "sql" => ["type" => "STRING", "description" => "Câu lệnh SQL MySQL hợp lệ."]
                            ],
                            "required" => ["sql"]
                        ]
                    ]
                ]
            ]
        ];
    } else {
        // Định dạng OpenAI compatible cho Qwen
        return [
            [
                "type" => "function",
                "function" => [
                    "name" => "run_sql",
                    "description" => "Truy vấn hoặc cập nhật CSDL khách sạn (SELECT/INSERT).",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "sql" => ["type" => "string", "description" => "Câu lệnh SQL MySQL hợp lệ."]
                        ],
                        "required" => ["sql"]
                    ]
                ]
            ]
        ];
    }
}

/**
 * Xử lý gọi tool run_sql
 */
function handle_tool_call($functionCall, $db)
{
    $name = isset($functionCall['name']) ? $functionCall['name'] : ($functionCall['function']['name'] ?? '');
    $args = isset($functionCall['args']) ? $functionCall['args'] : (json_decode($functionCall['function']['arguments'] ?? '{}', true));

    if ($name === 'run_sql' && isset($args['sql'])) {
        $sql = $args['sql'];
        
        // Security check
        if (preg_match('/^\s*(DROP|DELETE|TRUNCATE|ALTER|GRANT|REVOKE)/i', $sql)) {
            return ["error" => "Câu lệnh SQL bị từ chối vì lý do bảo mật."];
        }

        try {
            $stmt = $db->query($sql);
            if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $sql)) {
                $db_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return ["result" => $db_result];
            } else {
                return ["ok" => true, "affected_rows" => $stmt->rowCount(), "last_id" => $db->lastInsertId()];
            }
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
    return ["error" => "Công cụ không tồn tại."];
}

/**
 * Stream câu trả lời từ AI (SSE) - Hỗ trợ Gemini & Qwen (DashScope)
 */
function stream_ai_reply($user_message, $db, $conv_id = 0)
{
    $provider = get_active_ai_provider();
    
    if ($provider === 'qwen') {
        return stream_qwen_reply($user_message, $db, $conv_id);
    } else {
        return stream_gemini_reply($user_message, $db, $conv_id);
    }
}

/**
 * Stream Gemini Logic
 */
function stream_gemini_reply($user_message, $db, $conv_id)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["text" => "Lỗi: Chưa cấu hình Gemini API Key."]) . "\n\n";
        return "";
    }

    $model = defined('AI_MODEL') ? AI_MODEL : 'gemini-2.0-flash';
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    $contents = [
        ["role" => "user", "parts" => [["text" => $system_prompt . "\n\nKhách: " . $user_message]]]
    ];
    $tools = get_ai_tools('gemini');

    $max_iterations = 3;
    $full_response_text = "";

    for ($i = 0; $i < $max_iterations; $i++) {
        $data = [
            "contents" => $contents,
            "tools" => $tools,
            "generationConfig" => ["temperature" => 0.2, "maxOutputTokens" => 2048]
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":streamGenerateContent?alt=sse&key=" . $api_key;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $buffer = "";
        $current_fc = null;

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$full_response_text, &$current_fc, &$buffer) {
            $buffer .= $data;
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $event = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);
                
                $lines = explode("\n", $event);
                foreach ($lines as $line) {
                    if (strpos($line, 'data: ') === 0) {
                        $jsonStr = substr($line, 6);
                        $chunk = json_decode($jsonStr, true);
                        
                        if (isset($chunk['candidates'][0]['content']['parts'])) {
                            foreach ($chunk['candidates'][0]['content']['parts'] as $part) {
                                if (isset($part['text'])) {
                                    $full_response_text .= $part['text'];
                                    echo "data: " . json_encode(["text" => $part['text']]) . "\n\n";
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
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_exec($ch);
        curl_close($ch);

        // Ghi log sử dụng Gemini
        log_key_usage(get_active_key_index(), strlen($full_response_text) / 4, (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'admin' : 'client');

        if ($current_fc) {
            $contents[] = ["role" => "model", "parts" => [["functionCall" => $current_fc]]];
            $tool_result = handle_tool_call($current_fc, $db);
            
            echo "data: " . json_encode(["status" => "running_tool", "tool" => $current_fc['name']]) . "\n\n";
            if (ob_get_level() > 0) ob_flush(); flush();

            $contents[] = [
                "role" => "user",
                "parts" => [["functionResponse" => ["name" => $current_fc['name'], "response" => ["content" => $tool_result]]]]
            ];
            $current_fc = null;
        } else {
            break;
        }
    }

    return $full_response_text;
}

/**
 * Stream Qwen Logic (DashScope OpenAI-Compatible)
 */
function stream_qwen_reply($user_message, $db, $conv_id)
{
    $api_key = get_active_qwen_key();
    $model = get_active_qwen_model();
    
    if (empty($api_key)) {
        echo "data: " . json_encode(["text" => "Lỗi: Chưa cấu hình Qwen API Key."]) . "\n\n";
        return "";
    }

    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    $messages = [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user", "content" => $user_message]
    ];
    $tools = get_ai_tools('qwen');

    $max_iterations = 3;
    $full_response_text = "";

    for ($i = 0; $i < $max_iterations; $i++) {
        $data = [
            "model" => $model,
            "messages" => $messages,
            "stream" => true,
            "tools" => $tools,
            "temperature" => 0.2
        ];

        // DashScope OpenAI Compatible Endpoint
        $url = "https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        
        $buffer = "";
        $tool_calls = [];

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$full_response_text, &$tool_calls, &$buffer) {
            $buffer .= $data;
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);
                
                if (strpos($line, 'data: ') === 0) {
                    $jsonStr = substr($line, 6);
                    if ($jsonStr === '[DONE]') break;
                    
                    $chunk = json_decode($jsonStr, true);
                    if (isset($chunk['choices'][0]['delta'])) {
                        $delta = $chunk['choices'][0]['delta'];
                        
                        if (isset($delta['content'])) {
                            $full_response_text .= $delta['content'];
                            echo "data: " . json_encode(["text" => $delta['content']]) . "\n\n";
                            if (ob_get_level() > 0) ob_flush(); flush();
                        }
                        
                        if (isset($delta['tool_calls'])) {
                            foreach ($delta['tool_calls'] as $tc) {
                                $idx = $tc['index'];
                                if (!isset($tool_calls[$idx])) $tool_calls[$idx] = ['id' => $tc['id'], 'function' => ['name' => '', 'arguments' => '']];
                                if (isset($tc['function']['name'])) $tool_calls[$idx]['function']['name'] .= $tc['function']['name'];
                                if (isset($tc['function']['arguments'])) $tool_calls[$idx]['function']['arguments'] .= $tc['function']['arguments'];
                            }
                        }
                    }
                }
            }
            return strlen($data);
        });
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_exec($ch);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Giả lập tính token (hoặc lấy từ API nếu có hỗ trợ trong stream)
        // Qwen stream không trả về usage trực tiếp trừ khi dùng cờ stream_options.
        // Ta ước tính tạm thời hoặc ghi nhận 1 request.
        log_key_usage('qwen', strlen($full_response_text) / 4, (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'admin' : 'client');

        if (!empty($tool_calls)) {
            // Qwen (OpenAI) style tool handling
            $messages[] = ["role" => "assistant", "content" => $full_response_text, "tool_calls" => array_values($tool_calls)];
            
            foreach ($tool_calls as $tc) {
                echo "data: " . json_encode(["status" => "running_tool", "tool" => $tc['function']['name']]) . "\n\n";
                if (ob_get_level() > 0) ob_flush(); flush();

                $result = handle_tool_call($tc, $db);
                $messages[] = [
                    "role" => "tool",
                    "tool_call_id" => $tc['id'],
                    "name" => $tc['function']['name'],
                    "content" => json_encode($result)
                ];
            }
            $tool_calls = [];
        } else {
            break;
        }
    }

    return $full_response_text;
}

/**
 * Hàm cũ (Legacy) - Tương thích ngược
 */
function generate_ai_reply($user_message, $db, $conv_id = 0)
{
    // Chuyển hướng sang stream nhưng không stream output (hoặc thực hiện gọi không stream)
    // Để nhanh chóng, ta có thể dùng hàm generateContent bình thường
    $provider = get_active_ai_provider();
    if ($provider === 'qwen') {
        return generate_qwen_reply_sync($user_message, $db, $conv_id);
    } else {
        return generate_gemini_reply_sync($user_message, $db, $conv_id);
    }
}

function generate_gemini_reply_sync($user_message, $db, $conv_id)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) return "Lỗi cấu hình AI.";
    $model = defined('AI_MODEL') ? AI_MODEL : 'gemini-2.0-flash';
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    $contents = [["role" => "user", "parts" => [["text" => $system_prompt . "\n\nKhách: " . $user_message]]]];
    $tools = get_ai_tools('gemini');

    for ($i = 0; $i < 3; $i++) {
        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $api_key);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["contents" => $contents, "tools" => $tools]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        if (!isset($result['candidates'][0]['content']['parts'])) break;
        $parts = $result['candidates'][0]['content']['parts'];
        $found_fc = false;
        $final_text = "";
        foreach ($parts as $part) {
            if (isset($part['text'])) $final_text .= $part['text'];
            if (isset($part['functionCall'])) { $found_fc = true; $fc = $part['functionCall']; }
        }
        if ($found_fc) {
            $contents[] = ["role" => "model", "parts" => [["functionCall" => $fc]]];
            $contents[] = ["role" => "user", "parts" => [["functionResponse" => ["name" => $fc['name'], "response" => ["content" => handle_tool_call($fc, $db)]]]]];
        } else { return $final_text; }
    }
    return "AI hiện chưa có phản hồi.";
}

function generate_qwen_reply_sync($user_message, $db, $conv_id)
{
    $api_key = get_active_qwen_key();
    $model = get_active_qwen_model();
    if (empty($api_key)) return "Lỗi cấu hình Qwen AI.";
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    $messages = [["role" => "system", "content" => $system_prompt], ["role" => "user", "content" => $user_message]];
    $tools = get_ai_tools('qwen');

    for ($i = 0; $i < 3; $i++) {
        $ch = curl_init("https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["model" => $model, "messages" => $messages, "tools" => $tools]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch); curl_close($ch);
        $result = json_decode($response, true);
        if (!isset($result['choices'][0]['message'])) break;
        $msg = $result['choices'][0]['message'];
        if (isset($msg['tool_calls'])) {
            $messages[] = $msg;
            foreach ($msg['tool_calls'] as $tc) {
                $messages[] = ["role" => "tool", "tool_call_id" => $tc['id'], "name" => $tc['function']['name'], "content" => json_encode(handle_tool_call($tc, $db))];
            }
        } else { return $msg['content']; }
    }
    return "AI hiện chưa có phản hồi.";
}
