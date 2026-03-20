<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
$db = getDB();

require_once __DIR__ . '/../helpers/api_key_manager.php';

// Xử lý chuyển đổi Provider thủ công
if (isset($_POST['switch_provider'])) {
    $new_p = $_POST['switch_provider'];
    set_active_ai_provider($new_p);
    header('Location: ai-stats.php?switched=1');
    exit;
}

$page_title = 'Thống Kê Lưu Lượng AI (API Quota)';
$page_subtitle = 'Giám sát và chuyển đổi linh hoạt giữa Gemini & Qwen';

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
$current_active_key_idx = get_active_key_index();
$last_updated_time = file_exists($log_file) ? date('m/d/Y H:i:s', filemtime($log_file)) : 'Chưa có dữ liệu';
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
    <div class="flex gap-2">
        <?php if (isset($_GET['switched'])): ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 animate-bounce">
                <span class="material-symbols-outlined text-sm">check_circle</span> Đã chuyển đổi thành công!
            </div>
        <?php endif; ?>
        <button onclick="window.location.reload()" class="bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all hover:shadow-md border border-gray-200 dark:border-slate-700">
            <span class="material-symbols-outlined text-[20px]">refresh</span> Làm mới
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card ring-2 ring-purple-500/20">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">AI Provider Đang Chạy</h3>
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
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Dung lượng Admin</h3>
            <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <span class="material-symbols-outlined text-blue-500 text-xl">admin_panel_settings</span>
            </div>
        </div>
        <p class="text-2xl font-black text-gray-900 dark:text-white"><?php echo number_format($admin_tokens); ?> <span class="text-xs font-normal text-gray-400 italic">tk</span></p>
        <p class="text-xs font-bold text-blue-600 dark:text-blue-400 mt-2"><?php echo number_format($admin_requests); ?> requests</p>
    </div>

    <div class="stat-card !border-l-4 !border-l-emerald-500">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Dung lượng Khách</h3>
            <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                <span class="material-symbols-outlined text-emerald-500 text-xl">support_agent</span>
            </div>
        </div>
        <p class="text-2xl font-black text-gray-900 dark:text-white"><?php echo number_format($client_tokens); ?> <span class="text-xs font-normal text-gray-400 italic">tk</span></p>
        <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-2"><?php echo number_format($client_requests); ?> requests</p>
    </div>

    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Failover System</h3>
            <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <span class="material-symbols-outlined text-orange-500 text-xl">sync_alt</span>
            </div>
        </div>
        <p class="text-2xl font-black text-gray-900 dark:text-white">Auto</p>
        <p class="text-xs font-bold text-orange-600 dark:text-orange-400 mt-2">Sẵn sàng luân chuyển</p>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="flex items-center gap-2 mb-6 bg-gray-100/50 dark:bg-slate-800/50 p-1.5 rounded-2xl w-fit border border-gray-200 dark:border-slate-700">
    <button onclick="switchTab('gemini')" id="tab-gemini" 
        class="tab-btn flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all <?php echo $active_provider === 'gemini' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-gray-500'; ?>">
        <span class="material-symbols-outlined text-[20px]">google</span>
        Google Gemini
    </button>
    <button onclick="switchTab('qwen')" id="tab-qwen" 
        class="tab-btn flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all <?php echo $active_provider === 'qwen' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-gray-500'; ?>">
        <span class="material-symbols-outlined text-[20px]">rocket_launch</span>
        Alibaba Qwen
    </button>
</div>

