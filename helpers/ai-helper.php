<?php
/**
 * Aurora AI Helper - Xử lý chat AI với Google Gemini
 * 
 * Các hàm chính:
 * - stream_gemini_reply(): Stream phản hồi từ Gemini API
 * - get_aurora_system_prompt(): Lấy prompt hệ thống cho Aurora AI
 * - process_tool_call_after_stream(): Xử lý tool calls (nếu có)
 */

require_once __DIR__ . '/api_key_manager.php';

/**
 * Lấy prompt hệ thống cho Aurora AI Chat
 */
function get_aurora_system_prompt($db, $conv_id = null)
{
    $prompt = "Bạn là Aurora AI - Trợ lý ảo của Aurora Hotel Plaza.
    
THÔNG TIN KHÁCH SẠN:
- Tên: Aurora Hotel Plaza
- Địa chỉ: Biên Hòa, Đồng Nai, Việt Nam
- Phong cách: Indochine (Đông Dương) sang trọng
- Đối tượng: Khách du lịch, công tác, gia đình

CHÍNH SÁCH:
- Check-in: 14:00 | Check-out: 12:00
- Trẻ em: Dưới 1m20 miễn phí (không giường), 1m20-1m40 phụ thu 50%, trên 1m40 tính người lớn
- Hủy phòng: Miễn phí trước 24h, phí 100% sau đó

CÁC LOẠI PHÒNG:
1. Studio Apartment - ~45m², 2-3 người, giá từ 800.000đ/đêm
2. Family Apartment - ~65m², 3-4 người, giá từ 1.200.000đ/đêm
3. Premium Studio - ~50m², 2-3 người, giá từ 1.000.000đ/đêm
4. Premium Family - ~70m², 4-5 người, giá từ 1.500.000đ/đêm
5. Classical Room - ~40m², 2 người, giá từ 700.000đ/đêm
6. Indochine Room - ~55m², 2-3 người, giá từ 1.100.000đ/đêm

TIỆN ÍCH:
- WiFi tốc độ cao miễn phí
- Bãi đậu xe miễn phí
- Bữa sáng buffet (tùy phòng)
- Hồ bơi vô cực
- Phòng gym
- Nhà hàng Aurora
- Quầy lễ tân 24/7

HƯỚNG DẪN ĐẶT PHÒNG:
1. Hỏi ngày check-in, check-out
2. Hỏi số người lớn, trẻ em (kèm chiều cao)
3. Gợi ý phòng phù hợp
4. Khi khách xác nhận, trả về tag [BOOK_NOW_BTN: slug=xxx, name=xxx, cin=YYYY-MM-DD, cout=YYYY-MM-DD]

CÁC TAG ĐẶC BIỆT CÓ THỂ DÙNG:
- [BOOK_NOW_BTN: slug=xxx, name=xxx, cin=YYYY-MM-DD, cout=YYYY-MM-DD] - Nút đặt phòng
- [BOOK_NOW_BTN_SUCCESS: booking_code=xxx, booking_id=xxx] - Hiển thị sau khi đặt thành công
- [VIEW_QR_BTN: code=xxx, id=xxx] - Nút xem QR code booking

NGÔN NGỮ:
- Trả lời bằng tiếng Việt tự nhiên, thân thiện
- Xưng hô: 'Bạn' hoặc 'Anh/Chị' với khách
- Tự xưng: 'Em' hoặc 'Aurora AI'

LƯU Ý:
- Không bịa đặt thông tin không có thật
- Nếu không biết, hướng dẫn khách liên hệ lễ tân: 0901 234 567
- Luôn hỏi đầy đủ thông tin trước khi gợi ý đặt phòng";

    // Nếu có conv_id, có thể thêm thông tin từ DB
    if ($conv_id && $db) {
        try {
            $stmt = $db->prepare("
                SELECT c.booking_id, b.booking_code, b.check_in_date, b.check_out_date,
                       u.full_name, u.phone, u.email
                FROM chat_conversations c
                LEFT JOIN bookings b ON c.booking_id = b.booking_id
                LEFT JOIN users u ON c.customer_id = u.user_id
                WHERE c.conversation_id = ?
            ");
            $stmt->execute([$conv_id]);
            $info = $stmt->fetch();

            if ($info && $info['booking_id']) {
                $prompt .= "\n\nTHÔNG TIN BOOKING LIÊN KẾT:\n";
                $prompt .= "- Mã đặt: " . ($info['booking_code'] ?? 'N/A') . "\n";
                $prompt .= "- Check-in: " . ($info['check_in_date'] ?? 'N/A') . "\n";
                $prompt .= "- Check-out: " . ($info['check_out_date'] ?? 'N/A') . "\n";
            }

            if ($info && $info['full_name']) {
                $prompt .= "\nTHÔNG TIN KHÁCH:\n";
                $prompt .= "- Tên: " . $info['full_name'] . "\n";
                if ($info['phone'])
                    $prompt .= "- SĐT: " . $info['phone'] . "\n";
                if ($info['email'])
                    $prompt .= "- Email: " . $info['email'] . "\n";
            }
        } catch (Exception $e) {
            // Bỏ qua lỗi DB
        }
    }

    return $prompt;
}

/**
 * Stream phản hồi từ Google Gemini sử dụng google-gemini-php/client
 */
function stream_gemini_reply($user_message, $db, $conv_id, &$history = [], $turn = 1)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Chưa cấu hình Gemini API Key. Vui lòng liên hệ quản trị viên."]) . "\n\n";
        return "";
    }

    $model_name = env('AI_MODEL', 'gemini-2.0-flash');
    $system_prompt = get_aurora_system_prompt($db, $conv_id);

    $gemini_hist = "";
    foreach ($history as $msg) {
        $prefix = ($msg['role'] == "user") ? "Khách: " : "Aurora AI: ";
        $gemini_hist .= $prefix . $msg['content'] . "\n\n";
    }

    $full_prompt = $system_prompt . "\n\n" . $gemini_hist . "Khách: " . $user_message;

    try {
        $client = new \Gemini\Client($api_key);
        $response = $client->generativeModel($model_name)->streamGenerateContent($full_prompt);

        $full_response_text = "";
        $is_tool_call = false;
        $is_decided = false;

        foreach ($response as $res) {
            $text = $res->text();
            $full_response_text .= $text;

            if (!$is_decided) {
                if (strlen($full_response_text) >= 1) {
                    if ($full_response_text[0] !== '[') {
                        $is_decided = true;
                        echo "data: " . json_encode(["text" => $full_response_text]) . "\n\n";
                        if (ob_get_level() > 0)
                            ob_flush();
                        flush();
                    } else if (strlen($full_response_text) >= 6) {
                        if (
                            strpos($full_response_text, '[TOOL_') === 0 ||
                            strpos($full_response_text, '[BOOK_') === 0 ||
                            strpos($full_response_text, '[VIEW_') === 0
                        ) {
                            $is_tool_call = true;
                        }
                        $is_decided = true;
                        if (!$is_tool_call) {
                            echo "data: " . json_encode(["text" => $full_response_text]) . "\n\n";
                            if (ob_get_level() > 0)
                                ob_flush();
                            flush();
                        }
                    }
                }
            } else {
                if (!$is_tool_call) {
                    echo "data: " . json_encode(["text" => $text]) . "\n\n";
                    if (ob_get_level() > 0)
                        ob_flush();
                    flush();
                }
            }
        }

        // Tool call handling - chỉ xử lý nếu là tool call thực sự
        if ($is_tool_call && $turn <= 2) {
            $tool_result = process_tool_call_after_stream($full_response_text, $user_message, $db, $conv_id, $history, $turn);
            if ($tool_result !== null) {
                return $tool_result;
            }
        }

        return $full_response_text;

    } catch (Exception $e) {
        // Xử lý lỗi rate limit (429)
        $error_msg = $e->getMessage();
        if (strpos($error_msg, '429') !== false || strpos($error_msg, 'quota') !== false) {
            // Thử rotate key và retry
            if (rotate_gemini_key()) {
                return stream_gemini_reply($user_message, $db, $conv_id, $history, $turn + 1);
            }
            echo "data: " . json_encode(["error" => "Hệ thống đang quá tải. Vui lòng thử lại sau ít giây."]) . "\n\n";
        } else {
            echo "data: " . json_encode(["error" => "Xin lỗi, Aurora đang gặp sự cố kết nối. Vui lòng thử lại sau."]) . "\n\n";
        }
        error_log("Gemini API Error: " . $e->getMessage());
        return "";
    }
}

