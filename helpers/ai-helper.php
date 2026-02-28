<?php
/**
 * Trợ lý ảo AI - Xử lý gọi API Lễ tân
 * ===============================================
 */

function generate_ai_reply($user_message, $db, $conv_id = 0)
{
    // Lấy API Key động từ file Config bí mật (Không đẩy lên Github)
    $api_key = '';
    $key_file = __DIR__ . '/../config/api_keys.php';
    if (file_exists($key_file)) {
        require_once $key_file;
        if (defined('GEMINI_API_KEY')) {
            $api_key = GEMINI_API_KEY;
        }
    } else {
        // Fallback đọc từ biến môi trường (nếu cài đặt trực tiếp trên CPanel/Hosting)
        $api_key = getenv('GEMINI_API_KEY');
    }

    if (empty($api_key)) {
        return "Xin lỗi, hệ thống chưa được cấu hình khóa API (API Key) để Trợ lý ảo hoạt động.";
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
    }

    // 2. Định nghĩa vai trò (System Prompt) cho Bot
    // Đây là "não bộ" của Bot
    $system_prompt = "
Bạn là Aurora, Trợ lý AI Thông minh của khách sạn Aurora Hotel Plaza. Nữ giới.
Nhiệm vụ cốt lõi:
- Luôn giữ thái độ chuyên nghiệp, thân thiện, xưng hô 'Dạ/Vâng', 'Quý khách/Em'.
- Tư vấn linh hoạt, khéo léo và không máy móc. Khách hỏi gì ngoài lề vẫn có thể nói chuyện vui vẻ tĩnh bình thường miễn là lịch sự.
- Dựa vào [DỮ LIỆU KIẾN THỨC] để tư vấn và báo giá chi tiết, không tự bịa đặt số liệu.

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

    if (curl_errno($ch)) {
        error_log('Curl error: ' . curl_error($ch));
        return "Xin lỗi, hệ thống đang gặp sự cố kết nối AI.";
    }

    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return $result['candidates'][0]['content']['parts'][0]['text'];
    }

    // Fallback error logging for API failure
    error_log("Gemini API Error Response: " . print_r($result, true));
    return "Dạ vấn đề này hơi khó, để em chuyển một bạn hỗ trợ viên người thật tư vấn chi tiết hơn cho mình nhé! Quý khách giúp em đợi 1 xíu ạ.";
}
