<?php
require_once __DIR__ . '/config/load_env.php';
require_once __DIR__ . '/helpers/api_key_manager.php';

header('Content-Type: application/json');

$debug = [
    'AI_PROVIDER_ENV' => env('AI_PROVIDER'),
    'QWEN_API_KEY_ENV' => !empty(env('QWEN_API_KEY')) ? 'SET (Length: ' . strlen(env('QWEN_API_KEY')) . ')' : 'NOT SET',
    'QWEN_MODEL_ENV' => env('QWEN_MODEL'),
    'GEMINI_API_KEY_ENV' => !empty(env('GEMINI_API_KEY')) ? 'SET' : 'NOT SET',
    'ACTIVE_PROVIDER' => get_active_ai_provider(),
    'QWEN_KEY' => !empty(get_active_qwen_key()) ? 'SET' : 'NOT SET',
    'QWEN_MODEL' => get_active_qwen_model(),
    'ALL_GEMINI_KEYS_COUNT' => count(get_all_valid_keys()),
    'SERVER_DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
    'SEARCH_PATHS' => []
];

// Re-run path logic to see where it searches
$current_dir = __DIR__;
for ($i = 0; $i < 6; $i++) {
    $debug['SEARCH_PATHS'][] = $current_dir . '/config/.env';
    $debug['SEARCH_PATHS'][] = $current_dir . '/.env';
    $parent = dirname($current_dir);
    if ($parent === $current_dir || $parent === '/' || $parent === '\\') break;
    $current_dir = $parent;
}
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
    $debug['SEARCH_PATHS'][] = dirname($doc_root) . '/config/.env';
    $debug['SEARCH_PATHS'][] = dirname($doc_root) . '/.env';
}

echo json_encode($debug, JSON_PRETTY_PRINT);
