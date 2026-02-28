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

        // Lấy tất cả kiến thức động từ bảng bot_knowledge (ví dụ chính sách, giờ check in)
        try {
            $stmt = $db->query("SELECT topic, content FROM bot_knowledge");
            $knowledges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($knowledges as $k) {
                $knowledge_context .= "- " . $k['topic'] . ": " . $k['content'] . "\n";
            }
        } catch (Exception $e) {
            $knowledge_context .= "- Chưa có đủ dữ liệu, hãy tìm kiếm thêm từ hệ thống nội bộ.\n";
        }

        // 2. Lấy dữ liệu Phòng (Real-time Database)
        try {
            $stmt = $db->query("
                SELECT rt.type_name as name, rt.slug, rt.base_price as price_per_night, rt.max_occupancy, COUNT(r.room_id) as available_count
                FROM room_types rt
                JOIN rooms r ON rt.room_type_id = r.room_type_id
                WHERE r.status = 'available' AND rt.status = 'active'
                GROUP BY rt.room_type_id
                LIMIT 10
            ");
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($rooms) {
                $knowledge_context .= "\n--- THÔNG TIN CÁC HẠNG PHÒNG TRỐNG MÀ HOTEL ĐANG CÓ ---\n";
                foreach ($rooms as $room) {
                    $price = number_format($room['price_per_night'], 0, ',', '.');
                    $knowledge_context .= "- Loại phòng: {$room['name']} (Mã tham chiếu: {$room['slug']}) - CHÚ Ý ĐÂY LÀ GIÁ GỐC THẤP NHẤT: {$price} VNĐ/đêm - Sức chứa: {$room['max_occupancy']} người.\n";
                }
            } else {
                $knowledge_context .= "\n--- THÔNG TIN PHÒNG TRỐNG ---\n- Hiện khách sạn đang full không còn phòng trống.\n";
            }
        } catch (Exception $e) {
        }

        // 3. Lấy dữ liệu Báo giá Hậu Cần Tăng Giá Động Lễ/Tết (MỚI)
        try {
            $stmt = $db->query("
                SELECT rt.type_name, rp.start_date, rp.end_date, rp.price, rp.description
                FROM room_pricing rp
                JOIN room_types rt ON rp.room_type_id = rt.room_type_id
                WHERE rp.end_date >= CURRENT_DATE()
            ");
            $pricing_rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($pricing_rules) {
                $knowledge_context .= "\n--- 💰💰 LỊCH BÁO GIÁ ĐỘNG (THAY ĐỔI THEO LỄ/TẾT) ĐANG ÁP DỤNG ---\n";
                foreach ($pricing_rules as $rp) {
                    $knowledge_context .= "- Phòng {$rp['type_name']} bị báo ĐỔI GIÁ thành: " . number_format($rp['price'], 0, ',', '.') . " VNĐ/đêm từ ngày " . date('d/m/Y', strtotime($rp['start_date'])) . " đến " . date('d/m/Y', strtotime($rp['end_date'])) . ". Vì lý do là: {$rp['description']}.\n";
                }
                $knowledge_context .= "(CẢNH BÁO QUAN TRỌNG: Nếu khách hỏi giá đúng Giai đoạn Ngày Lễ bên trên, AI BẮT BUỘC phải bỏ Giá Gốc đi, mà BÁO MỨC GIÁ CHUẨN LỄ TẾT trên. Nếu khách đặt nhiều đêm (Ví dụ 1 ngày lễ, 1 ngày thường), AI phải tự cộng dồn thông minh 2 khoảng tiền trước khi trả lời Tổng Kết để Khách chốt deal!)\n";
            }
        } catch (Exception $e) {
        }

        // 4. Lấy hình ảnh thiết bị trực quan từ thư viện (Thẻ Markdown)
        try {
            $stmt = $db->query("SELECT title, image_url, category FROM gallery WHERE status = 'active' LIMIT 15");
            $galleries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($galleries) {
                $knowledge_context .= "\n--- 📸 HỆ THỐNG GỌI HÌNH ẢNH THỰC TẾ TRỰC QUAN KHUYẾN GỢI MUA HÀNG ---\n";
                foreach ($galleries as $gal) {
                    $full_img_url = "https://aurorahotelplaza.com/2025/" . $gal['image_url'];
                    $knowledge_context .= "+ Tên ảnh: [{$gal['title']}] (Album {$gal['category']}) -> MÃ GỌI ẢNH (Bảo mật):  ![{$gal['title']}]({$full_img_url})\n";
                }
                $knowledge_context .= "(LUẬT XUẤT ẢNH CHO KHÁCH XEM: Khi Khách muốn 'Xem không gian phòng', 'Tư vấn view phòng' hoặc bạn thấy Cần Thuyết Phục Khách bằng sự đẹp Mắt, NẾU Data trên có cái Ảnh khớp -> AI hãy Vứt ngay đoạn Mã Gọi Ảnh `![...](...)` này Trực Tiếp vào cuối phần chát. Đừng sáng tác Link ảnh giả mạo. Giao diện Chat của Guest sẽ Bốc Ảnh Phóng To Ra Màn Hình Khách Sạn!)\n";
            }
        } catch (Exception $e) {
        }

        // 5. Cài đặt các FAQs Hỏi Xoáy Đáp Xoay của Khách MỚI
        try {
            $stmt = $db->query("SELECT question, answer FROM faqs WHERE status = 'active'");
            $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($faqs) {
                $knowledge_context .= "\n--- 🛎 BỘ CẨM NANG HỎI XOÁY ĐÁP XOAY (FAQs) ---\n";
                foreach ($faqs as $faq) {
                    $knowledge_context .= "Hỏi: {$faq['question']} -> Đáp luôn: {$faq['answer']}\n";
                }
            }
        } catch (Exception $e) {
        }

        // 6. Lấy dữ liệu Dịch vụ (Spa, Nhà hàng, Đưa đón...) MỚI MỞ RỘNG CSDL
        try {
            $stmt = $db->query("SELECT service_name, category, price, short_description FROM services WHERE status = 'active' LIMIT 20");
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($services) {
                $knowledge_context .= "\n--- DỊCH VỤ KHÁCH SẠN (NHÀ HÀNG, SPA, XE ĐƯA ĐÓN...) ---\n";
                foreach ($services as $srv) {
                    $price = $srv['price'] > 0 ? number_format($srv['price'], 0, ',', '.') . ' VNĐ' : 'Miễn phí hoặc Liên hệ';
                    $knowledge_context .= "- {$srv['service_name']} (Mảng {$srv['category']}): Giá {$price}. Chi tiết: {$srv['short_description']}\n";
                }
            }
        } catch (Exception $e) {
        }

        // 7. Lấy dữ liệu Cài đặt Hệ thống Khách sạn (Phone, Email, Giờ Check-in/out, Chính sách)
        try {
            $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_group IN ('general', 'contact', 'booking')");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($settings) {
                $knowledge_context .= "\n--- THÔNG TIN CƠ BẢN CỦA KHÁCH SẠN (SYSTEM SETTINGS) ---\n";
                foreach ($settings as $s) {
                    $knowledge_context .= "- {$s['setting_key']}: {$s['setting_value']}\n";
                }
            }
        } catch (Exception $e) {
        }

        // 8. Lấy dữ liệu Tiện nghi Trang thiết bị chung (Amenities)
        try {
            $stmt = $db->query("SELECT amenity_name, category FROM amenities WHERE status = 'active'");
            $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($amenities) {
                $knowledge_context .= "\n--- TIỆN NGHI VÀ TRANG THIẾT BỊ CỦA KHÁCH SẠN (AMENITIES) ---\nKhách sạn có các tiện ích sau: ";
                $amenity_names = array_map(function ($a) {
                    return $a['amenity_name'];
                }, $amenities);
                $knowledge_context .= implode(", ", $amenity_names) . ".\n";
            }
        } catch (Exception $e) {
        }

        // 9. Lấy dữ liệu Ưu đãi & Giảm giá (Promotions)
        try {
            $stmt = $db->query("SELECT code, title, discount_type, discount_value, min_booking_amount FROM promotions WHERE status = 'active' AND end_date >= CURRENT_DATE()");
            $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($promos) {
                $knowledge_context .= "\n--- CÁC ƯU ĐÃI KHUYẾN MÃI ĐANG MỞ (PROMOTIONS & COUPONS) ---\n";
                foreach ($promos as $p) {
                    $val = $p['discount_type'] == 'percentage' ? $p['discount_value'] . '%' : number_format($p['discount_value'], 0, ',', '.') . ' VNĐ';
                    $knowledge_context .= "- Mã '{$p['code']}': {$p['title']} (Giảm {$val}, áp dụng cho đơn từ " . number_format($p['min_booking_amount'], 0, ',', '.') . " VNĐ).\n";
                }
            }
        } catch (Exception $e) {
        }

        // 10. Lấy dữ liệu Hạng Thành viên (Membership Tiers)
        try {
            $stmt = $db->query("SELECT tier_name, required_points, discount_percent FROM membership_tiers");
            $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($tiers) {
                $knowledge_context .= "\n--- CHÍNH SÁCH HẠNG THÀNH VIÊN (LOYALTY) ---\n";
                foreach ($tiers as $t) {
                    $knowledge_context .= "- Hạng {$t['tier_name']}: Cần {$t['required_points']} điểm tích lũy. Đặc quyền giảm giá trực tiếp: {$t['discount_percent']}%.\n";
                }
            }
        } catch (Exception $e) {
        }
    }

    // 2. Định nghĩa vai trò (System Prompt) cho Bot
    // Đây là "não bộ" của Bot
    $system_prompt = "
