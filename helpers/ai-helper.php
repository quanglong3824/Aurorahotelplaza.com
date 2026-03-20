<?php
/**
 * Trợ lý ảo AI - Xử lý gọi API Lễ tân (Version 2.0 - Streaming & Tool Optimization)
 * ==============================================================================
 */

// Model mặc định cho tốc độ và hiệu năng
if (!defined('AI_MODEL')) define('AI_MODEL', 'gemini-2.0-flash');

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
    if ($functionCall['name'] === 'run_sql' && isset($functionCall['args']['sql'])) {
        $sql = $functionCall['args']['sql'];
        
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
 * HÀM MỚI: Stream câu trả lời từ AI (SSE)
 */
function stream_ai_reply($user_message, $db, $conv_id = 0)
{
    require_once __DIR__ . '/api_key_manager.php';
    $api_key = get_active_gemini_key();
    
    if (empty($api_key)) {
        echo "data: " . json_encode(["text" => "Lỗi: Chưa cấu hình API Key."]) . "\n\n";
        return;
    }

    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    $contents = [
        ["role" => "user", "parts" => [["text" => $system_prompt . "\n\nKhách: " . $user_message]]]
    ];
    $tools = get_ai_tools();

    $max_iterations = 3;
    $full_response_text = "";

    for ($i = 0; $i < $max_iterations; $i++) {
        $data = [
            "contents" => $contents,
            "tools" => $tools,
            "generationConfig" => [
                "temperature" => 0.2,
                "maxOutputTokens" => 1024,
            ]
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . AI_MODEL . ":streamGenerateContent?alt=sse&key=" . $api_key;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $buffer = ""; // Biến đệm để xử lý dữ liệu SSE bị cắt đoạn
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$full_response_text, &$contents, &$functionCall, &$buffer) {
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
                                    if (ob_get_level() > 0) ob_flush();
                                    flush();
                                }
                                if (isset($part['functionCall'])) {
                                    $functionCall = $part['functionCall'];
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

        if (isset($functionCall)) {
            $contents[] = ["role" => "model", "parts" => [["functionCall" => $functionCall]]];
            $tool_result = handle_tool_call($functionCall, $db);
            
            echo "data: " . json_encode(["status" => "running_tool", "tool" => $functionCall['name']]) . "\n\n";
            if (ob_get_level() > 0) ob_flush(); flush();

            $contents[] = [
                "role" => "user",
                "parts" => [["functionResponse" => ["name" => $functionCall['name'], "response" => ["content" => $tool_result]]]]
            ];
            unset($functionCall);
        } else {
            break;
        }
    }

    return $full_response_text;
}

/**
 * Hàm cũ (Legacy) - Giữ lại để tương thích ngược nhưng gọi logic mới
 */
function generate_ai_reply($user_message, $db, $conv_id = 0)
{
    require_once __DIR__ . '/api_key_manager.php';
    $api_key = get_active_gemini_key();
    if (empty($api_key)) return "Lỗi cấu hình AI.";

    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    $contents = [["role" => "user", "parts" => [["text" => $system_prompt . "\n\nKhách: " . $user_message]]]];
    $tools = get_ai_tools();

    $max_iterations = 3;
    $final_text = "";

    for ($i = 0; $i < $max_iterations; $i++) {
        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/" . AI_MODEL . ":generateContent?key=" . $api_key);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["contents" => $contents, "tools" => $tools]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if (!isset($result['candidates'][0]['content']['parts'])) break;

        $parts = $result['candidates'][0]['content']['parts'];
        $found_fc = false;
        foreach ($parts as $part) {
            if (isset($part['text'])) $final_text .= $part['text'];
            if (isset($part['functionCall'])) {
                $found_fc = true;
                $fc = $part['functionCall'];
            }
        }

        if ($found_fc) {
            $contents[] = ["role" => "model", "parts" => [["functionCall" => $fc]]];
            $contents[] = ["role" => "user", "parts" => [["functionResponse" => ["name" => $fc['name'], "response" => ["content" => handle_tool_call($fc, $db)]]]]];
        } else {
            break;
        }
    }
    return $final_text ?: "AI hiện chưa có phản hồi, vui lòng thử lại sau.";
}
