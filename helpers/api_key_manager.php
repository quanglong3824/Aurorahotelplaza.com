<?php
// helpers/api_key_manager.php

require_once __DIR__ . '/../config/load_env.php';
@require_once __DIR__ . '/../config/api_keys.php';

/**
 * Lấy Provider AI đang hoạt động (Luôn là gemini)
 */
function get_active_ai_provider() {
    return 'gemini';
}

/**
 * Đặt Provider AI đang hoạt động (Vô hiệu hóa việc đổi sang qwen)
 */
function set_active_ai_provider($provider) {
    return true;
}

/**
 * Lấy API Key cho Gemini
 */
function get_active_gemini_key()
{
    $valid_keys = get_all_valid_keys();
    if (empty($valid_keys)) return '';

    $index_file = __DIR__ . '/../config/current_key_idx.txt';
    $current_idx = 0;
    if (file_exists($index_file)) {
        $current_idx = (int) file_get_contents($index_file);
    }

    if ($current_idx >= count($valid_keys)) {
        $current_idx = 0;
        file_put_contents($index_file, 0);
    }

    $limits = get_key_rate_limits();
    $now = time();
    $start_idx = $current_idx;

    while (isset($limits[$current_idx])) {
        $check_ts = is_array($limits[$current_idx]) ? ($limits[$current_idx]['reset_time'] ?? 0) : $limits[$current_idx];
        if ($check_ts <= $now) break;
        
        $current_idx++;
        if ($current_idx >= count($valid_keys)) $current_idx = 0;
        if ($current_idx == $start_idx) return $valid_keys[$start_idx];
    }

    if ($current_idx != $start_idx) {
        file_put_contents($index_file, $current_idx);
    }

    return $valid_keys[$current_idx];
}

/**
 * Xoay vòng Key Gemini khi gặp lỗi 429
 */
function rotate_gemini_key()
{
    $valid_keys = get_all_valid_keys();
    if (count($valid_keys) <= 1) return false;

    $index_file = __DIR__ . '/../config/current_key_idx.txt';
    $current_idx = 0;
    if (file_exists($index_file)) {
        $current_idx = (int) file_get_contents($index_file);
    }

    $limits = get_key_rate_limits();
    $now = time();
    $start_idx = $current_idx;

    do {
        $current_idx++;
        if ($current_idx >= count($valid_keys)) $current_idx = 0;
        if ($current_idx == $start_idx) break;
        $limit_val = $limits[$current_idx] ?? 0;
        $check_ts = is_array($limit_val) ? ($limit_val['reset_time'] ?? 0) : $limit_val;
    } while ($check_ts > $now);

    file_put_contents($index_file, $current_idx);
    return $valid_keys[$current_idx];
}

/**
 * Thu thập tất cả các Key Gemini hợp lệ
 */
function get_all_valid_keys()
{
    $valid_keys = [];

    // 1. Lấy từ biến toàn cục trong api_keys.php
    global $GEMINI_API_KEYS;
    if (!empty($GEMINI_API_KEYS) && is_array($GEMINI_API_KEYS)) {
        $valid_keys = array_merge($valid_keys, $GEMINI_API_KEYS);
    }

    // 2. Lấy từ env() chuỗi danh sách
    $env_keys_str = env('GEMINI_API_KEYS');
    if ($env_keys_str) {
        $ek = array_map('trim', explode(',', $env_keys_str));
        $valid_keys = array_merge($valid_keys, $ek);
    }

    // 3. Lấy từ define cũ
    if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
        $valid_keys[] = GEMINI_API_KEY;
    }

    // Làm sạch và lọc các key trống/placeholder
    return array_values(array_unique(array_filter($valid_keys, function($k) {
        return !empty($k) && strpos($k, 'ĐIỀN_API_KEY') === false && strlen($k) > 10;
    })));
}

function get_active_key_index() {
    $index_file = __DIR__ . '/../config/current_key_idx.txt';
    return file_exists($index_file) ? (int) file_get_contents($index_file) : 0;
}

function log_key_usage($key_id, $tokens_used, $role = 'admin') {
    $log_file = __DIR__ . '/../config/key_usage_stats.json';
    $stats = file_exists($log_file) ? json_decode(file_get_contents($log_file), true) ?: [] : [];
    $today = date('Y-m-d');
    if (!isset($stats[$today])) $stats[$today] = [];
    if (!isset($stats[$today][$key_id])) {
        $stats[$today][$key_id] = ['requests' => 0, 'tokens' => 0, 'admin_requests' => 0, 'admin_tokens' => 0, 'client_requests' => 0, 'client_tokens' => 0, 'last_used' => null];
    }
    $stats[$today][$key_id]['requests'] += 1;
    $stats[$today][$key_id]['tokens'] += (int) $tokens_used;
    if ($role === 'admin') {
        $stats[$today][$key_id]['admin_requests'] += 1; $stats[$today][$key_id]['admin_tokens'] += (int) $tokens_used;
    } else {
        $stats[$today][$key_id]['client_requests'] += 1; $stats[$today][$key_id]['client_tokens'] += (int) $tokens_used;
    }
    $stats[$today][$key_id]['last_used'] = date('H:i:s');
    file_put_contents($log_file, json_encode($stats, JSON_PRETTY_PRINT));
}

function mark_key_rate_limited($key_index, $retry_seconds = 60) {
    $file = __DIR__ . '/../config/rate_limits.json';
    $limits = file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];
    $limits[$key_index] = time() + (int) $retry_seconds;
    file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));
}

function get_key_rate_limits() {
    $file = __DIR__ . '/../config/rate_limits.json';
    return file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];
}

function get_key_usage_stats() {
    $log_file = __DIR__ . '/../config/key_usage_stats.json';
    if (!file_exists($log_file)) return [];
    $stats = json_decode(file_get_contents($log_file), true);
    return $stats[date('Y-m-d')] ?? [];
}

// Dummy functions to prevent errors
function get_active_qwen_key() { return ''; }
function get_active_qwen_model() { return 'gemini-model-only'; }
