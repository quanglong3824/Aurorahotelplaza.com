<?php
/**
 * Trợ lý ảo AI - Xử lý gọi API Lễ tân
 * ===============================================
 */

function generate_ai_reply($user_message, $db, $conv_id = 0)
{
    require_once __DIR__ . '/api_key_manager.php';
    $api_key = get_active_gemini_key();

    if (empty($api_key)) {
        return "Xin lỗi, hệ thống chưa được cấu hình khóa API (API Key) để Trợ lý ảo hoạt động. Quý khách vui lòng cấu hình tại config/api_keys.php";
    }

    // 1. (RAG) Kéo tri thức từ Database
    $knowledge_context = "";
    $history_context = "";

    if ($db) {
        // ... (Fetch history context) ...
        try {
            if ($conv_id > 0) {
                // Lấy 8 tin nhắn gần nhất để làm Context ngữ cảnh
                $stmtH = $db->prepare("
                    SELECT sender_type, message 
                    FROM chat_messages 
                    WHERE conversation_id = ? 
                      AND message_type = 'text' 
                      AND is_internal = 0
                    ORDER BY message_id DESC 
                    LIMIT 8
                ");
                $stmtH->execute([$conv_id]);
                $rows = $stmtH->fetchAll(PDO::FETCH_ASSOC);
                $rows = array_reverse($rows);

                if (count($rows) > 1) { // Lớn hơn 1 vì dòng cuối cùng chính là user_message hiện tại
                    $history_context .= "\n[LỊCH SỬ TRÒ CHUYỆN GẦN NHẤT ĐỂ THAM KHẢO NGỮ CẢNH]\n";
                    foreach ($rows as $r) {
                        $roleName = ($r['sender_type'] === 'customer') ? 'Khách' : (($r['sender_type'] === 'bot') ? 'AI' : 'Lễ tân');
                        $history_context .= "{$roleName}: {$r['message']}\n";
                    }
                    $history_context .= "[KẾT THÚC LỊCH SỬ]\n";
                }
            }
        } catch (Exception $e) {
        }

        // Tính năng Static RAG (SQL) cho (bot_knowledge, gallery, faqs, services, system_settings, amenities, promotions, membership_tiers)
        // Đã được thay thế hoàn toàn bằng DB schema truyền trong System Prompt để AI tự gọi function `run_sql` giúp TỐI ƯU TOKEN tối đa.
    }

    // 2. Định nghĩa vai trò (System Prompt) cho Bot
    $system_prompt = "
Bạn là Aurora - Hệ thống lõi AI của Khách sạn Aurora Hotel Plaza. Nữ giới.
QUYỀN HẠN: Bạn có quyền truy cập Đọc/Ghi vào CSDL thông qua công cụ `run_sql`.

[CẤU QUY NHỎ BẢN ĐỒ CƠ SỞ DỮ LIỆU ĐỂ BẠN TRUY XUẤT/THÊM MỚI]
- `rooms`: room_id, room_type_id, room_number, status (available|occupied|cleaning|maintenance).
- `room_types`: room_type_id, type_name, slug, base_price, holiday_price, max_occupancy.
- `bookings`: booking_id, guest_name, guest_phone, room_type_id, check_in_date, check_out_date, total_amount, status.
- `room_pricing`: pricing_id, room_type_id, start_date, end_date, price (Giá tăng dịp lễ).
- `services`: service_id, service_name, category, price, short_description.
- `amenities`: amenity_id, amenity_name, status. 
- `promotions`: promotion_code, promotion_name, discount_value, start_date, end_date, status.
- `faqs`: question, answer, category.
- `bot_knowledge`: topic, content (Chính sách, quy định khách sạn).
- `gallery`: title, image_url, category.
- `membership_tiers`: tier_name, min_points, discount_percentage.
- `system_settings`: setting_key, setting_value (Thông tin ĐT, Email, Quy định).
- `reviews`: room_type_id, rating, title, comment.

[QUY TẮC VẬN HÀNH TỐI ƯU CHI PHÍ]
1. KHÔNG ĐOÁN MÒ: Nếu khách hỏi thông tin (ví dụ: 'Còn phòng không?', 'Giá phòng bao nhiêu?'), BẮT BUỘC gọi hàm `run_sql` với lệnh SELECT để lấy dữ liệu thực tế. Không trả lời dựa trên trí nhớ cũ.
2. QUYỀN THAY ĐỔI DB: Nếu khách chốt đặt phòng hoặc Admin yêu cầu đổi giá, hãy gọi `run_sql` với lệnh INSERT hoặc UPDATE tương ứng.
3. TIẾT KIỆM TOKEN: Chỉ SELECT những cột cần thiết. Tránh SELECT *.
4. NGÔN NGỮ: BẮT BUỘC nhận diện và trả lời bằng ngôn ngữ của người dùng (Tiếng Việt, Anh, Trung, Hàn, Nhật...). Tự động dịch dữ liệu từ CSDL.

[LUẬT ĐẶT PHÒNG TỰ ĐỘNG]
Khi khách muốn đặt phòng:
- Bước 1: Xin thông tin chi tiết (Ngày Check-in, Ngày Check-out, Số lượng người) nếu chat context chưa có.
- Bước 2: Gọi `run_sql` với lệnh SELECT vào `room_pricing` và `rooms`, `room_types` để kiểm tra giá theo ngày lễ và tình trạng phòng trống. Tính tổng tiền.
- Bước 3: Sau khi khách đồng ý chốt, gọi `run_sql` với lệnh INSERT vào bảng `bookings` lưu trữ.
- Bước 4: Mời khách ấn nút lấy mã đặt phòng/QR bằng cách chèn ĐÚNG CÚ PHÁP ĐOẠN MÃ sau vào CUỐI văn bản:
[BOOK_NOW_BTN: slug={Mã thao chiếu slug của phòng}, name={Tên phòng}, cin={Ngày check-in dd/mm/yyyy}, cout={Ngày check-out dd/mm/yyyy}]

[QUY TẮC HIỂN THỊ HÌNH ẢNH (QUAN TRỌNG)]
Nếu khách muốn xem ảnh phòng/không gian:
- Bước 1: Gọi `run_sql` truy vấn bảng `gallery` bằng câu lệnh dùng `LIKE` (Ví dụ: `SELECT image_url, title FROM gallery WHERE title LIKE '%phòng%'` thay vì dùng dấu `=`) để tìm kiếm ảnh linh hoạt.
- Bước 2: Hiển thị ĐÚNG chuẩn markdown: `![Mô tả title](https://aurorahotelplaza.com/2025/đường_dẫn_image_url)`. Không tự chế ra URL ảnh không tồn tại.

[QUY TẮC UI & TRUY VẤN ĐƠN HÀNG]
1. NHỮNG LINK (ĐƯỜNG DẪN) TRONG GIAO DIỆN WEB:
   - Trang chủ: `/`
   - Danh sách Phòng: `/rooms`
   - Dịch vụ & Spa: `/services`
   - Ưu đãi: `/promotions`
   - Liên hệ: `/contact`
   - Hồ sơ Cá nhân: `/profile`
   - Tra cứu đặt phòng / Đăng nhập: `/login-register`
2. CÁCH ĐIỀU HƯỚNG BẰNG NÚT: NẾU khách cần đi tới trang nào, bạn TỰ ĐỘNG sinh thêm nút liên kết đẹp mắt bằng cú pháp thẻ này ở cuối tin nhắn:
   [LINK_BTN: name=Xem Danh Sách Phòng, url=/rooms]
   (Cho phép nhúng nhiều thẻ LINK_BTN liên tiếp để tạo nhiều nút).
3. TRA CỨU ĐƠN & MÃ QR CODE: Nếu khách muốn kiểm tra trạng thái đơn đặt phòng. Yêu cầu khách cung cấp số điện thoại hoặc mã đơn (`booking_id`). Sau khi bạn chạy lệnh SELECT trong bảng `bookings` tìm thấy đơn hợp lệ. Hãy xuất thẻ xác nhận kèm nút để lấy mã QR bằng thẻ:
   [VIEW_QR_BTN: code=MãĐơnCủaKhách, id=IDcủaĐơn]

[QUY TẮC GIAO TIẾP]
- Luôn giữ thái độ chuyên nghiệp, thân thiện: 'Dạ/Vâng', 'Quý khách/Em'.
- Nếu khách hỏi những thông tin mà HOÀN TOÀN KHÔNG TỒN TẠI trong CSDL, TUYỆT ĐỐI KHÔNG ĐƯỢC BỊA ĐẶT. Hãy lịch sự xin lỗi khách.

[DỮ LIỆU KIẾN THỨC BỔ SUNG]
{$knowledge_context}
{$history_context}
    ";

    $contents = [
        ["role" => "user", "parts" => [["text" => $system_prompt . "\n\nUser: " . $user_message]]]
    ];

    $tools = [
        [
            "functionDeclarations" => [
                [
                    "name" => "run_sql",
                    "description" => "Thực thi câu lệnh SQL trực tiếp vào DB khách sạn để Lấy dữ liệu hoặc Cập nhật dữ liệu.",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "sql" => [
                                "type" => "STRING",
                                "description" => "Câu lệnh SQL chuẩn MySQL. Được cấp quyền thực thi SELECT, INSERT, UPDATE, DELETE tùy theo tình huống."
                            ]
                        ],
                        "required" => ["sql"]
                    ]
                ]
            ]
        ]
    ];

    $max_iterations = 4; // Cho phép AI suy nghĩ và gọi tối đa 4 lần Function
    $final_response = "Dạ hệ thống AI đang bận xử lý, Quý khách vui lòng đợi trong giây lát hoặc liên hệ Hotline ạ.";

    for ($i = 0; $i < $max_iterations; $i++) {
        $data = [
            "contents" => $contents,
            "tools" => $tools,
            "generationConfig" => [
                "temperature" => 0.4, // Giảm temperature để SQL chính xác hơn
                "topK" => 40,
                "topP" => 0.95,
                "maxOutputTokens" => 1024,
            ]
        ];
        $json_data = json_encode($data);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Xử lý Rate Limit (429) Của Gemini API
        if ($http_code === 429) {
            $errData = json_decode($response, true);
            $retrySeconds = 60;
            if (isset($errData['error']['details'])) {
                foreach ($errData['error']['details'] as $detail) {
                    if (isset($detail['retryDelay']))
                        $retrySeconds = (int) filter_var($detail['retryDelay'], FILTER_SANITIZE_NUMBER_INT) ?: 60;
                }
            }
            mark_key_rate_limited(get_active_key_index(), $retrySeconds + 5);
            $new_key = rotate_gemini_key();
            if ($new_key && $new_key !== $api_key) {
                // Đổi Key Mới Và Gọi lại
                $api_key = $new_key;
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);
            }
        }

        $result = json_decode($response, true);

        // Ghi log số Tokens nếu có
        $tokens_used = $result['usageMetadata']['totalTokenCount'] ?? 0;
        if ($tokens_used > 0) {
            log_key_usage(get_active_key_index(), $tokens_used, 'client');
        }

        if (error_get_last() || !isset($result['candidates'][0]['content'])) {
            error_log("Gemini Error: " . print_r($result, true));
            break; // Trả về text mặc định do lỗi
        }

        $content_parts = $result['candidates'][0]['content']['parts'];

        $has_function_call = false;
        $text_response = "";

        foreach ($content_parts as $part) {
            if (isset($part['text'])) {
                $text_response .= $part['text'];
            }
            if (isset($part['functionCall'])) {
                $has_function_call = true;
                $functionCall = $part['functionCall'];
            }
        }

        if ($has_function_call) {
            // AI Muốn Gọi Function
            $contents[] = [
                "role" => "model",
                "parts" => [["functionCall" => $functionCall]]
            ];

            if ($functionCall['name'] === 'run_sql' && isset($functionCall['args']['sql'])) {
                $sql = $functionCall['args']['sql'];
                try {
                    $stmt = $db->query($sql);
                    if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN|PRAGMA)/i', $sql)) {
                        $db_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $response_data = ["result" => $db_result];
                    } else {
                        $response_data = ["status" => "success", "affected_rows" => $stmt->rowCount()];
                    }
                } catch (Exception $e) {
                    $response_data = ["error" => "SQL Error: " . $e->getMessage()];
                }

                // Ràng buộc giới hạn size kết quả để tránh lỗi context too long
                $json_res = json_encode($response_data);
                if (strlen($json_res) > 4000) {
                    $response_data = ["error" => "Kết quả mảng SQL quá lớn, bộ nhớ tràn. Hãy thiết kế QUERY SQL cẩn thận hơn bằng LIMIT hoặc WHERE."];
                }

                $contents[] = [
                    "role" => "user",
                    "parts" => [
                        [
                            "functionResponse" => [
                                "name" => "run_sql",
                                "response" => ["name" => "run_sql", "content" => $response_data]
                            ]
                        ]
                    ]
                ];
            } else {
                $contents[] = [
                    "role" => "user",
                    "parts" => [
                        [
                            "functionResponse" => [
                                "name" => $functionCall['name'],
                                "response" => ["error" => "Function không tồn tại trên hệ thống"]
                            ]
                        ]
                    ]
                ];
            }
        } else {
            // Không nhận diện FunctionCall nữa -> AI đã trả về phản hồi cuối
            if (!empty($text_response)) {
                $final_response = $text_response;
            }
            break;
        }
    }

    return $final_response;
}
