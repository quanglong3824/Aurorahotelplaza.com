<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
$db = getDB();

$page_title = 'Thống Kê Lưu Lượng AI (API Quota)';
$page_subtitle = 'Giám sát và phân tích lưu lượng sử dụng API Gemini & Qwen';

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

foreach ($today_stats as $key_id => $stat) {
    $at = $stat['admin_tokens'] ?? 0;
    $ar = $stat['admin_requests'] ?? 0;
    $ct = $stat['client_tokens'] ?? 0;
    $cr = $stat['client_requests'] ?? 0;
    $tt = $stat['tokens'] ?? 0;
    $rt = $stat['requests'] ?? 0;

    $total_tokens += $tt;
    $total_requests += $rt;
    $admin_tokens += $at;
    $admin_requests += $ar;
    $client_tokens += $ct;
    $client_requests += $cr;
}

$budget_tokens_per_key = 1000000;
$valid_keys = get_all_valid_keys();
$total_keys = count($valid_keys);
$max_daily_tokens = $total_keys * $budget_tokens_per_key;
$current_active_key_idx = get_active_key_index();
$last_updated_time = file_exists($log_file) ? date('m/d/Y H:i:s', filemtime($log_file)) : 'Chưa có dữ liệu';
$rate_limits = get_key_rate_limits();
$active_provider = get_active_ai_provider();

require_once 'includes/admin-header.php';
?>

