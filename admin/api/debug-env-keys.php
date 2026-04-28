<?php
/**
 * Debug API: Check Environment & AI Keys
 * GET /admin/api/debug-env-keys.php
 *
 * Use this to debug:
 * - Which .env file is being loaded
 * - What AI keys are detected (Alibaba/Gemini)
 * - Current provider and rate limit status
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

$provider = get_active_ai_provider();

$env_paths = debug_env_path();
$limits = get_key_rate_limits($provider);

if ($provider === 'alibaba') {
    $keys = get_all_valid_alibaba_keys();
    $key_status = debug_alibaba_key_status();
} else {
    $keys = get_all_valid_keys();
    $key_status = debug_key_status();
}

$env_debug = [
    'AI_PROVIDER' => env('AI_PROVIDER', 'alibaba'),
    'ALIBABA_API_KEY' => env('ALIBABA_API_KEY', 'NOT FOUND'),
    'ALIBABA_API_KEYS' => env('ALIBABA_API_KEYS', 'NOT FOUND'),
    'ALIBABA_API_URL' => env('ALIBABA_API_URL', 'https://coding-intl.dashscope.aliyuncs.com/v1'),
    'ALIBABA_MODEL' => env('ALIBABA_MODEL', 'glm-5'),
    'GEMINI_API_KEY' => env('GEMINI_API_KEY', 'NOT FOUND'),
    'GEMINI_API_KEYS' => env('GEMINI_API_KEYS', 'NOT FOUND'),
    'GEMINI_MODEL' => env('AI_MODEL', 'gemini-2.0-flash'),
];

foreach ($keys as $i => $key) {
    $keys[$i] = substr($key, 0, 15) . '...' . substr($key, -4);
}

foreach ($env_debug as $k => $v) {
    if (strpos($k, 'API_KEY') !== false && $v !== 'NOT FOUND' && !empty($v)) {
        $env_debug[$k] = substr($v, 0, 15) . '... (length: ' . strlen($v) . ')';
    }
}

$active_key = $provider === 'alibaba' ? get_active_alibaba_key() : get_active_gemini_key();

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'active_provider' => $provider,
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
        ? "No {$provider} keys found! Check your .env file path and format."
        : (count($limits) > 0
            ? "Rate limits exist for {$provider}. Keys will auto-recover when limits expire."
            : 'All good! Keys are ready to use.')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

function debug_alibaba_key_status() {
    $keys = get_all_valid_alibaba_keys();
    $limits = get_key_rate_limits('alibaba');
    $current_idx = get_active_alibaba_key_index();
    $now = time();

    $status = [];
    foreach ($keys as $i => $key) {
        $limit_until = $limits[$i] ?? 0;
        $status[] = [
            'index' => $i,
            'key_preview' => substr($key, 0, 15) . '...',
            'is_current' => $i === $current_idx,
            'rate_limited' => $limit_until > $now,
            'limit_expires_in' => $limit_until > $now ? ($limit_until - $now) . 's' : 'N/A'
        ];
    }

    return $status;
}