<?php
/**
 * Aurora Hotel Plaza - Security Guard (Anti-Crawl & Anti-Bot)
 * Hệ thống bảo mật đa tầng giúp ngăn chặn cào dữ liệu và tấn công tự động
 */

require_once __DIR__ . '/bot-detector.php';
require_once __DIR__ . '/security.php';

class SecurityGuard {
    private static $max_requests_per_minute = 60;
    private static $honeypot_path = '/system-admin-trap-hidden'; // Đường dẫn bẫy

    /**
     * Kiểm tra toàn diện Request hiện tại
     */
    public static function protect() {
        $ip = Security::getClientIP();
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        // 1. Kiểm tra danh sách đen (Blacklist) - Ưu tiên hàng đầu để tiết kiệm tài nguyên
        if (self::isIPBlacklisted($ip)) {
            self::terminateRequest("Your IP ($ip) has been permanently blocked due to repeated security violations.");
        }

        // 2. Kiểm tra Honeypot (Bẫy)
        if (str_contains($uri, self::$honeypot_path)) {
            self::blacklistIP($ip, "HoneyPot Trap Triggered: " . $uri, true);
            self::terminateRequest("Access Denied (Security Violation)");
        }

        // 3. Kiểm tra Rate Limit
        if (!Security::checkRateLimit($ip, self::$max_requests_per_minute, 60)) {
            self::logViolation($ip, "Rate limit exceeded");
            self::terminateRequest("Too many requests. Please slow down.");
        }

        // 4. Kiểm tra Bot chuyên sâu (BotDetector)
        $botInfo = BotDetector::detect();
        if ($botInfo['is_bot'] && $botInfo['type'] === 'bad') {
            // TỰ ĐỘNG BLACKLIST VĨNH VIỄN các Bad Bot đã được xác nhận (như DotBot, Generic Bot, Fake Bots)
            self::blacklistIP($ip, "Confirmed Malicious Bot: " . $botInfo['name'], true);
            self::terminateRequest("Bot access denied: " . $botInfo['name']);
        }

        // 5. Phát hiện "Silent Attack" (Truy cập thẳng trang nhạy cảm không qua trang chủ/referer)
        $sensitive_paths = ['/auth/login', '/dat-phong', '/booking', '/admin'];
        foreach ($sensitive_paths as $path) {
            if (str_contains($uri, $path) && empty($referer) && !isset($_SESSION['user_id'])) {
                // Đây là hành vi quét link tự động của script
                self::blacklistIP($ip, "Suspicious Direct Access to Sensitive Path: " . $path);
                // Với vi phạm này, tạm thời chặn request hiện tại
                self::terminateRequest("Access Denied: Suspicious browsing pattern detected.");
            }
        }
    }

    /**
     * Kỹ thuật làm nhiễu dữ liệu nhạy cảm (Số điện thoại, Giá)
     * Bot Regex thông thường sẽ không quét được
     */
    public static function obfuscate($data) {
        if (empty($data)) return $data;
        
        $chars = mb_str_split((string)$data);
        $output = '';
        foreach ($chars as $char) {
            // Chèn thẻ span rác với style ẩn
            $output .= htmlspecialchars($char) . '<span style="display:none">'.rand(10,99).'</span>';
        }
        return $output;
    }

    /**
     * Chặn đứng request và xuất thông báo
     */
    private static function terminateRequest($message) {
        header('HTTP/1.1 403 Forbidden');
        die("<!DOCTYPE html><html><head><title>Access Denied</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-center;height:100vh;margin:0;background:#f8fafc;color:#1e293b;text-align:center;}</style></head><body><div><h1>🔒 Security Shield</h1><p>$message</p><hr><p><small>Aurora Hotel Plaza - Cyber Security System</small></p></div></body></html>");
    }

    /**
     * Đưa IP vào danh sách đen
     */
    public static function blacklistIP($ip, $reason, $permanent = false) {
        $db = getDB();
        if (!$db) return;

        try {
            $expires = $permanent ? null : date('Y-m-d H:i:s', strtotime('+24 hours'));
            $stmt = $db->prepare("
                INSERT INTO security_blacklist (ip_address, reason, is_permanent, expires_at) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE attempts = attempts + 1, reason = ?, expires_at = ?
            ");
            $stmt->execute([$ip, $reason, $permanent ? 1 : 0, $expires, $reason, $expires]);
            
            // Ghi vào Activity Log
            require_once __DIR__ . '/activity-logger.php';
            ActivityLogger::log(null, 'security', 'blacklist', 0, "IP $ip blocked. Reason: $reason");
        } catch (Exception $e) {}
    }

    /**
     * Kiểm tra IP có trong blacklist không
     */
    private static function isIPBlacklisted($ip) {
        $db = getDB();
        if (!$db) return false;

        try {
            $stmt = $db->prepare("SELECT id FROM security_blacklist WHERE ip_address = ? AND (is_permanent = 1 OR expires_at > NOW())");
            $stmt->execute([$ip]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Ghi nhận vi phạm nhẹ
     */
    private static function logViolation($ip, $details) {
        Security::logSecurityEvent("Violation", $details);
    }
}
