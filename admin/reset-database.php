<?php
session_start();
require_once '../config/database.php';

// QUAN TRỌNG: Chỉ admin mới được phép truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Reset Database';
$page_subtitle = 'Xóa toàn bộ dữ liệu (giữ lại admin)';

$message = '';
$error = '';

// Xử lý reset database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    $confirmation = $_POST['confirmation'] ?? '';
    
    // Yêu cầu nhập "RESET DATABASE" để xác nhận
    if ($confirmation === 'RESET DATABASE') {
        try {
            $db = getDB();
            
            // Tắt foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Lưu tạm admin users
            $db->exec("CREATE TEMPORARY TABLE IF NOT EXISTS temp_admin_users AS SELECT * FROM users WHERE user_role = 'admin'");
            
            // Xóa dữ liệu các bảng
            $tables_to_truncate = [
                'service_bookings',
                'booking_services',
                'payments',
                'bookings',
                'rooms',
                'room_types',
                'seasonal_pricing',
                'services',
                'promotions',
                'banners',
                'blog_comments',
                'blog_posts',
                'gallery',
                'faqs',
                'contact_submissions',
                'reviews',
                'notifications',
                'membership_tiers',
                'activity_logs',
                'email_logs'
            ];
            
            foreach ($tables_to_truncate as $table) {
                try {
                    $db->exec("TRUNCATE TABLE $table");
                } catch (Exception $e) {
                    error_log("Error truncating $table: " . $e->getMessage());
                }
            }
            
            // Xóa users trừ admin
            $db->exec("DELETE FROM user_loyalty WHERE user_id NOT IN (SELECT user_id FROM temp_admin_users)");
            $db->exec("DELETE FROM users WHERE user_role != 'admin'");
            
            // Reset AUTO_INCREMENT
            $tables_to_reset = [
                'bookings', 'rooms', 'room_types', 'payments', 'services',
                'service_bookings', 'promotions', 'banners', 'blog_posts',
                'blog_comments', 'gallery', 'faqs', 'reviews', 'notifications',
                'membership_tiers', 'seasonal_pricing', 'contact_submissions',
                'activity_logs', 'email_logs', 'users'
            ];
            
            foreach ($tables_to_reset as $table) {
                try {
                    $db->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
                } catch (Exception $e) {
                    error_log("Error resetting AUTO_INCREMENT for $table: " . $e->getMessage());
                }
            }
            
            // Dọn dẹp
            $db->exec("DROP TEMPORARY TABLE IF EXISTS temp_admin_users");
            
            // Bật lại foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // Log activity
            $stmt = $db->prepare("
                INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
                VALUES (:user_id, 'database_reset', 'Reset toàn bộ database (giữ admin)', :ip, NOW())
            ");
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':ip' => $_SERVER['REMOTE_ADDR']
            ]);
            
            $message = 'Database đã được reset thành công! Tất cả dữ liệu đã bị xóa (trừ tài khoản admin).';
            
        } catch (Exception $e) {
            error_log("Database reset error: " . $e->getMessage());
            $error = 'Có lỗi xảy ra khi reset database: ' . $e->getMessage();
        }
    } else {
        $error = 'Vui lòng nhập chính xác "RESET DATABASE" để xác nhận.';
    }
}

include 'includes/admin-header.php';
?>

