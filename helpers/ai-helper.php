<?php
/**
 * Aurora AI Helper - Xử lý chat AI với nhiều AI Providers
 * 
 * Hỗ trợ:
 * - Alibaba GLM-5 (mặc định)
 * - Google Gemini
 *
 * Các hàm chính:
 * - stream_ai_reply(): Alias cho provider hiện tại
 * - stream_alibaba_reply(): Stream phản hồi từ Alibaba GLM-5 API
 * - stream_gemini_reply(): Stream phản hồi từ Gemini API
 * - get_aurora_system_prompt(): Lấy prompt hệ thống cho Aurora AI
 * - process_tool_call_after_stream(): Xử lý tool calls (nếu có)
 * - call_ai_sync(): Gọi AI đồng bộ (không stream) - dùng cho admin
 */

require_once __DIR__ . '/api_key_manager.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Main AI reply function - tự động chọn provider
 */
function stream_ai_reply($user_message, $db, $conv_id, &$history = [], $turn = 1) {
    $provider = get_active_ai_provider();
    
    if ($provider === 'alibaba') {
        return stream_alibaba_reply($user_message, $db, $conv_id, $history, $turn);
    }
    
    return stream_gemini_reply($user_message, $db, $conv_id, $history, $turn);
}

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
- Nếu không biết, hướng dẫn khách liên hệ lễ tân: 0251 3918 888
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
 * Stream phản hồi từ Alibaba GLM/Qwen API (DashScope)
 */