/**
 * Xử lý tool calls sau khi stream xong
 * Hiện tại chỉ xử lý các tag đặc biệt như [BOOK_NOW_BTN]
 */
function process_tool_call_after_stream($response, $user_message, $db, $conv_id, &$history, $turn)
{
    // Kiểm tra xem có phải tool call không
    if (strpos($response, '[TOOL_') === 0) {
        // Tool call handling - có thể mở rộng sau
        return null;
    }

    // Nếu là booking button, giữ nguyên để client-side xử lý
    if (
        strpos($response, '[BOOK_NOW_BTN') !== false ||
        strpos($response, '[VIEW_QR_BTN') !== false ||
        strpos($response, '[BOOK_NOW_BTN_SUCCESS') !== false
    ) {
        return $response;
    }

    return null;
}

/**
 * Gọi AI đồng bộ (không stream) - Dùng cho admin AI
 */
function call_ai_sync($message, $db, $conv_id = null, $system_prompt = null)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        return "Lỗi: Chưa cấu hình API Key";
    }

    $model_name = env('AI_MODEL', 'gemini-2.0-flash');

    if ($system_prompt === null) {
        $system_prompt = get_aurora_system_prompt($db, $conv_id);
    }

    try {
        $client = new \Gemini\Client($api_key);
        $response = $client->generativeModel($model_name)->generateContent($system_prompt . "\n\n" . $message);
        return $response->text();
    } catch (Exception $e) {
        error_log("AI Sync Error: " . $e->getMessage());
        return "Xin lỗi, hệ thống AI đang gặp sự cố: " . $e->getMessage();
    }
}