<div class="max-w-3xl mx-auto">
    <!-- Warning Alert -->
    <div class="bg-red-50 border-2 border-red-500 rounded-xl p-6 mb-6">
        <div class="flex items-start gap-4">
            <span class="material-symbols-outlined text-red-600 text-4xl">warning</span>
            <div>
                <h3 class="text-xl font-bold text-red-600 mb-2">⚠️ CẢNH BÁO QUAN TRỌNG</h3>
                <p class="text-red-700 mb-2">Chức năng này sẽ <strong>XÓA TOÀN BỘ DỮ LIỆU</strong> trong database, bao gồm:</p>
                <ul class="list-disc list-inside text-red-700 space-y-1 mb-3">
                    <li>Tất cả đặt phòng và thanh toán</li>
                    <li>Tất cả phòng và loại phòng</li>
                    <li>Tất cả khách hàng (trừ admin)</li>
                    <li>Tất cả dịch vụ và đơn dịch vụ</li>
                    <li>Tất cả nội dung (blog, gallery, FAQs)</li>
                    <li>Tất cả đánh giá và thông báo</li>
                    <li>Tất cả logs và lịch sử</li>
                </ul>
                <p class="text-red-700 font-bold">
                    ✅ Chỉ giữ lại: Tài khoản ADMIN và System Settings
                </p>
                <p class="text-red-700 mt-2">
                    🔴 Hành động này <strong>KHÔNG THỂ HOÀN TÁC</strong>!
                </p>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-xl mb-6">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined">check_circle</span>
                <span><?php echo $message; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-xl mb-6">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined">error</span>
                <span><?php echo $error; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Reset Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">delete_forever</span>
                Reset Database
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" onsubmit="return confirmReset()">
                <div class="space-y-6">
                    <div>
                        <p class="text-gray-700 mb-4">
                            Để xác nhận bạn muốn reset database, vui lòng nhập chính xác văn bản sau:
                        </p>
                        <div class="bg-gray-100 dark:bg-slate-800 p-4 rounded-lg mb-4">
                            <code class="text-lg font-mono font-bold text-red-600">RESET DATABASE</code>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nhập để xác nhận *</label>
                        <input type="text" 
                               name="confirmation" 
                               id="confirmation"
                               class="form-input font-mono" 
                               placeholder="RESET DATABASE"
                               required
                               autocomplete="off">
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-400 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">
                            <strong>Lưu ý:</strong> Sau khi reset, bạn có thể chạy file 
                            <code class="bg-yellow-200 px-2 py-1 rounded">docs/INSERT_ROOMS_DATA.sql</code> 
                            để tạo lại 126 phòng mẫu.
                        </p>
                    </div>
                    
                    <div class="flex justify-between items-center pt-4 border-t">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <span class="material-symbols-outlined text-sm">arrow_back</span>
                            Quay lại Dashboard
                        </a>
                        <button type="submit" name="confirm_reset" class="btn btn-danger">
                            <span class="material-symbols-outlined text-sm">delete_forever</span>
                            Reset Database
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card mt-6">
        <div class="card-header">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined">info</span>
                Thông tin
            </h3>
        </div>
        <div class="card-body">
            <div class="space-y-3 text-sm">
                <p><strong>Mục đích:</strong> Dùng để reset database về trạng thái ban đầu khi cần test hoặc bắt đầu lại từ đầu.</p>
                <p><strong>Thời gian:</strong> Quá trình reset mất khoảng 5-10 giây.</p>
                <p><strong>Backup:</strong> Nên backup database trước khi thực hiện nếu cần giữ lại dữ liệu.</p>
                <p><strong>Alternative:</strong> Có thể chạy file SQL trực tiếp: <code class="bg-gray-100 px-2 py-1 rounded">docs/RESET_DATABASE_KEEP_ADMIN.sql</code></p>
            </div>
        </div>
    </div>
</div>

<script>
function confirmReset() {
    const confirmation = document.getElementById('confirmation').value;
    
    if (confirmation !== 'RESET DATABASE') {
        alert('Vui lòng nhập chính xác "RESET DATABASE" để xác nhận.');
        return false;
    }
    
    return confirm(
        '⚠️ XÁC NHẬN LẦN CUỐI ⚠️\n\n' +
        'Bạn có CHẮC CHẮN muốn xóa TOÀN BỘ dữ liệu?\n\n' +
        'Hành động này KHÔNG THỂ HOÀN TÁC!\n\n' +
        'Nhấn OK để tiếp tục, Cancel để hủy.'
    );
}
</script>

<style>
.btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
    transform: translateY(-2px);
}
</style>

<?php include 'includes/admin-footer.php'; ?>
