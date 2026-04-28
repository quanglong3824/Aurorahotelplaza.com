<?php
/**
 * Test Alibaba DashScope API trực tiếp - coding-intl
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ALIBABA DASHSCOPE API TEST (coding-intl)</h1>";

require_once __DIR__ . '/config/load_env.php';

$api_key = env('ALIBABA_API_KEY');
echo "<p>API Key: " . substr($api_key, 0, 20) . "... (length: " . strlen($api_key) . ")</p>";

// Test: coding-intl OpenAI-compatible
echo "<h2>Test: coding-intl OpenAI-compatible</h2>";

$request_body = [
    'model' => 'qwen3.5-plus',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello, reply OK only']
    ],
    'temperature' => 0.7,
    'max_tokens' => 100
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://coding-intl.dashscope.aliyuncs.com/v1/chat/completions',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($request_body),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<pre>";
echo "HTTP Code: $http_code\n";
echo "CURL Error: $curl_error\n";
echo "Response:\n";
echo $response;
echo "</pre>";

$decoded = json_decode($response, true);
if ($decoded) {
    echo "<pre>Decoded:\n";
    print_r($decoded);
    echo "</pre>";
    
    if (isset($decoded['choices'][0]['message']['content'])) {
        echo "<p style='color:green; font-weight:bold'>✓ SUCCESS! AI Reply: " . $decoded['choices'][0]['message']['content'] . "</p>";
    }
}

// Test different models
echo "<h2>Test Available Models</h2>";
$models = ['qwen3.5-plus', 'qwen3-max', 'glm-5', 'qwen3-coder-plus'];

foreach ($models as $model) {
    $request_body = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => 'Say OK']
        ],
        'max_tokens' => 50
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://coding-intl.dashscope.aliyuncs.com/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ],
        CURLOPT_TIMEOUT => 15
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $decoded = json_decode($response, true);
    $status = $http_code === 200 ? '✓ OK' : '✗ HTTP ' . $http_code;
    $reply = isset($decoded['choices'][0]['message']['content']) 
        ? substr($decoded['choices'][0]['message']['content'], 0, 50) 
        : ($decoded['error']['message'] ?? 'N/A');
    
    echo "<p><strong>$model:</strong> $status - <em>$reply</em></p>";
}
?>
<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
h1 { color: #d4af37; }
h2 { border-bottom: 2px solid #d4af37; margin-top: 30px; }
pre { background: #fff; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>