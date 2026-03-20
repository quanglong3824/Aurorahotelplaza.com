<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
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
    $tt = $stat['tokens'] ?? 0;
    $rt = $stat['requests'] ?? 0;
    $at = $stat['admin_tokens'] ?? 0;
    $ar = $stat['admin_requests'] ?? 0;
    $ct = $stat['client_tokens'] ?? 0;
    $cr = $stat['client_requests'] ?? 0;

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

<div class="flex justify-between items-center mb-8">
    <div class="flex items-center gap-3">
        <div class="p-3 bg-indigo-50 dark:bg-slate-800 rounded-2xl border border-indigo-100 dark:border-slate-700">
            <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400">query_stats</span>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Tổng quan API Quota</h3>
            <p class="text-sm text-gray-500">Cập nhật lúc: <?php echo $last_updated_time; ?></p>
        </div>
    </div>
    <button onclick="window.location.reload()" class="bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all hover:shadow-md border border-gray-200 dark:border-slate-700">
        <span class="material-symbols-outlined text-[20px]">refresh</span> Làm mới
    </button>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">AI Provider</h3>
            <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <span class="material-symbols-outlined text-purple-500 text-xl">smart_toy</span>
            </div>
        </div>
        <p class="text-2xl font-black text-gray-900 dark:text-white uppercase"><?php echo $active_provider; ?></p>
        <div class="flex items-center gap-1.5 mt-2">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            <span class="text-xs font-bold text-gray-500"><?php echo ($active_provider === 'qwen') ? get_active_qwen_model() : (defined('AI_MODEL') ? AI_MODEL : 'gemini-2.0-flash'); ?></span>
        </div>
    </div>

    <div class="stat-card !border-l-4 !border-l-blue-500">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Hệ thống (Admin)</h3>
            <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <span class="material-symbols-outlined text-blue-500 text-xl">admin_panel_settings</span>
            </div>
        </div>
        <p class="text-2xl font-black text-gray-900 dark:text-white"><?php echo number_format($admin_tokens); ?> <span class="text-xs font-normal text-gray-400 italic">tk</span></p>
        <p class="text-xs font-bold text-blue-600 dark:text-blue-400 mt-2 flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">call_made</span> <?php echo number_format($admin_requests); ?> requests
        </p>
    </div>

    <div class="stat-card !border-l-4 !border-l-emerald-500">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Khách hàng (Client)</h3>
            <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                <span class="material-symbols-outlined text-emerald-500 text-xl">support_agent</span>
            </div>
        </div>
        <p class="text-2xl font-black text-gray-900 dark:text-white"><?php echo number_format($client_tokens); ?> <span class="text-xs font-normal text-gray-400 italic">tk</span></p>
        <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-2 flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">call_received</span> <?php echo number_format($client_requests); ?> requests
        </p>
    </div>

    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Trạng Thái Key</h3>
            <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <span class="material-symbols-outlined text-orange-500 text-xl">vpn_key</span>
            </div>
        </div>
        <p class="text-2xl font-black text-gray-900 dark:text-white">
            <?php echo ($active_provider === 'qwen') ? 'Qwen API' : 'Key #' . $current_active_key_idx; ?>
        </p>
        <p class="text-xs font-bold text-green-600 dark:text-green-400 mt-2 flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">check_circle</span> Hoạt động ổn định
        </p>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="flex items-center gap-2 mb-6 bg-gray-100/50 dark:bg-slate-800/50 p-1.5 rounded-2xl w-fit border border-gray-200 dark:border-slate-700">
    <button onclick="switchTab('gemini')" id="tab-gemini" 
        class="tab-btn flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all <?php echo $active_provider === 'gemini' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
        <span class="material-symbols-outlined text-[20px]">google</span>
        Google Gemini
    </button>
    <button onclick="switchTab('qwen')" id="tab-qwen" 
        class="tab-btn flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all <?php echo $active_provider === 'qwen' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
        <span class="material-symbols-outlined text-[20px]">rocket_launch</span>
        Alibaba Qwen
    </button>
</div>

