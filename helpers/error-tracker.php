<?php
/**
 * Aurora Hotel Plaza - AI Error Tracker
 * ======================================
 * Hệ thống bắt lỗi toàn bộ web và phân tích bằng AI
 * Gửi thông báo qua Telegram Bot
 */

if (!defined('DB_NAME')) {
    require_once __DIR__ . '/../config/database.php';
}

class AuroraErrorTracker
{
    private static $initialized = false;
    private static $db = null;
    private static $errorQueue = [];
    private static $telegramBotToken = null; // Telegram Bot Token
    private static $telegramChatId = null; // Telegram Chat ID nhận alert

    /**
     * Khởi tạo Error Tracker - gọi sớm nhất có thể
     */
    public static function init()
    {
        if (self::$initialized)
            return;

        // Load cấu hình Telegram
        self::loadTelegramConfig();

        // ─── PHP Error Handlers ───────────────────────────────────────
        set_error_handler([self::class, 'handlePhpError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);

        // ─── Bật error reporting đầy đủ nhưng không hiển thị ra màn hình ─
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);

        self::$initialized = true;
    }

    /**
     * Load cấu hình Telegram từ DB hoặc constants
     */
    private static function loadTelegramConfig()
    {
        if (defined('TELEGRAM_BOT_TOKEN')) {
            self::$telegramBotToken = TELEGRAM_BOT_TOKEN;
        }
        if (defined('TELEGRAM_CHAT_ID')) {
            self::$telegramChatId = TELEGRAM_CHAT_ID;
        }

        try {
            $db = self::getDb();
            if ($db) {
                // Lấy giá trị mới nhất KHÔNG rỗng cho mỗi key (tránh đọc nhầm row rỗng)
                $stmt = $db->prepare(
                    "SELECT setting_key, setting_value FROM system_settings 
                     WHERE setting_key IN ('telegram_bot_token', 'telegram_chat_id')
                       AND setting_value IS NOT NULL AND setting_value != ''
                     ORDER BY setting_id DESC"
                );
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    // Chỉ set lần đầu (DESC = mới nhất trước)
                    if ($row['setting_key'] === 'telegram_bot_token' && empty(self::$telegramBotToken)) {
                        self::$telegramBotToken = $row['setting_value'];
                    }
                    if ($row['setting_key'] === 'telegram_chat_id' && empty(self::$telegramChatId)) {
                        self::$telegramChatId = $row['setting_value'];
                    }
                }
            }
        } catch (\Throwable $e) {
            // silent
        }
    }

    /**
     * Lấy DB connection (lazy init)
     */
    private static function getDb()
    {
        if (self::$db === null) {
            try {
                self::$db = getDB();
            } catch (\Throwable $e) {
                self::$db = false;
            }
        }
        return self::$db;
    }

    /**
     * Xử lý PHP errors (E_WARNING, E_NOTICE, etc.)
     */
    public static function handlePhpError($errno, $errstr, $errfile, $errline)
    {
        // Bỏ qua lỗi bị tắt bởi @
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $severity = self::getSeverityFromErrno($errno);
        $message = "$errstr in $errfile on line $errline";

        self::captureError([
            'type' => 'php_error',
            'severity' => $severity,
            'message' => $message,
            'file' => $errfile,
            'line' => $errline,
            'url' => self::getCurrentUrl(),
            'context' => ['errno' => $errno],
        ]);

        // Trả về false để PHP vẫn xử lý lỗi bình thường
        return false;
    }

    /**
     * Xử lý Uncaught Exceptions
     */
    public static function handleException(\Throwable $e)
    {
        self::captureError([
            'type' => 'php_exception',
            'severity' => 'critical',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => self::getCurrentUrl(),
            'context' => [
                'exception_class' => get_class($e),
                'trace' => array_slice($e->getTrace(), 0, 5), // 5 frames đầu
            ],
        ]);
    }

