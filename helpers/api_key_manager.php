<?php
/**
 * API Key Manager - Aurora Hotel Plaza
 * Quản lý và xoay vòng Gemini API Keys
 * Tự động xử lý rate limit (429)
 */

require_once __DIR__ . '/../config/load_env.php';
@include_once __DIR__ . '/../config/api_keys.php';

if (!defined('AI_CONFIG_PATH')) {
    define('AI_CONFIG_PATH', sys_get_temp_dir());
}

/**
 * Lấy Gemini API Key đang hoạt động
 * Tự động skip keys bị rate limit
 */
function get_active_gemini_key()
{
    $valid_keys = get_all_valid_keys();
    $total = count($valid_keys);

    if ($total === 0) {
        error_log("API Key Manager: No valid Gemini API keys found");
        return '';
    }

    cleanup_expired_rate_limits();

    $limits = get_key_rate_limits();
    $now = time();

    for ($i = 0; $i < $total; $i++) {
        $limit_until = $limits[$i] ?? 0;
        if ($limit_until <= $now) {
            $index_file = AI_CONFIG_PATH . '/current_key_idx.txt';
            @file_put_contents($index_file, $i);
            return $valid_keys[$i];
        }
    }

    error_log("API Key Manager: All keys rate limited, clearing limits");
    clear_all_rate_limits();
    return $valid_keys[0];
}

/**
 * Xoay vòng Key Gemini khi gặp lỗi 429
 */
function rotate_gemini_key()
{
    $valid_keys = get_all_valid_keys();
    $total = count($valid_keys);

    if ($total <= 1) {
        error_log("API Key Manager: Cannot rotate - only 1 key available");
        return false;
    }

    $index_file = AI_CONFIG_PATH . '/current_key_idx.txt';
    $current_idx = 0;
    if (file_exists($index_file)) {
        $current_idx = (int) file_get_contents($index_file);
    }

    mark_key_rate_limited($current_idx, 60);

    cleanup_expired_rate_limits();
    $limits = get_key_rate_limits();
    $now = time();

    for ($i = 0; $i < $total; $i++) {
        $next_idx = ($current_idx + 1 + $i) % $total;
        $limit_until = $limits[$next_idx] ?? 0;

        if ($limit_until <= $now) {
            file_put_contents($index_file, $next_idx);
            error_log("API Key Manager: Rotated to key index $next_idx");
            return $valid_keys[$next_idx];
        }
    }

    error_log("API Key Manager: All keys exhausted after rotation. Auto-switching to Opencode fallback.");
    set_active_ai_provider('opencode');
    
    // Vẫn clear limit và set next index cho Gemini nếu sau này quay lại
    clear_all_rate_limits();
    $next_idx = ($current_idx + 1) % $total;
    file_put_contents($index_file, $next_idx);
    return false; // Return false để báo hiệu Gemini đã sập
}

/**
 * Thu thập tất cả các Key Gemini hợp lệ từ nhiều nguồn
 */
function get_all_valid_keys()
{
    $valid_keys = [];

    // 1. Từ env() - ưu tiên cao nhất (GEMINI_API_KEYS có thể chứa nhiều key)
    $env_keys_str = env('GEMINI_API_KEYS', '');
    if ($env_keys_str) {
        $keys = array_map('trim', explode(',', $env_keys_str));
        $valid_keys = array_merge($valid_keys, $keys);
    }

    // 2. Từ GEMINI_API_KEY đơn lẻ
    $single_key = env('GEMINI_API_KEY', '');
    if ($single_key) {
        $valid_keys[] = $single_key;
    }

    // 3. Từ biến toàn cục api_keys.php (fallback)
    global $GEMINI_API_KEYS;
    if (!empty($GEMINI_API_KEYS) && is_array($GEMINI_API_KEYS)) {
        $valid_keys = array_merge($valid_keys, $GEMINI_API_KEYS);
    }

    // 4. Từ constant (legacy)
    if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
        $valid_keys[] = GEMINI_API_KEY;
    }

    // Clean: unique, no placeholders, valid length
    $valid_keys = array_filter(array_unique($valid_keys), function($k) {
        $k = trim($k);
        return !empty($k)
            && strlen($k) > 30
            && strpos($k, 'ĐIỀN_') === false
            && strpos($k, 'YOUR_') === false
            && strpos($k, 'xxx') === false
            && strpos($k, 'API_KEY') === false;
    });

    return array_values($valid_keys);
}

/**
 * Lấy index của key hiện tại
 */
function get_active_key_index() {
    $index_file = AI_CONFIG_PATH . '/current_key_idx.txt';
    return file_exists($index_file) ? (int) file_get_contents($index_file) : 0;
}

/**
 * Log usage stats (optional)
 */