function stream_alibaba_reply($user_message, $db, $conv_id, &$history = [], $turn = 1)
{
    $api_key = get_active_alibaba_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Chưa cấu hình Alibaba API Key. Vui lòng liên hệ quản trị viên."]) . "\n\n";
        return "";
    }

    $api_url = defined('ALIBABA_API_URL') ? ALIBABA_API_URL : 'https://dashscope.aliyuncs.com/compatible-mode/v1';
    $model = defined('ALIBABA_MODEL') ? ALIBABA_MODEL : 'qwen-plus';
    $system_prompt = get_aurora_system_prompt($db, $conv_id);

    $messages = [
        ['role' => 'system', 'content' => $system_prompt]
    ];

    foreach ($history as $msg) {
        $messages[] = [
            'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
            'content' => $msg['content']
        ];
    }

    $messages[] = ['role' => 'user', 'content' => $user_message];

    $request_body = [
        'model' => $model,
        'messages' => $messages,
        'stream' => true,
        'temperature' => 0.7,
        'max_tokens' => 2048
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url . '/chat/completions',
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
            'X-DashScope-SSE: enable'
        ],
        CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$full_response_text, &$is_tool_call, &$is_decided) {
            static $buffer = '';
            $buffer .= $data;

            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line === 'data: [DONE]') continue;
                if (strpos($line, 'data: ') !== 0) continue;

                $json_str = substr($line, 6);
                $decoded = json_decode($json_str, true);

                if (!$decoded || !isset($decoded['choices'][0]['delta']['content'])) continue;

                $text = $decoded['choices'][0]['delta']['content'];
                $full_response_text .= $text;

                if (!$is_decided) {
                    if (strlen($full_response_text) >= 1) {
                        if ($full_response_text[0] !== '[') {
                            $is_decided = true;
                            echo "data: " . json_encode(["text" => $full_response_text]) . "\n\n";
                            if (ob_get_level() > 0) ob_flush();
                            flush();
                        } else if (strlen($full_response_text) >= 6) {
                            if (strpos($full_response_text, '[TOOL_') === 0 ||
                                strpos($full_response_text, '[BOOK_') === 0 ||
                                strpos($full_response_text, '[VIEW_') === 0) {
                                $is_tool_call = true;
                            }
                            $is_decided = true;
                            if (!$is_tool_call) {
                                echo "data: " . json_encode(["text" => $full_response_text]) . "\n\n";
                                if (ob_get_level() > 0) ob_flush();
                                flush();
                            }
                        }
                    }
                } else {
                    if (!$is_tool_call) {
                        echo "data: " . json_encode(["text" => $text]) . "\n\n";
                        if (ob_get_level() > 0) ob_flush();
                        flush();
                    }
                }
            }

            return strlen($data);
        }
    ]);

    $full_response_text = "";
    $is_tool_call = false;
    $is_decided = false;

    try {
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 429 || strpos($curl_error, '429') !== false) {
            $current_idx = get_active_alibaba_key_index();
            mark_key_rate_limited($current_idx, 60, 'alibaba');

            $new_key = rotate_alibaba_key();
            if ($new_key && $turn <= 1) {
                echo "data: " . json_encode(["status" => "switching", "message" => "Đang chuyển đổi API key..."]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                return stream_alibaba_reply($user_message, $db, $conv_id, $history, $turn + 1);
            }
            echo "data: " . json_encode(["error" => "Hệ thống đang quá tải. Vui lòng thử lại sau ít giây hoặc gọi Hotline 0251 3918 888."]) . "\n\n";
            return "";
        }

        if ($http_code >= 400 || $curl_error) {
            error_log("Alibaba API Error: HTTP $http_code - $curl_error");
            echo "data: " . json_encode(["error" => "Xin lỗi, Aurora đang gặp sự cố kết nối. Vui lòng thử lại sau."]) . "\n\n";
            return "";
        }

        if ($is_tool_call && $turn <= 2) {
            $tool_result = process_tool_call_after_stream($full_response_text, $user_message, $db, $conv_id, $history, $turn);
            if ($tool_result !== null) {
                return $tool_result;
            }
        }

        return $full_response_text;

    } catch (Exception $e) {
        error_log("Alibaba API Exception: " . $e->getMessage());
        echo "data: " . json_encode(["error" => "Xin lỗi, Aurora đang gặp sự cố kết nối. Vui lòng thử lại sau."]) . "\n\n";
        return "";
    }
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
        if (strpos($error_msg, '429') !== false || strpos($error_msg, 'quota') !== false || strpos($error_msg, 'rate') !== false) {
            // Mark current key as rate limited
            $current_idx = get_active_key_index();
            mark_key_rate_limited($current_idx, 60);

            // Thử rotate key và retry
            $new_key = rotate_gemini_key();
            if ($new_key && $turn <= 1) {
                echo "data: " . json_encode(["status" => "switching", "message" => "Đang chuyển đổi API key..."]) . "\n\n";
                if (ob_get_level() > 0) ob_flush(); flush();
                return stream_gemini_reply($user_message, $db, $conv_id, $history, $turn + 1);
            }
            echo "data: " . json_encode(["error" => "Hệ thống đang quá tải. Vui lòng thử lại sau ít giây hoặc gọi Hotline 0251 3918 888."]) . "\n\n";
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
        // Parse tool call format: [TOOL_XXX: params]
        $tool_pattern = '/\[TOOL_(\w+):\s*([^\]]+)\]/';
        $matches = [];
        if (preg_match($tool_pattern, $response, $matches)) {
            $tool_name = $matches[1];
            $tool_params = $matches[2];

            // Parse parameters
            $params = [];
            $param_pairs = explode(',', $tool_params);
            foreach ($param_pairs as $pair) {
                $pair = trim($pair);
                if (strpos($pair, '=') !== false) {
                    $kv = explode('=', $pair, 2);
                    $params[trim($kv[0])] = trim($kv[1]);
                }
            }

            $result = handle_tool_call($tool_name, $params, $db, $conv_id);

            // Add tool result to history and get AI's final response
            $history[] = ['role' => 'user', 'content' => $user_message];
            $history[] = ['role' => 'assistant', 'content' => $response];
            $history[] = ['role' => 'system', 'content' => "Tool {$tool_name} executed. Result: " . json_encode($result)];

            $final_prompt = "Dựa trên kết quả tool: " . json_encode($result) . "\n\nHãy trả lời khách hàng một cách thân thiện và hữu ích.";
            return stream_gemini_reply($final_prompt, $db, $conv_id, $history, $turn + 1);
        }
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
 * Handle tool calls
 */
function handle_tool_call($tool_name, $params, $db, $conv_id)
{
    switch ($tool_name) {
        case 'BOOK_ROOM':
            return handle_tool_book_room($params, $db, $conv_id);

        case 'GET_PRICE':
            return handle_tool_get_price($params, $db);

        case 'CHECK_AVAILABILITY':
            return handle_tool_check_availability($params, $db);

        case 'GET_BOOKING_INFO':
            return handle_tool_get_booking_info($params, $db);

        default:
            return ['success' => false, 'error' => 'Unknown tool: ' . $tool_name];
    }
}

/**
 * Tool: Đặt phòng
 */
function handle_tool_book_room($params, $db, $conv_id)
{
    $slug = $params['slug'] ?? '';
    $check_in = $params['check_in'] ?? '';
    $check_out = $params['check_out'] ?? '';

    if (!$slug || !$check_in || !$check_out) {
        return ['success' => false, 'error' => 'Thiếu thông tin đặt phòng'];
    }

    try {
        $stmt = $db->prepare("SELECT type_id, type_name, price_per_night FROM room_types WHERE slug = ? AND status = 'active'");
        $stmt->execute([$slug]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            return ['success' => false, 'error' => 'Không tìm thấy loại phòng'];
        }

        $cin = new DateTime($check_in);
        $cout = new DateTime($check_out);
        $nights = $cin->diff($cout)->days;

        if ($nights <= 0) {
            return ['success' => false, 'error' => 'Ngày check-out phải sau ngày check-in'];
        }

        $total_price = $room['price_per_night'] * $nights;
        $booking_code = 'AUR' . date('Ymd') . rand(1000, 9999);

        $stmt = $db->prepare("
            INSERT INTO bookings (room_type_id, check_in_date, check_out_date, total_price, status, created_at, booking_code)
            VALUES (?, ?, ?, ?, 'pending', NOW(), ?)
        ");
        $stmt->execute([$room['type_id'], $check_in, $check_out, $total_price, $booking_code]);

        return [
            'success' => true,
            'booking_code' => $booking_code,
            'booking_id' => $db->lastInsertId(),
            'room_name' => $room['type_name'],
            'nights' => $nights,
            'total_price' => $total_price
        ];

    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()];
    }
}

/**
 * Tool: Lấy giá phòng
 */
function handle_tool_get_price($params, $db)
{
    $room_type = $params['room_type'] ?? '';

    try {
        if ($room_type) {
            $stmt = $db->prepare("SELECT type_name, price_per_night, capacity, size_sqm FROM room_types WHERE type_name LIKE ? AND status = 'active'");
            $stmt->execute(['%' . $room_type . '%']);
        } else {
            $stmt = $db->query("SELECT type_name, price_per_night, capacity, size_sqm FROM room_types WHERE status = 'active' ORDER BY price_per_night ASC");
        }

        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'rooms' => $rooms];

    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Lỗi database'];
    }
}