<!-- Tab Content: Gemini -->
<div id="content-gemini" class="tab-content <?php echo $active_provider !== 'gemini' ? 'hidden' : ''; ?>">
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-indigo-500">api</span>
                <span class="font-bold text-gray-900 dark:text-white text-lg">Google Gemini (Dự phòng cho Qwen)</span>
            </div>
            <?php if ($active_provider !== 'gemini'): ?>
                <form method="POST">
                    <button type="submit" name="switch_provider" value="gemini" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs font-black shadow-lg shadow-indigo-500/30 transition-all">
                        KÍCH HOẠT GEMINI
                    </button>
                </form>
            <?php else: ?>
                <span class="px-4 py-2 bg-green-100 text-green-700 rounded-xl text-xs font-black">ĐANG PHỤC VỤ CHÍNH</span>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-slate-700/50 text-gray-500 dark:text-gray-400 uppercase text-[11px] font-black tracking-wider">
                    <tr>
                        <th class="px-8 py-4">API Key</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Tokens</th>
                        <th class="px-6 py-4 text-center">Requests</th>
                        <th class="px-8 py-4 text-right">Last Used</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    <?php foreach ($valid_keys as $idx => $val): 
                        $stat = $today_stats[$idx] ?? [];
                        $is_active = ($idx == $current_active_key_idx && $active_provider === 'gemini');
                    ?>
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-8 py-5">
                            <span class="font-mono text-xs font-bold text-gray-600 dark:text-gray-300"><?php echo substr($val, 0, 8) . '...' . substr($val, -4); ?></span>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <?php if ($is_active): ?>
                                <span class="bg-green-50 text-green-600 px-3 py-1 rounded-full text-[10px] font-black italic">ON AIR</span>
                            <?php else: ?>
                                <span class="text-gray-400 text-[10px] font-bold">STANDBY</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-5 text-right font-black"><?php echo number_format($stat['tokens'] ?? 0); ?></td>
                        <td class="px-6 py-5 text-center font-bold text-gray-500"><?php echo number_format($stat['requests'] ?? 0); ?></td>
                        <td class="px-8 py-5 text-right text-xs font-bold text-gray-400 italic"><?php echo $stat['last_used'] ?? '--:--:--'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab Content: Qwen -->
<div id="content-qwen" class="tab-content <?php echo $active_provider !== 'qwen' ? 'hidden' : ''; ?>">
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-purple-500">auto_awesome</span>
                <span class="font-bold text-gray-900 dark:text-white text-lg">Alibaba Qwen (Dự phòng cho Gemini)</span>
            </div>
            <?php if ($active_provider !== 'qwen'): ?>
                <form method="POST">
                    <button type="submit" name="switch_provider" value="qwen" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl text-xs font-black shadow-lg shadow-purple-500/30 transition-all">
                        KÍCH HOẠT QWEN
                    </button>
                </form>
            <?php else: ?>
                <span class="px-4 py-2 bg-green-100 text-green-700 rounded-xl text-xs font-black">ĐANG PHỤC VỤ CHÍNH</span>
            <?php endif; ?>
        </div>
        <div class="p-10 text-center">
            <?php 
            $q_stat = $today_stats['qwen'] ?? [];
            ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-2xl mx-auto">
                <div class="p-8 rounded-3xl bg-purple-50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-900/30">
                    <p class="text-sm font-bold text-purple-400 uppercase tracking-widest mb-2">Tokens Today</p>
                    <h4 class="text-5xl font-black text-purple-600"><?php echo number_format($q_stat['tokens'] ?? 0); ?></h4>
                </div>
                <div class="p-8 rounded-3xl bg-slate-50 dark:bg-slate-900/30 border border-slate-100 dark:border-slate-700">
                    <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Requests Today</p>
                    <h4 class="text-5xl font-black text-gray-900 dark:text-white"><?php echo number_format($q_stat['requests'] ?? 0); ?></h4>
                </div>
            </div>
            <div class="mt-8 text-sm text-gray-500">
                <p>Model đang chạy: <b class="text-gray-900 dark:text-white"><?php echo get_active_qwen_model(); ?></b></p>
                <p>Endpoint: dashscope.aliyuncs.com</p>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    // Ẩn tất cả nội dung tab
    document.querySelectorAll('.tab-content').forEach(c => {
        c.style.display = 'none';
    });
    // Hiện tab được chọn
    document.getElementById('content-' + tab).style.display = 'block';
    
    // Reset style nút tab
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-white', 'dark:bg-slate-700', 'text-indigo-600', 'shadow-sm');
        btn.classList.add('text-gray-500');
    });
    
    // Active nút được chọn
    const activeBtn = document.getElementById('tab-' + tab);
    activeBtn.classList.remove('text-gray-500');
    activeBtn.classList.add('bg-white', 'dark:bg-slate-700', 'text-indigo-600', 'shadow-sm');
}

// Chạy khởi tạo để đảm bảo tab đúng lúc ban đầu
document.addEventListener('DOMContentLoaded', function() {
    const activeP = '<?php echo $active_provider; ?>';
    switchTab(activeP);
});
</script>

<?php require_once 'includes/admin-footer.php'; ?>