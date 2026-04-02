<?php
/**
 * Stream phản hồi từ Google Gemini
 */
function stream_gemini_reply($user_message, $db, $conv_id, &$history = [], $turn = 1)
{
    $api_key = get_active_gemini_key();
    if (empty($api_key)) {
        echo "data: " . json_encode(["error" => "Chưa cấu hình Gemini API Key."]) . "\n\n";
        return "";
    }

    $model = env('AI_MODEL', 'gemini-2.0-flash');
    $system_prompt = get_aurora_system_prompt($db, $conv_id);
    
    $gemini_hist = "";
    foreach($history as $msg) {
        $prefix = ($msg['role'] == "user") ? "Khởi tạo/Khách: " : "Model (Vòng trước): ";
        $gemini_hist .= $prefix . $msg['content'] . "\n\n";
    }
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?alt=sse&key={$api_key}";
    $data = [
        "contents" => [["role" => "user", "parts" => [["text" => $system_prompt . "\n\n" . $gemini_hist . "Khách/Hệ thống (Hiện tại): " . $user_message]]]],
        "generationConfig" => ["temperature" => 0.4, "maxOutputTokens" => 2048]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $full_response_text = "";
    $buffer = "";
    $is_tool_call = false;
    $is_decided = false;

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$full_response_text, &$buffer, &$is_tool_call, &$is_decided) {
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
                }
            }
        }
        return strlen($data);
    });

    curl_exec($ch);
    curl_close($ch);

    if ($is_tool_call && $turn <= 2) {
        return process_tool_call_after_stream($full_response_text, $user_message, $db, $conv_id, $history, $turn);
    }

    return $full_response_text;
}
