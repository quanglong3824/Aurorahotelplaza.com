<?php
/**
 * Trợ lý ảo AI - Xử lý gọi API Lễ tân
 * ===============================================
 * AI CÓ KHẢ NĂNG: CRUD, Read full từ CSDL, UI buttons
 * QUY TẮC: Không cung cấp auth, không tiết lộ giá, đọc thông tin chính xác
 */

function generate_ai_reply($user_message, $db, $conv_id = 0)
{
    require_once __DIR__ . '/api_key_manager.php';
    $api_key = get_active_gemini_key();

    if (empty($api_key)) {
        return "Xin lỗi, hệ thống chưa được cấu hình khóa API để Trợ lý ảo hoạt động.";
    }

    // 1. Load lịch sử chat để AI hiểu ngữ cảnh
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

            if (count($rows) > 1) {
                $history_context .= "\n[LỊCH SỬ TRÒ CHUYỆN]\n";
                foreach ($rows as $r) {
                    $roleName = ($r['sender_type'] === 'customer') ? 'Khách' : 'AI';
                    $history_context .= "{$roleName}: {$r['message']}\n";
                }
                $history_context .= "[KẾT THÚC]\n";
            }
        } catch (Exception $e) {
            error_log("AI history load error: " . $e->getMessage());
        }
    }
        // Đã được thay thế hoàn toàn bằng DB schema truyền trong System Prompt để AI tự gọi function `run_sql` giúp TỐI ƯU TOKEN tối đa.
    }

    // 2. System Prompt - Định nghĩa vai trò và quy tắc cho AI
    $system_prompt = "
Bạn là Aurora - Trợ lý ảo AI của Khách sạn Aurora Hotel Plaza.
Giới tính: Nữ. Tính cách: Thân thiện, chuyên nghiệp, nhiệt tình.
Ngôn ngữ: Tự động nhận diện và trả lời bằng ngôn ngữ của khách (Tiếng Việt, English, 한국어, 日本語, 中文...).

=====================================
[QUY TẮC BẢO MẬT TUYỆT ĐỐI - KHÔNG VI PHẠM]
=====================================
1. ❌ TUYỆT ĐỐI KHÔNG cung cấp thông tin đăng nhập, mật khẩu, token, session
2. ❌ TUYỆT ĐỐI KHÔNG tiết lộ giá phòng, bảng giá (khi khách hỏi 'giá bao nhiêu' → từ chối lịch sự)
3. ❌ TUYỆT ĐỐI KHÔNG cho phép truy cập admin, database schema chi tiết
4. ❌ TUYỆT ĐỐI KHÔNG bịa đặt thông tin không có trong CSDL
5. ✅ NẾU khách hỏi giá: 'Dạ giá phòng sẽ được hiển thị khi Quý khách chọn ngày cụ thể trên website ạ. Em có thể hỗ trợ gì thêm?'

=====================================
[NGUYÊN TẮC GIAO TIẾP]
=====================================
1. LUÔN đọc THÔNG TIN CHÍNH XÁC từ CSDL trước khi trả lời
2. KHÔNG hỏi lại nếu đã có thông tin trong context
3. TRẢ LỜI NGAY vào câu hỏi, sau đó mới hỏi thêm (nếu cần)
4. Xưng hô: 'Em' (AI) - 'Quý khách' (Khách hàng)
5. Phong cách: Lịch sự, chuyên nghiệp, có thể dùng emoji nhẹ nhàng

=====================================
[CẤU TRÚC CƠ SỞ DỮ LIỆU - BẠN CÓ THỂ TRUY CẬP]
=====================================
Bảng rooms: room_id, room_type_id, room_number, status (available|occupied|cleaning|maintenance)
Bảng room_types: room_type_id, type_name, slug, base_price, max_occupancy, size_sqm, description
Bảng bookings: booking_id, booking_code, guest_name, guest_email, guest_phone, room_type_id, check_in_date, check_out_date, num_adults, num_children, total_amount, status (pending|confirmed|checked_in|checked_out|cancelled), payment_status, created_at
Bảng services: service_id, service_name, category, description, status
Bảng amenities: amenity_id, amenity_name, description, status
Bảng faqs: faq_id, question, answer, category
Bảng gallery: gallery_id, title, image_url, category (room|apartment|restaurant|facility)
Bảng bot_knowledge: topic, content (chính sách, quy định)
Bảng system_settings: setting_key, setting_value (thông tin liên hệ, giờ hoạt động)