/**
 * Tool: Kiểm tra phòng trống
 */
function handle_tool_check_availability($params, $db)
{
    $check_in = $params['check_in'] ?? '';
    $check_out = $params['check_out'] ?? '';

    if (!$check_in || !$check_out) {
        return ['success' => false, 'error' => 'Thiếu ngày check-in/check-out'];
    }

    try {
        $stmt = $db->query("SELECT type_id, type_name, slug, price_per_night FROM room_types WHERE status = 'active'");
        $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $available = [];
        foreach ($room_types as $rt) {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM bookings b
                JOIN rooms r ON b.room_id = r.room_id
                WHERE r.room_type_id = ?
                AND b.status NOT IN ('cancelled', 'rejected')
                AND (
                    (b.check_in_date <= ? AND b.check_out_date > ?)
                    OR (b.check_in_date < ? AND b.check_out_date >= ?)
                    OR (b.check_in_date >= ? AND b.check_out_date <= ?)
                )
            ");
            $stmt->execute([$rt['type_id'], $check_out, $check_in, $check_out, $check_in, $check_in, $check_out]);
            $booked_count = $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = ? AND status = 'available'");
            $stmt->execute([$rt['type_id']]);
            $total_rooms = $stmt->fetchColumn();

            $available_count = $total_rooms - $booked_count;

            if ($available_count > 0) {
                $available[] = [
                    'type_name' => $rt['type_name'],
                    'slug' => $rt['slug'],
                    'price_per_night' => $rt['price_per_night'],
                    'available_rooms' => $available_count
                ];
            }
        }

        return ['success' => true, 'available' => $available];

    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Lỗi database'];
    }
}

