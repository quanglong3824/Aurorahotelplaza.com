<?php
/**
 * Gemini PHP Client - Standalone Version
 * 
 * Client gọi Gemini API không cần thư viện bên ngoài
 * Chỉ cần PHP 7.4+ với cURL enabled
 * 
 * Usage:
 *   $client = new Gemini\Client('YOUR_API_KEY');
 *   $response = $client->generativeModel('gemini-2.0-flash')->generateContent('Hello');
 *   echo $response->text();
 */

namespace Gemini;

class Client
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function generativeModel(string $modelName): GenerativeModel
    {
        return new GenerativeModel($this->apiKey, $modelName);
    }
}