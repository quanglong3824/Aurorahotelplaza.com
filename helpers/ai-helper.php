<?php
/**
 * Trợ lý ảo AI - Aurora Hotel Plaza (Qwen Exclusive Version)
 * ==========================================================
 */

require_once __DIR__ . '/api_key_manager.php';

// Đảm bảo buffering tắt hoàn toàn cho SSE
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');

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
 * Tool definitions cho Qwen
 */
function get_ai_tools()
{
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

/**
 * Xử lý gọi tool run_sql
 */
function handle_tool_call($functionCall, $db)
{
    $name = $functionCall['function']['name'] ?? '';
    $args = json_decode($functionCall['function']['arguments'] ?? '{}', true);

    if ($name === 'run_sql' && isset($args['sql'])) {
        $sql = $args['sql'];
        if (preg_match('/^\s*(DROP|DELETE|TRUNCATE|ALTER|GRANT|REVOKE)/i', $sql)) {
            return ["error" => "SQL denied for security reasons."];
        }

        try {
            $stmt = $db->query($sql);
            if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $sql)) {
                return ["result" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            } else {
                return ["ok" => true, "affected_rows" => $stmt->rowCount(), "last_id" => $db->lastInsertId()];
            }
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
    return ["error" => "Tool not found."];
}

/**
 * Stream câu trả lời từ AI (SSE) - Qwen Exclusive
 */
function stream_ai_reply($user_message, $db, $conv_id = 0)
{
    return stream_qwen_reply($user_message, $db, $conv_id);
}

/**
 * Stream Qwen Logic
 */
function stream_qwen_reply($user_message, $db, $conv_id)
{
    $api_key = get_active_qwen_key();
    $model = get_active_qwen_model();
    if (empty($api_key)) return "Lỗi: Chưa cấu hình Qwen API Key.";

    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    $messages = [["role" => "system", "content" => $system_prompt], ["role" => "user", "content" => $user_message]];
    $tools = get_ai_tools();

    $full_response_text = "";

    for ($i = 0; $i < 3; $i++) {
        $data = ["model" => $model, "messages" => $messages, "stream" => true, "tools" => $tools, "temperature" => 0.2];
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
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 && empty($full_response_text)) return "Lỗi: API Qwen trả về mã lỗi " . $http_code;

        if (!empty($full_response_text)) {
            log_key_usage('qwen', strlen($full_response_text) / 4, (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'admin' : 'client');
        }

        if (!empty($tool_calls)) {
            $messages[] = ["role" => "assistant", "content" => $full_response_text, "tool_calls" => array_values($tool_calls)];
            foreach ($tool_calls as $tc) {
                echo "data: " . json_encode(["status" => "running_tool", "tool" => $tc['function']['name']]) . "\n\n";
                if (ob_get_level() > 0) ob_flush(); flush();
                $result = handle_tool_call($tc, $db);
                $messages[] = ["role" => "tool", "tool_call_id" => $tc['id'], "name" => $tc['function']['name'], "content" => json_encode($result)];
            }
            $tool_calls = [];
        } else break;
    }
    return $full_response_text;
}

/**
 * Sync Reply - Qwen Exclusive
 */
function generate_ai_reply($user_message, $db, $conv_id = 0)
{
    return generate_qwen_reply_sync($user_message, $db, $conv_id);
}

function generate_qwen_reply_sync($user_message, $db, $conv_id)
{
    $api_key = get_active_qwen_key();
    $model = get_active_qwen_model();
    if (empty($api_key)) return "Lỗi: Chưa cấu hình Qwen.";
    $messages = [["role" => "system", "content" => get_aurora_system_prompt($db, $conv_id)], ["role" => "user", "content" => $user_message]];
    for ($i = 0; $i < 3; $i++) {
        $ch = curl_init("https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["model" => $model, "messages" => $messages, "tools" => get_ai_tools()]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = json_decode(curl_exec($ch), true); curl_close($ch);
        if (!isset($res['choices'][0]['message'])) break;
        $msg = $res['choices'][0]['message'];
        if (isset($msg['tool_calls'])) {
            $messages[] = $msg;
            foreach ($msg['tool_calls'] as $tc) {
                $messages[] = ["role" => "tool", "tool_call_id" => $tc['id'], "name" => $tc['function']['name'], "content" => json_encode(handle_tool_call($tc, $db))];
            }
        } else return $msg['content'];
    }
    return "AI hiện bận, vui lòng thử lại sau.";
}

// Dummy functions for compatibility
function stream_gemini_reply($msg, $db, $id) { return stream_qwen_reply($msg, $db, $id); }
function generate_gemini_reply_sync($msg, $db, $id) { return generate_qwen_reply_sync($msg, $db, $id); }