<div class="p-4 sm:ml-64">
    <div class="p-4 border-2 border-gray-200 border-dashed rounded-lg dark:border-gray-700 mt-14">
        
        <div class="flex justify-between items-center mb-6">
            <div class="text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-800 px-4 py-2 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-indigo-500 animate-spin-slow">sync</span>
                Cập nhật lần cuối: <b class="text-gray-900 dark:text-white"><?php echo $last_updated_time; ?></b>
            </div>
            <button onclick="window.location.reload()" class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 transition-colors border border-indigo-100 dark:border-indigo-800">
                <span class="material-symbols-outlined text-[18px]">refresh</span> Làm mới dữ liệu
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">AI Provider</h3>
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <span class="material-symbols-outlined text-purple-500">smart_toy</span>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white uppercase"><?php echo $active_provider; ?></p>
                <p class="text-xs text-gray-500 mt-2">Model: <b><?php echo ($active_provider === 'qwen') ? get_active_qwen_model() : (defined('AI_MODEL') ? AI_MODEL : 'gemini-2.0-flash'); ?></b></p>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 border-l-4 border-l-blue-500">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Admin AI</h3>
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <span class="material-symbols-outlined text-blue-500">admin_panel_settings</span>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($admin_tokens); ?> <span class="text-xs text-gray-500 font-normal">tk</span></p>
                <p class="text-sm text-gray-500 mt-2">Requests: <b><?php echo number_format($admin_requests); ?></b></p>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 border-l-4 border-l-emerald-500">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Client AI</h3>
                    <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                        <span class="material-symbols-outlined text-emerald-500">support_agent</span>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($client_tokens); ?> <span class="text-xs text-gray-500 font-normal">tk</span></p>
                <p class="text-sm text-gray-500 mt-2">Requests: <b><?php echo number_format($client_requests); ?></b></p>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Trạng Thái Key</h3>
                    <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                        <span class="material-symbols-outlined text-orange-500">key</span>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo ($active_provider === 'qwen') ? 'Qwen Active' : 'Gemini #' . $current_active_key_idx; ?>
                </p>
                <p class="text-xs text-green-600 mt-2 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span> Hệ thống ổn định
                </p>
            </div>
        </div>

        <!-- Tabs for Providers -->
        <div class="mb-6 border-b border-gray-200 dark:border-slate-700">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                <li class="mr-2">
                    <button onclick="switchTab('gemini')" id="tab-gemini" class="inline-flex items-center p-4 border-b-2 rounded-t-lg group <?php echo $active_provider === 'gemini' ? 'text-indigo-600 border-indigo-600 active' : 'border-transparent hover:text-gray-600 hover:border-gray-300'; ?>">
                        <span class="material-symbols-outlined mr-2">google</span> Gemini
                    </button>
                </li>
                <li class="mr-2">
                    <button onclick="switchTab('qwen')" id="tab-qwen" class="inline-flex items-center p-4 border-b-2 rounded-t-lg group <?php echo $active_provider === 'qwen' ? 'text-indigo-600 border-indigo-600 active' : 'border-transparent hover:text-gray-600 hover:border-gray-300'; ?>">
                        <span class="material-symbols-outlined mr-2">rocket_launch</span> Qwen
                    </button>
                </li>
            </ul>
        </div>

        <!-- Gemini Content -->
        <div id="content-gemini" class="tab-content <?php echo $active_provider !== 'gemini' ? 'hidden' : ''; ?>">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700 font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-500">data_exploration</span> Google Gemini Keys
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-slate-700 text-gray-700 dark:text-gray-300 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">Key ID</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3">Tokens</th>
                                <th class="px-6 py-3">Reqs</th>
                                <th class="px-6 py-3">Lần Cuối</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($valid_keys)): ?>
                                <tr><td colspan="5" class="p-6 text-center">Chưa cấu hình Gemini Key.</td></tr>
                            <?php else: ?>
                                <?php foreach ($valid_keys as $idx => $val): 
                                    $stat = $today_stats[$idx] ?? [];
                                    $is_active = ($idx == $current_active_key_idx && $active_provider === 'gemini');
                                    $tt = $stat['tokens'] ?? 0;
                                    $rt = $stat['requests'] ?? 0;
                                ?>
                                <tr class="border-b dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                    <td class="px-6 py-4 font-mono text-xs"><?php echo substr($val, 0, 8) . '...' . substr($val, -4); ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($is_active): ?>
                                            <span class="bg-green-100 text-green-800 text-[10px] px-2 py-1 rounded-full font-bold">ACTIVE</span>
                                        <?php else: ?>
                                            <span class="bg-gray-100 text-gray-600 text-[10px] px-2 py-1 rounded-full">STANDBY</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 font-bold"><?php echo number_format($tt); ?></td>
                                    <td class="px-6 py-4"><?php echo number_format($rt); ?></td>
                                    <td class="px-6 py-4 text-gray-500 text-xs"><?php echo $stat['last_used'] ?? 'N/A'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Qwen Content -->
        <div id="content-qwen" class="tab-content <?php echo $active_provider !== 'qwen' ? 'hidden' : ''; ?>">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700 font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-purple-500">auto_awesome</span> Alibaba Qwen
                </div>
                <div class="p-8">
                    <?php 
                    $q_stat = $today_stats['qwen'] ?? [];
                    $q_tt = $q_stat['tokens'] ?? 0;
                    $q_rt = $q_stat['requests'] ?? 0;
                    ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                        <div class="p-6 bg-purple-50 dark:bg-purple-900/10 rounded-2xl border border-purple-100 dark:border-purple-900/30">
                            <p class="text-sm text-purple-600 dark:text-purple-400 font-semibold mb-2">Tokens</p>
                            <h4 class="text-4xl font-bold text-gray-900 dark:text-white"><?php echo number_format($q_tt); ?></h4>
                        </div>
                        <div class="p-6 bg-indigo-50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-100 dark:border-indigo-900/30">
                            <p class="text-sm text-indigo-600 dark:text-indigo-400 font-semibold mb-2">Requests</p>
                            <h4 class="text-4xl font-bold text-gray-900 dark:text-white"><?php echo number_format($q_rt); ?></h4>
                        </div>
                        <div class="p-6 bg-slate-50 dark:bg-slate-700/30 rounded-2xl border border-slate-200 dark:border-slate-700">
                            <p class="text-sm text-gray-500 font-semibold mb-2">Lần Cuối</p>
                            <h4 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $q_stat['last_used'] ?? 'N/A'; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.getElementById('content-' + tab).classList.remove('hidden');
    document.querySelectorAll('[id^="tab-"]').forEach(t => {
        t.classList.remove('text-indigo-600', 'border-indigo-600', 'active');
        t.classList.add('border-transparent');
    });
    const activeBtn = document.getElementById('tab-' + tab);
    activeBtn.classList.remove('border-transparent');
    activeBtn.classList.add('text-indigo-600', 'border-indigo-600', 'active');
}
</script>

<?php require_once 'includes/admin-footer.php'; ?>