<?php
/**
 * Aurora Hotel Plaza - Traffic Tracker
 * Hệ thống theo dõi lưu lượng truy cập toàn diện
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/bot-detector.php';

class AuroraTrafficTracker {
    private static $db = null;

    public static function init() {
        // Không track trong khu vực admin
        $current_path = $_SERVER['PHP_SELF'] ?? '';
        if (strpos($current_path, '/admin/') !== false) return;

        // Bắt đầu session nếu chưa có
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // LOẠI TRỪ TRUY CẬP CỦA STAFF (Admin/Receptionist/Sale)
        // Nếu sếp đảo trang ở frontend thì hệ thống cũng sẽ bỏ qua không đếm
        if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale'])) {
            return;
        }

        self::track();
    }

    private static function track() {
        try {
            $db = getDB();
            if (!$db) return;

            $session_id = session_id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $user_id = $_SESSION['user_id'] ?? null;
            $page_url = $_SERVER['REQUEST_URI'] ?? '/';
            $referer = $_SERVER['HTTP_REFERER'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Nhận diện Bot chuyên sâu
            $botInfo = BotDetector::detect();
            $is_bot = $botInfo['is_bot'];
            $bot_type = $botInfo['type']; // good|bad|none
            $bot_name = $botInfo['name'];

            // Xác định thiết bị
            $device = 'desktop';
            if ($is_bot) {
                $device = 'bot';
            } else if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($user_agent))) {
                $device = 'tablet';
            } else if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($user_agent))) {
                $device = 'mobile';
            }

            // Kiểm tra xem đã track page view này trong 5 phút qua chưa (tránh spam F5)
            $stmtCheck = $db->prepare("
                SELECT id FROM traffic_logs 
                WHERE session_id = ? AND page_url = ? 
                AND visit_time > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                LIMIT 1
            ");
            $stmtCheck->execute([$session_id, $page_url]);
            if ($stmtCheck->fetch()) {
                return; // Đã ghi nhận gần đây
            }

            // Kiểm tra xem đây có phải visitor duy nhất trong ngày không
            $is_unique = 0;
            $stmtUnique = $db->prepare("
                SELECT id FROM traffic_logs 
                WHERE ip_address = ? AND DATE(visit_time) = CURDATE() 
                LIMIT 1
            ");
            $stmtUnique->execute([$ip]);
            if (!$stmtUnique->fetch()) {
                $is_unique = 1;
            }

            // Ghi log chi tiết
            $stmtInsert = $db->prepare("
                INSERT INTO traffic_logs 
                (session_id, ip_address, user_id, page_url, referer, user_agent, device_type, is_unique, bot_type, bot_name)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([
                $session_id, $ip, $user_id, $page_url, $referer, $user_agent, $device, $is_unique,
                ($is_bot ? $bot_type : null), ($is_bot ? $bot_name : null)
            ]);

            // Cập nhật bảng thống kê nhanh theo ngày
            self::updateDailyStats($db, $is_unique, $device);

        } catch (Exception $e) {
            error_log("Traffic Tracking Error: " . $e->getMessage());
        }
    }

    private static function updateDailyStats($db, $is_unique, $device) {
        $today = date('Y-m-d');
        $unique_inc = $is_unique ? 1 : 0;
        $mobile_inc = ($device === 'mobile' || $device === 'tablet') ? 1 : 0;
        $desktop_inc = ($device === 'desktop') ? 1 : 0;
        $bot_inc = ($device === 'bot') ? 1 : 0;

        $sql = "INSERT INTO traffic_stats_daily 
                (stat_date, total_hits, unique_visitors, mobile_hits, desktop_hits, bot_hits) 
                VALUES (?, 1, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                total_hits = total_hits + 1,
                unique_visitors = unique_visitors + ?,
                mobile_hits = mobile_hits + ?,
                desktop_hits = desktop_hits + ?,
                bot_hits = bot_hits + ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $today, $unique_inc, $mobile_inc, $desktop_inc, $bot_inc,
            $unique_inc, $mobile_inc, $desktop_inc, $bot_inc
        ]);
    }
}
