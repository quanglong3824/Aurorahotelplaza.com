<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
$db = getDB();

require_once __DIR__ . '/../helpers/api_key_manager.php';

// Xử lý chuyển đổi Provider thủ công (Chuyển sang qwen)
if (isset($_POST['switch_provider'])) {
    $new_p = $_POST['switch_provider'];
    // Nếu có hàm set_active_ai_provider, xử lý tại đây
    if (function_exists('set_active_ai_provider')) {
        set_active_ai_provider($new_p);
    }
    header('Location: qwen-stats.php?switched=1');
    exit;
}

$page_title = 'Thống kê Qwen API';
$page_subtitle = 'Quản lý lưu lượng và mức sử dụng API Qwen (Alibaba) hiện tại';

$log_file = AI_CONFIG_PATH . '/key_usage_stats.json';
$all_stats = [];
if (file_exists($log_file)) {
    $data = @file_get_contents($log_file);
    if ($data) {
        $all_stats = json_decode($data, true) ?: [];
    }
}

$today = date('Y-m-d');
$today_stats = $all_stats[$today] ?? [];

$qwen_stats = $today_stats['qwen'] ?? [
    'tokens' => 0, 'requests' => 0,
    'admin_tokens' => 0, 'admin_requests' => 0,
    'client_tokens' => 0, 'client_requests' => 0,
    'last_used' => null
];

$total_tokens = $qwen_stats['tokens'] ?? 0;
$total_requests = $qwen_stats['requests'] ?? 0;
$admin_tokens = $qwen_stats['admin_tokens'] ?? 0;
$admin_requests = $qwen_stats['admin_requests'] ?? 0;
$client_tokens = $qwen_stats['client_tokens'] ?? 0;
$client_requests = $qwen_stats['client_requests'] ?? 0;
$last_used = !empty($qwen_stats['last_used']) ? date('H:i:s d/m/Y', strtotime($today . ' ' . $qwen_stats['last_used'])) : 'Chưa có dữ liệu';

$active_provider = get_active_ai_provider();
$model = get_active_qwen_model();

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
    .status-pulse { position: relative; display: inline-flex; }
    .status-pulse::after {
        content: ''; position: absolute; width: 100%; height: 100%; background: inherit; border-radius: inherit; animation: pulse-ring 1.5s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
    }
    @keyframes pulse-ring { 0% { transform: scale(0.7); opacity: 0.5; } 100% { transform: scale(2.2); opacity: 0; } }
</style>

