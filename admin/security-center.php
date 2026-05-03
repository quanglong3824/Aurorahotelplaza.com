<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../helpers/security.php';
$db = getDB();

if (!$db) {
    die("Lỗi: Không thể kết nối cơ sở dữ liệu. Vui lòng kiểm tra lại cấu hình.");
}

$page_title = 'Trung Tâm Bảo Mật';
$page_subtitle = 'Quản lý Anti-Bot, Blacklist và bảo vệ dữ liệu toàn diện.';

$page_hash = Security::hashAdminPage('security-center');

// Xử lý Gỡ bỏ IP khỏi Blacklist
if (isset($_GET['remove_ip'])) {
    $ip = $_GET['remove_ip'];
    $stmt = $db->prepare("DELETE FROM security_blacklist WHERE ip_address = ?");
    $stmt->execute([$ip]);
    header("Location: index.php?p=$page_hash&success=1");
    exit;
}

// Lấy danh sách Blacklist
$blacklist = $db->query("SELECT * FROM security_blacklist ORDER BY created_at DESC")->fetchAll();

// Lấy log Honeypot gần đây
$honeypot_logs = $db->query("SELECT * FROM security_honeypot_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();

require_once 'includes/admin-header.php';
?>

<div class="space-y-6">
    <!-- Thống kê bảo mật nhanh -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stat-card">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-bold text-slate-500 uppercase">Đã chặn vĩnh viễn</p>
                <span class="material-symbols-outlined text-rose-500">gpp_bad</span>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white">
                <?php 
                $stmt = $db->query("SELECT COUNT(*) FROM security_blacklist WHERE is_permanent = 1");
                echo $stmt->fetchColumn();
                ?>
            </p>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-bold text-slate-500 uppercase">Lượt sập bẫy Honeypot</p>
                <span class="material-symbols-outlined text-amber-500">pest_control</span>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white">
                <?php 
                $stmt = $db->query("SELECT COUNT(*) FROM security_blacklist WHERE reason LIKE '%HoneyPot%'");
                echo $stmt->fetchColumn();
                ?>
            </p>
        </div>
        <div class="stat-card border-emerald-200 bg-emerald-50/30">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-bold text-emerald-600 uppercase">Trạng thái hệ thống</p>
                <span class="material-symbols-outlined text-emerald-500">verified_user</span>
            </div>
            <p class="text-xl font-black text-emerald-700">ĐANG BẢO VỆ</p>
            <p class="text-[10px] text-emerald-600 mt-1 font-bold">Lớp phòng thủ Anti-Crawl 2.0 Active</p>
        </div>
    </div>

    <!-- Danh sách Blacklist -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Danh sách đen (Blacklist)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 text-[10px] font-black text-slate-400 uppercase tracking-[2px]">
                        <th class="px-6 py-4">IP Address</th>
                        <th class="px-6 py-4">Lý do chặn</th>
                        <th class="px-6 py-4">Thời hạn</th>
                        <th class="px-6 py-4">Lượt vi phạm</th>
                        <th class="px-6 py-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    <?php foreach ($blacklist as $item): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors text-sm">
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300"><?php echo $item['ip_address']; ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs text-rose-600 font-medium"><?php echo htmlspecialchars($item['reason']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($item['is_permanent']): ?>
                                <span class="badge badge-danger">Vĩnh viễn</span>
                            <?php else: ?>
                                <span class="text-[10px] text-slate-500">Hết hạn: <?php echo date('H:i d/m', strtotime($item['expires_at'])); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-slate-400"><?php echo $item['attempts']; ?></td>
                        <td class="px-6 py-4">
                            <a href="index.php?p=<?php echo $page_hash; ?>&remove_ip=<?php echo $item['ip_address']; ?>" 
                               onclick="return confirm('Bạn có chắc muốn gỡ chặn IP này?')"
                               class="text-xs font-bold text-indigo-600 hover:underline">Gỡ chặn</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($blacklist)): ?>
                        <tr><td colspan="5" class="p-8 text-center text-slate-400 italic">Chưa có IP nào bị chặn.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mẹo bảo mật bổ sung -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-indigo-600 rounded-2xl p-6 text-white shadow-xl">
            <h4 class="font-black uppercase tracking-tight mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined">shield</span> Khuyến nghị từ Chuyên gia
            </h4>
            <div class="space-y-4 text-sm opacity-90">
                <p>✔️ <strong>Chặn quốc gia:</strong> Nếu chỉ phục vụ khách VN, hãy dùng Cloudflare để chặn toàn bộ IP ngoài Việt Nam. Điều này loại bỏ 90% bot rác.</p>
                <p>✔️ <strong>Honeypot:</strong> Đường dẫn bẫy ẩn đã được cài đặt vào Footer. Bất kỳ script nào quét vào link này sẽ bị chặn vĩnh viễn.</p>
                <p>✔️ <strong>Dữ liệu nhiễu:</strong> Số điện thoại và giá cả đã được tự động chèn mã rác để chống các tool bóc tách nội dung.</p>
            </div>
        </div>
        <div class="bg-slate-900 rounded-2xl p-6 text-slate-300 border border-slate-700">
            <h4 class="font-black uppercase tracking-tight mb-4 text-white flex items-center gap-2">
                <span class="material-symbols-outlined">admin_panel_settings</span> Bảo vệ Admin
            </h4>
            <div class="space-y-3 text-sm">
                <div class="p-3 rounded-lg bg-slate-800 border border-slate-700">
                    <p class="text-xs font-bold text-white mb-1">Mẹo đổi tên thư mục Admin:</p>
                    <code class="text-[10px] text-amber-400">Đổi /admin thành /quan-ly-rieng-123</code>
                </div>
                <div class="p-3 rounded-lg bg-slate-800 border border-slate-700">
                    <p class="text-xs font-bold text-white mb-1">Thiết lập IP Whitelist:</p>
                    <p class="text-[10px]">Chỉ cho phép IP nhà bạn truy cập vào trang quản trị để an toàn tuyệt đối.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