/**
 * Tool: Lấy thông tin booking
 */
function handle_tool_get_booking_info($params, $db)
{
    $booking_code = $params['booking_code'] ?? '';

    if (!$booking_code) {
        return ['success' => false, 'error' => 'Thiếu mã booking'];
    }

    try {
        $stmt = $db->prepare("
            SELECT b.booking_id, b.booking_code, b.check_in_date, b.check_out_date,
                   b.total_price, b.status, b.created_at,
                   rt.type_name as room_type
            FROM bookings b
            JOIN room_types rt ON b.room_type_id = rt.type_id
            WHERE b.booking_code = ?
        ");
        $stmt->execute([$booking_code]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            return ['success' => false, 'error' => 'Không tìm thấy booking'];
        }

        return ['success' => true, 'booking' => $booking];

    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Lỗi database'];
    }
}

/**
 * Gọi AI đồng bộ (không stream) - Dùng cho admin AI
 */
function call_ai_sync($message, $db, $conv_id = null, $system_prompt = null)
{
    $provider = get_active_ai_provider();
    
    if ($provider === 'alibaba') {
        return call_alibaba_sync($message, $db, $conv_id, $system_prompt);
    }
    
    return call_gemini_sync($message, $db, $conv_id, $system_prompt);
}

/**
 * Gọi Alibaba GLM/Qwen đồng bộ (không stream)
 */
function call_alibaba_sync($message, $db, $conv_id = null, $system_prompt = null)
{
    $api_key = get_active_alibaba_key();
    if (empty($api_key)) {
        return "Lỗi: Chưa cấu hình Alibaba API Key";
    }

    $api_url = defined('ALIBABA_API_URL') ? ALIBABA_API_URL : 'https://dashscope.aliyuncs.com/compatible-mode/v1';
    $model = defined('ALIBABA_MODEL') ? ALIBABA_MODEL : 'qwen-plus';

    if ($system_prompt === null) {
        $system_prompt = get_aurora_system_prompt($db, $conv_id);
    }

    $messages = [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $message]
    ];

    $request_body = [
        'model' => $model,
        'messages' => $messages,
        'stream' => false,
        'temperature' => 0.7,
        'max_tokens' => 2048
    ];

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url . '/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request_body),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ],
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 429) {
            $current_idx = get_active_alibaba_key_index();
            mark_key_rate_limited($current_idx, 60, 'alibaba');
            rotate_alibaba_key();
            return "Hệ thống đang quá tải, vui lòng thử lại sau.";
        }

        if ($http_code >= 400 || $curl_error) {
            error_log("Alibaba Sync API Error: HTTP $http_code - $curl_error");
            return "Lỗi kết nối AI: " . ($curl_error ?: "HTTP $http_code");
        }

        $decoded = json_decode($response, true);
        if (isset($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        }

        return "Lỗi: Không thể parse phản hồi từ AI";

    } catch (Exception $e) {
        error_log("Alibaba Sync Exception: " . $e->getMessage());
        return "Lỗi hệ thống AI: " . $e->getMessage();
    }
}

/**
 * Gọi Gemini đồng bộ (không stream)
 */
function call_gemini_sync($message, $db, $conv_id = null, $system_prompt = null)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        return "Lỗi: Chưa cấu hình Gemini API Key";
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
        error_log("Gemini Sync Error: " . $e->getMessage());

        if (strpos($e->getMessage(), '429') !== false) {
            $current_idx = get_active_key_index();
            mark_key_rate_limited($current_idx, 60, 'gemini');
            rotate_gemini_key();
        }

        return "Xin lỗi, hệ thống AI đang gặp sự cố: " . $e->getMessage();
    }
}