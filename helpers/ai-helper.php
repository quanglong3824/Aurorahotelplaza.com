<?php
/**
 * Aurora AI Helper - Xử lý chat AI với Google Gemini
 * 
 * Sử dụng Gemini REST API trực tiếp qua cURL (không cần SDK)
 *
 * Các hàm chính:
 * - stream_gemini_reply(): Stream phản hồi từ Gemini API (SSE)
 * - call_gemini_sync(): Gọi Gemini đồng bộ (không stream)
 * - get_aurora_system_prompt(): Lấy prompt hệ thống cho Aurora AI
 * - process_tool_call_after_stream(): Xử lý tool calls (nếu có)
 */

require_once __DIR__ . '/api_key_manager.php';

/**
 * Gemini API Base URL
 */
define('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com/v1beta/models/');

/**
 * Lấy prompt hệ thống cho Aurora AI Chat
 */
function get_aurora_system_prompt($db, $conv_id = null, $current_message = "")
{
    $days = ["Chủ Nhật", "Thứ Hai", "Thứ Ba", "Thứ Tư", "Thứ Năm", "Thứ Sáu", "Thứ Bảy"];
    $w = date('w');
    $weekday = $days[$w];
    $currentDateTime = date('H:i:s') . " - " . $weekday . " ngày " . date('d/m/Y');

    // Kiểm tra cuối tuần (Thứ 6, 7, CN)
    $isWeekend = ($w == 5 || $w == 6 || $w == 0);
    $priceNote = $isWeekend ? "Hôm nay là CUỐI TUẦN, hãy báo giá weekend_price nếu có." : "Hôm nay là NGÀY THƯỜNG, hãy báo giá base_price.";
    
    // Logic tra cứu đơn hàng tự động từ tin nhắn
    $autoBookingInfo = "";
    if ($db && !empty($current_message)) {
        // Tìm mã BKG hoặc SĐT
        $bookingCode = "";
        if (preg_match('/(BKG|BK)[A-Z0-9]{5,}/i', $current_message, $m)) $bookingCode = strtoupper($m[0]);
        
        $phone = "";
        if (preg_match('/(0|\+84)[0-9]{8,10}/', $current_message, $m)) $phone = $m[0];

        if ($bookingCode || $phone) {
            try {
                $sql = "SELECT b.*, r.type_name FROM bookings b 
                        LEFT JOIN room_types r ON b.room_type_id = r.room_type_id 
                        WHERE b.booking_code = ? OR b.guest_phone = ? 
                        ORDER BY b.created_at DESC LIMIT 1";
                $stmtB = $db->prepare($sql);
                $stmtB->execute([$bookingCode, $phone]);
                $b = $stmtB->fetch(PDO::FETCH_ASSOC);
                
                if ($b) {
                    $autoBookingInfo = "\n[HỆ THỐNG ĐÃ TÌM THẤY ĐƠN HÀNG]:
- Mã: {$b['booking_code']}
- Khách: {$b['guest_name']} ({$b['guest_phone']})
- Loại phòng: {$b['type_name']}
- Check-in: {$b['check_in_date']} | Check-out: {$b['check_out_date']}
- Trạng thái: {$b['status']} | Thanh toán: {$b['payment_status']}
- QR Code: " . ($b['qr_code'] ? $b['qr_code'] : 'Chưa có') . "
=> HƯỚNG DẪN: Hãy xác nhận lại với khách. Nếu đơn đã confirmed, hãy dùng tag [VIEW_QR_BTN: code={$b['booking_code']}, id={$b['booking_id']}] để khách xem mã QR.";
                }
            } catch (Exception $e) {}
        }
    }

    // 1. Nhận diện khách quen
    $userInfo = "";
    if ($conv_id && $db) {
        $stmtU = $db->prepare("
            SELECT u.full_name, u.phone, c.last_message_at 
            FROM chat_conversations c 
            JOIN users u ON c.customer_id = u.user_id 
            WHERE c.conversation_id = ?
        ");
        $stmtU->execute([$conv_id]);
        $user = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userInfo = "KHÁCH QUEN: Tên là {$user['full_name']}, SĐT: {$user['phone']}. Lần cuối chat: {$user['last_message_at']}. Hãy chào mừng họ quay trở lại!";
        }
    }

    // 2. Lấy dữ liệu phòng & Ảnh thumbnail
    $rooms_info = "";
    if ($db) {
        try {
            $stmt = $db->query("SELECT type_name, base_price, price_published, max_occupancy, thumbnail, slug FROM room_types WHERE status = 'active' ORDER BY sort_order ASC");
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $baseUrl = "https://aurorahotelplaza.com/"; 
            foreach ($rooms as $r) {
                $p_pub = floatval($r['price_published']);
                $p_base = floatval($r['base_price']);
                // Lấy giá thấp nhất hiện có để tư vấn
                $displayPrice = ($p_pub > 0 && $p_pub < $p_base) ? $p_pub : $p_base;
                
                $thumb = ltrim($r['thumbnail'], '/');
                $imgUrl = $baseUrl . $thumb;
                $rooms_info .= "- {$r['type_name']}: " . number_format($displayPrice) . "đ. [IMAGE: {$imgUrl}] [slug: {$r['slug']}]\n";
            }
            
            $stmtK = $db->query("SELECT topic, content FROM bot_knowledge");
            $knowledge = $stmtK->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) { $rooms_info = "Lỗi dữ liệu."; }
    }

    $genInfo = $knowledge['general_info'] ?? "Aurora Hotel Plaza là khách sạn 4 sao.";
    $cancelPolicy = $knowledge['cancellation_policy'] ?? "Hủy miễn phí trước 24h.";
    $guestPolicy = $knowledge['extra_guest_policy'] ?? "Dưới 1m free.";

    $prompt = "Bạn là SIÊU TRỢ LÝ QUẢN GIA của Aurora Hotel Plaza.
{$userInfo}

[DỮ LIỆU THỜI GIAN THỰC]
- Bây giờ là: {$currentDateTime}.
- {$priceNote}

[KỸ NĂNG ĐẶC BIỆT]
1. HIỂN THỊ ẢNH: Khi tư vấn một loại phòng, bạn BẮT BUỘC chèn tag [IMAGE: url_anh_trong_danh_sách] để khách xem ảnh.
2. LƯU LIÊN HỆ/LEAD/PHÀN NÀN: Khi khách để lại SĐT hoặc phàn nàn về hư hỏng/dịch vụ, hãy dùng tag: [SAVE_CONTACT: name=Tên khách, phone=SĐT, msg=Nội dung]. Hệ thống sẽ tự ghi vào bảng liên hệ.
3. BÁN THÊM: Chủ động gợi ý xe đưa đón (500k), Spa hoặc Rooftop Bar nếu thấy phù hợp.

[DANH SÁCH PHÒNG & GIÁ]
{$rooms_info}

[CHÍNH SÁCH]
{$genInfo}
- Trẻ em: {$guestPolicy}
- Hủy: {$cancelPolicy}

{$autoBookingInfo}

[LOGIC PHẢN HỒI - QUAN TRỌNG]
- TRA CỨU ĐƠN HÀNG: Luôn ưu tiên dùng [HỆ THỐNG ĐÃ TÌM THẤY ĐƠN HÀNG] để xác nhận với khách.
- CỨU HỘ & PHÀN NÀN: Nếu khách báo hỏng hóc, cần kỹ thuật, hoặc để lại SĐT để liên hệ, bạn BẮT BUỘC (100%) phải dùng tag [SAVE_CONTACT: name=..., phone=..., msg=...]. KHÔNG ĐƯỢC CHỈ HỨA SUÔNG. Nếu không có tên/SĐT, hãy khéo léo hỏi khách rồi mới dùng tag.
- KHÔNG BAO GIỜ HIỆN TAG THÔ: Các tag như [SAVE_CONTACT], [IMAGE]... phải nằm trong nội dung phản hồi nhưng hệ thống sẽ ẩn nó đi, bạn cứ yên tâm sử dụng.
- XƯNG HÔ: Dạ, Aurora Hotel Plaza xin chào... Tiếng Việt lịch sự, tinh tế.";


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
 * Stream phản hồi từ Google Gemini REST API (cURL trực tiếp)
 */
function stream_gemini_reply($user_message, $db, $conv_id, &$history = [], $turn = 1)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Chưa cấu hình Gemini API Key. Vui lòng liên hệ quản trị viên."]) . "\n\n";
        return "";
    }

    $model = env('AI_MODEL', 'gemini-2.0-flash');
    $system_prompt = get_aurora_system_prompt($db, $conv_id, $user_message);

    // Build contents array cho Gemini multi-turn (giới hạn tin nhắn cuối để tiết kiệm token)
    // Cực kỳ quan trọng: Gemini yêu cầu role phải xen kẽ (user -> model -> user) và bắt đầu bằng user.
    if (empty($history) && $db && $conv_id) { $stmtH = $db->prepare("SELECT sender_type as role, message as content FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC LIMIT 50"); $stmtH->execute([$conv_id]); $db_history = $stmtH->fetchAll(PDO::FETCH_ASSOC); foreach($db_history as $h) { $history[] = ["role" => ($h["role"] === "bot" ? "model" : "user"), "content" => $h["content"]]; } }
    $full_history = $history;
    $full_history[] = ['role' => 'user', 'content' => $user_message];
    $recent_history = array_slice($full_history, -20);

    $contents = [];
    foreach ($recent_history as $msg) {
        $role = $msg['role'] === 'user' ? 'user' : 'model';
        $content = trim($msg['content']);
        if (empty($content)) continue;

        if (empty($contents)) {
            if ($role === 'model') continue; // Bắt buộc tin nhắn đầu tiên phải là user
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $content]]
            ];
        } else {
            $last_idx = count($contents) - 1;
            if ($contents[$last_idx]['role'] === $role) {
                // Nếu 2 tin nhắn liên tiếp cùng role (VD: khách nhắn 2 câu liên tục), ta gộp lại
                $contents[$last_idx]['parts'][0]['text'] .= "\n" . $content;
            } else {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $content]]
                ];
            }
        }
    }

    $request_body = [
        'system_instruction' => ['parts' => [['text' => $system_prompt]]],
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 4096
        ]
    ];

    $url = GEMINI_API_BASE . "{$model}:streamGenerateContent?alt=sse&key={$api_key}";

    $full_response_text = "";
    $is_tool_call = false;
    $is_decided = false;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 120,
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

                if (!$decoded) continue;

                // Gemini format: candidates[0].content.parts[0].text
                $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($text === null) continue;

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

    try {
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // Auto-retry cho lỗi 503/500/502 (Gemini server overload)
        if (in_array($http_code, [500, 502, 503]) && $turn <= 2) {
            error_log("Gemini API: HTTP $http_code - Auto-retry lần $turn sau 2 giây...");
            sleep(2);
            return stream_gemini_reply($user_message, $db, $conv_id, $history, $turn + 1);
        }

        if ($http_code === 429 || strpos($curl_error, '429') !== false) {
            $current_idx = get_active_key_index();
            mark_key_rate_limited($current_idx, 60);

            $new_key = rotate_gemini_key();
            if ($new_key && $turn <= 1) {
                echo "data: " . json_encode(["status" => "switching", "message" => "Đang chuyển đổi API key..."]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                return stream_gemini_reply($user_message, $db, $conv_id, $history, $turn + 1);
            }
            echo "data: " . json_encode(["error" => "Hệ thống đang quá tải. Vui lòng thử lại sau ít giây hoặc gọi Hotline 0251 3918 888."]) . "\n\n";
            return "";
        }

        if ($http_code >= 400 || $curl_error) {
            error_log("Gemini API Error: HTTP $http_code - $curl_error");
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
        error_log("Gemini API Exception: " . $e->getMessage());
        echo "data: " . json_encode(["error" => "Xin lỗi, Aurora đang gặp sự cố kết nối. Vui lòng thử lại sau."]) . "\n\n";
        return "";
    }
}

/**
 * Xử lý tool calls sau khi stream xong
 */
function process_tool_call_after_stream($response, $user_message, $db, $conv_id, &$history, $turn)
{
    if (strpos($response, '[TOOL_') === 0) {
        $tool_pattern = '/\[TOOL_(\w+):\s*([^\]]+)\]/';
        $matches = [];
        if (preg_match($tool_pattern, $response, $matches)) {
            $tool_name = $matches[1];
            $tool_params = $matches[2];

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

            $history[] = ['role' => 'user', 'content' => $user_message];
            $history[] = ['role' => 'assistant', 'content' => $response];
            $history[] = ['role' => 'user', 'content' => "Tool {$tool_name} executed. Result: " . json_encode($result)];

            $final_prompt = "Dựa trên kết quả tool: " . json_encode($result) . "\n\nHãy trả lời khách hàng một cách thân thiện và hữu ích.";
            return stream_ai_reply($final_prompt, $db, $conv_id, $history, $turn + 1);
        }
        return null;
    }

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
 * Gọi Gemini đồng bộ (không stream) - Dùng cho admin AI, error tracker
 */
function call_gemini_sync($message, $db, $conv_id = null, $system_prompt = null, $retry = 0)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        return "Lỗi: Chưa cấu hình Gemini API Key";
    }

    $model = env('AI_MODEL', 'gemini-2.0-flash');

    if ($system_prompt === null) {
        $system_prompt = get_aurora_system_prompt($db, $conv_id);
    }

    $url = GEMINI_API_BASE . "{$model}:generateContent?key={$api_key}";

    $request_body = [
        'system_instruction' => ['parts' => [['text' => $system_prompt]]],
        'contents' => [['role' => 'user', 'parts' => [['text' => $message]]]],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 4096
        ]
    ];

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request_body),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // Auto-retry cho lỗi 503/500/502 (Gemini server overload)
        if (in_array($http_code, [500, 502, 503]) && $retry < 3) {
            $wait = pow(2, $retry); // 1s, 2s, 4s
            error_log("Gemini Sync: HTTP $http_code - Auto-retry lần " . ($retry + 1) . " sau {$wait}s...");
            sleep($wait);
            return call_gemini_sync($message, $db, $conv_id, $system_prompt, $retry + 1);
        }

        if ($http_code === 429) {
            $current_idx = get_active_key_index();
            mark_key_rate_limited($current_idx, 60);
            rotate_gemini_key();
            return "Hệ thống đang quá tải, vui lòng thử lại sau.";
        }

        if ($http_code >= 400 || $curl_error) {
            error_log("Gemini Sync API Error: HTTP $http_code - $curl_error - Response: $response");
            return "Lỗi kết nối AI: " . ($curl_error ?: "HTTP $http_code");
        }

        $decoded = json_decode($response, true);

        // Gemini response format
        if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            return $decoded['candidates'][0]['content']['parts'][0]['text'];
        }

        if (isset($decoded['error'])) {
            return "Lỗi API: " . ($decoded['error']['message'] ?? $decoded['error']['code']);
        }

        return "Lỗi: Không thể parse phản hồi từ AI - " . substr($response, 0, 200);

    } catch (Exception $e) {
        error_log("Gemini Sync Exception: " . $e->getMessage());
        return "Lỗi hệ thống AI: " . $e->getMessage();
    }
}

