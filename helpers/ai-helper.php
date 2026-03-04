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

    // 2. System Prompt - Tối ưu token
    $system_prompt = "Bạn là Aurora - AI lễ tân của Aurora Hotel Plaza. Nữ, thân thiện, chuyên nghiệp.

[DB SCHEMA - Chỉ SELECT cột cần thiết]
rooms: room_id,room_type_id,room_number,status(available|occupied|cleaning|maintenance)
room_types: room_type_id,type_name,slug,base_price,holiday_price,max_occupancy
bookings: booking_id,booking_code,guest_name,guest_phone,room_type_id,check_in_date,check_out_date,total_amount,status
room_pricing: pricing_id,room_type_id,start_date,end_date,price
services: service_id,service_name,category,price,short_description
amenities: amenity_id,amenity_name,status
promotions: promotion_code,promotion_name,discount_value,start_date,end_date,status
faqs: question,answer,category
bot_knowledge: topic,content
gallery: title,image_url,category
membership_tiers: tier_name,min_points,discount_percentage
system_settings: setting_key,setting_value
reviews: room_type_id,rating,comment
contact_submissions: name,email,phone,subject,message,status

[QUY TẮC]
1. Luôn gọi run_sql để lấy dữ liệu thực tế, KHÔNG đoán mò.
2. Chỉ SELECT cột cần thiết, thêm LIMIT ≤20.
3. Trả lời ngắn gọn, dùng bullet. Không chào hỏi dài.
4. Trả lời bằng ngôn ngữ của khách (VI/EN/CN/KR/JP...).
5. KHÔNG bịa đặt nếu DB không có dữ liệu.

[BUTTONS]
- Điều hướng: [LINK_BTN: name=Tên nút, url=/path]
- Xem QR booking: [VIEW_QR_BTN: code=BKG001, id=123]
- Đặt phòng: [BOOK_NOW_BTN: slug=deluxe, name=Phòng Deluxe, cin=15/03/2025, cout=17/03/2025]

[SITEMAP] /|/rooms|/services|/promotions|/contact|/profile|/login-register

[ĐẶT PHÒNG] Hỏi ngày CI/CO + số khách → SELECT giá + phòng trống → tính tổng → INSERT bookings khi khách đồng ý → xuất BOOK_NOW_BTN.

[KHIẾU NẠI / YÊU CẦU HỖ TRỢ] Khi khách phàn nàn dịch vụ, phòng ốc hoặc yêu cầu hỗ trợ → Tự động ghi nhận vào CSDL (bảng contact_submissions) bằng lệnh thực thi SQL:
INSERT INTO contact_submissions (submission_id, name, email, phone, subject, message, status) VALUES (0, 'Tên khách (nếu có)/Khách ẩn danh', '', 'SĐT (nếu có)/Không có', '[AI-REQUEST] Khiếu nại/Hỗ trợ', 'Nội dung chi tiết...', 'new')
Sau đó làm dịu khách, xin lỗi và báo bộ phận chuyên môn sẽ theo dõi xử lý ngay.

[ẢNH] SELECT image_url,title FROM gallery WHERE title LIKE '%keyword%' → hiển thị: ![title](https://aurorahotelplaza.com/2025/{image_url})

[TRA CỨU ĐƠN] Hỏi SĐT hoặc mã đơn → SELECT bookings → xuất VIEW_QR_BTN.

{$history_context}";

    $contents = [
        ["role" => "user", "parts" => [["text" => $system_prompt . "\n\nKhách: " . $user_message]]]
    ];

    $tools = [
        [
            "functionDeclarations" => [
                [
                    "name" => "run_sql",
                    "description" => "Chạy SQL trên DB khách sạn (SELECT/INSERT/UPDATE).",
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

    $max_iterations = 3; // Tối đa 3 vòng AI suy nghĩ → tiết kiệm token
    $final_response = "Dạ hệ thống AI đang bận xử lý, Quý khách vui lòng đợi hoặc liên hệ Hotline ạ.";

    for ($i = 0; $i < $max_iterations; $i++) {
        $data = [
            "contents" => $contents,
            "tools" => $tools,
            "generationConfig" => [
                "temperature" => 0.3,  // Thấp → chính xác, ít token suy nghĩ
                "topK" => 20,   // Giảm từ 40 → 20
                "topP" => 0.8,  // Giảm từ 0.95 → 0.8
                "maxOutputTokens" => 512,  // Giảm từ 1024 → 512 (đủ cho chat lễ tân)
            ]
        ];
        $json_data = json_encode($data);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Xử lý Rate Limit 429 - xoay key dự phòng
        if ($http_code === 429) {
            $errData = json_decode($response, true);
            $retrySeconds = 60;
            foreach (($errData['error']['details'] ?? []) as $detail) {
                if (isset($detail['retryDelay']))
                    $retrySeconds = (int) filter_var($detail['retryDelay'], FILTER_SANITIZE_NUMBER_INT) ?: 60;
            }
            mark_key_rate_limited(get_active_key_index(), $retrySeconds + 5);
            $new_key = rotate_gemini_key();
            if ($new_key && $new_key !== $api_key) {
                $api_key = $new_key;
                $ch2 = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch2, CURLOPT_POSTFIELDS, $json_data);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch2, CURLOPT_TIMEOUT, 20);
                $response = curl_exec($ch2);
                $http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);
            }
        }

        $result = json_decode($response, true);
        $tokens_used = $result['usageMetadata']['totalTokenCount'] ?? 0;
        if ($tokens_used > 0)
            log_key_usage(get_active_key_index(), $tokens_used, 'client');

        if (!isset($result['candidates'][0]['content'])) {
            error_log("Gemini Error: " . substr($response, 0, 500));
            break;
        }

        $content_parts = $result['candidates'][0]['content']['parts'];
        $has_function_call = false;
        $text_response = "";

        foreach ($content_parts as $part) {
            if (isset($part['text']))
                $text_response .= $part['text'];
            if (isset($part['functionCall'])) {
                $has_function_call = true;
                $functionCall = $part['functionCall'];
            }
        }

        if ($has_function_call) {
            $contents[] = ["role" => "model", "parts" => [["functionCall" => $functionCall]]];

            if ($functionCall['name'] === 'run_sql' && isset($functionCall['args']['sql'])) {
                $sql = $functionCall['args']['sql'];
                try {
                    $stmt = $db->query($sql);
                    if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $sql)) {
                        $db_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $response_data = ["result" => $db_result];
                    } else {
                        $response_data = ["ok" => true, "rows" => $stmt->rowCount()];
                    }
                } catch (Exception $e) {
                    $response_data = ["error" => $e->getMessage()];
                }

                // Giới hạn size SQL result → tránh bùng nổ token input
                if (strlen(json_encode($response_data)) > 3000) {
                    $response_data = ["error" => "Kết quả quá lớn. Hãy thêm LIMIT hoặc chọn ít cột hơn."];
                }
            } else {
                $response_data = ["error" => "Function không tồn tại."];
            }

            $contents[] = [
                "role" => "user",
                "parts" => [
                    [
                        "functionResponse" => [
                            "name" => $functionCall['name'],
                            "response" => ["name" => $functionCall['name'], "content" => $response_data]
                        ]
                    ]
                ]
            ];
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