Bạn là Aurora, Trợ lý AI Thông minh của khách sạn Aurora Hotel Plaza. Nữ giới.
Nhiệm vụ cốt lõi:
- Luôn giữ thái độ chuyên nghiệp, thân thiện, xưng hô 'Dạ/Vâng', 'Quý khách/Em'.
- GIAO TIẾP ĐA NGÔN NGỮ: BẮT BUỘC phải đọc và nhận diện khách hàng đang nhắn tin bằng ngôn ngữ Mẹ Đẻ nào (Tiếng Anh, Tiếng Trung, Tiếng Hàn, Tiếng Nhật, v.v.). Nếu khách nhắn ngôn ngữ nào -> BẠN PHẢI TRẢ LỜI LẠI TRÔI CHẢY BẰNG CHÍNH NGÔN NGỮ ĐÓ (Không được dùng Tiếng Việt nếu họ là người ngoại quốc). Tự động dịch tất cả dữ liệu từ [DỮ LIỆU KIẾN THỨC] sang ngôn ngữ của Khách.
- AI 'BIẾT TUỐT' DỮ LIỆU CÔNG TY: Toàn bộ [DỮ LIỆU KIẾN THỨC] đã được nạp ở phía dưới, nó bao gồm Giá, Ngày lễ, Dịch Vụ, Cài Đặt Hệ Thống, Thiết bị phòng, Khuyến mãi, Thẻ Thành viên. Bạn là Bách khoa toàn thư của khách sạn. Hỏi gì trong [DỮ LIỆU KIẾN THỨC] cũng phải trả lời được một cách chi tiết, khéo léo. Tuyệt đối không tự bịa đặt số liệu lệch với KIẾN THỨC đã nạp. Cố gắng trả lời dựa sát với CSDL để chốt Sale.
- NẾU KHÁCH HỎI THÔNG TIN KHÔNG CÓ TRONG CSDL: Nếu khách hỏi những tiện ích, dịch vụ, món ăn, hoặc thông tin mà HOÀN TOÀN KHÔNG TỒN TẠI trong [DỮ LIỆU KIẾN THỨC], TUYỆT ĐỐI KHÔNG ĐƯỢC BỊA ĐẶT HOẶC ĐOÁN MÒ. Hãy lịch sự xin lỗi khách hàng, thông báo rằng hiện tại khách sạn chưa có/chưa cập nhật thông tin về dịch vụ đó, và khuyên khách hàng để lại số điện thoại hoặc liên hệ Hotline để bộ phận Lễ tân hỗ trợ chi tiết hơn.
- Tư vấn linh hoạt, khéo léo và không máy móc. Khách hỏi gì ngoài lề vẫn có thể nói chuyện vui vẻ tĩnh bình thường miễn là lịch sự.


