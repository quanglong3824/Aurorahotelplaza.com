<?php
// helpers/api_key_manager.php

require_once __DIR__ . '/../config/load_env.php';
@include_once __DIR__ . '/../config/api_keys.php';

/**
 * Lấy Provider AI đang hoạt động (Mặc định là qwen nếu có cấu hình)
 */
function get_active_ai_provider() {
    return env('AI_PROVIDER', 'qwen');
}

/**
 * Đặt Provider AI đang hoạt động
 */
function set_active_ai_provider($provider) {
    // Nếu có hỗ trợ lưu vào file, có thể lưu tại AI_CONFIG_PATH
    return true;
}

function get_active_qwen_key() {
    $key = env('QWEN_API_KEY');
    if (!$key) return '';
    
    // Hỗ trợ nếu người dùng nhập nhiều key cách nhau bởi dấu phẩy
    $keys = array_map('trim', explode(',', $key));
    return $keys[0];
}

function get_active_qwen_model() {
    // Ưu tiên QWEN_MODEL, nếu không có thì dùng AI_MODEL, cuối cùng là fallback qwen-max
    return env('QWEN_MODEL', env('AI_MODEL', 'qwen-max'));
}
function get_active_gemini_key()
{
    $valid_keys = get_all_valid_keys();
    $total = count($valid_keys);
    if ($total === 0) return '';

    $index_file = AI_CONFIG_PATH . '/current_key_idx.txt';
    $current_idx = 0;
    if (file_exists($index_file)) {
        $current_idx = (int) @file_get_contents($index_file);
    }

    if ($current_idx >= $total) {
        $current_idx = 0;
        @file_put_contents($index_file, 0);
    }

    $limits = get_key_rate_limits();
    $now = time();

    // Nếu key hiện tại bị giới hạn, hãy rotate ngay lập tức
    $limit_until = $limits[$current_idx] ?? 0;
    if ($limit_until > $now) {
        $new_key = rotate_gemini_key();
        return $new_key ?: $valid_keys[0]; // Trả về key mới hoặc fallback key đầu tiên
    }

    return $valid_keys[$current_idx];
}

/**
 * Xoay vòng Key Gemini khi gặp lỗi 429
 */
function rotate_gemini_key()
{
    $valid_keys = get_all_valid_keys();
    $total = count($valid_keys);
    if ($total <= 1) return false;

    $index_file = AI_CONFIG_PATH . '/current_key_idx.txt';
    $current_idx = 0;
    if (file_exists($index_file)) {
        $current_idx = (int) file_get_contents($index_file);
    }

    $limits = get_key_rate_limits();
    $now = time();
    $next_idx = $current_idx;

    // Thử tìm key tiếp theo trong vòng lặp (tránh lặp vô tận)
    for ($i = 0; $i < $total; $i++) {
        $next_idx = ($next_idx + 1) % $total;
        
        $limit_until = $limits[$next_idx] ?? 0;
        if ($limit_until <= $now) {
            // Đã tìm thấy key không bị limit
            file_put_contents($index_file, $next_idx);
            return $valid_keys[$next_idx];
        }
    }

    return false; // Không còn key nào khả dụng
}

/**
 * Thu thập tất cả các Key Gemini hợp lệ
 */
function get_all_valid_keys()
{
    $valid_keys = [];

    // 1. ƯU TIÊN: Lấy từ env() đơn lẻ hoặc danh sách (Đây là nơi người dùng thường cấu hình mới nhất)
    $env_keys_str = env('GEMINI_API_KEYS');
    if ($env_keys_str) {
        $ek = array_map('trim', explode(',', $env_keys_str));
        $valid_keys = array_merge($valid_keys, $ek);
    }

    $single_key = env('GEMINI_API_KEY');
    if ($single_key) {
        $valid_keys[] = $single_key;
    }

    // 2. PHỤ: Lấy từ biến toàn cục trong api_keys.php (Dự phòng)
    global $GEMINI_API_KEYS;
    if (!empty($GEMINI_API_KEYS) && is_array($GEMINI_API_KEYS)) {
        $valid_keys = array_merge($valid_keys, $GEMINI_API_KEYS);
    }
    
    if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
        $valid_keys[] = GEMINI_API_KEY;
    }

    // Làm sạch: Bỏ các key trùng, key placeholder, key quá ngắn
    $valid_keys = array_filter(array_unique($valid_keys), function($k) {
        return !empty($k) && strlen($k) > 20 && strpos($k, 'ĐIỀN_') === false;
    });

    return array_values($valid_keys);
}

function get_active_key_index() {
    $index_file = AI_CONFIG_PATH . '/current_key_idx.txt';
    return file_exists($index_file) ? (int) file_get_contents($index_file) : 0;
}

function log_key_usage($key_id, $tokens_used, $role = 'admin') {
    $log_file = AI_CONFIG_PATH . '/key_usage_stats.json';
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
    $file = AI_CONFIG_PATH . '/rate_limits.json';
    $limits = file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];
    $limits[$key_index] = time() + (int) $retry_seconds;
    file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));
}

function get_key_rate_limits() {
    $file = AI_CONFIG_PATH . '/rate_limits.json';
    return file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];
}

function get_key_usage_stats() {
    $log_file = AI_CONFIG_PATH . '/key_usage_stats.json';
    if (!file_exists($log_file)) return [];
    $stats = json_decode(file_get_contents($log_file), true);
    return $stats[date('Y-m-d')] ?? [];
}

// Dummy functions to prevent errors
function get_active_qwen_key() { return ''; }
function get_active_qwen_model() { return 'gemini-model-only'; }
