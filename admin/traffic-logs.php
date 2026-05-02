<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
$db = getDB();

$page_title = 'Nhật Ký Lưu Lượng';
$page_subtitle = 'Danh sách chi tiết tất cả các lượt truy cập hệ thống.';

// Phân trang
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    $stmt = $db->prepare("SELECT * FROM traffic_logs ORDER BY visit_time DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();

    $total_stmt = $db->query("SELECT COUNT(*) FROM traffic_logs");
    $total_rows = $total_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);
} catch (Exception $e) {
    $logs = [];
    $total_pages = 0;
}

require_once 'includes/admin-header.php';
?>

<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-900/50 text-[10px] font-black text-slate-400 uppercase tracking-[2px]">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Thời gian</th>
                    <th class="px-6 py-4">IP Address</th>
                    <th class="px-6 py-4">Phiên (Session)</th>
                    <th class="px-6 py-4">Thiết bị</th>
                    <th class="px-6 py-4">Trang đích</th>
                    <th class="px-6 py-4">Nguồn (Referer)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                <?php foreach ($logs as $log): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors text-sm">
                    <td class="px-6 py-4 text-slate-400 text-xs font-mono">#<?php echo $log['id']; ?></td>
                    <td class="px-6 py-4 text-slate-500 whitespace-nowrap"><?php echo date('H:i:s d/m/Y', strtotime($log['visit_time'])); ?></td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300"><?php echo $log['ip_address']; ?></span>
                            <?php if ($log['is_unique']): ?>
                                <span class="text-[9px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-black uppercase">Mới</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-[10px] font-mono text-slate-400 truncate max-w-[100px]"><?php echo $log['session_id']; ?></td>
                    <td class="px-6 py-4">
                        <span class="text-xs px-2 py-1 bg-slate-100 dark:bg-slate-900 rounded-lg text-slate-600 dark:text-slate-400 font-bold"><?php echo strtoupper($log['device_type']); ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <p class="max-w-[250px] truncate text-xs text-indigo-600 font-medium"><?php echo htmlspecialchars($log['page_url']); ?></p>
                    </td>
                    <td class="px-6 py-4">
                        <p class="max-w-[150px] truncate text-xs text-slate-500 italic"><?php echo $log['referer'] ?: 'Direct / No referer'; ?></p>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <div class="p-6 border-t border-slate-100 dark:border-slate-700 flex justify-center">
        <nav class="flex gap-2">
            <?php for ($i = 1; $i <= min(10, $total_pages); $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border <?php echo $i == $page ? 'bg-indigo-600 border-indigo-600 text-white font-black' : 'border-slate-200 text-slate-500 hover:border-indigo-600'; ?> transition-all text-xs">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
