<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/api_keys.php';
$db = getDB();

$page_title = 'Thống Kê Opencode AI';
$page_subtitle = 'Quản lý lưu lượng và token sử dụng Opencode toàn hệ thống';

// Refresh connection / clear cache (if any)
if (isset($_POST['refresh_stats'])) {
    header('Location: ai-stats.php?refreshed=1');
    exit;
}

// Thống kê từ bảng ai_logs
$stats = [
    'today' => [
        'admin' => ['tokens' => 0, 'reqs' => 0],
        'client' => ['tokens' => 0, 'reqs' => 0],
    ],
    'total' => [
        'admin' => ['tokens' => 0, 'reqs' => 0],
        'client' => ['tokens' => 0, 'reqs' => 0],
    ],
    'models' => []
];

try {
    // Tổng token hôm nay
    $stmt_today = $db->query("
        SELECT ai_type, SUM(tokens_used) as total_tokens, COUNT(*) as total_reqs 
        FROM ai_logs 
        WHERE DATE(created_at) = CURDATE() AND status = 'success'
        GROUP BY ai_type
    ");
    while ($row = $stmt_today->fetch(PDO::FETCH_ASSOC)) {
        $type = $row['ai_type'] == 'admin' ? 'admin' : 'client';
        $stats['today'][$type]['tokens'] = (int)$row['total_tokens'];
        $stats['today'][$type]['reqs'] = (int)$row['total_reqs'];
    }

    // Tổng token toàn thời gian
    $stmt_total = $db->query("
        SELECT ai_type, SUM(tokens_used) as total_tokens, COUNT(*) as total_reqs 
        FROM ai_logs 
        WHERE status = 'success'
        GROUP BY ai_type
    ");
    while ($row = $stmt_total->fetch(PDO::FETCH_ASSOC)) {
        $type = $row['ai_type'] == 'admin' ? 'admin' : 'client';
        $stats['total'][$type]['tokens'] = (int)$row['total_tokens'];
        $stats['total'][$type]['reqs'] = (int)$row['total_reqs'];
    }

    // Thống kê mô hình
    $stmt_models = $db->query("
        SELECT model_name, SUM(tokens_used) as tokens, COUNT(*) as reqs
        FROM ai_logs
        WHERE status = 'success'
        GROUP BY model_name
        ORDER BY reqs DESC
    ");
    while ($row = $stmt_models->fetch(PDO::FETCH_ASSOC)) {
        $stats['models'][] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching AI stats: " . $e->getMessage());
}

$today_total_tokens = $stats['today']['admin']['tokens'] + $stats['today']['client']['tokens'];
$today_total_reqs = $stats['today']['admin']['reqs'] + $stats['today']['client']['reqs'];

$all_time_tokens = $stats['total']['admin']['tokens'] + $stats['total']['client']['tokens'];
$all_time_reqs = $stats['total']['admin']['reqs'] + $stats['total']['client']['reqs'];

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
        background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
    }
</style>

<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">
                <span class="material-symbols-outlined text-3xl">memory</span>
            </div>
            <div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">Opencode AI Stats</h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="status-pulse w-2 h-2 bg-green-500 rounded-full"></span>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Hệ thống hoạt động với Opencode API</p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <form method="POST">
                <button type="submit" name="refresh_stats" class="p-3 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all group shadow-sm">
                    <span class="material-symbols-outlined text-gray-500 dark:text-gray-400 group-hover:rotate-180 transition-transform duration-500">refresh</span>
                </button>
            </form>
            <div class="h-10 w-px bg-gray-200 dark:bg-slate-700 mx-1 hidden md:block"></div>
            <div class="bg-emerald-50 dark:bg-emerald-900/20 px-4 py-2 rounded-xl border border-emerald-100 dark:border-emerald-800 flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-lg">check_circle</span>
                <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Active Model: <?php echo htmlspecialchars(OPENCODE_MODEL); ?></span>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['refreshed'])): ?>
        <div class="bg-emerald-500 text-white p-4 rounded-2xl flex items-center gap-4 shadow-lg shadow-emerald-500/20 animate-bounce-in">
            <span class="material-symbols-outlined bg-white/20 p-1 rounded-full">check</span>
            <span class="font-bold">Đã làm mới dữ liệu thống kê!</span>
        </div>
    <?php endif; ?>

    <!-- Main Dashboard Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Stats Overview -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Summary Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Tokens Today -->
                <div class="glass-card rounded-3xl p-6 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined">toll</span>
                        </div>
                        <span class="text-[10px] font-black text-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-lg uppercase tracking-wider">Hôm nay</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo number_format($today_total_tokens); ?></h4>
                        <span class="text-xs font-bold text-gray-400 italic">tokens</span>
                    </div>
                    <p class="mt-2 text-[11px] font-bold text-emerald-600 dark:text-emerald-400"><?php echo number_format($today_total_reqs); ?> requests hôm nay</p>
                    <div class="mt-4 flex items-center gap-2">
                        <div class="flex-1 h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full token-gradient rounded-full" style="width: <?php echo min(100, ($today_total_tokens/50000)*100); ?>%"></div>
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
                        <h4 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo number_format($stats['today']['admin']['tokens']); ?></h4>
                        <span class="text-xs font-bold text-gray-400 italic">tk</span>
                    </div>
                    <p class="mt-2 text-[11px] font-bold text-purple-600 dark:text-purple-400"><?php echo number_format($stats['today']['admin']['reqs']); ?> requests hôm nay</p>
                </div>

                <!-- Guest Usage -->
                <div class="glass-card rounded-3xl p-6 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                            <span class="material-symbols-outlined">smart_toy</span>
                        </div>
                        <span class="text-[10px] font-black text-blue-500 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded-lg uppercase tracking-wider">Guest AI</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo number_format($stats['today']['client']['tokens']); ?></h4>
                        <span class="text-xs font-bold text-gray-400 italic">tk</span>
                    </div>
                    <p class="mt-2 text-[11px] font-bold text-blue-600 dark:text-blue-400"><?php echo number_format($stats['today']['client']['reqs']); ?> requests hôm nay</p>
                </div>
            </div>

            <!-- Detailed Table -->
            <div class="bg-white dark:bg-slate-800 rounded-[32px] border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm">
                <div class="p-8 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between bg-gray-50/50 dark:bg-slate-800/50">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 p-2 rounded-xl">monitoring</span>
                        <h3 class="font-black text-gray-900 dark:text-white text-lg tracking-tight uppercase">Thống kê theo mô hình</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">All time usage</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[2px] border-b border-gray-50 dark:border-slate-700">
                                <th class="px-8 py-5">Tên Mô Hình</th>
                                <th class="px-6 py-5 text-right">Tổng Tokens</th>
                                <th class="px-6 py-5 text-right">Tổng Requests</th>
                                <th class="px-8 py-5 text-right">Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                            <?php 
                            if (empty($stats['models'])) {
                                echo '<tr><td colspan="4" class="px-8 py-6 text-center text-gray-500 text-sm">Chưa có dữ liệu.</td></tr>';
                            }
                            foreach ($stats['models'] as $idx => $model): 
                                $pct = $all_time_tokens > 0 ? round(($model['tokens'] / $all_time_tokens) * 100, 1) : 0;
                            ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/30 transition-all group">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-slate-700 flex items-center justify-center font-black text-xs text-gray-400 group-hover:bg-emerald-500 group-hover:text-white transition-all">
                                            #<?php echo $idx + 1; ?>
                                        </div>
                                        <div>
                                            <p class="font-mono text-sm font-bold text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($model['model_name'] ?: 'Unknown'); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6 text-right">
                                    <p class="font-black text-gray-900 dark:text-white"><?php echo number_format($model['tokens']); ?></p>
                                    <p class="text-[10px] text-gray-400 font-bold">tokens</p>
                                </td>
                                <td class="px-6 py-6 text-right">
                                    <p class="font-black text-gray-900 dark:text-white"><?php echo number_format($model['reqs']); ?></p>
                                    <p class="text-[10px] text-gray-400 font-bold">reqs</p>
                                </td>
                                <td class="px-8 py-6 min-w-[120px]">
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-xs font-black text-gray-700 dark:text-gray-300"><?php echo $pct; ?>%</span>
                                        <div class="w-full h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden flex justify-end">
                                            <div class="h-full bg-emerald-500 rounded-full" style="width: <?php echo $pct; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Control Panel & Insights -->
        <div class="space-y-8">
            <!-- Active Model Card -->
            <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-[32px] p-8 text-white shadow-xl shadow-emerald-600/20 relative overflow-hidden">
                <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                    <span class="material-symbols-outlined text-[200px]">api</span>
                </div>
                <div class="relative z-10">
                    <p class="text-[10px] font-black uppercase tracking-[3px] text-emerald-200 opacity-80 mb-6">Cấu Hình Hiện Tại</p>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-14 h-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20">
                            <span class="material-symbols-outlined text-3xl">smart_toy</span>
                        </div>
                        <div>
                            <h4 class="text-xl font-black tracking-tight">Opencode API</h4>
                            <p class="text-xs font-medium text-emerald-100 opacity-80"><?php echo htmlspecialchars(OPENCODE_MODEL); ?></p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-4 flex flex-col gap-1 border border-white/5 overflow-hidden">
                            <span class="text-[10px] font-bold text-emerald-200 uppercase tracking-widest">Endpoint URL</span>
                            <span class="text-xs font-mono truncate text-white"><?php echo htmlspecialchars(OPENCODE_API_URL); ?></span>
                        </div>
                        <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-4 flex flex-col gap-1 border border-white/5 overflow-hidden">
                            <span class="text-[10px] font-bold text-emerald-200 uppercase tracking-widest">API Key</span>
                            <span class="text-xs font-mono truncate text-white"><?php 
                                $key = OPENCODE_API_KEY;
                                echo htmlspecialchars(substr($key, 0, 8) . str_repeat('*', max(0, strlen($key) - 12)) . substr($key, -4)); 
                            ?></span>
                        </div>
                    </div>

                    <div class="mt-8">
                        <a href="settings.php" class="block w-full text-center bg-white text-emerald-600 font-black py-4 rounded-2xl text-xs uppercase tracking-widest shadow-lg hover:scale-[1.02] active:scale-[0.98] transition-all">
                            Cấu Hình API
                        </a>
                    </div>
                </div>
            </div>

            <!-- Global Stats Card -->
            <div class="glass-card rounded-[32px] p-8">
                <div class="flex items-center gap-3 mb-8">
                    <span class="material-symbols-outlined text-blue-500 bg-blue-50 dark:bg-blue-900/30 p-2 rounded-xl">public</span>
                    <h3 class="font-black text-gray-900 dark:text-white uppercase text-sm tracking-wider">Tổng Quan Toàn Hệ Thống</h3>
                </div>
                
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Tổng Tokens (All time)</p>
                        <p class="text-lg font-black text-gray-900 dark:text-white"><?php echo number_format($all_time_tokens); ?></p>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Tổng Requests (All time)</p>
                        <p class="text-lg font-black text-gray-900 dark:text-white"><?php echo number_format($all_time_reqs); ?></p>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-100 dark:border-slate-700">
                    <a href="ai-logs.php" class="flex items-center justify-between group">
                        <span class="text-xs font-black text-gray-500 dark:text-gray-400 group-hover:text-emerald-500 transition-colors uppercase tracking-widest">Xem Nhật Ký AI</span>
                        <span class="material-symbols-outlined text-gray-400 group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>