<?php
/**
 * Trợ lý ảo AI - Xử lý gọi API Lễ tân
 * ===============================================
 * AI CÓ KHẢ NĂNG: CRUD, Read full từ CSDL, UI buttons
 * QUY TẮC: Không cung cấp auth, không tiết lộ giá, đọc thông tin chính xác
 */

// Hàm ghi log chi tiết cho hệ thống AI (Success/Error/Usage)
function log_ai_activity($db, $type, $prompt, $reply, $model, $tokens, $status, $error = '', $code = 200, $conv_id = 0, $exec_time = 0)
{
    try {
        $stmt = $db->prepare("INSERT INTO ai_logs (ai_type, conv_id, prompt_text, reply_text, model_name, tokens_used, status, error_message, http_code, execution_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$type, $conv_id, mb_substr($prompt, 0, 1000), mb_substr($reply, 0, 3000), $model, (int) $tokens, $status, $error, (int) $code, $exec_time]);
    } catch (Exception $e) {
        error_log("Failed to write AI log: " . $e->getMessage());
    }
}

function generate_ai_reply($user_message, $db, $conv_id = 0)
{
    require_once __DIR__ . '/api_key_manager.php';
    $api_key = get_active_gemini_key();
    $start_time = microtime(true);
    $model_used = 'gemini-3-flash';

    if (empty($api_key)) {
        log_ai_activity($db, 'client', $user_message, '', $model_used, 0, 'error', 'Missing API Key', 0, $conv_id);
        return "Xin lỗi, hệ thống chưa được cấu hình khóa API để Trợ lý ảo hoạt động.";
    }

    // 1. Load lịch sử chat - Tối ưu: chỉ lấy 6 messages gần nhất
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
                LIMIT 6
            ");
            $stmtH->execute([$conv_id]);
            $rows = $stmtH->fetchAll(PDO::FETCH_ASSOC);
            $rows = array_reverse($rows);

            if (count($rows) > 1) {
                $history_context .= "\n[LỊCH SỬ]\n";
                foreach ($rows as $r) {
                    $roleName = ($r['sender_type'] === 'customer') ? 'K' : 'A';
                    // Cắt ngắn message dài > 100 chars
                    $msg = strlen($r['message']) > 100 ? substr($r['message'], 0, 100) . '...' : $r['message'];
                    $history_context .= "{$roleName}: {$msg}\n";
                }
                $history_context .= "[END]\n";
            }
        } catch (Exception $e) {
            error_log("AI history load error: " . $e->getMessage());
        }
    }

    // 2. System Prompt - Tối ưu cho tốc độ & tiết kiệm token
    $system_prompt = <<<PROMPT
Bạn là Aurora - Trợ lý ảo AI của Khách sạn Aurora Hotel Plaza.
Nữ. Thân thiện. Chuyên nghiệp.

[QUY TẮC BẢO MẬT]
❌ KHÔNG: Cung cấp login info, mật khẩu, token, giá phòng
❌ KHÔNG: Thực thi INSERT/UPDATE/DELETE từ client
✅ CÓ: Đọc CSDL trước khi trả lời
✅ CÓ: Trả lời ngắn gọn, đi thẳng vào câu hỏi

[CẤU TRÚC CÂU TRẢ LỜI]
1. Trả lời trực tiếp (1-2 câu)
2. Thông tin chi tiết (bullet points, max 5 items)
3. 1-2 buttons (không spam)

[CÁC FUNCTIONS available]
- run_sql: SELECT only (max 50 records)
- get_room_info: Thông tin phòng (KHÔNG có giá)
- check_room_availability: Kiểm tra phòng trống
- lookup_booking: Tra booking (phone OR code)
- get_services: Dịch vụ (category optional)
- get_hotel_info: TT khách sạn
- get_faq: FAQ (category optional)

[QUY TẮC TIẾT KIỆM TOKEN]
1. KHÔNG chào hỏi dài dòng
2. KHÔNG nhắc lại câu hỏi
3. Dùng bullet points thay vì paragraph
4. Giới hạn 5 items/list
5. KHÔNG giải thích function calls

[VÍ DỤ MẪU]
Q: "Phòng deluxe có gì?"
A: "Dạ phòng Deluxe có:
• Diện tích: 35m²
• Tối đa: 3 người
• Tiện nghi: WiFi, TV, Minibar
[LINK_BTN: name=Xem chi tiết, url=/rooms/deluxe]"