=====================================
[CÁC CHỨC NĂNG AI CÓ THỂ THỰC HIỆN]
=====================================

1. ✅ ĐỌC THÔNG TIN (READ):
   - Xem danh sách phòng còn trống
   - Xem thông tin phòng (tiện nghi, diện tích, sức chứa)
   - Xem dịch vụ, tiện ích
   - Xem FAQ, chính sách
   - Xem thông tin liên hệ khách sạn
   - Tra cứu booking (yêu cầu SĐT hoặc mã booking)

2. ✅ TẠO MỚI (CREATE):
   - Tạo booking mới (khi khách chốt)
   - Tạo yêu cầu liên hệ
   - Tạo feedback/review

3. ✅ CẬP NHẬT (UPDATE):
   - Cập nhật thông tin booking (nếu khách cung cấp mã booking)
   - Cập nhật yêu cầu đặc biệt

4. ✅ XÓA/HỦY (DELETE/CANCEL):
   - Hủy booking (yêu cầu mã booking + lý do)

=====================================
[CÁCH TRẢ LỜI THEO TỪNG LOẠI CÂU HỎI]
=====================================

A. KHÁCH HỎI VỀ PHÒNG:
   - BƯỚC 1: Gọi run_sql SELECT từ room_types, rooms
   - BƯỚC 2: Liệt kê thông tin (tên, diện tích, sức chứa, tiện nghi)
   - BƯỚC 3: Nếu khách muốn xem ảnh → SELECT gallery → hiển thị markdown
   - BƯỚC 4: Gợi ý nút xem chi tiết: [LINK_BTN: name=Xem chi tiết, url=/rooms]

B. KHÁCH HỎI GIÁ:
   - TRẢ LỜI: 'Dạ giá phòng sẽ được hiển thị chính xác khi Quý khách chọn ngày cụ thể trên website. Em không thể báo giá chung chung để tránh sai sót ạ.'
   - GỢI Ý: [LINK_BTN: name=Chọn ngày và xem giá, url=/booking]

C. KHÁCH MUỐN ĐẶT PHÒNG:
   - BƯỚC 1: Xin thông tin (check-in, check-out, số người) nếu chưa có
   - BƯỚC 2: Kiểm tra phòng trống bằng run_sql
   - BƯỚC 3: Khi khách chốt → INSERT vào bookings
   - BƯỚC 4: Xuất nút: [BOOK_NOW_BTN: slug=..., name=..., cin=..., cout=...]

D. KHÁCH TRA CỨU BOOKING:
   - YÊU CẦU: Số điện thoại HOẶC mã booking
   - SELECT từ bookings WHERE guest_phone = ? OR booking_code = ?
   - Hiển thị: Mã booking, trạng thái, ngày check-in/out
   - Xuất nút QR: [VIEW_QR_BTN: code=..., id=...]

E. KHÁCH HỎI DỊCH VỤ/TIỆN ÍCH:
   - SELECT từ services, amenities
   - Liệt kê: tên, mô tả, status
   - Gợi ý nút: [LINK_BTN: name=Xem dịch vụ, url=/services]

F. KHÁCH HỎI CHÍNH SÁCH:
   - SELECT từ bot_knowledge, faqs
   - Trả lời chính xác theo CSDL
   - Không bịa đặt

=====================================
[CÚ PHÁP UI BUTTONS - CHÈN VÀO CUỐI TIN NHẮN]
=====================================
[LINK_BTN: name=Tên hiển thị, url=/đường-dẫn]
[VIEW_QR_BTN: code=BOOK123, id=456]
[BOOK_NOW_BTN: slug=deluxe, name=Phòng Deluxe, cin=15/03/2025, cout=17/03/2025]

