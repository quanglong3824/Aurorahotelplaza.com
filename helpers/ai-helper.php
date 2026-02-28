<?php
/**
 * Trợ lý ảo AI - Xử lý gọi API Lễ tân
 * ===============================================
 */

function generate_ai_reply($user_message, $db)
{
    // Để tích hợp thật, bạn hãy thay API_KEY thật vào đây.
    // Ví dụ API Gemini từ Google (rất rẻ và thông minh)
    $api_key = 'AIzaSyCTfxfJW2c72Q8FjgrtzD27D3dLtE5cc6o'; // ĐIỀN API KEY Ở ĐÂY

    // 1. (RAG) Kéo tri thức từ Database
    $knowledge_context = "";
    if ($db) {
        // Lấy tất cả kiến thức động từ bảng bot_knowledge (ví dụ chính sách, giờ check in)
        try {
            $stmt = $db->query("SELECT topic, content FROM bot_knowledge");
            $knowledges = $stmt->fetchAll();
            foreach ($knowledges as $k) {
                $knowledge_context .= "- " . $k['topic'] . ": " . $k['content'] . "\n";
            }
        } catch (Exception $e) {
            // Chưa có bảng bot_knowledge thì bỏ qua
            $knowledge_context .= "- Chưa có đủ dữ liệu, hãy tìm kiếm thêm từ hệ thống nội bộ.\n";
        }

        // 2. Lấy dữ liệu Phòng (Real-time Database)
        try {
            // Cập nhật câu query cho phù hợp với cấu trúc bảng thực tế của bạn
            // Giả sử bạn có bảng `rooms` hoặc `room_types`
            $stmt = $db->query("
                SELECT name, price_per_night, max_adults, max_children, status 
                FROM rooms 
                WHERE status = 'available' 
                LIMIT 10
            ");
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($rooms) {
                $knowledge_context .= "\n--- THÔNG TIN PHÒNG TRỐNG HIỆN TẠI ---\n";
                foreach ($rooms as $room) {
                    $price = number_format($room['price_per_night'], 0, ',', '.');
                    $knowledge_context .= "- {$room['name']}: Giá {$price} VNĐ/đêm. Tiêu chuẩn: {$room['max_adults']} NL, {$room['max_children']} TE.\n";
                }
            } else {
                $knowledge_context .= "\n--- THÔNG TIN PHÒNG TRỐNG HIỆN TẠI ---\n- Hiện tại khách sạn đang hết phòng trống.\n";
            }
        } catch (Exception $e) {
            // Bỏ qua nếu cấu trúc tên bảng bị sai lệch đôi chút
        }
    }

    // 2. Định nghĩa vai trò (System Prompt) cho Bot
    // Đây là "não bộ" của Bot
    $system_prompt = "
Bạn là Aurora, Lễ tân ảo CSKH của khách sạn Aurora Hotel Plaza.
Bạn phải luôn giữ thái độ niềm nở, lịch sự, xưng hô 'Dạ/Vâng', 'Quý khách/Em'.
Tuyệt đối không bịa đặt thông tin. Nếu có thông tin trong [DỮ LIỆU KIẾN THỨC] dưới đây, hãy trả lời theo nó.
Nếu khách hỏi gì nằm ngoài dữ liệu hệ thống, hãy nói 'Dạ, vấn đề này em sẽ chuyển cho nhân viên hỗ trợ trực tiếp. Quý khách vui lòng đợi trong giây lát ạ.'

[DỮ LIỆU KIẾN THỨC BẠN ĐÃ HỌC]
{$knowledge_context}
    ";

    // Thực hiện cURL POST Request tới Google Gemini API
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key;

    $data = [
        "contents" => [
            ["role" => "user", "parts" => [["text" => $system_prompt . "\n\nUser: " . $user_message]]]
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
        curl_close($ch);
        return "Xin lỗi, hệ thống đang gặp sự cố kết nối AI.";
    }
    curl_close($ch);

    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return $result['candidates'][0]['content']['parts'][0]['text'];
    }

    // Fallback error logging for API failure
    error_log("Gemini API Error Response: " . print_r($result, true));
    return "Dạ vấn đề này hơi khó, để em chuyển một bạn hỗ trợ viên người thật tư vấn chi tiết hơn cho mình nhé! Quý khách giúp em đợi 1 xíu ạ.";
}
