<?php
/**
 * Trợ lý ảo AI - Xử lý gọi API Lễ tân
 * ===============================================
 */

function generate_ai_reply($user_message, $db, $conv_id = 0)
{
    // Để tích hợp thật, bạn hãy thay API_KEY thật vào đây.
    $api_key = 'AIzaSyCTfxfJW2c72Q8FjgrtzD27D3dLtE5cc6o'; // ĐIỀN API KEY Ở ĐÂY

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
                $knowledge_context .= "\n--- THÔNG TIN PHÒNG TRỐNG HIỆN TẠI ---\n";
                foreach ($rooms as $room) {
                    $price = number_format($room['price_per_night'], 0, ',', '.');
                    $knowledge_context .= "- Loại phòng: {$room['name']} (Mã tham chiếu: {$room['slug']}) - Giá từ: {$price} VNĐ/đêm - Sức chứa tối đa: {$room['max_occupancy']} người (Còn trống {$room['available_count']} phòng).\n";
                }
            } else {
                $knowledge_context .= "\n--- THÔNG TIN PHÒNG TRỐNG HIỆN TẠI ---\n- Hiện tại khách sạn đang hết phòng trống.\n";
            }
        } catch (Exception $e) {
            // Error silently ignored
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