function log_key_usage($key_index, $tokens_used = 0, $role = 'client') {
    $log_file = AI_CONFIG_PATH . '/key_usage_stats.json';
    $stats = file_exists($log_file) ? json_decode(file_get_contents($log_file), true) ?: [] : [];
    $today = date('Y-m-d');

    if (!isset($stats[$today])) $stats[$today] = [];
    if (!isset($stats[$today][$key_index])) {
        $stats[$today][$key_index] = [
            'requests' => 0,
            'tokens' => 0,
            'client_requests' => 0,
            'client_tokens' => 0,
            'admin_requests' => 0,
            'admin_tokens' => 0,
            'last_used' => null
        ];
    }

    $stats[$today][$key_index]['requests'] += 1;
    $stats[$today][$key_index]['tokens'] += (int) $tokens_used;
    $stats[$today][$key_index]['last_used'] = date('H:i:s');

    if ($role === 'admin') {
        $stats[$today][$key_index]['admin_requests'] += 1;
        $stats[$today][$key_index]['admin_tokens'] += (int) $tokens_used;
    } else {
        $stats[$today][$key_index]['client_requests'] += 1;
        $stats[$today][$key_index]['client_tokens'] += (int) $tokens_used;
    }

    @file_put_contents($log_file, json_encode($stats, JSON_PRETTY_PRINT));
}

/**
 * Mark key as rate limited
 */
function mark_key_rate_limited($key_index, $retry_seconds = 60) {
    $file = AI_CONFIG_PATH . '/rate_limits_gemini.json';
    $limits = file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];

    $valid_keys = get_all_valid_keys();
    if ($key_index >= count($valid_keys)) {
        $key_index = 0;
    }

    $limits[$key_index] = time() + (int) $retry_seconds;
    @file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));
    error_log("API Key Manager: Gemini Key index $key_index rate limited for {$retry_seconds}s");
}

/**
 * Get rate limits data
 */
function get_key_rate_limits() {
    $file = AI_CONFIG_PATH . '/rate_limits_gemini.json';
    if (!file_exists($file)) return [];

    $limits = json_decode(file_get_contents($file), true);
    return $limits ?: [];
}

/**
 * Clean up expired rate limit entries
 */
function cleanup_expired_rate_limits() {
    $file = AI_CONFIG_PATH . '/rate_limits_gemini.json';
    if (!file_exists($file)) return;

    $limits = json_decode(file_get_contents($file), true) ?: [];
    $now = time();
    $cleaned = [];

    foreach ($limits as $idx => $until) {
        if ($until > $now) {
            $cleaned[$idx] = $until;
        }
    }

    if (count($cleaned) !== count($limits)) {
        @file_put_contents($file, json_encode($cleaned, JSON_PRETTY_PRINT));
        error_log("API Key Manager: Cleaned up " . (count($limits) - count($cleaned)) . " expired rate limits");
    }
}

/**
 * Clear all rate limits
 */
function clear_all_rate_limits() {
    $file = AI_CONFIG_PATH . '/rate_limits_gemini.json';
    if (file_exists($file)) {
        @file_put_contents($file, '{}');
        error_log("API Key Manager: Cleared all Gemini rate limits");
    }
}

/**
 * Get today's usage stats
 */
function get_key_usage_stats() {
    $log_file = AI_CONFIG_PATH . '/key_usage_stats.json';
    if (!file_exists($log_file)) return [];
    $stats = json_decode(file_get_contents($log_file), true);
    return $stats[date('Y-m-d')] ?? [];
}

/**
 * Debug: Get all key info
 */
function debug_key_status() {
    $keys = get_all_valid_keys();
    $limits = get_key_rate_limits();
    $current_idx = get_active_key_index();
    $now = time();

    $status = [];
    foreach ($keys as $i => $key) {
        $limit_until = $limits[$i] ?? 0;
        $status[] = [
            'index' => $i,
            'key_preview' => substr($key, 0, 10) . '...',
            'is_current' => $i === $current_idx,
            'rate_limited' => $limit_until > $now,
            'limit_expires_in' => $limit_until > $now ? ($limit_until - $now) . 's' : 'N/A'
        ];
    }

    return $status;
}

/**
 * Force refresh keys from .env
 */
function refresh_keys_from_env() {
    clear_all_rate_limits();

    $index_file = AI_CONFIG_PATH . '/current_key_idx.txt';
    @file_put_contents($index_file, 0);

    if (function_exists('loadEnvVariables')) {
        loadEnvVariables();
    }

    $keys = get_all_valid_keys();
    error_log("API Key Manager: Refreshed - found " . count($keys) . " Gemini keys");

    return count($keys) > 0;
}

/**
 * Quản lý Provider (Gemini / Opencode)
 */
function get_active_ai_provider() {
    $file = AI_CONFIG_PATH . '/current_provider.txt';
    $provider = 'gemini';
    
    if (file_exists($file)) {
        $provider = trim(file_get_contents($file));
    }
    
    // Auto-detect: if gemini has no keys but opencode has a key, auto switch to opencode
    if ($provider === 'gemini') {
        global $GEMINI_API_KEYS;
        $has_gemini = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) || !empty($GEMINI_API_KEYS);
        if (!$has_gemini && defined('OPENCODE_API_KEY') && !empty(OPENCODE_API_KEY)) {
            $provider = 'opencode';
            set_active_ai_provider('opencode');
        }
    }
    
    return $provider;
}

function set_active_ai_provider($provider) {
    if (in_array($provider, ['gemini', 'opencode'])) {
        @file_put_contents(AI_CONFIG_PATH . '/current_provider.txt', $provider);
        error_log("API Key Manager: Switched provider to $provider");
        return true;
    }
    return false;
}

function get_active_api_key() {
    $provider = get_active_ai_provider();
    if ($provider === 'opencode') {
        return defined('OPENCODE_API_KEY') ? OPENCODE_API_KEY : '';
    }
    return get_active_gemini_key();
}