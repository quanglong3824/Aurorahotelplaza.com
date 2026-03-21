<?php
/**
 * Trợ lý ảo AI - Aurora Hotel Plaza (Gemini Exclusive Version)
 * ==========================================================
 */

require_once __DIR__ . '/api_key_manager.php';

// Đảm bảo buffering tắt hoàn toàn cho SSE
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');

// Model mặc định cho tốc độ và hiệu năng
if (!defined('AI_MODEL')) define('AI_MODEL', 'gemini-2.0-flash');

/**
 * Lấy System Prompt tối ưu cho Aurora AI
 */
function get_aurora_system_prompt($db, $conv_id = 0)
{
    $current_date = date('m/d/Y', time() + 7 * 3600);
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
1. Deluxe (slug: deluxe): 32m², 1 King. Giá: 1.400.000 VND (Đơn) | 1.600.000 VND (Đôi)
2. Premium Double (slug: premium-deluxe): 48m², 1 Super King. Giá: 1.700.000 VND | 1.900.000 VND
3. Premium Twin (slug: premium-twin): 48m², 2 đơn. Giá: 1.700.000 VND | 1.900.000 VND
4. Aurora Studio/VIP (slug: vip-suite): 54m², Jacuzzi. Giá: 2.200.000 VND | 2.300.000 VND
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
 * Tool definitions cho Gemini
 */
function get_ai_tools()
{
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
}

/**
 * Xử lý gọi tool run_sql
 */
function handle_tool_call($functionCall, $db)
{
    $name = $functionCall['name'] ?? '';
    $args = $functionCall['args'] ?? [];

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
 * Stream câu trả lời từ AI (SSE)
 */
function stream_ai_reply($user_message, $db, $conv_id = 0)
{
    return stream_gemini_reply($user_message, $db, $conv_id);
}

/**
 * Stream Gemini Logic
 */
function stream_gemini_reply($user_message, $db, $conv_id)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) return "Lỗi: Chưa cấu hình Gemini API Key.";

    $model = defined('AI_MODEL') ? AI_MODEL : 'gemini-2.0-flash';
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    $contents = [["role" => "user", "parts" => [["text" => $system_prompt . "\n\nKhách: " . $user_message]]]];
    $tools = get_ai_tools();

    $full_response_text = "";

    for ($i = 0; $i < 3; $i++) {
        $data = ["contents" => $contents, "tools" => $tools, "generationConfig" => ["temperature" => 0.2, "maxOutputTokens" => 2048]];
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
                        $json_str = substr($line, 6);
                        $chunk = json_decode($json_str, true);
                        if (isset($chunk['candidates'][0]['content']['parts'])) {
                            foreach ($chunk['candidates'][0]['content']['parts'] as $part) {
                                if (isset($part['text'])) {
                                    $full_response_text .= $part['text'];
                                    echo "data: " . json_encode(["text" => $part['text']]) . "\n\n";
                                    if (ob_get_level() > 0) ob_flush(); flush();
                                }
                                if (isset($part['functionCall'])) $current_fc = $part['functionCall'];
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $exec_res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code !== 200) {
            // Log lỗi vào hệ thống để Admin theo dõi
            if (class_exists('AuroraErrorTracker')) {
                AuroraErrorTracker::capture('ai_api_error', "Gemini API Error $http_code: $curl_error", ['model' => $model, 'key_index' => get_active_key_index()]);
            }

            // Xoay vòng key nếu gặp lỗi 429 (hết quota) hoặc 403 (key bị khóa/lộ), 400 (bad request/key invalid)
            if (in_array($http_code, [429, 403, 400, 401])) {
                mark_key_rate_limited(get_active_key_index(), 3600); // Tạm khóa key này 1h
                $new_key = rotate_gemini_key();
                if ($new_key) {
                    $api_key = $new_key;
                    continue; 
                }
            }
            
            if (empty($full_response_text)) {
                echo "data: " . json_encode(["error" => "AI hiện đang bận (Mã lỗi: $http_code). Vui lòng thử lại sau giây lát."]) . "\n\n";
                return "Error $http_code";
            }
        }

        // Ghi log
        if (!empty($full_response_text)) {
            log_key_usage(get_active_key_index(), strlen($full_response_text) / 4, (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'admin' : 'client');
        }

        if ($current_fc) {
            $contents[] = ["role" => "model", "parts" => [["functionCall" => $current_fc]]];
            $tool_result = handle_tool_call($current_fc, $db);
            echo "data: " . json_encode(["status" => "running_tool", "tool" => $current_fc['name']]) . "\n\n";
            if (ob_get_level() > 0) ob_flush(); flush();
            $contents[] = ["role" => "user", "parts" => [["functionResponse" => ["name" => $current_fc['name'], "response" => ["content" => $tool_result]]]]];
            $current_fc = null;
        } else break;
    }
    return $full_response_text;
}

/**
 * Sync Reply
 */
function generate_ai_reply($user_message, $db, $conv_id = 0)
{
    return generate_gemini_reply_sync($user_message, $db, $conv_id);
}

function generate_gemini_reply_sync($user_message, $db, $conv_id)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) return "Lỗi: Chưa cấu hình Gemini.";
    $model = defined('AI_MODEL') ? AI_MODEL : 'gemini-2.0-flash';
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    $contents = [["role" => "user", "parts" => [["text" => $system_prompt . "\n\nKhách: " . $user_message]]]];
    
    for ($i = 0; $i < 3; $i++) {
        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $api_key);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["contents" => $contents, "tools" => get_ai_tools()]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = json_decode(curl_exec($ch), true); 
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            if (in_array($http_code, [429, 403, 400])) {
                $api_key = rotate_gemini_key();
                if ($api_key) continue;
            }
            break;
        }

        if (!isset($res['candidates'][0]['content']['parts'])) break;
        $final_text = ""; $fc = null;
        foreach ($res['candidates'][0]['content']['parts'] as $p) {
            if (isset($p['text'])) $final_text .= $p['text'];
            if (isset($p['functionCall'])) $fc = $p['functionCall'];
        }
        
        if ($fc) {
            $contents[] = ["role" => "model", "parts" => [["functionCall" => $fc]]];
            $contents[] = ["role" => "user", "parts" => [["functionResponse" => ["name" => $fc['name'], "response" => ["content" => handle_tool_call($fc, $db)]]]]];
        } else return $final_text;
    }
    return "AI hiện bận, vui lòng thử lại sau.";
}

// Dummy functions for backward compatibility
function stream_qwen_reply($msg, $db, $id) { return stream_gemini_reply($msg, $db, $id); }
function generate_qwen_reply_sync($msg, $db, $id) { return generate_gemini_reply_sync($msg, $db, $id); }
