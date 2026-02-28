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
            $stmt = $db->query("
                SELECT rt.type_name as name, rt.base_price as price_per_night, rt.max_occupancy, COUNT(r.room_id) as available_count
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
                    $knowledge_context .= "- Loại phòng: {$room['name']} - Giá từ: {$price} VNĐ/đêm - Sức chứa tối đa: {$room['max_occupancy']} người (Còn trống {$room['available_count']} phòng).\n";
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
- Tư vấn linh hoạt, khéo léo và không máy móc. Khách hỏi gì ngoài lề vẫn có thể nói chuyện vui vẻ bình thường miễn là lịch sự.
- Dựa vào [DỮ LIỆU KIẾN THỨC] để tư vấn và báo giá chi tiết, không tự bịa đặt số liệu.
- Lưu ý cực quan trọng: Chỉ khi nào khách yêu cầu khiếu nại gay gắt hoặc đòi hỏi dịch vụ nằm ngoài khả năng trả lời thì mới xin phép chuyển qua người thật. Tuyệt đối không tự động nói câu 'vấn đề này hơi khó, để em chuyển một bạn hỗ trợ viên' khi khách chỉ hỏi thăm bình thường.

[DỮ LIỆU KIẾN THỨC (CẬP NHẬT REALTIME)]
{$knowledge_context}
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