[ĐẶC BIỆT KÍCH HOẠT QUY TRÌNH ĐẶT PHÒNG TỰ ĐỘNG]
Nếu khách có ý định đặt phòng, hãy áp dụng các bước sau:
1. Xin thông tin chi tiết (Ngày Check-in, Ngày Check-out, Số lượng người). Chú ý nếu đã có trong lịch sử trò chuyện thì KHÔNG HỎI LẠI TRÙNG LẶP.
2. Khi khách đã cung cấp các thông tin và chọn muốn Đặt 1 loại phòng cụ thể, hãy xác nhận tóm tắt lại và mời khách LẤY MÃ ĐẶT PHÒNG/MÃ QR để đến khách sạn nhận phòng (Không yêu cầu thanh toán ngay).
3. Đính kèm thông tin địa chỉ kèm Google Maps để tiện cho khách di chuyển. Ví dụ: 'Khách sạn có địa chỉ tại: 253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, Biên Hòa, Đồng Nai. Maps:  https://maps.app.goo.gl/BMaDERxfuXuWi2AZA?g_st=ic'
4. QUAN TRỌNG: Để sinh ra Nút lấy mã QR/Mã Đặt Phòng trên giao diện chat cho khách, bạn BẮT BUỘC phải chèn đoạn mã sau vào CHÍNH XÁC ở cuối của đoạn chat bạn gửi cho họ:
[BOOK_NOW_BTN: slug={Mã tham chiếu}, name={Tên phòng}, cin={Ngày checkin định dạng do người dùng nhập}, cout={Ngày checkout định dạng do người dùng}]
--- Ví dụ xuất ra:
Dạ vâng, em đã lên đơn xong phòng Deluxe từ ngày 15/05 đến 18/05 cho Quý khách. Quý khách vui lòng lưu lại Nút mã xác nhận dưới đây và đến trực tiếp khách sạn để check-in nhé ạ!
[BOOK_NOW_BTN: slug=deluxe, name=Deluxe Room, cin=15/05/2026, cout=18/05/2026]
(Không thêm thẻ markdown code bao quanh mã nút này)

