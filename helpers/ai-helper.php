<?php
/**
 * Aurora Hotel Plaza - AI Chat Engine v2.1 (International Qwen & Gemini)
 * ====================================================================
 */

require_once __DIR__ . '/api_key_manager.php';

if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');

/**
 * Lấy System Prompt đầy đủ cho Aurora AI
 */
function get_aurora_system_prompt($db, $conv_id = 0)
{
    $current_date = date('d/m/Y', time() + 7 * 3600);
    $current_time = date('H:i', time() + 7 * 3600);
    
    $history_context = "";
    if ($db && $conv_id > 0) {
        try {
            $stmt = $db->prepare("SELECT sender_type, message FROM chat_messages WHERE conversation_id = ? AND message_type = 'text' AND is_internal = 0 ORDER BY message_id DESC LIMIT 10");
            $stmt->execute([$conv_id]);
            $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
            if (!empty($rows)) {
                $history_context .= "\n[LỊCH SỬ HỘI THOẠI GẦN NHẤT]\n";
                foreach ($rows as $r) {
                    $role = ($r['sender_type'] === 'customer') ? 'Khách' : 'Aurora';
                    $history_context .= "{$role}: {$r['message']}\n";
                }
                $history_context .= "[KẾT THÚC LỊCH SỬ]\n";
            }
        } catch (Exception $e) {}
    }

    return "Bạn là Aurora - Trợ lý ảo lễ tân 5 sao của Aurora Hotel Plaza.
Giới tính: Nữ. Tính cách: Sang trọng, nồng hậu, tinh tế.
Vị trí: 253 Phạm Văn Thuận, Biên Hòa, Đồng Nai. Hotline: 0251 3918 888.

NHIỆM VỤ:
1. Chào đón khách nồng hậu.
2. Tư vấn phòng (Deluxe, Premium, Suite) và dịch vụ (Hồ bơi, Nhà hàng, Gym).
3. Trả lời ngắn gọn, dùng Markdown làm nổi bật thông tin quan trọng.
4. Định dạng giá: 1,500,000 VND.

Ngày giờ: {$current_date} {$current_time}.
{$history_context}";
}

/**
 * Điều phối gọi AI
 */
function stream_ai_reply($user_message, $db, $conv_id = 0)
{
    $provider = get_active_ai_provider();
    if ($provider === 'qwen') {
        return stream_qwen_reply_v1($user_message, $db, $conv_id);
    } else {
        return stream_gemini_reply($user_message, $db, $conv_id);
    }
}

/**
 * Stream phản hồi từ Qwen V1 OpenAI Compatible API
 */
function stream_qwen_reply_v1($user_message, $db, $conv_id)
{
    $api_key = get_active_qwen_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Chưa cấu hình Qwen API Key."]) . "\n\n";
        return "";
    }

    $base_url = get_active_ai_base_url();
    $model = get_active_qwen_model();
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    
    // Đảm bảo URL kết thúc bằng /chat/completions chuẩn OpenAI
    $url = $base_url;
    if (strpos($url, '/chat/completions') === false) {
        $url = rtrim($url, '/') . "/chat/completions";
    }
    
    $data = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => $system_prompt],
            ["role" => "user", "content" => $user_message]
        ],
        "stream" => true,
        "temperature" => 0.7
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
        'X-DashScope-SSE: enable', // Quan trọng cho một số Endpoint của Alibaba
        'Accept: text/event-stream'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Bắt buộc dùng IPv4 để bỏ qua fallback IPv6, giảm ping DNS
    
    // Gửi byte padding để bung bộ đệm (cache) của các web server như NGINX hoặc Cloudflare
    echo ":" . str_repeat(' ', 2048) . "\n\n";
    if (ob_get_level() > 0) ob_flush(); flush();

    $full_response_text = "";
    $buffer = "";

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$full_response_text, &$buffer) {
        $buffer .= $data;
        // Xử lý các dòng dữ liệu từ SSE
        while (($pos = strpos($buffer, "\n")) !== false) {
            $line = trim(substr($buffer, 0, $pos));
            $buffer = substr($buffer, $pos + 1);
            
            if (empty($line)) continue;
            
            if (strpos($line, 'data: ') === 0) {
                $content = substr($line, 6);
                if ($content === "[DONE]") break;
                
                $json = json_decode($content, true);
                if (isset($json['choices'][0]['delta']['content'])) {
                    $text = $json['choices'][0]['delta']['content'];
                    $full_response_text .= $text;
                    echo "data: " . json_encode(["text" => $text]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush(); 
                    flush();
                }
            }
        }
        return strlen($data);
    });

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code !== 200 && empty($full_response_text)) {
        $error_msg = "Mã lỗi: $http_code. " . ($curl_error ?: "Hệ thống AI đang bận.");
        echo "data: " . json_encode(["error" => $error_msg]) . "\n\n";
    }

    if (!empty($full_response_text)) {
        log_key_usage('qwen', strlen($full_response_text) / 2, (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'admin' : 'client');
    }
    
    return $full_response_text;
}

/**
 * Stream phản hồi từ Google Gemini
 */
function stream_gemini_reply($user_message, $db, $conv_id)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Chưa cấu hình Gemini API Key."]) . "\n\n";
        return "";
    }

    $model = env('AI_MODEL', 'gemini-2.0-flash');
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?alt=sse&key={$api_key}";
    $data = [
        "contents" => [["role" => "user", "parts" => [["text" => $system_prompt . "\n\nKhách: " . $user_message]]]],
        "generationConfig" => ["temperature" => 0.4, "maxOutputTokens" => 2048]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $full_response_text = "";
    $buffer = "";

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$full_response_text, &$buffer) {
        $buffer .= $data;
        while (($pos = strpos($buffer, "\n\n")) !== false) {
            $event = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + 2);
            foreach (explode("\n", $event) as $line) {
                if (strpos($line, 'data: ') === 0) {
                    $json = json_decode(substr($line, 6), true);
                    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                        $text = $json['candidates'][0]['content']['parts'][0]['text'];
                        $full_response_text .= $text;
                        echo "data: " . json_encode(["text" => $text]) . "\n\n";
                        if (ob_get_level() > 0) ob_flush(); flush();
                    }
                }
            }
        }
        return strlen($data);
    });

    curl_exec($ch);
    curl_close($ch);
    return $full_response_text;
}
