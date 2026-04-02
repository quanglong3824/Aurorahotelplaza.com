<?php
/**
 * Stream phản hồi từ Google Gemini sử dụng google-gemini-php/client
 */
function stream_gemini_reply($user_message, $db, $conv_id, &$history = [], $turn = 1)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Chưa cấu hình Gemini API Key."]) . "\n\n";
        return "";
    }

    $model_name = env('AI_MODEL', 'gemini-2.0-flash');
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    
    $gemini_hist = "";
    foreach($history as $msg) {
        $prefix = ($msg['role'] == "user") ? "Khởi tạo/Khách: " : "Model (Vòng trước): ";
        $gemini_hist .= $prefix . $msg['content'] . "\n\n";
    }
    
    $full_prompt = $system_prompt . "\n\n" . $gemini_hist . "Khách/Hệ thống (Hiện tại): " . $user_message;

    try {
        $client = Gemini::client($api_key);
        // Lưu ý: Client này mặc định dùng gemini-pro, ta có thể dùng factory để chọn model
        $response = $client->generativeModel($model_name)->streamGenerateContent($full_prompt);

        $full_response_text = "";
        $is_tool_call = false;
        $is_decided = false;

        foreach ($response as $res) {
            $text = $res->text();
            $full_response_text .= $text;
            
            if (!$is_decided) {
                if (strlen($full_response_text) >= 1) {
                    if ($full_response_text[0] !== '[') {
                        $is_decided = true;
                        echo "data: " . json_encode(["text" => $full_response_text]) . "\n\n";
                        if (ob_get_level() > 0) ob_flush(); flush();
                    } else if (strlen($full_response_text) >= 6) {
                        if (strpos($full_response_text, '[TOOL_') === 0) {
                            $is_tool_call = true;
                        }
                        $is_decided = true;
                        if (!$is_tool_call) {
                            echo "data: " . json_encode(["text" => $full_response_text]) . "\n\n";
                            if (ob_get_level() > 0) ob_flush(); flush();
                        }
                    }
                }
            } else {
                if (!$is_tool_call) {
                    echo "data: " . json_encode(["text" => $text]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush(); flush();
                }
            }
        }

        if ($is_tool_call && $turn <= 2) {
            return process_tool_call_after_stream($full_response_text, $user_message, $db, $conv_id, $history, $turn);
        }

        return $full_response_text;

    } catch (Exception $e) {
        echo "data: " . json_encode(["error" => "Gemini Client Error: " . $e->getMessage()]) . "\n\n";
        return "";
    }
}