    /**
     * Xử lý Fatal Errors (parse error, out of memory, etc.)
     */
    public static function handleFatalError()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR, E_RECOVERABLE_ERROR])) {
            self::captureError([
                'type' => 'php_fatal',
                'severity' => 'critical',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'url' => self::getCurrentUrl(),
                'context' => ['error_type' => $error['type']],
            ]);
        }
    }

    /**
     * Bắt lỗi JavaScript từ frontend (qua API)
     */
    public static function captureJsError(array $data)
    {
        return self::captureError([
            'type' => 'js_error',
            'severity' => $data['severity'] ?? 'error',
            'message' => $data['message'] ?? 'Unknown JS Error',
            'file' => $data['file'] ?? 'unknown',
            'line' => $data['line'] ?? 0,
            'url' => $data['url'] ?? self::getCurrentUrl(),
            'context' => [
                'col' => $data['col'] ?? 0,
                'stack' => $data['stack'] ?? '',
                'browser' => $data['browser'] ?? '',
                'user_agent' => $data['user_agent'] ?? '',
                'session_id' => $data['session_id'] ?? '',
                'user_id' => $data['user_id'] ?? null,
            ],
        ]);
    }

    /**
     * Bắt lỗi database query
     */
    public static function captureDbError($message, $query = '', $params = [])
    {
        return self::captureError([
            'type' => 'db_error',
            'severity' => 'error',
            'message' => $message,
            'file' => '',
            'line' => 0,
            'url' => self::getCurrentUrl(),
            'context' => [
                'query' => substr($query, 0, 500),
                'params' => $params,
            ],
        ]);
    }

    /**
     * Lưu lỗi thủ công từ code
     */
    public static function capture($type, $message, $context = [])
    {
        return self::captureError([
            'type' => $type,
            'severity' => 'warning',
            'message' => $message,
            'file' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'] ?? '',
            'line' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['line'] ?? 0,
            'url' => self::getCurrentUrl(),
            'context' => $context,
        ]);
    }

    /**
     * Core: Lưu lỗi vào DB và gửi Messenger nếu cần
     */
    private static function captureError(array $errorData)
    {
        // Lọc lỗi không quan trọng
        if (self::shouldIgnore($errorData))
            return null;

        // Chuẩn bị dữ liệu
        $pageUrl = $errorData['url'] ?? '';
        $errorType = $errorData['type'] ?? 'unknown';
        $severity = $errorData['severity'] ?? 'error';
        $message = substr($errorData['message'] ?? '', 0, 2000);
        $file = $errorData['file'] ?? '';
        $line = (int) ($errorData['line'] ?? 0);
        $context = $errorData['context'] ?? [];
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sessionId = session_id() ?: '';
        $userId = $_SESSION['user_id'] ?? null;

        // Fingerprint để kiểm tra lỗi trùng
        $fingerprint = md5($errorType . $message . $file . $line);

        try {
            $db = self::getDb();
            if (!$db)
                return null;

            // Kiểm tra có tồn tại trong DB chưa (trong 1 giờ)
            $checkStmt = $db->prepare(
                "SELECT id, occurrence_count, messenger_sent FROM error_logs 
                 WHERE fingerprint = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                 ORDER BY created_at DESC LIMIT 1"
            );
            $checkStmt->execute([$fingerprint]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Tăng occurrence_count
                $db->prepare("UPDATE error_logs SET occurrence_count = occurrence_count + 1, last_seen_at = NOW() WHERE id = ?")
                    ->execute([$existing['id']]);

                // Nếu chưa gửi Telegram lần nào → gửi ngay
                if (empty($existing['messenger_sent']) && in_array($severity, ['critical', 'error'])) {
                    self::triggerAiAnalysis((int) $existing['id'], $errorData);
                }
                return $existing['id'];
            }

            // Insert mới
            $stmt = $db->prepare(
                "INSERT INTO error_logs 
                 (error_type, severity, message, file_path, line_number, page_url, ip_address, user_agent, session_id, user_id, context_data, fingerprint, occurrence_count, ai_analyzed, messenger_sent, created_at, last_seen_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 0, NOW(), NOW())"
            );
            $stmt->execute([
                $errorType,
                $severity,
                $message,
                $file,
                $line,
                $pageUrl,
                $ipAddress,
                $userAgent,
                $sessionId,
                $userId,
                json_encode($context, JSON_UNESCAPED_UNICODE),
                $fingerprint,
            ]);
            $errorId = (int) $db->lastInsertId();

            // Gửi Telegram và phân tích AI ngay
            if (in_array($severity, ['critical', 'error']) && $errorId) {
                self::triggerAiAnalysis($errorId, $errorData);
            }

            return $errorId;

        } catch (\Throwable $e) {
            error_log('[ErrorTracker] Failed to save error: ' . $e->getMessage());
            return null;
        }

    }

    /**
     * Kích hoạt gửi Telegram ngay + AI phân tích
     * KHÔNG dùng shutdown function — không đáng tin trên LiteSpeed/shared hosting
     */
    private static function triggerAiAnalysis(int $errorId, array $errorData)
    {
        // Gửi Telegram ngay lập tức (synchronous ~1-2 giây)
        // Chấp nhận delay nhỏ — quan trọng hơn là admin nhận được alert
        self::analyzeWithAiAndNotify($errorId, $errorData);
    }

    /**
     * Gọi Gemini AI phân tích lỗi và gửi Telegram
     * Telegram được gửi NGAY — không phụ thuộc Gemini thành công hay không
     */
    public static function analyzeWithAiAndNotify(int $errorId, array $errorData)
    {
        try {
            // ── BƯỚC 1: Gửi Telegram NGAY với thông tin cơ bản ───────────────────
            // Không chờ Gemini — đảm bảo admin luôn nhận được alert
            self::sendTelegramAlert($errorId, $errorData, '(AI dang phan tich... vao Admin de xem ket qua)');

            // ── BƯỚC 2: Gọi Gemini để phân tích sâu hơn ─────────────────────────
            require_once __DIR__ . '/../config/api_keys.php';

            $severity = $errorData['severity'] ?? 'error';
            $message = $errorData['message'] ?? '';
            $type = $errorData['type'] ?? 'unknown';
            $file = $errorData['file'] ?? '';
            $line = $errorData['line'] ?? 0;
            $url = $errorData['url'] ?? '';
            $context = json_encode($errorData['context'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            $prompt = <<<PROMPT
Bạn là AI chuyên gia phân tích lỗi web cho hệ thống Aurora Hotel Plaza.

THÔNG TIN LỖI:
- Loại: $type
- Mức độ: $severity
- Thông điệp: $message
- File: $file (dòng $line)
- URL: $url
- Context: $context

Hãy phân tích ngắn gọn (tối đa 300 từ):
1. **Nguyên nhân**: Lỗi này do đâu?
2. **Tác động**: Ảnh hưởng gì đến người dùng/hệ thống?
3. **Giải pháp**: Cách khắc phục nhanh nhất?
4. **Mức ưu tiên**: Cần sửa ngay hay có thể đợi?

Trả lời súc tích, chuyên nghiệp bằng tiếng Việt.
PROMPT;

            $apiKeys = $GEMINI_API_KEYS ?? [];
            foreach ($apiKeys as $key) {
                if (empty(trim($key)))
                    continue;

                $response = self::callGeminiApi($key, $prompt);
                if ($response) {
                    // Lưu AI analysis vào DB
                    $db = self::getDb();
                    if ($db) {
                        $db->prepare("UPDATE error_logs SET ai_analysis = ?, ai_analyzed = 1 WHERE id = ?")
                            ->execute([$response, $errorId]);
                    }
                    // Gemini xong — KHÔNG gửi Telegram lần 2 để tránh spam
                    return;
                }
            }
        } catch (\Throwable $e) {
            error_log('[ErrorTracker] AI analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * Gọi Gemini API
     */
    private static function callGeminiApi(string $apiKey, string $prompt): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";
        $body = json_encode([
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 512],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $result) {
            $data = json_decode($result, true);
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }
        return null;
    }

    /**
     * Gửi thông báo qua Telegram Bot
     */
    private static function sendTelegramAlert(int $errorId, array $errorData, string $aiAnalysis)
    {
        $token = self::$telegramBotToken;
        $chatId = self::$telegramChatId;

        if (empty($token) || empty($chatId)) {
            error_log('[ErrorTracker] Telegram Bot Token or Chat ID not configured. Error #' . $errorId . ' not sent.');
            return false;
        }

        $severity = strtoupper($errorData['severity'] ?? 'ERROR');
        $type = $errorData['type'] ?? 'unknown';
        $message = substr($errorData['message'] ?? '', 0, 200);
        $url = $errorData['url'] ?? '';
        $time = date('d/m/Y H:i:s');
        $adminUrl = (defined('BASE_URL') ? BASE_URL : '') . "/admin/ai-bug.php?id=$errorId";

        // Rút gọn AI analysis (Telegram hỗ trợ Markdown)
        $shortAnalysis = substr(strip_tags($aiAnalysis), 0, 600);

        // Telegram hỗ trợ MarkdownV2 hoặc HTML — dùng HTML cho dễ
        $text = "<b>AURORA BUG ALERT</b>\n"
            . "<b>[{$severity}]</b> #{$errorId}\n"
            . "\n"
            . "<b>Loại:</b> {$type}\n"
            . "<b>Thời gian:</b> {$time}\n"
            . "<b>URL:</b> {$url}\n"
            . "\n"
            . "<b>Lỗi:</b>\n<code>" . htmlspecialchars($message) . "</code>\n"
            . "\n"
            . "<b>AI Phân tích:</b>\n" . htmlspecialchars($shortAnalysis) . "\n"
            . "\n"
            . "<a href=\"$adminUrl\">Xem chi tiet tren Admin</a>";

        $payload = json_encode([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = ($httpCode === 200);

        try {
            $db = self::getDb();
            if ($db) {
                $db->prepare("UPDATE error_logs SET messenger_sent = ?, messenger_sent_at = NOW() WHERE id = ?")
                    ->execute([$success ? 1 : 0, $errorId]);
            }
        } catch (\Throwable $e) {
        }

        return $success;
    }

    /**
     * Lọc bỏ các lỗi không đáng để track
     */
    private static function shouldIgnore(array $errorData): bool
    {
        $message = $errorData['message'] ?? '';
        $file = $errorData['file'] ?? '';

        // Bỏ qua lỗi từ thư viện bên ngoài không quan trọng
        $ignorePatterns = [
            'Undefined variable: _token',
            'headers already sent',
            'Cannot modify header information',
            '/vendor/',
            'node_modules',
            'Deprecated:',
        ];

        foreach ($ignorePatterns as $pattern) {
            if (stripos($message, $pattern) !== false || stripos($file, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lấy URL hiện tại
     */
    private static function getCurrentUrl(): string
    {
        if (PHP_SAPI === 'cli')
            return 'CLI';
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return "$protocol://$host$uri";
    }

    /**
     * Map errno sang severity string
     */
    private static function getSeverityFromErrno(int $errno): string
    {
        return match ($errno) {
            E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR => 'critical',
            E_WARNING, E_RECOVERABLE_ERROR => 'error',
            E_NOTICE, E_DEPRECATED => 'warning',
            default => 'info',
        };
    }

    /**
     * API: Lấy danh sách lỗi mới nhất
     */
    public static function getRecentErrors(int $limit = 50, string $severity = '', string $type = ''): array
    {
        try {
            $db = self::getDb();
            if (!$db)
                return [];

            $where = [];
            $params = [];

            if ($severity) {
                $where[] = 'severity = ?';
                $params[] = $severity;
            }
            if ($type) {
                $where[] = 'error_type = ?';
                $params[] = $type;
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $stmt = $db->prepare("SELECT * FROM error_logs $whereSql ORDER BY created_at DESC LIMIT ?");
            $params[] = $limit;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * API: Thống kê lỗi
     */
    public static function getStats(): array
    {
        try {
            $db = self::getDb();
            if (!$db)
                return [];

            $stmt = $db->query(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity = 'error' THEN 1 ELSE 0 END) as error,
                    SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning,
                    SUM(CASE WHEN ai_analyzed = 1 THEN 1 ELSE 0 END) as ai_analyzed,
                    SUM(CASE WHEN messenger_sent = 1 THEN 1 ELSE 0 END) as telegram_sent,
                    SUM(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as last_24h,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                 FROM error_logs"
            );
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