Hiển thị ảnh: ![Title](https://aurorahotelplaza.com/path/to/image.jpg)

=====================================
[LƯU Ý QUAN TRỌNG]
=====================================
- LUÔN gọi run_sql TRƯỚC khi trả lời về thông tin cụ thể
- KHÔNG đoán mò, không nói 'có lẽ', 'khoảng'
- Nếu CSDL không có thông tin → 'Dạ em không tìm thấy thông tin này. Quý khách có thể liên hệ Hotline để được hỗ trợ ạ.'
- Mỗi tin nhắn chỉ nên có 1-3 buttons, không spam
- Đọc kỹ câu hỏi của khách, tránh trả lời lạc đề

=====================================
[LỊCH SỬ TRÒ CHUYỆN GẦN ĐÂY]
=====================================
{$history_context}
    ";

    $contents = [
        ["role" => "user", "parts" => [["text" => $system_prompt . "\n\nUser: " . $user_message]]]
    ];

    // Định nghĩa các tools (functions) mà AI có thể gọi
    $tools = [
        [
            "functionDeclarations" => [
                [
                    "name" => "run_sql",
                    "description" => "Thực thi câu lệnh SQL để đọc hoặc cập nhật dữ liệu khách sạn. Chỉ dùng SELECT để đọc thông tin. Dùng INSERT/UPDATE khi khách chốt booking.",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "sql" => [
                                "type" => "STRING",
                                "description" => "Câu lệnh SQL MySQL. Ví dụ: SELECT * FROM room_types LIMIT 5"
                            ]
                        ],
                        "required" => ["sql"]
                    ]
                ],
                [
                    "name" => "get_room_info",
                    "description" => "Lấy thông tin chi tiết về phòng (không bao gồm giá)",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "room_type" => [
                                "type" => "STRING",
                                "description" => "Tên hoặc slug của phòng (ví dụ: deluxe, premium)"
                            ]
                        ],
                        "required" => ["room_type"]
                    ]
                ],
                [
                    "name" => "check_room_availability",
                    "description" => "Kiểm tra phòng còn trống không",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "check_in" => [
                                "type" => "STRING",
                                "description" => "Ngày check-in (YYYY-MM-DD)"
                            ],
                            "check_out" => [
                                "type" => "STRING",
                                "description" => "Ngày check-out (YYYY-MM-DD)"
                            ]
                        ],
                        "required" => ["check_in", "check_out"]
                    ]
                ],
                [
                    "name" => "lookup_booking",
                    "description" => "Tra cứu thông tin booking bằng số điện thoại hoặc mã booking",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "phone" => [
                                "type" => "STRING",
                                "description" => "Số điện thoại đặt phòng"
                            ],
                            "booking_code" => [
                                "type" => "STRING",
                                "description" => "Mã booking (ví dụ: BK12345)"
                            ]
                        ],
                        "required" => []
                    ]
                ],
                [
                    "name" => "get_services",
                    "description" => "Lấy danh sách dịch vụ và tiện ích",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "category" => [
                                "type" => "STRING",
                                "description" => "Danh mục (spa, restaurant, gym, all)"
                            ]
                        ],
                        "required" => []
                    ]
                ],
                [
                    "name" => "get_hotel_info",
                    "description" => "Lấy thông tin khách sạn (địa chỉ, hotline, giờ hoạt động)",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [],
                        "required" => []
                    ]
                ],
                [
                    "name" => "get_faq",
                    "description" => "Lấy câu hỏi thường gặp",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "category" => [
                                "type" => "STRING",
                                "description" => "Danh mục (booking, payment, policy, all)"
                            ],
                            "question" => [
                                "type" => "STRING",
                                "description" => "Từ khóa tìm kiếm trong câu hỏi"
                            ]
                        ],
                        "required" => []
                    ]
                ]
            ]
        ]
    ];

    // Số lần AI có thể suy nghĩ (function calls)
    $max_iterations = 5;
    $final_response = "Dạ em đang xử lý yêu cầu của Quý khách. Xin vui lòng đợi trong giây lát ạ.";

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

            $function_name = $functionCall['name'];
            $function_args = $functionCall['args'] ?? [];

            // Xử lý từng function
            if ($function_name === 'run_sql' && isset($function_args['sql'])) {
                $sql = $function_args['sql'];
                
                // Bảo mật: Chỉ cho phép SELECT, SHOW, DESCRIBE (đọc dữ liệu)
                // KHÔNG cho phép INSERT, UPDATE, DELETE, DROP, TRUNCATE từ client
                if (preg_match('/^\s*(INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE|REPLACE)/i', $sql)) {
                    $response_data = ["error" => "Chức năng này không khả dụng. Vui lòng liên hệ lễ tân để thực hiện."];
                } else {
                    try {
                        $stmt = $db->query($sql);
                        if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN|PRAGMA)/i', $sql)) {
                            $db_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            // Giới hạn số lượng record trả về
                            if (count($db_result) > 50) {
                                $db_result = array_slice($db_result, 0, 50);
                                $response_data = ["result" => $db_result, "note" => "Chỉ hiển thị 50 kết quả đầu tiên"];
                            } else {
                                $response_data = ["result" => $db_result];
                            }
                        } else {
                            $response_data = ["status" => "success", "affected_rows" => $stmt->rowCount()];
                        }
                    } catch (Exception $e) {
                        $response_data = ["error" => "SQL Error: " . $e->getMessage()];
                    }
                }

            } elseif ($function_name === 'get_room_info' && isset($function_args['room_type'])) {
                // Lấy thông tin phòng (KHÔNG bao gồm giá)
                $room_type = $function_args['room_type'];
                try {
                    $stmt = $db->prepare("
                        SELECT rt.type_name, rt.slug, rt.max_occupancy, rt.size_sqm, rt.description,
                               (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id AND r.status = 'available') as available_rooms
                        FROM room_types rt
                        WHERE rt.slug LIKE :room_type OR rt.type_name LIKE :room_type
                        LIMIT 5
                    ");
                    $stmt->execute(['room_type' => "%{$room_type}%"]);
                    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Lấy amenities cho mỗi phòng
                    foreach ($rooms as &$room) {
                        $stmtA = $db->prepare("
                            SELECT a.amenity_name
                            FROM room_amenities ra
                            JOIN amenities a ON ra.amenity_id = a.amenity_id
                            WHERE ra.room_type_id = (SELECT room_type_id FROM room_types WHERE slug = :slug)
                            AND a.status = 'active'
                        ");
                        $stmtA->execute(['slug' => $room['slug']]);
                        $room['amenities'] = $stmtA->fetchAll(PDO::FETCH_COLUMN);
                    }
                    
                    $response_data = ["result" => $rooms];
                } catch (Exception $e) {
                    $response_data = ["error" => $e->getMessage()];
                }

            } elseif ($function_name === 'check_room_availability') {
                // Kiểm tra phòng trống theo ngày
                $check_in = $function_args['check_in'] ?? null;
                $check_out = $function_args['check_out'] ?? null;
                
                if (!$check_in || !$check_out) {
                    $response_data = ["error" => "Vui lòng cung cấp ngày check-in và check-out"];
                } else {
                    try {
                        $stmt = $db->prepare("
                            SELECT rt.type_name, rt.slug, rt.max_occupancy,
                                   (SELECT COUNT(*) FROM rooms r 
                                    WHERE r.room_type_id = rt.room_type_id 
                                    AND r.status = 'available') as available_count
                            FROM room_types rt
                            WHERE rt.status = 'active'
                            ORDER BY rt.sort_order
                        ");
                        $stmt->execute();
                        $response_data = ["result" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
                    } catch (Exception $e) {
                        $response_data = ["error" => $e->getMessage()];
                    }
                }

            } elseif ($function_name === 'lookup_booking') {
                // Tra cứu booking
                $phone = $function_args['phone'] ?? null;
                $booking_code = $function_args['booking_code'] ?? null;
                
                if (!$phone && !$booking_code) {
                    $response_data = ["error" => "Vui lòng cung cấp số điện thoại HOẶC mã booking"];
                } else {
                    try {
                        $conditions = [];
                        $params = [];
                        
                        if ($phone) {
                            $conditions[] = "guest_phone = :phone";
                            $params['phone'] = $phone;
                        }
                        if ($booking_code) {
                            $conditions[] = "booking_code = :code";
                            $params['code'] = $booking_code;
                        }
                        
                        $stmt = $db->prepare("
                            SELECT booking_code, guest_name, guest_phone, 
                                   check_in_date, check_out_date, 
                                   num_adults, num_children,
                                   status, payment_status, created_at
                            FROM bookings
                            WHERE " . implode(' OR ', $conditions) . "
                            ORDER BY created_at DESC
                            LIMIT 5
                        ");
                        $stmt->execute($params);
                        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($bookings)) {
                            $response_data = ["result" => [], "note" => "Không tìm thấy booking nào"];
                        } else {
                            $response_data = ["result" => $bookings];
                        }
                    } catch (Exception $e) {
                        $response_data = ["error" => $e->getMessage()];
                    }
                }

            } elseif ($function_name === 'get_services') {
                // Lấy danh sách dịch vụ
                $category = $function_args['category'] ?? 'all';
                try {
                    if ($category === 'all') {
                        $stmt = $db->query("
                            SELECT service_name, category, description 
                            FROM services 
                            WHERE status = 'active'
                            ORDER BY category, sort_order
                            LIMIT 30
                        ");
                    } else {
                        $stmt = $db->prepare("
                            SELECT service_name, category, description 
                            FROM services 
                            WHERE category = :category AND status = 'active'
                            ORDER BY sort_order
                            LIMIT 30
                        ");
                        $stmt->execute(['category' => $category]);
                    }
                    $response_data = ["result" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
                } catch (Exception $e) {
                    $response_data = ["error" => $e->getMessage()];
                }

            } elseif ($function_name === 'get_hotel_info') {
                // Lấy thông tin khách sạn
                try {
                    $stmt = $db->query("
                        SELECT setting_key, setting_value 
                        FROM system_settings 
                        WHERE setting_key IN ('hotel_name', 'hotel_address', 'hotel_phone', 'hotel_email', 'check_in_time', 'check_out_time')
                    ");
                    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    $response_data = ["result" => $settings];
                } catch (Exception $e) {
                    $response_data = ["error" => $e->getMessage()];
                }

            } elseif ($function_name === 'get_faq') {
                // Lấy FAQ
                $category = $function_args['category'] ?? 'all';
                $question = $function_args['question'] ?? null;
                try {
                    $conditions = [];
                    $params = [];
                    
                    if ($category !== 'all') {
                        $conditions[] = "category = :category";
                        $params['category'] = $category;
                    }
                    if ($question) {
                        $conditions[] = "question LIKE :question";
                        $params['question'] = "%{$question}%";
                    }
                    
                    $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
                    
                    $stmt = $db->prepare("
                        SELECT question, answer, category 
                        FROM faqs 
                        $where
                        ORDER BY category, sort_order
                        LIMIT 20
                    ");
                    $stmt->execute($params);
                    $response_data = ["result" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
                } catch (Exception $e) {
                    $response_data = ["error" => $e->getMessage()];
                }

            } else {
                $response_data = ["error" => "Function không tồn tại: {$function_name}"];
            }

            // Giới hạn kích thước response
            $json_res = json_encode($response_data);
            if (strlen($json_res) > 5000) {
                $response_data = ["error" => "Kết quả quá lớn. Vui lòng yêu cầu cụ thể hơn."];
            }

            // Thêm response vào contents
            $contents[] = [
                "role" => "user",
                "parts" => [
                    [
                        "functionResponse" => [
                            "name" => $function_name,
                            "response" => ["name" => $function_name, "content" => $response_data]
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