/**
 * Stream phản hồi từ Opencode (OpenRouter/OpenAI API)
 */
function stream_opencode_reply($user_message, $db, $conv_id, &$history = [], $turn = 1)
{
    $api_key = OPENCODE_API_KEY;
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Chưa cấu hình Opencode API Key."]) . "\n\n";
        return "";
    }

    $model = OPENCODE_MODEL;
    $url = OPENCODE_API_URL . "/chat/completions";
    $system_prompt = get_aurora_system_prompt($db, $conv_id, $user_message);

    $messages = [['role' => 'system', 'content' => $system_prompt]];
    
    $full_history = $history;
    if (empty($history) && $db && $conv_id) { $stmtH = $db->prepare("SELECT sender_type as role, message as content FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC LIMIT 50"); $stmtH->execute([$conv_id]); $db_history = $stmtH->fetchAll(PDO::FETCH_ASSOC); foreach($db_history as $h) { $history[] = ["role" => ($h["role"] === "bot" ? "assistant" : "user"), "content" => $h["content"]]; } }
    $full_history[] = ['role' => 'user', 'content' => $user_message];
    $recent_history = array_slice($full_history, -20);

    foreach ($recent_history as $msg) {
        $role = $msg['role'] === 'user' ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => $msg['content']];
    }

    $request_body = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 4096,
        'stream' => true
    ];

    $full_response_text = "";
    $is_tool_call = false;
    $is_decided = false;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
            'HTTP-Referer: https://aurorahotelplaza.com',
            'X-Title: Aurora Hotel Plaza'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$full_response_text, &$is_tool_call, &$is_decided) {
            static $stream_buffer = '';
            $stream_buffer .= $data;

            $lines = explode("\n", $stream_buffer);
            $stream_buffer = array_pop($lines); // Giữ lại dòng cuối cùng nếu nó chưa hoàn chỉnh

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line === 'data: [DONE]') continue;
                if (strpos($line, 'data: ') !== 0) continue;

                $json_str = substr($line, 6);
                $decoded = json_decode($json_str, true);
                if (!$decoded) continue;

                // Ưu tiên content, bỏ qua reasoning_content để tăng tốc hiển thị
                $text = $decoded['choices'][0]['delta']['content'] ?? null;
                if ($text === null) continue;

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

    try {
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code >= 400 || $curl_error) {
            error_log("Opencode API Error: HTTP $http_code - $curl_error");
            echo "data: " . json_encode(["error" => "Xin lỗi, hệ thống AI đang bảo trì. Vui lòng thử lại sau."]) . "\n\n";
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
        return "";
    }
}

