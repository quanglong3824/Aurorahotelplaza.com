<?php
// helpers/api_key_manager.php

require_once __DIR__ . '/../config/load_env.php';

/**
 * Lấy Provider AI đang hoạt động (gemini, qwen)
 */
function get_active_ai_provider() {
    $file = __DIR__ . '/../config/ai_active_provider.txt';
    if (file_exists($file)) {
        $saved = trim(file_get_contents($file));
        if (in_array($saved, ['gemini', 'qwen'])) return $saved;
    }
    $p = env('AI_PROVIDER', 'gemini');
    // error_log("AI Provider: " . $p); // Uncomment to debug
    return $p;
}

/**
 * Đặt Provider AI đang hoạt động
 */
function set_active_ai_provider($provider) {
    if (!in_array($provider, ['gemini', 'qwen'])) return false;
    $file = __DIR__ . '/../config/ai_active_provider.txt';
    return file_put_contents($file, $provider);
}

/**
 * Lấy API Key cho Qwen
 */
function get_active_qwen_key() {
    $key = env('QWEN_API_KEY', '');
    if (empty($key)) {
        // error_log("QWEN API KEY is empty in env");
    }
    return $key;
}

/**
 * Lấy Model cho Qwen
 */
function get_active_qwen_model() {
    return env('QWEN_MODEL', 'qwen-max');
}

function get_active_gemini_key()
{
    global $GEMINI_API_KEYS;

    // Load config nếu chưa có
    $key_file = __DIR__ . '/../config/api_keys.php';
    if (file_exists($key_file)) {
        require_once $key_file;
    }

    $valid_keys = get_all_valid_keys();

    if (empty($valid_keys)) {
        return '';
    }

    $index_file = __DIR__ . '/../config/current_key_idx.txt';
    $current_idx = 0;
    if (file_exists($index_file)) {
        $current_idx = (int) file_get_contents($index_file);
    }

    if ($current_idx >= count($valid_keys)) {
        $current_idx = 0;
        file_put_contents($index_file, 0);
    }

    // Kiểm tra xem Rate Limit có đang block key này không
    $limits = get_key_rate_limits();
    $now = time();
    $start_idx = $current_idx;

    // Tìm key đầu tiên không bị block
    while (isset($limits[$current_idx])) {
        $check_ts = is_array($limits[$current_idx]) ? ($limits[$current_idx]['reset_time'] ?? 0) : $limits[$current_idx];
        if ($check_ts <= $now) break;
        
        $current_idx++;
        if ($current_idx >= count($valid_keys))
            $current_idx = 0;
        if ($current_idx == $start_idx) {
            // Tất cả các key đều bị block! Trả về key có thời gian chờ NGẮN NHẤT
            return $valid_keys[$start_idx]; // fallback (sẽ bị lỗi limit tiếp, nhưng để cho người dùng xem lỗi)
        }
    }

    // Nếu phải xoay vòng để tìm ra key sống sót, update lại file
    if ($current_idx != $start_idx) {
        file_put_contents($index_file, $current_idx);
    }

    return $valid_keys[$current_idx];
}

function rotate_gemini_key()
{
    $valid_keys = get_all_valid_keys();

    if (count($valid_keys) <= 1)
        return false; // Không có key để xoay vòng

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
        if ($current_idx >= count($valid_keys)) {
            $current_idx = 0;
        }
        if ($current_idx == $start_idx) {
            break; // Đã xoay 1 vòng, tất cả đều tèo
        }
        
        $limit_val = $limits[$current_idx] ?? 0;
        $check_ts = is_array($limit_val) ? ($limit_val['reset_time'] ?? 0) : $limit_val;
    } while ($check_ts > $now);

    // Cập nhật index xuống file
    file_put_contents($index_file, $current_idx);

    return $valid_keys[$current_idx];
}

function get_all_valid_keys()
{
    global $GEMINI_API_KEYS;
    $key_file = __DIR__ . '/../config/api_keys.php';
    if (file_exists($key_file)) {
        require_once $key_file;
    }

    $valid_keys = [];
    if (!empty($GEMINI_API_KEYS) && is_array($GEMINI_API_KEYS)) {
        $valid_keys = array_filter($GEMINI_API_KEYS, function ($k) {
            return !empty(trim($k)) && strpos($k, 'ĐIỀN_API_KEY') === false;
        });
        $valid_keys = array_values($valid_keys);
    }

    // Tương thích ngược với define cũ
    $gemini_key = env('GEMINI_API_KEY');
    if ($gemini_key && !empty($gemini_key) && strpos($gemini_key, 'ĐIỀN_API_KEY') === false) {
        if (!in_array($gemini_key, $valid_keys)) {
            $valid_keys[] = $gemini_key;
        }
    }

    if (empty($valid_keys)) {
        $env_key = getenv('GEMINI_API_KEY');
        if ($env_key)
            $valid_keys[] = $env_key;
    }

    return $valid_keys;
}

function get_active_key_index()
{
    $index_file = __DIR__ . '/../config/current_key_idx.txt';
    if (file_exists($index_file)) {
        return (int) file_get_contents($index_file);
    }
    return 0;
}

// Hàm ghi nhận chi tiêu (Tokens và Request) của một Key
function log_key_usage($key_id, $tokens_used, $role = 'admin')
{
    $log_file = __DIR__ . '/../config/key_usage_stats.json';
    $stats = [];

    // Đọc log cũ nếu có
    if (file_exists($log_file)) {
        $data = file_get_contents($log_file);
        $stats = json_decode($data, true) ?: [];
    }

    // Khởi tạo nếu key này chưa được track ngày hôm nay
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

    // Cộng dồn
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

    // Lưu lại
    file_put_contents($log_file, json_encode($stats, JSON_PRETTY_PRINT));
}

// Ghi nhận một Key bị dính Rate Limit (HTTP 429) và thời gian sống sót
function mark_key_rate_limited($key_index, $retry_seconds = 60)
{
    $file = __DIR__ . '/../config/rate_limits.json';
    $limits = [];
    if (file_exists($file)) {
        $limits = json_decode(file_get_contents($file), true) ?: [];
    }
    // Lưu timestamp thời điểm sẽ được "thả tự do"
    $limits[$key_index] = time() + (int) $retry_seconds;
    file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));
}

// Lấy danh sách các Key đang bị Rate Limit và Timestamp tha bổng
function get_key_rate_limits()
{
    $file = __DIR__ . '/../config/rate_limits.json';
    if (!file_exists($file))
        return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

// Lấy thông kê sử dụng của các Key trong ngày
function get_key_usage_stats()
{
    $log_file = __DIR__ . '/../config/key_usage_stats.json';
    if (!file_exists($log_file))
        return [];

    $stats = json_decode(file_get_contents($log_file), true);
    $today = date('Y-m-d');
    return $stats[$today] ?? [];
}

