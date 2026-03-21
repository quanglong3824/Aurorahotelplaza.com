<?php
// helpers/api_key_manager.php

require_once __DIR__ . '/../config/load_env.php';
@require_once __DIR__ . '/../config/api_keys.php';

/**
 * Lấy Provider AI đang hoạt động (Luôn là qwen)
 */
function get_active_ai_provider() {
    if (defined('AI_PROVIDER')) return AI_PROVIDER;
    return env('AI_PROVIDER', 'qwen');
}

/**
 * Lấy API Key cho Qwen
 */
function get_active_qwen_key() {
    if (defined('QWEN_API_KEY') && QWEN_API_KEY !== '') return QWEN_API_KEY;
    return env('QWEN_API_KEY', '');
}

/**
 * Lấy Model cho Qwen
 */
function get_active_qwen_model() {
    if (defined('QWEN_MODEL')) return QWEN_MODEL;
    return env('QWEN_MODEL', 'qwen-max');
}

/**
 * Hàm ghi nhận chi tiêu (Tokens và Request) của một Key
 */
function log_key_usage($key_id, $tokens_used, $role = 'admin')
{
    $log_file = __DIR__ . '/../config/key_usage_stats.json';
    $stats = [];

    if (file_exists($log_file)) {
        $data = file_get_contents($log_file);
        $stats = json_decode($data, true) ?: [];
    }

    $today = date('Y-m-d');
    if (!isset($stats[$today])) {
        $stats = [$today => []];
    }

    if (!isset($stats[$today][$key_id])) {
        $stats[$today][$key_id] = [
            'requests' => 0,
            'tokens' => 0,
            'admin_requests' => 0,
            'admin_tokens' => 0,
            'client_requests' => 0,
            'client_tokens' => 0,
            'last_used' => null
        ];
    }

    $stats[$today][$key_id]['requests'] += 1;
    $stats[$today][$key_id]['tokens'] += (int) $tokens_used;

    if ($role === 'admin') {
        $stats[$today][$key_id]['admin_requests'] += 1;
        $stats[$today][$key_id]['admin_tokens'] += (int) $tokens_used;
    } else {
        $stats[$today][$key_id]['client_requests'] += 1;
        $stats[$today][$key_id]['client_tokens'] += (int) $tokens_used;
    }

    $stats[$today][$key_id]['last_used'] = date('H:i:s');
    file_put_contents($log_file, json_encode($stats, JSON_PRETTY_PRINT));
}

function get_key_usage_stats()
{
    $log_file = __DIR__ . '/../config/key_usage_stats.json';
    if (!file_exists($log_file)) return [];
    $stats = json_decode(file_get_contents($log_file), true);
    $today = date('Y-m-d');
    return $stats[$today] ?? [];
}

// Dummy functions to prevent errors in other files that might still call them
function get_active_gemini_key() { return ''; }
function rotate_gemini_key() { return false; }
function get_all_valid_keys() { return []; }
function mark_key_rate_limited($idx, $sec) { return true; }
function get_key_rate_limits() { return []; }
function get_active_key_index() { return 0; }