function call_opencode_sync($message, $db, $conv_id = null, $system_prompt = null, $retry = 0)
{
    $api_key = OPENCODE_API_KEY;
    if (empty($api_key)) return "Lỗi: Chưa cấu hình Opencode API Key";

    $model = OPENCODE_MODEL;
    $url = OPENCODE_API_URL . "/chat/completions";
    if ($system_prompt === null) $system_prompt = get_aurora_system_prompt($db, $conv_id);

    $messages = [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $message]
    ];

    $request_body = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 2048
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
            'HTTP-Referer: https://aurorahotelplaza.com',
            'X-Title: Aurora Hotel Plaza'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (in_array($http_code, [500, 502, 503]) && $retry < 3) {
        sleep(pow(2, $retry));
        return call_opencode_sync($message, $db, $conv_id, $system_prompt, $retry + 1);
    }

    if ($http_code >= 400) {
        return "Lỗi kết nối Opencode: HTTP $http_code - $response";
    }

    $decoded = json_decode($response, true);
    return $decoded['choices'][0]['message']['content'] ?? "Lỗi parse API";
}

/**
 * AUTO ROUTER
 */
function stream_ai_reply($user_message, $db, $conv_id, &$history = [], $turn = 1) {
    if (get_active_ai_provider() === 'opencode') {
        return stream_opencode_reply($user_message, $db, $conv_id, $history, $turn);
    }
    return stream_gemini_reply($user_message, $db, $conv_id, $history, $turn);
}

function call_ai_sync($message, $db, $conv_id = null, $system_prompt = null, $retry = 0) {
    if (get_active_ai_provider() === 'opencode') {
        return call_opencode_sync($message, $db, $conv_id, $system_prompt, $retry);
    }
    return call_gemini_sync($message, $db, $conv_id, $system_prompt, $retry);
}