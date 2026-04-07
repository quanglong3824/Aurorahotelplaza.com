<?php
/**
 * Vendor Autoload - Aurora Team Standalone Version
 * 
 * File này tự động load các class cần thiết cho Gemini API client
 * KHÔNG CẦN composer install - Chỉ cần upload thư mục vendor/ này lên
 * 
 * Usage:
 *   require_once 'vendor/autoload.php';
 *   $client = new Gemini\Client('YOUR_API_KEY');
 *   $response = $client->generativeModel('gemini-2.0-flash')->generateContent('Hello');
 *   echo $response->text();
 */

// Register PSR-4 autoloader for Gemini namespace
spl_autoload_register(function ($class) {
    // Define base path
    $baseDir = __DIR__ . '/';

    // Gemini client namespace
    if (strpos($class, 'Gemini\\') === 0) {
        $relativeClass = substr($class, strlen('Gemini\\'));
        $file = $baseDir . 'google-gemini-php/client/src/' . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    // Guzzle HTTP client namespaces
    $guzzleNamespaces = [
        'GuzzleHttp\\' => 'guzzlehttp/guzzle/src/',
        'GuzzleHttp\\Promise\\' => 'guzzlehttp/promises/src/',
        'GuzzleHttp\\Psr7\\' => 'guzzlehttp/psr7/src/',
    ];

    foreach ($guzzleNamespaces as $namespace => $path) {
        if (strpos($class, $namespace) === 0) {
            $relativeClass = substr($class, strlen($namespace));
            $file = $baseDir . $path . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    }

    // PSR namespaces
    $psrNamespaces = [
        'Psr\\Http\\Message\\' => 'psr/http-message/src/',
        'Psr\\Http\\Client\\' => 'psr/http-client/src/',
    ];

    foreach ($psrNamespaces as $namespace => $path) {
        if (strpos($class, $namespace) === 0) {
            $relativeClass = substr($class, strlen($namespace));
            $file = $baseDir . $path . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    }

    return false;
});

// Create class aliases for convenience
if (!class_exists('Gemini') && class_exists('Gemini\Client')) {
    class_alias('Gemini\Client', 'Gemini');
}

// Helper function (optional)
if (!function_exists('gemini')) {
    /**
     * Create a new Gemini client
     * 
     * @param string $apiKey API Key
     * @return Gemini\Client
     */
    function gemini(string $apiKey): Gemini\Client
    {
        return new Gemini\Client($apiKey);
    }
}

return true;