<div class="space-y-8 animate-fade-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/20">
                <span class="material-symbols-outlined text-3xl">memory</span>
            </div>
            <div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">Qwen API Tracking</h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="status-pulse w-2 h-2 bg-green-500 rounded-full"></span>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Đang theo dõi theo ngày • Cập nhật cuối: <?php echo $last_used; ?></p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <button onclick="window.location.reload()" class="p-3 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all shadow-sm group">
                <span class="material-symbols-outlined text-gray-500 dark:text-gray-400 transition-transform duration-500 group-hover:rotate-180">refresh</span>
            </button>
        </div>
    </div>

    <?php if (isset($_GET['switched'])): ?>
        <div class="bg-emerald-500 text-white p-4 rounded-2xl flex items-center gap-4 shadow-lg animate-bounce-in">
            <span class="material-symbols-outlined bg-white/20 p-1 rounded-full">check</span>
            <span class="font-bold">Đã đồng bộ hóa Provider thành công!</span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Tokens -->
                <div class="glass-card rounded-3xl p-6 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                            <span class="material-symbols-outlined">data_usage</span>
                        </div>
                        <span class="text-[10px] font-black text-blue-500 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded-lg uppercase tracking-wider">Tổng Tokens</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo number_format($total_tokens); ?></h4>
                        <span class="text-xs font-bold text-gray-400 italic">hôm nay</span>
                    </div>
                </div>

                <!-- Admin Usage -->
                <div class="glass-card rounded-3xl p-6 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600">
                            <span class="material-symbols-outlined">shield_person</span>
                        </div>
                        <span class="text-[10px] font-black text-purple-500 bg-purple-50 dark:bg-purple-900/30 px-2 py-1 rounded-lg uppercase tracking-wider">Admin Bot</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo number_format($admin_tokens); ?></h4>
                        <span class="text-xs font-bold text-gray-400 italic">tk</span>
                    </div>
                    <p class="mt-2 text-[11px] font-bold text-purple-600 dark:text-purple-400"><?php echo number_format($admin_requests); ?> lượt gọi hôm nay</p>
                </div>

                <!-- Client Usage -->
                <div class="glass-card rounded-3xl p-6 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined">support_agent</span>
                        </div>
                        <span class="text-[10px] font-black text-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-lg uppercase tracking-wider">Trợ Lý Khách</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo number_format($client_tokens); ?></h4>
                        <span class="text-xs font-bold text-gray-400 italic">tk</span>
                    </div>
                    <p class="mt-2 text-[11px] font-bold text-emerald-600 dark:text-emerald-400"><?php echo number_format($client_requests); ?> lượt gọi hôm nay</p>
                </div>
            </div>

            <!-- Details -->
            <div class="bg-white dark:bg-slate-800 rounded-[32px] border border-gray-200 dark:border-slate-700 p-8 shadow-sm">
                <h3 class="font-black text-gray-900 dark:text-white text-lg tracking-tight uppercase mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-500">settings</span>
                    Chi tiết cấu hình Qwen
                </h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center bg-gray-50 dark:bg-slate-900 p-4 rounded-xl border border-gray-100 dark:border-slate-700">
                        <span class="text-gray-500 dark:text-gray-400 font-semibold">Model Đang Dùng</span>
                        <span class="font-black text-gray-900 dark:text-white"><?php echo htmlspecialchars($model); ?></span>
                    </div>
                    <div class="flex justify-between items-center bg-gray-50 dark:bg-slate-900 p-4 rounded-xl border border-gray-100 dark:border-slate-700">
                        <span class="text-gray-500 dark:text-gray-400 font-semibold">Trạng Thái API Key</span>
                        <?php echo env('QWEN_API_KEY') ? '<span class="text-green-500 font-bold px-3 py-1 bg-green-50 dark:bg-green-900/30 rounded-lg">Đã Cấu Hình</span>' : '<span class="text-red-500 font-bold px-3 py-1 bg-red-50 dark:bg-red-900/30 rounded-lg">Thiếu Key</span>'; ?>
                    </div>
                    <div class="flex justify-between items-center bg-gray-50 dark:bg-slate-900 p-4 rounded-xl border border-gray-100 dark:border-slate-700">
                        <span class="text-gray-500 dark:text-gray-400 font-semibold">Provider Hiện Tại Mặc Định</span>
                        <span class="uppercase font-black text-indigo-500 px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg"><?php echo htmlspecialchars($active_provider); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <div class="bg-gradient-to-br from-blue-600 to-cyan-700 rounded-[32px] p-8 text-white shadow-xl shadow-blue-600/20 relative overflow-hidden">
                <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                    <span class="material-symbols-outlined text-[200px]">cloud</span>
                </div>
                <div class="relative z-10">
                    <p class="text-[10px] font-black uppercase tracking-[3px] text-blue-200 opacity-80 mb-6">Cài Đặt Hệ Thống</p>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-14 h-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20">
                            <span class="material-symbols-outlined text-3xl">language</span>
                        </div>
                        <div>
                            <h4 class="text-xl font-black tracking-tight">Alibaba Cloud</h4>
                            <p class="text-xs font-medium text-blue-100 opacity-80">Qwen DashScope API</p>
                        </div>
                    </div>

                    <form method="POST" class="mt-8">
                        <button type="submit" name="switch_provider" value="qwen" class="w-full bg-white text-blue-600 font-black py-4 rounded-2xl text-xs uppercase tracking-widest hover:scale-[1.02] transition-all shadow-lg">
                            Kích Hoạt Qwen Làm Mặc Định
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-slate-900 dark:bg-black rounded-[32px] p-8 text-white/90">
                <div class="flex items-center gap-3 mb-6">
                    <span class="material-symbols-outlined text-emerald-400">code</span>
                    <h3 class="font-black uppercase text-sm tracking-wider">Storage Path</h3>
                </div>
                <div class="bg-white/5 rounded-2xl p-4 border border-white/10 mb-4">
                    <p class="text-[10px] font-mono break-all text-gray-400">/config/key_usage_stats.json</p>
                </div>
                <div class="flex items-center gap-2 px-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[10px] font-bold text-gray-500 italic">Theo vết theo Token In/Out</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
