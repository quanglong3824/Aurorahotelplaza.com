<?php
/**
 * Aurora Booking Website - Database Configuration
 * ===============================================
 * Hỗ trợ kết nối song song: Local (XAMPP) và Host (Production)
 */

// Tự động phát hiện môi trường
$isLocal = true;
if (isset($_SERVER['SERVER_NAME'])) {
    $isLocal = (
        $_SERVER['SERVER_NAME'] === 'localhost' ||
        $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
        $_SERVER['SERVER_ADDR'] === '::1' ||
        strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
    );
}

// Cấu hình Local (XAMPP)
define('DB_LOCAL_NAME', 'auroraho_aurorahotelplaza.com');
define('DB_LOCAL_USER', 'root');
define('DB_LOCAL_PASSWORD', '');
define('DB_LOCAL_HOST', '127.0.0.1:3306');

// Cấu hình Host/Production
define('DB_HOST_NAME', 'auroraho_aurorahotelplaza.com');
define('DB_HOST_USER', 'auroraho_longdev');
define('DB_HOST_PASSWORD', '@longdev3824');
define('DB_HOST_HOST', 'localhost:3306');

// Chọn cấu hình dựa trên môi trường
if ($isLocal) {
    define('DB_NAME', DB_LOCAL_NAME);
    define('DB_USER', DB_LOCAL_USER);
    define('DB_PASSWORD', DB_LOCAL_PASSWORD);
    define('DB_HOST', DB_LOCAL_HOST);
    define('DB_ENVIRONMENT', 'LOCAL');
} else {
    define('DB_NAME', DB_HOST_NAME);
    define('DB_USER', DB_HOST_USER);
    define('DB_PASSWORD', DB_HOST_PASSWORD);
    define('DB_HOST', DB_HOST_HOST);
    define('DB_ENVIRONMENT', 'PRODUCTION');
}

/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');
define('DB_DEBUG', false);


//////
class Database
{
    private $host = DB_HOST;
    private $port = 3306;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASSWORD;
    private $charset = DB_CHARSET;
    public $conn;
    private $last_error = null;
    private $last_error_code = null;
    private $fallback_used = false;

    // Get database connection
    public function getConnection()
    {
        $this->conn = null;

        try {
            $hostWithoutPort = $this->parseHostAndPort($this->host);
            $dsn = "mysql:host=" . $hostWithoutPort . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            // Ghi nhận lỗi kết nối
            $this->last_error = $exception->getMessage();
            $this->last_error_code = $exception->getCode();
            if (defined('DB_DEBUG') && DB_DEBUG) {
                error_log("DB connection error: " . $this->last_error);
            }
            return false;
        }

        return $this->conn;
    }

    // Close connection
    public function closeConnection()
    {
        $this->conn = null;
    }

    public function getLastError()
    {
        return $this->last_error;
    }

    public function getLastErrorCode()
    {
        return $this->last_error_code;
    }

    public function isFallbackUsed()
    {
        return $this->fallback_used === true;
    }

    private function parseHostAndPort($host)
    {
        if (strpos($host, ':') !== false) {
            $parts = explode(':', $host, 2);
            $host = $parts[0];
            $port = $parts[1];
            if (is_numeric($port)) {
                $this->port = (int) $port;
            }
        }
        return $host;
    }
}

// Create database instance
function getDB()
{
    $database = new Database();
    return $database->getConnection();
}

function explainPDOError($message)
{
    // ... existing explainPDOError function body implied or copied if I had it fully. 
    // I only see it in Step 98 output.
    // I'll copy it from Step 98 just safely.
    $msg = strtolower($message);
    if (strpos($msg, 'access denied for user') !== false) {
        return 'Sai user/password hoặc user không có quyền truy cập từ host hiện tại.';
    }
    if (strpos($msg, 'unknown database') !== false) {
        return 'Tên database không tồn tại. Kiểm tra hằng số DB_NAME.';
    }
    if (strpos($msg, '2002') !== false || strpos($msg, 'connection refused') !== false || strpos($msg, 'no such file or directory') !== false) {
        return 'Không kết nối được tới MySQL. Kiểm tra DB_HOST/port hoặc MySQL chưa chạy.';
    }
    if (strpos($msg, 'getaddrinfo') !== false || strpos($msg, 'name or service not known') !== false) {
        return 'Hostname DB_HOST không phân giải được. Kiểm tra DNS hoặc dùng IP.';
    }
    if (strpos($msg, 'too many connections') !== false) {
        return 'MySQL quá tải (too many connections). Cần tăng giới hạn hoặc giảm tải.';
    }
    return 'Kiểm tra lại cấu hình DB_HOST, DB_USER, DB_PASSWORD, DB_NAME và firewall.';
}

function checkDBConnection($echo = true)
{
    $database = new Database();
    $conn = $database->getConnection();
    if ($conn === false) {
        $err = $database->getLastError();
        $code = $database->getLastErrorCode();
        $hint = explainPDOError($err ?? '');
        $output = "Kết nối thất bại. Lý do: {$err}";
        if (!empty($code)) {
            $output .= " (Code: {$code})";
        }
        $output .= "\nGợi ý: {$hint}";
        if ($echo) {
            echo "<pre>{$output}</pre>";
        }
        return ['success' => false, 'message' => $output];
    }
    if ($echo) {
        echo "<pre>Kết nối thành công. Host: " . DB_HOST . "</pre>";
    }
    return ['success' => true, 'message' => 'OK'];
}
?>