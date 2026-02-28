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
    $reset_mode = $_POST['reset_mode'] ?? 'transactions_only';

    // Yêu cầu nhập "RESET DATABASE" để xác nhận
    if ($confirmation === 'RESET DATABASE') {
        try {
            $db = getDB();

            // Tắt foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Lưu tạm admin users
            $db->exec("CREATE TEMPORARY TABLE IF NOT EXISTS temp_admin_users AS SELECT * FROM users WHERE user_role = 'admin'");

            $tables_to_truncate = [];

            // 1. Nhóm dữ liệu Giao dịch & Tương tác (Xóa ở cả 2 chế độ)
            // Bao gồm: Đặt phòng, Thanh toán, Lịch sử, Logs, Liên hệ, Đánh giá...
            $transaction_tables = [
                'activity_logs',
                'blog_comments',
                'blog_likes',
                'blog_ratings',
                'blog_shares',
                'booking_extra_guests',
                'booking_history',
                'bookings',
                'chat_conversations',
                'chat_messages',
                'chat_typing',
                'contact_submissions',
                'csrf_tokens',
                'email_logs',
                'notifications',
                'password_resets',
                'payments',
                'points_transactions',
                'promotion_usage',
                'push_subscriptions',
                'rate_limits',
                'refunds',
                'review_responses',
                'reviews',
                'service_bookings',
                'user_sessions'
            ];

            $tables_to_truncate = array_merge($tables_to_truncate, $transaction_tables);

            // 2. Nhóm dữ liệu Cứng/Cấu hình (Chỉ xóa nếu chọn Full Reset)
            // Bao gồm: Phòng, Dịch vụ, Bài viết, Hình ảnh, Cấu hình giá...
            if ($reset_mode === 'full') {
                $master_tables = [
                    'amenities',
                    'banners',
                    'blog_categories',
                    'blog_posts',
                    'bot_knowledge',
                    'chat_quick_replies',
                    'chat_settings',
                    'faqs',
                    'gallery',
                    'membership_tiers',
                    'page_content',
                    'pricing_policies',
                    'promotions',
                    'role_permissions',
                    'room_pricing',
                    'room_types',
                    'rooms',
                    'service_packages',
                    'services',
                    'system_settings',
                    'translations'
                ];
                $tables_to_truncate = array_merge($tables_to_truncate, $master_tables);
            }

            // Thực hiện Truncate
            foreach ($tables_to_truncate as $table) {
                try {
                    // Kiểm tra bảng tồn tại trước khi truncate
                    $check = $db->query("SHOW TABLES LIKE '$table'");
                    if ($check->rowCount() > 0) {
                        $db->exec("TRUNCATE TABLE $table");
                    }
                } catch (Exception $e) {
                    error_log("Error truncating $table: " . $e->getMessage());
                }
            }

            // Xử lý Users & Loyalty
            // Luôn xóa users thường và data loyalty của họ
            $db->exec("DELETE FROM user_loyalty WHERE user_id NOT IN (SELECT user_id FROM temp_admin_users)");
            $db->exec("DELETE FROM users WHERE user_role != 'admin'");

            // QUAN TRỌNG: Reset trạng thái phòng về "Trống" (available)
            // Vì khi xóa booking, phòng phải được giải phóng
            if ($reset_mode === 'transactions_only') {
                try {
                    $db->exec("UPDATE rooms SET status = 'available'");
                } catch (Exception $e) {
                    error_log("Error resetting room status: " . $e->getMessage());
                }
            }

            // Reset AUTO_INCREMENT cho các bảng đã xóa
            foreach ($tables_to_truncate as $table) {
                try {
                    $check = $db->query("SHOW TABLES LIKE '$table'");
                    if ($check->rowCount() > 0) {
                        $db->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
                    }
                } catch (Exception $e) {
                    // Ignore errors
                }
            }

            // Reset AUTO_INCREMENT cho users
            $db->exec("ALTER TABLE users AUTO_INCREMENT = 1");

            // Dọn dẹp
            $db->exec("DROP TEMPORARY TABLE IF EXISTS temp_admin_users");

            // Bật lại foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");

            // Log activity (sau khi reset, log lại hành động này vào bảng log mới tinh)
            $action_desc = ($reset_mode === 'full') ? 'Full Database Reset' : 'Clean Transactions Data';
            $stmt = $db->prepare("
                INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
                VALUES (:user_id, 'database_reset', :desc, :ip, NOW())
            ");
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':desc' => $action_desc . " (Sẵn sàng Production)",
                ':ip' => $_SERVER['REMOTE_ADDR']
            ]);

            $message = 'Đã dọn dẹp hệ thống thành công! Dữ liệu ' . ($reset_mode === 'full' ? 'toàn bộ' : 'giao dịch') . ' đã được xóa.';

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
                <p class="text-red-700 mb-2">Trang này dùng để xóa dữ liệu hệ thống. Hãy cân nhắc kỹ trước khi thực
                    hiện.</p>
                <p class="text-red-700 font-bold">
                    ✅ Luôn giữ lại: Tài khoản ADMIN và Cấu hình hệ thống (System Settings)
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
                Tùy chọn Dọn dẹp
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" onsubmit="return confirmReset()">
                <div class="space-y-6">

                    <!-- Mode Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Option 1: Production Ready -->
                        <label
                            class="relative flex p-4 cursor-pointer rounded-lg border-2 border-green-200 hover:border-green-500 bg-green-50 has-[:checked]:border-green-600 has-[:checked]:bg-green-100 transition-all">
                            <input type="radio" name="reset_mode" value="transactions_only" class="sr-only" checked>
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-symbols-outlined text-green-600">cleaning_services</span>
                                    <span class="font-bold text-green-800">Dọn dẹp Booking & Khách</span>
                                </div>
                                <p class="text-sm text-green-700">
                                    Chỉ xóa dữ liệu Đặt phòng, Thanh toán, Khách hàng, Đánh giá, Logs.
                                    <br><strong>TỰ ĐỘNG:</strong> Đưa tất cả phòng về trạng thái "Còn trống" (Màu xanh).
                                    <br><span class="font-semibold">GIỮ LẠI:</span> Phòng, Loại phòng, Dịch vụ, Bài
                                    viết, Ảnh, Cấu hình giá.
                                </p>
                                <div class="mt-2 text-xs font-semibold text-green-600 uppercase tracking-wider">Khuyên
                                    dùng cho lên Product</div>
                            </div>
                        </label>

                        <!-- Option 2: Full Reset -->
                        <label
                            class="relative flex p-4 cursor-pointer rounded-lg border-2 border-red-200 hover:border-red-500 bg-red-50 has-[:checked]:border-red-600 has-[:checked]:bg-red-100 transition-all">
                            <input type="radio" name="reset_mode" value="full" class="sr-only">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-symbols-outlined text-red-600">delete_sweep</span>
                                    <span class="font-bold text-red-800">Reset Toàn Bộ (Xóa Hết)</span>
                                </div>
                                <p class="text-sm text-red-700">
                                    Xóa TẤT CẢ mọi dữ liệu về trạng thái ban đầu của database rỗng.
                                    <br>Phòng, Dịch vụ, Bài viết cũng sẽ bị xóa.
                                </p>
                                <div class="mt-2 text-xs font-semibold text-red-600 uppercase tracking-wider">Cẩn thận
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="border-t pt-4">
                        <p class="text-gray-700 mb-4">
                            Để xác nhận, vui lòng nhập chính xác văn bản sau:
                        </p>
                        <div class="bg-gray-100 dark:bg-slate-800 p-4 rounded-lg mb-4 text-center">
                            <code class="text-lg font-mono font-bold text-red-600">RESET DATABASE</code>
                        </div>

                        <div class="form-group max-w-md mx-auto">
                            <input type="text" name="confirmation" id="confirmation"
                                class="form-input font-mono text-center border-red-300 focus:border-red-500 focus:ring-red-500"
                                placeholder="Nhập RESET DATABASE vào đây" required autocomplete="off">
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <span class="material-symbols-outlined text-sm">arrow_back</span>
                            Quay lại Dashboard
                        </a>
                        <button type="submit" name="confirm_reset" class="btn btn-danger w-full md:w-auto">
                            <span class="material-symbols-outlined text-sm">run_circle</span>
                            Thực hiện Reset
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
                <p><strong>Mục đích:</strong> Dùng để reset database về trạng thái ban đầu khi cần test hoặc bắt đầu lại
                    từ đầu.</p>
                <p><strong>Thời gian:</strong> Quá trình reset mất khoảng 5-10 giây.</p>
                <p><strong>Backup:</strong> Nên backup database trước khi thực hiện nếu cần giữ lại dữ liệu.</p>
                <p><strong>Alternative:</strong> Có thể chạy file SQL trực tiếp: <code
                        class="bg-gray-100 px-2 py-1 rounded">docs/RESET_DATABASE_KEEP_ADMIN.sql</code></p>
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