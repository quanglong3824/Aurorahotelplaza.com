<?php
/**
 * Google Gemini API Keys Configuration
 * Strictly loads from environment variables
 */

require_once __DIR__ . '/load_env.php';

$env_keys = env('GEMINI_API_KEYS', '');

if (!empty($env_keys)) {
    $GEMINI_API_KEYS = array_map('trim', explode(',', $env_keys));
} else {
    $GEMINI_API_KEYS = [];
}

// Single key fallback for backward compatibility
define('GEMINI_API_KEY', env('GEMINI_API_KEY', ''));
?>
