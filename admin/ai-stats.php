<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
$db = getDB();

require_once __DIR__ . '/../helpers/api_key_manager.php';

// Xử lý chuyển đổi Provider thủ công (Mặc định là Gemini)
if (isset($_POST['switch_provider'])) {
    $new_p = $_POST['switch_provider'];
    set_active_ai_provider($new_p);
    header('Location: ai-stats.php?switched=1');
    exit;
}

$page_title = 'Trung Tâm Điều Hành AI';
$page_subtitle = 'Quản lý lưu lượng và hạn mức API Gemini toàn hệ thống';

// Đọc toàn bộ file thống kê JSON
$log_file = __DIR__ . '/../config/key_usage_stats.json';
$all_stats = [];
if (file_exists($log_file)) {
    $data = @file_get_contents($log_file);
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

$valid_keys = get_all_valid_keys();
$total_keys = count($valid_keys);
$current_active_key_idx = get_active_key_index();
$last_updated_time = file_exists($log_file) ? date('H:i:s d/m/Y', filemtime($log_file)) : 'Chưa có dữ liệu';
$active_provider = get_active_ai_provider();
$rate_limits = get_key_rate_limits();
$now = time();

require_once 'includes/admin-header.php';
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    }
    .dark .glass-card {
        background: rgba(30, 41, 59, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .status-pulse {
        position: relative;
        display: inline-flex;
    }
    .status-pulse::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: inherit;
        border-radius: inherit;
        animation: pulse-ring 1.5s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
    }
    @keyframes pulse-ring {
        0% { transform: scale(0.7); opacity: 0.5; }
        100% { transform: scale(2.2); opacity: 0; }
    }
    .token-gradient {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
    }
</style>

<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                <span class="material-symbols-outlined text-3xl">hub</span>
            </div>
            <div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">AI Command Center</h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="status-pulse w-2 h-2 bg-green-500 rounded-full"></span>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Hệ thống đang hoạt động ổn định • <?php echo $last_updated_time; ?></p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <button onclick="window.location.reload()" class="p-3 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all group shadow-sm">
                <span class="material-symbols-outlined text-gray-500 dark:text-gray-400 group-hover:rotate-180 transition-transform duration-500">refresh</span>
            </button>
            <div class="h-10 w-px bg-gray-200 dark:bg-slate-700 mx-1 hidden md:block"></div>
            <div class="bg-indigo-50 dark:bg-indigo-900/20 px-4 py-2 rounded-xl border border-indigo-100 dark:border-indigo-800 flex items-center gap-3">
                <img src="../assets/img/src/logo/favicon.png" class="w-5 h-5 grayscale opacity-50" alt="">
                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Active Pool: <?php echo $total_keys; ?> Keys</span>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['switched'])): ?>
        <div class="bg-emerald-500 text-white p-4 rounded-2xl flex items-center gap-4 shadow-lg shadow-emerald-500/20 animate-bounce-in">
            <span class="material-symbols-outlined bg-white/20 p-1 rounded-full">check</span>
            <span class="font-bold">Đã đồng bộ hóa Provider thành công!</span>
        </div>
    <?php endif; ?>

    <!-- Main Dashboard Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Stats Overview -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Summary Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Tokens -->
                <div class="glass-card rounded-3xl p-6 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                            <span class="material-symbols-outlined">toll</span>
                        </div>
                        <span class="text-[10px] font-black text-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded-lg uppercase tracking-wider">Total Tokens</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo number_format($total_tokens); ?></h4>
                        <span class="text-xs font-bold text-gray-400 italic">today</span>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <div class="flex-1 h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full token-gradient rounded-full" style="width: <?php echo min(100, ($total_tokens/1000000)*100); ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Admin Usage -->
                <div class="glass-card rounded-3xl p-6 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600">
                            <span class="material-symbols-outlined">psychology</span>
                        </div>
                        <span class="text-[10px] font-black text-purple-500 bg-purple-50 dark:bg-purple-900/30 px-2 py-1 rounded-lg uppercase tracking-wider">Admin Bot</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo number_format($admin_tokens); ?></h4>
                        <span class="text-xs font-bold text-gray-400 italic">tk</span>
                    </div>
                    <p class="mt-2 text-[11px] font-bold text-purple-600 dark:text-purple-400"><?php echo number_format($admin_requests); ?> requests today</p>
                </div>

                <!-- Guest Usage -->
                <div class="glass-card rounded-3xl p-6 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined">smart_toy</span>
                        </div>
                        <span class="text-[10px] font-black text-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-lg uppercase tracking-wider">Guest AI</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo number_format($client_tokens); ?></h4>
                        <span class="text-xs font-bold text-gray-400 italic">tk</span>
                    </div>
                    <p class="mt-2 text-[11px] font-bold text-emerald-600 dark:text-emerald-400"><?php echo number_format($client_requests); ?> requests today</p>
                </div>
            </div>

            <!-- Detailed Table -->
            <div class="bg-white dark:bg-slate-800 rounded-[32px] border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm">
                <div class="p-8 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between bg-gray-50/50 dark:bg-slate-800/50">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 p-2 rounded-xl">database</span>
                        <h3 class="font-black text-gray-900 dark:text-white text-lg tracking-tight uppercase">API Keys Management</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-green-500"></span>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Failover System Ready</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[2px] border-b border-gray-50 dark:border-slate-700">
                                <th class="px-8 py-5">Index & Identifer</th>
                                <th class="px-6 py-5 text-center">Status</th>
                                <th class="px-6 py-5 text-right">Tokens</th>
                                <th class="px-6 py-5 text-center">Load</th>
                                <th class="px-8 py-5 text-right">Last Sync</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                            <?php foreach ($valid_keys as $idx => $val): 
                                $stat = $today_stats[$idx] ?? [];
                                $is_active = ($idx == $current_active_key_idx && $active_provider === 'gemini');
                                $is_blocked = isset($rate_limits[$idx]) && $rate_limits[$idx] > $now;
                                $tokens = $stat['tokens'] ?? 0;
                                $load_percent = min(100, round(($tokens / 1000000) * 100));
                            ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/30 transition-all group">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-slate-700 flex items-center justify-center font-black text-xs text-gray-400 group-hover:bg-indigo-500 group-hover:text-white transition-all">
                                            #<?php echo $idx + 1; ?>
                                        </div>
                                        <div>
                                            <p class="font-mono text-xs font-bold text-gray-700 dark:text-gray-300"><?php echo substr($val, 0, 10) . '...' . substr($val, -6); ?></p>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">Google Gemini API</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6 text-center">
                                    <?php if ($is_blocked): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg text-[10px] font-black uppercase tracking-tight">
                                            <span class="material-symbols-outlined text-xs">block</span> Cooldown
                                        </span>
                                    <?php elseif ($is_active): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-lg text-[10px] font-black uppercase tracking-tight shadow-sm">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> Running
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-50 dark:bg-slate-700 text-gray-400 dark:text-gray-500 rounded-lg text-[10px] font-black uppercase tracking-tight">
                                            Standby
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-6 text-right">
                                    <p class="font-black text-gray-900 dark:text-white"><?php echo number_format($tokens); ?></p>
                                    <p class="text-[10px] text-gray-400 font-bold"><?php echo number_format($stat['requests'] ?? 0); ?> reqs</p>
                                </td>
                                <td class="px-6 py-6 min-w-[120px]">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-500 rounded-full" style="width: <?php echo $load_percent; ?>%"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-gray-400"><?php echo $load_percent; ?>%</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <p class="text-xs font-bold text-gray-600 dark:text-gray-300"><?php echo isset($stat['last_used']) ? date('H:i', strtotime($stat['last_used'])) : '--:--'; ?></p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter mt-1 italic">Synced</p>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-6 bg-gray-50 dark:bg-slate-800/50 border-t border-gray-100 dark:border-slate-700 text-center">
                    <p class="text-xs font-bold text-gray-400 italic">Hệ thống tự động luân chuyển Key (Rotate) khi gặp lỗi 429 hoặc đạt giới hạn sử dụng.</p>
                </div>
            </div>
        </div>

        <!-- Right: Control Panel & Insights -->
        <div class="space-y-8">
            <!-- Active Model Card -->
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-[32px] p-8 text-white shadow-xl shadow-indigo-600/20 relative overflow-hidden">
                <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                    <span class="material-symbols-outlined text-[200px]">google</span>
                </div>
                <div class="relative z-10">
                    <p class="text-[10px] font-black uppercase tracking-[3px] text-indigo-200 opacity-80 mb-6">Current Configuration</p>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-14 h-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20">
                            <span class="material-symbols-outlined text-3xl">smart_toy</span>
                        </div>
                        <div>
                            <h4 class="text-xl font-black tracking-tight">Gemini Flash</h4>
                            <p class="text-xs font-medium text-indigo-100 opacity-80">v2.0-flash (Production)</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-4 flex items-center justify-between border border-white/5">
                            <span class="text-xs font-bold">Temperature</span>
                            <span class="text-xs font-black px-2 py-1 bg-white/20 rounded-lg">0.2</span>
                        </div>
                        <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-4 flex items-center justify-between border border-white/5">
                            <span class="text-xs font-bold">Max Output</span>
                            <span class="text-xs font-black px-2 py-1 bg-white/20 rounded-lg">2048</span>
                        </div>
                    </div>

                    <form method="POST" class="mt-8">
                        <button type="submit" name="switch_provider" value="gemini" class="w-full bg-white text-indigo-600 font-black py-4 rounded-2xl text-xs uppercase tracking-widest shadow-lg hover:scale-[1.02] active:scale-[0.98] transition-all">
                            Refresh Connection
                        </button>
                    </form>
                </div>
            </div>

            <!-- Failover Card -->
            <div class="glass-card rounded-[32px] p-8">
                <div class="flex items-center gap-3 mb-8">
                    <span class="material-symbols-outlined text-purple-500 bg-purple-50 dark:bg-purple-900/30 p-2 rounded-xl">security</span>
                    <h3 class="font-black text-gray-900 dark:text-white uppercase text-sm tracking-wider">Failover Strategy</h3>
                </div>
                
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white shrink-0 mt-1">
                            <span class="material-symbols-outlined text-[16px]">check</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-gray-900 dark:text-white">Auto Key-Rotation</p>
                            <p class="text-[11px] text-gray-500 font-medium mt-1">Tự động chuyển đổi sang Key khả dụng tiếp theo khi gặp mã lỗi 429.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center text-white shrink-0 mt-1">
                            <span class="material-symbols-outlined text-[16px]">priority_high</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-gray-900 dark:text-white">Rate Limit Tracking</p>
                            <p class="text-[11px] text-gray-500 font-medium mt-1">Ghi nhớ Key bị giới hạn tốc độ và tạm ngưng sử dụng trong 60 phút.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-100 dark:border-slate-700">
                    <a href="ai-logs.php" class="flex items-center justify-between group">
                        <span class="text-xs font-black text-gray-500 dark:text-gray-400 group-hover:text-indigo-500 transition-colors uppercase tracking-widest">View System Logs</span>
                        <span class="material-symbols-outlined text-gray-400 group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </a>
                </div>
            </div>

            <!-- Developer Tools -->
            <div class="bg-slate-900 dark:bg-black rounded-[32px] p-8 text-white/90">
                <div class="flex items-center gap-3 mb-6">
                    <span class="material-symbols-outlined text-emerald-400">code</span>
                    <h3 class="font-black uppercase text-sm tracking-wider">Storage Path</h3>
                </div>
                <div class="bg-white/5 rounded-2xl p-4 border border-white/10">
                    <p class="text-[10px] font-mono break-all text-gray-400">/config/key_usage_stats.json</p>
                </div>
                <div class="mt-4 flex items-center justify-between px-2">
                    <span class="text-[10px] font-bold text-gray-500 italic">JSON Persistent Storage</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