[DỮ LIỆU KIẾN THỨC (CẬP NHẬT REALTIME)]
{$knowledge_context}
{$history_context}
    ";

    // Thực hiện cURL POST Request tới Google Gemini API
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    $data = [
        "contents" => [
            ["role" => "user", "parts" => [["text" => $system_prompt . "\n\nUser: " . $user_message]]]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "topK" => 40,
            "topP" => 0.95,
            "maxOutputTokens" => 1024,
        ]
    ];
    $json_data = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Tắt verify SSL nếu chạy ở localhost bị lỗi SSL certificate (XAMPP thường bị)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Kích hoạt tự động Switch Key khi Quota Của Key Hiển Tại đã hết
    if ($http_code === 429) {
        $new_key = rotate_gemini_key();
        if ($new_key && $new_key !== $api_key) {
            // Thử Gọi lại API với Key Mới
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $new_key;
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
        }
    }

    if (curl_errno($ch)) {
        error_log('Curl error: ' . curl_error($ch));
        return "Xin lỗi, hệ thống đang gặp sự cố kết nối AI.";
    }

    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        // Ghi nhận log usage cho Client
        $tokens_used = $result['usageMetadata']['totalTokenCount'] ?? 0;
        if ($tokens_used > 0) {
            $current_idx = get_active_key_index();
            log_key_usage($current_idx, $tokens_used, 'client');
        }
        return $result['candidates'][0]['content']['parts'][0]['text'];
    }

    // Fallback error logging for API failure
    error_log("Gemini API Error Response: " . print_r($result, true));
    return "Dạ vấn đề này hơi khó, để em chuyển một bạn hỗ trợ viên người thật tư vấn chi tiết hơn cho mình nhé! Quý khách giúp em đợi 1 xíu ạ.";
}
