<?php
/**
 * Aurora Hotel Plaza - Bot Detector
 * Nhận diện và xác thực các loại bot truy cập hệ thống
 */

class BotDetector {
    private static $good_bots = [
        'Googlebot' => ['googlebot.com', 'google.com'],
        'Bingbot' => ['search.msn.com'],
        'Applebot' => ['apple.com'],
        'FacebookExternalHit' => ['facebook.com'],
        'meta-externalagent' => ['facebook.com'],
        'ClaudeBot' => ['anthropic.com'],
        'Coccocbot' => ['coccoc.com'],
        'Twitterbot' => ['twitter.com'],
        'LinkedInBot' => ['linkedin.com']
    ];

    private static $bad_bots = [
        'python-requests',
        'Go-http-client',
        'Java',
        'libwww-perl',
        'cURL',
        'Wget',
        'AhrefsBot',
        'SemrushBot',
        'DotBot',
        'MJ12bot',
        'HubSpot',
        'GPTBot',
        'PetalBot',
        'Amazonbot',
        'Scrapy',
        'HeadlessChrome',
        'axios',
        'PostmanRuntime',
        'GuzzleHttp',
        'Nimbustools',
        'AdsBot-Google'
    ];

    /**
     * Kiểm tra và nhận diện bot
     * @return array ['is_bot' => boolean, 'type' => 'good|bad|none', 'name' => string]
     */
    public static function detect() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $accept_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

        if (empty($user_agent)) {
            return ['is_bot' => true, 'type' => 'bad', 'name' => 'Empty User-Agent'];
        }

        // 1. Kiểm tra cache trước
        $cache_result = self::getCache($ip, $user_agent);
        if ($cache_result) {
            return $cache_result;
        }

        $result = ['is_bot' => false, 'type' => 'none', 'name' => ''];

        // 2. Nhận diện Good Bots (Search Engines & Social)
        foreach (self::$good_bots as $bot_name => $domains) {
            if (stripos($user_agent, $bot_name) !== false) {
                // Xác thực Reverse DNS cho các bot quan trọng
                if (in_array($bot_name, ['Googlebot', 'Bingbot', 'Applebot'])) {
                    if (self::verifyReverseDNS($ip, $domains)) {
                        $result = ['is_bot' => true, 'type' => 'good', 'name' => $bot_name];
                    } else {
                        $result = ['is_bot' => true, 'type' => 'bad', 'name' => 'Fake ' . $bot_name];
                    }
                } else {
                    $result = ['is_bot' => true, 'type' => 'good', 'name' => $bot_name];
                }
                break;
            }
        }

        // 3. Nhận diện Bad Bots / Scrapers theo tên cụ thể
        if (!$result['is_bot']) {
            foreach (self::$bad_bots as $bot_pattern) {
                if (stripos($user_agent, $bot_pattern) !== false) {
                    $result = ['is_bot' => true, 'type' => 'bad', 'name' => $bot_pattern];
                    break;
                }
            }
        }

        // 4. Nhận diện theo từ khóa chung (bot, spider, crawl, v.v.)
        if (!$result['is_bot']) {
            $generic_bot_patterns = ['bot', 'spider', 'crawl', 'slurp', 'mediapartners', 'preview'];
            foreach ($generic_bot_patterns as $pattern) {
                if (stripos($user_agent, $pattern) !== false) {
                    $result = ['is_bot' => true, 'type' => 'bad', 'name' => 'Generic Bot (' . $pattern . ')'];
                    break;
                }
            }
        }

        // 5. Kiểm tra dấu hiệu bất thường (Heuristic Detection)
        if (!$result['is_bot']) {
            // Trình duyệt thật luôn gửi Accept-Language, script thường thì không
            // Ngoại trừ các tool preview hoặc bot mới chưa có trong list
            if (empty($accept_lang)) {
                $result = ['is_bot' => true, 'type' => 'bad', 'name' => 'Suspicious (No Accept-Lang)'];
            }
        }

        // 6. Lưu cache và trả về
        self::setCache($ip, $user_agent, $result);
        return $result;
    }

    /**
     * Xác thực bot qua Reverse DNS
     */
    private static function verifyReverseDNS($ip, $valid_domains) {
        $hostname = gethostbyaddr($ip);
        if (!$hostname || $hostname === $ip) return false;

        $isValidDomain = false;
        foreach ($valid_domains as $domain) {
            if (str_ends_with(strtolower($hostname), "." . $domain) || strtolower($hostname) === $domain) {
                $isValidDomain = true;
                break;
            }
        }

        if (!$isValidDomain) return false;

        // Forward DNS check (Xác nhận IP của hostname trùng với IP hiện tại)
        $resolved_ip = gethostbyname($hostname);
        return $resolved_ip === $ip;
    }

    /**
     * Lưu kết quả vào cache (Sử dụng file hoặc Session để tối ưu)
     */
    private static function setCache($ip, $user_agent, $result) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $key = md5($ip . $user_agent);
        $_SESSION['bot_cache'][$key] = [
            'result' => $result,
            'expires' => time() + 86400 // Cache 24h
        ];
    }

    /**
     * Lấy kết quả từ cache
     */
    private static function getCache($ip, $user_agent) {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $key = md5($ip . $user_agent);
        if (isset($_SESSION['bot_cache'][$key])) {
            $cache = $_SESSION['bot_cache'][$key];
            if ($cache['expires'] > time()) {
                return $cache['result'];
            }
            unset($_SESSION['bot_cache'][$key]);
        }
        return null;
    }
}
