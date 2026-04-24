<?php
/**
 * Debug API: Check Environment & Gemini Keys
 * GET /admin/api/debug-env-keys.php
 *
 * Use this to debug:
 * - Which .env file is being loaded
 * - What Gemini keys are detected
 * - Current rate limit status
 */

session_start();
header('Content-Type: application/json');

require_once '../../config/load_env.php';
require_once '../../helpers/api_key_manager.php';

// Check admin auth (optional - can be disabled for debugging)
$require_auth = true;
if ($require_auth && (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized - login as admin']);
    exit;
}

// Gather debug info
$env_paths = debug_env_path();
$keys = get_all_valid_keys();
$limits = get_key_rate_limits();
$key_status = debug_key_status();

// Check env values
$env_debug = [
    'GEMINI_API_KEYS' => env('GEMINI_API_KEYS', 'NOT FOUND'),
    'GEMINI_API_KEY' => env('GEMINI_API_KEY', 'NOT FOUND'),
    'AI_MODEL' => env('AI_MODEL', 'NOT FOUND'),
    'AI_PROVIDER' => env('AI_PROVIDER', 'NOT FOUND'),
];

// Mask key values for security
foreach ($keys as $i => $key) {
    $keys[$i] = substr($key, 0, 10) . '...' . substr($key, -4);
}

if (!empty($env_debug['GEMINI_API_KEYS']) && $env_debug['GEMINI_API_KEYS'] !== 'NOT FOUND') {
    $env_debug['GEMINI_API_KEYS'] = substr($env_debug['GEMINI_API_KEYS'], 0, 15) . '... (length: ' . strlen(env('GEMINI_API_KEYS', '')) . ')';
}
if (!empty($env_debug['GEMINI_API_KEY']) && $env_debug['GEMINI_API_KEY'] !== 'NOT FOUND') {
    $env_debug['GEMINI_API_KEY'] = substr($env_debug['GEMINI_API_KEY'], 0, 15) . '...';
}

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'ai_config_path' => defined('AI_CONFIG_PATH') ? AI_CONFIG_PATH : 'NOT DEFINED',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET',
    'env_file_checks' => $env_paths,
    'env_values' => $env_debug,
    'keys_found' => count($keys),
    'keys_preview' => $keys,
    'key_status' => $key_status,
    'rate_limits_raw' => $limits,
    'recommendation' => count($keys) === 0
        ? 'No keys found! Check your .env file path and format.'
        : (count($limits) > 0
            ? 'Rate limits exist. Call refresh-gemini-keys.php?action=clear_limits to reset.'
            : 'All good! Keys are ready to use.')
], JSON_PRETTY_PRINT);