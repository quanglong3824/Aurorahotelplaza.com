<?php
/**
 * Debug API: Check Environment & Gemini Keys
 * GET /admin/api/debug-env-keys.php
 */

session_start();
header('Content-Type: application/json');

require_once '../../config/load_env.php';
require_once '../../helpers/api_key_manager.php';

// Check admin auth
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized - login as admin']);
    exit;
}

$env_paths = debug_env_path();
$limits = get_key_rate_limits();

$keys = get_all_valid_keys();
$key_status = debug_key_status();

$env_debug = [
    'GEMINI_API_KEY' => env('GEMINI_API_KEY', 'NOT FOUND'),
    'GEMINI_API_KEYS' => env('GEMINI_API_KEYS', 'NOT FOUND'),
    'AI_MODEL' => env('AI_MODEL', 'gemini-2.0-flash'),
];

foreach ($keys as $i => $key) {
    $keys[$i] = substr($key, 0, 15) . '...' . substr($key, -4);
}

foreach ($env_debug as $k => $v) {
    if (strpos($k, 'API_KEY') !== false && $v !== 'NOT FOUND' && !empty($v)) {
        $env_debug[$k] = substr($v, 0, 15) . '... (length: ' . strlen($v) . ')';
    }
}

$active_key = get_active_gemini_key();

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'provider' => 'gemini',
    'active_key_preview' => !empty($active_key) ? substr($active_key, 0, 15) . '...' : 'NO KEY',
    'ai_config_path' => defined('AI_CONFIG_PATH') ? AI_CONFIG_PATH : sys_get_temp_dir(),
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET',
    'env_file_checks' => $env_paths,
    'env_values' => $env_debug,
    'keys_found' => count($keys),
    'keys_preview' => $keys,
    'key_status' => $key_status,
    'rate_limits_raw' => $limits,
    'recommendation' => count($keys) === 0
        ? "No Gemini keys found! Check your .env file path and format."
        : (count($limits) > 0
            ? "Rate limits exist. Keys will auto-recover when limits expire."
            : 'All good! Keys are ready to use.')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);