<!-- Tab Content: Gemini -->
<div id="content-gemini" class="tab-content <?php echo $active_provider !== 'gemini' ? 'hidden' : ''; ?> animate-fade-in">
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-500">api</span>
                <span class="font-bold text-gray-900 dark:text-white text-lg">Chi tiết danh sách Gemini Keys</span>
            </div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Auto-Rotating Enabled</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-slate-700/50 text-gray-500 dark:text-gray-400 uppercase text-[11px] font-black tracking-wider">
                    <tr>
                        <th class="px-8 py-4">API Key ID</th>
                        <th class="px-6 py-4 text-center">Trạng Thái</th>
                        <th class="px-6 py-4 text-right">Tokens Đã Dùng</th>
                        <th class="px-6 py-4 text-center">Số Requests</th>
                        <th class="px-8 py-4 text-right">Lần cuối sử dụng</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    <?php if (empty($valid_keys)): ?>
                        <tr><td colspan="5" class="px-8 py-12 text-center text-gray-400 font-medium">Chưa cấu hình Gemini Key trong hệ thống.</td></tr>
                    <?php else: ?>
                        <?php foreach ($valid_keys as $idx => $val): 
                            $stat = $today_stats[$idx] ?? [];
                            $is_active = ($idx == $current_active_key_idx && $active_provider === 'gemini');
                            $tt = $stat['tokens'] ?? 0;
                            $rt = $stat['requests'] ?? 0;
                        ?>
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-slate-700 flex items-center justify-center font-bold text-xs text-gray-500"><?php echo $idx + 1; ?></div>
                                    <span class="font-mono text-xs font-bold text-gray-600 dark:text-gray-300"><?php echo substr($val, 0, 8) . '...' . substr($val, -4); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <?php if ($is_active): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-full text-[11px] font-black">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span> ACTIVE
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-gray-100 dark:bg-slate-700 text-gray-500 rounded-full text-[11px] font-bold">STANDBY</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 text-right font-black text-gray-900 dark:text-white"><?php echo number_format($tt); ?></td>
                            <td class="px-6 py-5 text-center">
                                <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-lg font-bold"><?php echo number_format($rt); ?></span>
                            </td>
                            <td class="px-8 py-5 text-right text-xs font-bold text-gray-400 italic"><?php echo $stat['last_used'] ?? '--:--:--'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab Content: Qwen -->
<div id="content-qwen" class="tab-content <?php echo $active_provider !== 'qwen' ? 'hidden' : ''; ?> animate-fade-in">
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center gap-2">
            <span class="material-symbols-outlined text-purple-500">auto_awesome</span>
            <span class="font-bold text-gray-900 dark:text-white text-lg">Alibaba Qwen (DashScope) Analytics</span>
        </div>
        <div class="p-8">
            <?php 
            $q_stat = $today_stats['qwen'] ?? [];
            $q_tt = $q_stat['tokens'] ?? 0;
            $q_rt = $q_stat['requests'] ?? 0;
            ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
                <div class="relative overflow-hidden p-8 rounded-3xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white shadow-lg shadow-indigo-500/20">
                    <div class="absolute top-0 right-0 p-4 opacity-20"><span class="material-symbols-outlined text-6xl">generating_tokens</span></div>
                    <p class="text-sm font-bold text-white/80 uppercase tracking-widest mb-2">Tokens Used</p>
                    <h4 class="text-5xl font-black"><?php echo number_format($q_tt); ?></h4>
                </div>
                <div class="p-8 rounded-3xl bg-white dark:bg-slate-700 border border-gray-100 dark:border-slate-600 shadow-sm">
                    <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Total Requests</p>
                    <h4 class="text-5xl font-black text-gray-900 dark:text-white"><?php echo number_format($q_rt); ?></h4>
                </div>
                <div class="p-8 rounded-3xl bg-white dark:bg-slate-700 border border-gray-100 dark:border-slate-600 shadow-sm">
                    <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Last Activity</p>
                    <h4 class="text-2xl font-black text-gray-900 dark:text-white mt-3"><?php echo $q_stat['last_used'] ?? 'No data yet'; ?></h4>
                </div>
            </div>
            
            <div class="bg-gray-50 dark:bg-slate-900/50 p-8 rounded-3xl border border-gray-100 dark:border-slate-700">
                <h5 class="font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-green-500">info</span> Thông tin kết nối API Qwen
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
                    <div class="flex flex-col gap-1.5">
                        <span class="text-[11px] font-black text-gray-400 uppercase">Provider Endpoint</span>
                        <span class="text-sm font-bold text-indigo-500 font-mono">dashscope.aliyuncs.com</span>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <span class="text-[11px] font-black text-gray-400 uppercase">Current Model</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo get_active_qwen_model(); ?></span>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <span class="text-[11px] font-black text-gray-400 uppercase">Authentication</span>
                        <span class="text-sm font-bold text-green-600 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Bearer Token Verified
                        </span>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <span class="text-[11px] font-black text-gray-400 uppercase">Interface</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">OpenAI-Compatible Mode v1</span>
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
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-white', 'dark:bg-slate-700', 'text-indigo-600', 'shadow-sm');
        btn.classList.add('text-gray-500');
    });
    
    const activeBtn = document.getElementById('tab-' + tab);
    activeBtn.classList.remove('text-gray-500');
    activeBtn.classList.add('bg-white', 'dark:bg-slate-700', 'text-indigo-600', 'shadow-sm');
}
</script>

<style>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<?php require_once 'includes/admin-footer.php'; ?>