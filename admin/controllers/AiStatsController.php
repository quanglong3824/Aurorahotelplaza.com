<?php
/**
 * Aurora Hotel Plaza - AI Stats Controller
 */

require_once __DIR__ . '/../../helpers/api_key_manager.php';

function getAiStatsData() {
    // Đọc toàn bộ file thống kê JSON
    $log_file = __DIR__ . '/../../config/key_usage_stats.json';
    $all_stats = [];
    if (file_exists($log_file)) {
        $data = file_get_contents($log_file);
        if ($data) {
            $all_stats = json_decode($data, true) ?: [];
        }
    }

    $today = date('Y-m-d');
    $today_stats = $all_stats[$today] ?? [];

    // Tính tổng dung lượng hôm nay
    $total_tokens = 0;
    $total_requests = 0;
    $admin_tokens = 0;
    $admin_requests = 0;
    $client_tokens = 0;
    $client_requests = 0;

    foreach ($today_stats as $key_idx => $stat) {
        // Tương thích lùi với logs cũ
        $at = isset($stat['admin_tokens']) ? $stat['admin_tokens'] : 0;
        $ar = isset($stat['admin_requests']) ? $stat['admin_requests'] : 0;
        $ct = $stat['client_tokens'] ?? 0;
        $cr = $stat['client_requests'] ?? 0;

        // Nếu chưa phân quyền (logs cũ), mặc định đổ hết cho Admin
        if (!isset($stat['admin_tokens']) && !isset($stat['client_tokens'])) {
            $at = $stat['tokens'] ?? 0;
            $ar = $stat['requests'] ?? 0;
        }

        // Đôi khi $tt < $at + $ct do lỗi log cũ
        $tt = $stat['tokens'] ?? 0;
        $rt = $stat['requests'] ?? 0;

        // Đảm bảo Total bao phủ đủ
        if ($tt < ($at + $ct))
            $tt = $at + $ct;
        if ($rt < ($ar + $cr))
            $rt = $ar + $cr;

        $total_tokens += $tt;
        $total_requests += $rt;

        $admin_tokens += $at;
        $admin_requests += $ar;
        $client_tokens += $ct;
        $client_requests += $cr;
    }

    $budget_tokens_per_key = 1000000;
    $budget_requests_per_key = 1500;
    $valid_keys = get_all_valid_keys();
    $total_keys = count($valid_keys);
    $max_daily_tokens = $total_keys * $budget_tokens_per_key;
    $current_active_key_idx = get_active_key_index();

    $percent_tokens = $max_daily_tokens > 0 ? min(100, round(($total_tokens / $max_daily_tokens) * 100, 2)) : 0;
    $last_updated_time = file_exists($log_file) ? date('m/d/Y H:i:s', filemtime($log_file)) : 'Chưa có dữ liệu';
    $rate_limits = get_key_rate_limits();

    return [
        'today_stats' => $today_stats,
        'total_tokens' => $total_tokens,
        'total_requests' => $total_requests,
        'admin_tokens' => $admin_tokens,
        'admin_requests' => $admin_requests,
        'client_tokens' => $client_tokens,
        'client_requests' => $client_requests,
        'total_keys' => $total_keys,
        'max_daily_tokens' => $max_daily_tokens,
        'current_active_key_idx' => $current_active_key_idx,
        'percent_tokens' => $percent_tokens,
        'last_updated_time' => $last_updated_time,
        'rate_limits' => $rate_limits,
        'valid_keys' => $valid_keys,
        'budget_tokens_per_key' => $budget_tokens_per_key
    ];
}
