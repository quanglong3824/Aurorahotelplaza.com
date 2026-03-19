<?php
/**
 * Aurora Booking Website - Database Configuration
 * Strictly loads from environment variables
 */

require_once __DIR__ . '/load_env.php';

// Tự động phát hiện môi trường
$isLocal = false;
if (isset($_SERVER['SERVER_NAME'])) {
    $isLocal = (
        $_SERVER['SERVER_NAME'] === 'localhost' ||
        $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
        $_SERVER['SERVER_ADDR'] === '::1' ||
        strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
    );
}

// Cấu hình Database - Tất cả lấy từ .env
if ($isLocal) {
    define('DB_NAME', env('DB_LOCAL_NAME'));
    define('DB_USER', env('DB_LOCAL_USER', 'root'));
    define('DB_PASSWORD', env('DB_LOCAL_PASSWORD', ''));
    define('DB_HOST', env('DB_LOCAL_HOST', '127.0.0.1:3306'));
    define('DB_ENVIRONMENT', 'LOCAL');
} else {
    define('DB_NAME', env('DB_HOST_NAME'));
    define('DB_USER', env('DB_HOST_USER'));
    define('DB_PASSWORD', env('DB_HOST_PASSWORD'));
    define('DB_HOST', env('DB_HOST_HOST', 'localhost:3306'));
    define('DB_ENVIRONMENT', 'PRODUCTION');
}

/** Database charset */
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
define('DB_DEBUG', env('DB_DEBUG', false));

class Database
{
    private $host = DB_HOST;
    private $port = 3306;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASSWORD;
    private $charset = DB_CHARSET;
    public $conn;

    public function getConnection()
    {
        if (empty($this->db_name)) {
            error_log("Database Error: DB_NAME is not defined in .env");
            return false;
        }

        $this->conn = null;
        try {
            $hostWithoutPort = $this->parseHostAndPort($this->host);
            $dsn = "mysql:host=" . $hostWithoutPort . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            error_log("Connection failed: " . $exception->getMessage());
            return false;
        }
        return $this->conn;
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

function getDB()
{
    $database = new Database();
    return $database->getConnection();
}
?>