Q: "Giá phòng?"
A: "Dạ giá hiển thị khi chọn ngày cụ thể trên website ạ.
[LINK_BTN: name=Chọn ngày, url=/booking]"

Q: "Check booking 0901234567"
A: "Dạ tìm thấy booking:
• Mã: BK12345
• Status: Đã xác nhận
• Check-in: 15/03/2025
[VIEW_QR_BTN: code=BK12345, id=789]"

[LỊCH SỬ CHAT]
{$history_context}
PROMPT;


    $contents = [
        ["role" => "user", "parts" => [["text" => $user_message]]]
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

    // Số lần AI có thể suy nghĩ (3-5 là hợp lý)
    $max_iterations = 4;
    $final_response = "Dạ em đang xử lý. Xin vui lòng đợi ạ.";

    // Debug logging
    error_log("=== AI REQUEST ===");
    error_log("Message length: " . strlen($user_message));
    error_log("Conv ID: " . $conv_id);

    for ($i = 0; $i < $max_iterations; $i++) {
        $data = [
            "system_instruction" => [
                "parts" => [["text" => $system_prompt]]
            ],
            "contents" => $contents,
            "tools" => $tools,
            "generationConfig" => [
                "temperature" => 0.3,      // Giảm nhiệt độ → AI chính xác hơn, ít sáng tạo
                "topK" => 32,              // Giảm topK → Tập trung vào token phổ biến
                "topP" => 0.8,             // Giảm topP → Chọn token an toàn hơn
                "maxOutputTokens" => 512,  // Giới hạn output → Tiết kiệm token & nhanh hơn
                "stopSequences" => ["\n\n\n"] // Dừng ở 3 dòng trống → Tránh output dài
            ]
        ];
        $json_data = json_encode($data);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model_used}:generateContent?key=" . $api_key;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $exec_time = round(microtime(true) - $start_time, 2);

        if ($curl_error) {
            error_log("AI cURL Error: " . $curl_error);
            log_ai_activity($db, 'client', $user_message, '', $model_used, 0, 'error', "cURL Error: $curl_error", $http_code, $conv_id, $exec_time);
            break;
        }

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
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model_used}:generateContent?key=" . $api_key;
                $ch_retry = curl_init($url);
                curl_setopt($ch_retry, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch_retry, CURLOPT_POSTFIELDS, $json_data);
                curl_setopt($ch_retry, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_retry, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch_retry);
                $http_code = curl_getinfo($ch_retry, CURLINFO_HTTP_CODE);
                curl_close($ch_retry);
            }
        }

        if ($http_code !== 200) {
            error_log("AI Gemini API Error (HTTP $http_code): " . $response);
            log_ai_activity($db, 'client', $user_message, '', $model_used, 0, 'error', "API Error: " . mb_substr($response, 0, 500), $http_code, $conv_id, $exec_time);
            break;
        }

        $result = json_decode($response, true);

        // Ghi log số Tokens nếu có
        $tokens_used = $result['usageMetadata']['totalTokenCount'] ?? 0;
        if ($tokens_used > 0) {
            log_key_usage(get_active_key_index(), $tokens_used, 'client');
        }

        if (!isset($result['candidates'][0]['content'])) {
            error_log("AI Gemini Unexpected Response Format: " . $response);
            log_ai_activity($db, 'client', $user_message, '', $model_used, $tokens_used, 'error', "Unexpected Response Format", $http_code, $conv_id, $exec_time);
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
                        $conditions = []; // Khởi tạo array
                        $params = [];

                        if ($phone) {
                            $conditions[] = "guest_phone = :phone";
                            $params['phone'] = $phone;
                        }
                        if ($booking_code) {
                            $conditions[] = "booking_code = :code";
                            $params['code'] = $booking_code;
                        }

                        // Nếu không có conditions (hiếm), fallback
                        if (empty($conditions)) {
                            $response_data = ["error" => "Vui lòng cung cấp thông tin tra cứu"];
                        } else {
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
                log_ai_activity($db, 'client', $user_message, $final_response, $model_used, $tokens_used, 'success', '', 200, $conv_id, $exec_time);
            }
            break;
        }
    }

    return $final_response;
}
