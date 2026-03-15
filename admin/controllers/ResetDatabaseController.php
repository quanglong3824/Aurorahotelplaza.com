<?php
/**
 * Aurora Hotel Plaza - Reset Database Controller
 * Handles database reset logic
 */

function handleResetDatabase() {
    $message = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
        if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
            die('CSRF validation failed.');
        }
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

                // 1. Nhóm dữ liệu Giao dịch & Tương tác
                $transaction_tables = [
                    'activity_logs', 'blog_comments', 'blog_likes', 'blog_ratings', 'blog_shares',
                    'booking_extra_guests', 'booking_history', 'bookings', 'chat_conversations',
                    'chat_messages', 'chat_typing', 'contact_submissions', 'csrf_tokens',
                    'email_logs', 'notifications', 'password_resets', 'payments',
                    'points_transactions', 'promotion_usage', 'push_subscriptions',
                    'rate_limits', 'refunds', 'review_responses', 'reviews',
                    'service_bookings', 'user_sessions'
                ];
                $tables_to_truncate = array_merge($tables_to_truncate, $transaction_tables);

                // 2. Nhóm dữ liệu Cứng/Cấu hình
                if ($reset_mode === 'full') {
                    $master_tables = [
                        'amenities', 'banners', 'blog_categories', 'blog_posts', 'bot_knowledge',
                        'chat_quick_replies', 'chat_settings', 'faqs', 'gallery',
                        'membership_tiers', 'page_content', 'pricing_policies', 'promotions',
                        'role_permissions', 'room_pricing', 'room_types', 'rooms',
                        'service_packages', 'services', 'system_settings', 'translations'
                    ];
                    $tables_to_truncate = array_merge($tables_to_truncate, $master_tables);
                }

                // Thực hiện Truncate
                foreach ($tables_to_truncate as $table) {
                    try {
                        $check = $db->query("SHOW TABLES LIKE '$table'");
                        if ($check->rowCount() > 0) {
                            $db->exec("TRUNCATE TABLE $table");
                        }
                    } catch (Exception $e) {
                        error_log("Error truncating $table: " . $e->getMessage());
                    }
                }

                // Xử lý Users & Loyalty
                $db->exec("DELETE FROM user_loyalty WHERE user_id NOT IN (SELECT user_id FROM temp_admin_users)");
                $db->exec("DELETE FROM users WHERE user_role != 'admin'");

                // Reset trạng thái phòng về "Trống"
                if ($reset_mode === 'transactions_only') {
                    try {
                        $db->exec("UPDATE rooms SET status = 'available'");
                    } catch (Exception $e) {
                        error_log("Error resetting room status: " . $e->getMessage());
                    }
                }

                // Reset AUTO_INCREMENT
                foreach ($tables_to_truncate as $table) {
                    try {
                        $check = $db->query("SHOW TABLES LIKE '$table'");
                        if ($check->rowCount() > 0) {
                            $db->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
                        }
                    } catch (Exception $e) {}
                }
                $db->exec("ALTER TABLE users AUTO_INCREMENT = 1");

                // Dọn dẹp
                $db->exec("DROP TEMPORARY TABLE IF EXISTS temp_admin_users");
                $db->exec("SET FOREIGN_KEY_CHECKS = 1");

                // Log activity
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

    return [
        'message' => $message,
        'error' => $error
    ];
}
