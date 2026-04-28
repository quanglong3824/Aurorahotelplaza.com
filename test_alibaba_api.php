<?php
/**
 * Test Alibaba DashScope API trực tiếp
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ALIBABA DASHSCOPE API TEST</h1>";

require_once __DIR__ . '/config/load_env.php';

$api_key = env('ALIBABA_API_KEY');
echo "<p>API Key: " . substr($api_key, 0, 20) . "... (length: " . strlen($api_key) . ")</p>";

// Test 1: Native DashScope API
echo "<h2>Test 1: Native DashScope API</h2>";

$request_body = [
    'model' => 'qwen-plus',
    'input' => [
        'messages' => [
            ['role' => 'user', 'content' => 'Hello, reply OK only']
        ]
    ],
    'parameters' => [
        'temperature' => 0.7,
        'max_tokens' => 100,
        'result_format' => 'message'
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
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
}

// Test 2: OpenAI-compatible mode
echo "<h2>Test 2: OpenAI-compatible Mode</h2>";

$request_body2 = [
    'model' => 'qwen-plus',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello, reply OK only']
    ],
    'temperature' => 0.7,
    'max_tokens' => 100
];

$ch2 = curl_init();
curl_setopt_array($ch2, [
    CURLOPT_URL => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($request_body2),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ],
    CURLOPT_TIMEOUT => 30
]);

$response2 = curl_exec($ch2);
$http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$curl_error2 = curl_error($ch2);
curl_close($ch2);

echo "<pre>";
echo "HTTP Code: $http_code2\n";
echo "CURL Error: $curl_error2\n";
echo "Response:\n";
echo $response2;
echo "</pre>";

$decoded2 = json_decode($response2, true);
if ($decoded2) {
    echo "<pre>Decoded:\n";
    print_r($decoded2);
    echo "</pre>";
}

// Test 3: Different models
echo "<h2>Test 3: Available Models</h2>";
$models = ['qwen-turbo', 'qwen-plus', 'qwen-max', 'qwen-long'];

foreach ($models as $model) {
    $request_body3 = [
        'model' => $model,
        'input' => [
            'messages' => [
                ['role' => 'user', 'content' => 'Say OK']
            ]
        ],
        'parameters' => [
            'result_format' => 'message'
        ]
    ];
    
    $ch3 = curl_init();
    curl_setopt_array($ch3, [
        CURLOPT_URL => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_body3),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ],
        CURLOPT_TIMEOUT => 15
    ]);
    
    $response3 = curl_exec($ch3);
    $http_code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
    curl_close($ch3);
    
    $status = $http_code3 === 200 ? '✓ OK' : '✗ HTTP ' . $http_code3;
    echo "<p><strong>$model:</strong> $status</p>";
    
    if ($http_code3 !== 200) {
        echo "<pre style='color:red'>" . substr($response3, 0, 300) . "</pre>";
    }
}
?>
<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
h1 { color: #d4af37; }
h2 { border-bottom: 2px solid #d4af37; margin-top: 30px; }
pre { background: #fff; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>