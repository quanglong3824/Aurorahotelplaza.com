<?php
$page_title = 'Thống Kê Lưu Lượng AI (API Quota)';
$page_subtitle = 'Giám sát và phân tích lưu lượng sử dụng API Gemini của Khách Hàng và Quản Trị Viên';
require_once 'includes/admin-header.php';
require_once __DIR__ . '/../helpers/api_key_manager.php';

// Đọc toàn bộ file thống kê JSON
$log_file = __DIR__ . '/../config/key_usage_stats.json';
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
$last_updated_time = file_exists($log_file) ? date('d/m/Y H:i:s', filemtime($log_file)) : 'Chưa có dữ liệu';
$rate_limits = get_key_rate_limits();
?>

<div class="flex justify-between items-center mb-6">
    <div
        class="text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-800 px-4 py-2 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px] text-indigo-500 animate-spin-slow">sync</span>
        Cập nhật lần cuối: <b class="text-gray-900 dark:text-white">
            <?php echo $last_updated_time; ?>
        </b>
    </div>
    <button onclick="window.location.reload()"
        class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 transition-colors border border-indigo-100 dark:border-indigo-800">
        <span class="material-symbols-outlined text-[18px]">refresh</span>
        Làm mới dữ liệu
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Thống kê chung -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Total Tokens Hôm Nay</h3>
            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                <span class="material-symbols-outlined text-indigo-500">generating_tokens</span>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">
            <?php echo number_format($total_tokens); ?> <span class="text-sm font-normal text-gray-400">/
                <?php echo number_format($max_daily_tokens); ?>
            </span>
        </p>
        <div class="mt-4">
            <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo $percent_tokens; ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2 text-right">
                <?php echo $percent_tokens; ?>% giới hạn (
                <?php echo $total_keys; ?> keys)
            </p>
        </div>
    </div>

    <div class="stat-card border-l-4 border-l-blue-500">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Hệ Thống (Admin AI)</h3>
            <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <span class="material-symbols-outlined text-blue-500">admin_panel_settings</span>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">
            <?php echo number_format($admin_tokens); ?> <span class="text-xs text-gray-500 font-normal">Tokens</span>
        </p>
        <p class="text-sm text-gray-500 mt-2">Đã gọi: <b>
                <?php echo number_format($admin_requests); ?>
            </b> requests</p>
        <?php $a_pct = $total_tokens > 0 ? round(($admin_tokens / $total_tokens) * 100, 1) : 0; ?>
        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Chiếm
            <?php echo $a_pct; ?>% lưu lượng hôm nay
        </p>
    </div>

    <div class="stat-card border-l-4 border-l-emerald-500">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Khách Hàng (Customer AI)</h3>
            <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                <span class="material-symbols-outlined text-emerald-500">support_agent</span>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">
            <?php echo number_format($client_tokens); ?> <span class="text-xs text-gray-500 font-normal">Tokens</span>
        </p>
        <p class="text-sm text-gray-500 mt-2">Đấu nối trả lời: <b>
                <?php echo number_format($client_requests); ?>
            </b> requests</p>
        <?php $c_pct = $total_tokens > 0 ? round(($client_tokens / $total_tokens) * 100, 1) : 0; ?>
        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Chiếm
            <?php echo $c_pct; ?>% lưu lượng hôm nay
        </p>
    </div>

    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">API Key Đang Hoạt Động</h3>
            <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <span class="material-symbols-outlined text-orange-500">key</span>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">Key #
            <?php echo $current_active_key_idx; ?>
        </p>
        <p class="text-sm text-gray-500 mt-2 text-green-600 flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">check_circle</span>
            Hệ thống Auto-Rotate Đang Bật
        </p>
    </div>
</div>

