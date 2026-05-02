<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
$db = getDB();

$page_title = 'Quản Lý Lưu Lượng';
$page_subtitle = 'Theo dõi lưu lượng truy cập và hành vi khách hàng theo thời gian thực.';

// Thống kê tổng quát (Hits, Unique, Devices)
$stats = [
    'total_hits' => 0,
    'unique_visitors' => 0,
    'mobile_hits' => 0,
    'desktop_hits' => 0,
    'hits_today' => 0,
    'unique_today' => 0
];

try {
    // Tổng quát toàn thời gian
    $stmtAll = $db->query("SELECT SUM(total_hits) as hits, SUM(unique_visitors) as unique_v, SUM(mobile_hits) as mobile, SUM(desktop_hits) as desktop FROM traffic_stats_daily");
    $all = $stmtAll->fetch();
    if ($all) {
        $stats['total_hits'] = (int)$all['hits'];
        $stats['unique_visitors'] = (int)$all['unique_v'];
        $stats['mobile_hits'] = (int)$all['mobile'];
        $stats['desktop_hits'] = (int)$all['desktop'];
    }

    // Hôm nay
    $stmtToday = $db->prepare("SELECT total_hits, unique_visitors FROM traffic_stats_daily WHERE stat_date = CURDATE()");
    $stmtToday->execute();
    $today = $stmtToday->fetch();
    if ($today) {
        $stats['hits_today'] = (int)$today['total_hits'];
        $stats['unique_today'] = (int)$today['unique_visitors'];
    }

    // Top Pages
    $stmtTop = $db->query("SELECT page_url, COUNT(*) as views FROM traffic_logs GROUP BY page_url ORDER BY views DESC LIMIT 10");
    $top_pages = $stmtTop->fetchAll();

    // Dữ liệu biểu đồ 7 ngày gần nhất
    $stmtChart = $db->query("SELECT stat_date, total_hits, unique_visitors FROM traffic_stats_daily ORDER BY stat_date DESC LIMIT 7");
    $chart_data = array_reverse($stmtChart->fetchAll());

} catch (Exception $e) {
    // If tables not created, try creating them once
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS traffic_logs (id INT AUTO_INCREMENT PRIMARY KEY, session_id VARCHAR(100) NOT NULL, ip_address VARCHAR(45) NOT NULL, user_id INT DEFAULT NULL, page_url TEXT NOT NULL, page_title VARCHAR(255) DEFAULT NULL, referer TEXT DEFAULT NULL, user_agent TEXT DEFAULT NULL, device_type ENUM('desktop', 'mobile', 'tablet', 'bot') DEFAULT 'desktop', is_unique TINYINT(1) DEFAULT 0, visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, duration INT DEFAULT 0, KEY idx_session (session_id), KEY idx_visit_time (visit_time), KEY idx_ip (ip_address)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        $db->exec("CREATE TABLE IF NOT EXISTS traffic_stats_daily (id INT AUTO_INCREMENT PRIMARY KEY, stat_date DATE NOT NULL UNIQUE, total_hits INT DEFAULT 0, unique_visitors INT DEFAULT 0, mobile_hits INT DEFAULT 0, desktop_hits INT DEFAULT 0, total_duration INT DEFAULT 0, KEY idx_date (stat_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        header("Refresh:0");
    } catch (Exception $e2) {}
}

require_once 'includes/admin-header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    <!-- Thẻ chỉ số chính -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="stat-card">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Tổng lượt xem (Hits)</p>
                <span class="material-symbols-outlined text-indigo-500">ads_click</span>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white"><?php echo number_format($stats['total_hits']); ?></p>
            <p class="text-xs text-green-600 font-bold mt-2 flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">trending_up</span>
                +<?php echo number_format($stats['hits_today']); ?> hôm nay
            </p>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Khách duy nhất (UV)</p>
                <span class="material-symbols-outlined text-purple-500">group</span>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white"><?php echo number_format($stats['unique_visitors']); ?></p>
            <p class="text-xs text-green-600 font-bold mt-2 flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">person_add</span>
                +<?php echo number_format($stats['unique_today']); ?> khách mới
            </p>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Mobile Traffic</p>
                <span class="material-symbols-outlined text-blue-500">smartphone</span>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white"><?php echo $stats['total_hits'] > 0 ? round(($stats['mobile_hits'] / $stats['total_hits']) * 100) : 0; ?>%</p>
            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full mt-3 overflow-hidden">
                <div class="h-full bg-blue-500" style="width: <?php echo $stats['total_hits'] > 0 ? ($stats['mobile_hits'] / $stats['total_hits']) * 100 : 0; ?>%"></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Desktop Traffic</p>
                <span class="material-symbols-outlined text-amber-500">desktop_windows</span>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white"><?php echo $stats['total_hits'] > 0 ? round(($stats['desktop_hits'] / $stats['total_hits']) * 100) : 0; ?>%</p>
            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full mt-3 overflow-hidden">
                <div class="h-full bg-amber-500" style="width: <?php echo $stats['total_hits'] > 0 ? ($stats['desktop_hits'] / $stats['total_hits']) * 100 : 0; ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart Lưu lượng 7 ngày -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Xu hướng lưu lượng (7 ngày)</h3>
                <div class="flex items-center gap-4 text-xs font-bold uppercase tracking-widest text-slate-400">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-indigo-500"></span> Hits</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-400"></span> Visitors</span>
                </div>
            </div>
            <div class="h-[300px]">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <!-- Thống kê trang xem nhiều -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Trang xem nhiều nhất</h3>
            </div>
            <div class="divide-y divide-slate-50 dark:divide-slate-700">
                <?php foreach ($top_pages as $page): ?>
                <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors flex items-center justify-between group">
                    <div class="flex-1 min-w-0 pr-4">
                        <p class="text-xs font-mono text-slate-500 truncate group-hover:text-indigo-500 transition-colors"><?php echo htmlspecialchars($page['page_url']); ?></p>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-black text-slate-900 dark:text-white"><?php echo number_format($page['views']); ?></span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase ml-1">views</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($top_pages)): ?>
                    <p class="p-8 text-center text-slate-400 text-sm italic">Chưa có dữ liệu truy cập.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Danh sách lượt truy cập mới nhất -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Log truy cập thời gian thực</h3>
            <a href="traffic-logs.php" class="text-xs font-bold text-indigo-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 text-[10px] font-black text-slate-400 uppercase tracking-[2px]">
                        <th class="px-6 py-4">Thời gian</th>
                        <th class="px-6 py-4">IP Address</th>
                        <th class="px-6 py-4">Thiết bị</th>
                        <th class="px-6 py-4">Trang đích</th>
                        <th class="px-6 py-4">Nguồn</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    <?php
                    $stmtLogs = $db->query("SELECT * FROM traffic_logs ORDER BY visit_time DESC LIMIT 10");
                    $logs = $stmtLogs->fetchAll();
                    foreach ($logs as $log):
                        $device_icon = 'desktop_windows';
                        if ($log['device_type'] === 'mobile') $device_icon = 'smartphone';
                        if ($log['device_type'] === 'tablet') $device_icon = 'tablet_mac';
                        if ($log['device_type'] === 'bot') $device_icon = 'robot';
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors text-sm">
                        <td class="px-6 py-4 text-slate-500 whitespace-nowrap"><?php echo date('H:i:s d/m/Y', strtotime($log['visit_time'])); ?></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300"><?php echo $log['ip_address']; ?></span>
                                <?php if ($log['is_unique']): ?>
                                    <span class="text-[9px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-black uppercase">Mới</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="material-symbols-outlined text-slate-400 align-middle" title="<?php echo $log['device_type']; ?>"><?php echo $device_icon; ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="max-w-[200px] truncate text-xs text-indigo-600"><?php echo htmlspecialchars($log['page_url']); ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="max-w-[150px] truncate text-xs text-slate-500"><?php echo $log['referer'] ? htmlspecialchars(parse_url($log['referer'], PHP_URL_HOST)) : 'Trực tiếp'; ?></p>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Initialize Chart
    const ctx = document.getElementById('trafficChart').getContext('2d');
    const chartData = <?php echo json_encode($chart_data); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => d.stat_date),
            datasets: [
                {
                    label: 'Lượt xem (Hits)',
                    data: chartData.map(d => d.total_hits),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2
                },
                {
                    label: 'Khách (Visitors)',
                    data: chartData.map(d => d.unique_visitors),
                    borderColor: '#a855f7',
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: false,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#a855f7',
                    pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { font: { size: 10, weight: 'bold' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10, weight: 'bold' } }
                }
            }
        }
    });
</script>

<?php require_once 'includes/admin-footer.php'; ?>
