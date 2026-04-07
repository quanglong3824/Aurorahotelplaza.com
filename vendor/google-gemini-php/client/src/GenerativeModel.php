<?php
/**
 * Gemini GenerativeModel - Standalone Version
 * 
 * Xử lý gọi API generateContent và streamGenerateContent
 */

namespace Gemini;

class GenerativeModel
{
    private string $apiKey;
    private string $modelName;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(string $apiKey, string $modelName)
    {
        $this->apiKey = $apiKey;
        $this->modelName = $modelName;
    }

    /**
     * Generate content (non-streaming)
     * 
     * @param string $prompt Nội dung prompt
     * @return Response
     */
    public function generateContent(string $prompt): Response
    {
        $url = "{$this->baseUrl}/models/{$this->modelName}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
            ]
        ];

        $responseBody = $this->makeRequest($url, $payload);
        return new Response($responseBody);
    }

    /**
     * Stream generate content (SSE)
     * 
     * @param string $prompt Nội dung prompt
     * @return \Generator Yields Response objects
     */
    public function streamGenerateContent(string $prompt): \Generator
    {
        $url = "{$this->baseUrl}/models/{$this->modelName}:streamGenerateContent?key={$this->apiKey}&alt=sse";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // Process SSE stream
        $buffer = '';
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $data) use (&$buffer) {
            $buffer .= $data;

            // Process complete SSE messages
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $message = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                if (strpos($message, 'data: ') === 0) {
                    $jsonData = trim(substr($message, 6));
                    if (!empty($jsonData) && $jsonData !== '[DONE]') {
                        try {
                            $decoded = json_decode($jsonData, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                yield new Response($decoded);
                            }
                        } catch (\Exception $e) {
                            // Skip invalid JSON
                        }
                    }
                }
            }
            return strlen($data);
        });

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Make HTTP request to Gemini API
     */
    private function makeRequest(string $url, array $payload): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL Error: $error");
        }

        if ($httpCode === 429) {
            throw new \Exception("Rate limit exceeded (429). Please wait and try again.");
        }

        if ($httpCode === 403 || $httpCode === 401) {
            throw new \Exception("Invalid API Key or permission denied ({$httpCode})");
        }

        if ($httpCode !== 200) {
            throw new \Exception("API Error: HTTP {$httpCode} - $responseBody");
        }

        $decoded = json_decode($responseBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response: " . json_last_error_msg());
        }

        return $decoded;
    }
}