<div
    class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden mb-8">
    <div
        class="p-6 border-b border-gray-200 dark:border-slate-700 font-bold text-gray-900 dark:text-white flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-indigo-500">data_exploration</span>
            Chi Tiết Tiêu Thụ Theo Từng API Key (Hôm Nay)
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-32">Mã Khóa API</th>
                    <th>Trạng Thái</th>
                    <th>Tokens Đã Dùng</th>
                    <th>Requests</th>
                    <th>Phân Bổ Định Tuyến (Lõi / Web)</th>
                    <th>Lần Dùng Cuối</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($valid_keys)): ?>
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-500">Chưa có API Key nào được cấu hình trong hệ
                            thống.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($valid_keys as $k_idx => $key_val):
                        $stat = $today_stats[$k_idx] ?? [];
                        $at = isset($stat['admin_tokens']) ? $stat['admin_tokens'] : ($stat['tokens'] ?? 0);
                        $ct = $stat['client_tokens'] ?? 0;
                        // Kiểm tra an toàn tính toán tổng thể:
                        $tt = $at + $ct;
                        // Đè $tt về số nhỏ hơn hoặc bằng sum
                        // Chống lỗi khi total > admin + client vì lí do nào đó
                        if (isset($stat['tokens']) && $stat['tokens'] > $tt) {
                            $tt = $stat['tokens'];
                        }
                        $rt = $stat['requests'] ?? 0;

                        $isActive = ($k_idx == $current_active_key_idx);

                        $apct = $tt > 0 ? round(($at / $tt) * 100, 1) : 0;
                        $cpct = $tt > 0 ? round(($ct / $tt) * 100, 1) : 0;

                        $quota_pct = round(($tt / $budget_tokens_per_key) * 100, 1);
                        $quota_color = $quota_pct > 90 ? 'bg-red-500' : ($quota_pct > 70 ? 'bg-orange-500' : 'bg-green-500');

                        $limit_ts = $rate_limits[$k_idx] ?? 0;
                        $is_limited = ($limit_ts > time());
                        $wait_sec = $is_limited ? ($limit_ts - time()) : 0;

                        // Rút gọn Key để bảo mật
                        $safe_key = substr($key_val, 0, 8) . '...' . substr($key_val, -4);
                        ?>
                        <tr>
                            <td class="font-mono text-center">
                                <b class="text-xs text-slate-800 dark:text-slate-300"
                                    title="<?php echo htmlspecialchars($key_val); ?>">
                                    <?php echo $safe_key; ?>
                                </b>
                                <?php if ($is_limited): ?>
                                    <br><span class="badge badge-danger mt-1 shadow-sm"><span
                                            class="material-symbols-outlined text-[12px] mr-1">timer</span>Bị giới hạn</span>
                                <?php elseif ($isActive): ?>
                                    <br><span class="badge badge-success mt-1 shadow-sm"><span
                                            class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1 animate-pulse"></span>Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_limited): ?>
                                    <div class="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm font-bold">
                                        <span class="material-symbols-outlined animate-pulse">warning</span>
                                        Quota Exceeded
                                    </div>
                                    <div class="text-xs text-red-500 mt-1">Mở khóa lại sau: <b><?php echo $wait_sec; ?></b> giây
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col gap-1 w-32">
                                        <div class="flex justify-between text-xs text-gray-500">
                                            <span>Quota</span>
                                            <b>
                                                <?php echo $quota_pct; ?>%
                                            </b>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-1.5">
                                            <div class="<?php echo $quota_color; ?> h-1.5 rounded-full"
                                                style="width: <?php echo min(100, $quota_pct); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-bold text-gray-900 dark:text-white">
                                    <?php echo number_format($tt); ?>
                                </div>
                                <div class="text-xs text-gray-500">Max: 1,000,000</div>
                            </td>
                            <td>
                                <div class="font-medium">
                                    <?php echo number_format($rt); ?> / 1,500
                                </div>
                            </td>
                            <td>
                                <div class="w-64">
                                    <div class="flex items-center justify-between text-[10px] mb-1">
                                        <span class="text-blue-600 font-bold">Admin:
                                            <?php echo $apct; ?>%
                                        </span>
                                        <span class="text-emerald-600 font-bold">Client:
                                            <?php echo $cpct; ?>%
                                        </span>
                                    </div>
                                    <div class="flex h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="bg-blue-500 h-full" style="width: <?php echo $apct; ?>%"></div>
                                        <div class="bg-emerald-500 h-full" style="width: <?php echo $cpct; ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                                        <span>
                                            <?php echo number_format($at); ?> tk
                                        </span>
                                        <span>
                                            <?php echo number_format($ct); ?> tk
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="font-mono text-xs text-gray-500">
                                <?php echo $stat['last_used'] ?? 'N/A'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>