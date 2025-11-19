<?php
/**
 * Security Helper Class
 * Provides comprehensive security functions for Aurora Hotel Plaza
 * 
 * Features:
 * - Input validation and sanitization
 * - CSRF token generation and validation
 * - XSS prevention
 * - SQL injection prevention
 * - Rate limiting
 * - Session security
 */

class Security {
    
    /**
     * Generate CSRF Token
     * @return string
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF Token
     * @param string $token
     * @return bool
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF Token Input Field
     * @return string
     */
    public static function getCSRFInput() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Sanitize String Input
     * @param string $input
     * @return string
     */
    public static function sanitizeString($input) {
        if (is_null($input)) {
            return '';
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Strip tags and encode special characters
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove any remaining dangerous characters
        $input = preg_replace('/[^\p{L}\p{N}\s\-\_\.\@\,\!\?\:\;\(\)]/u', '', $input);
        
        return trim($input);
    }
    
    /**
     * Sanitize Email
     * @param string $email
     * @return string|false
     */
    public static function sanitizeEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }
    
    /**
     * Sanitize URL
     * @param string $url
     * @return string|false
     */
    public static function sanitizeURL($url) {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : false;
    }
    
    /**
     * Sanitize Integer
     * @param mixed $input
     * @return int
     */
    public static function sanitizeInt($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize Float
     * @param mixed $input
     * @return float
     */
    public static function sanitizeFloat($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize HTML (for rich text editors)
     * @param string $html
     * @return string
     */
    public static function sanitizeHTML($html) {
        // Allow only safe HTML tags
        $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><tr><td><th><thead><tbody>';
        
        $html = strip_tags($html, $allowed_tags);
        
        // Remove dangerous attributes
        $html = preg_replace('/<([^>]+?)(?:on\w+|style|formaction)\s*=\s*[\'"]?[^\'"]*[\'"]?([^>]*)>/i', '<$1$2>', $html);
        
        return $html;
    }
    
    /**
     * Prevent SQL Injection (use with prepared statements)
     * @param mysqli $conn
     * @param string $input
     * @return string
     */
    public static function escapeSQLString($conn, $input) {
        return mysqli_real_escape_string($conn, $input);
    }
    
    /**
     * Validate Password Strength
     * @param string $password
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Mật khẩu phải chứa ít nhất 1 chữ hoa';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Mật khẩu phải chứa ít nhất 1 chữ thường';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Mật khẩu phải chứa ít nhất 1 số';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Hash Password Securely
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verify Password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Rate Limiting Check
     * @param string $identifier (IP or user ID)
     * @param int $max_attempts
     * @param int $time_window (seconds)
     * @return bool
     */
    public static function checkRateLimit($identifier, $max_attempts = 5, $time_window = 300) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        $data = $_SESSION[$key];
        $elapsed = time() - $data['first_attempt'];
        
        // Reset if time window has passed
        if ($elapsed > $time_window) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Check if exceeded max attempts
        if ($data['attempts'] >= $max_attempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION[$key]['attempts']++;
        return true;
    }
    
    /**
     * Get Client IP Address
     * @return string
     */
    public static function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate IP
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    
    /**
     * Secure Session Configuration
     */
    public static function secureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            
            // Regenerate session ID periodically
            session_start();
            
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 1800) {
                // Regenerate session after 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Validate File Upload
     * @param array $file ($_FILES array)
     * @param array $allowed_types
     * @param int $max_size (bytes)
     * @return array ['valid' => bool, 'error' => string]
     */
    public static function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'], $max_size = 5242880) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'File không hợp lệ'];
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            return ['valid' => false, 'error' => 'File quá lớn (max: ' . ($max_size / 1024 / 1024) . 'MB)'];
        }
        
        // Check file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_types)) {
            return ['valid' => false, 'error' => 'Định dạng file không được phép'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'application/pdf', 'image/webp'
        ];
        
        if (!in_array($mime, $allowed_mimes)) {
            return ['valid' => false, 'error' => 'MIME type không hợp lệ'];
        }
        
        return ['valid' => true, 'error' => ''];
    }
    
    /**
     * Generate Secure Random String
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Prevent Clickjacking
     */
    public static function preventClickjacking() {
        header('X-Frame-Options: SAMEORIGIN');
        header('Content-Security-Policy: frame-ancestors \'self\'');
    }
    
    /**
     * Log Security Event
     * @param string $event
     * @param string $details
     */
    public static function logSecurityEvent($event, $details = '') {
        $log_file = __DIR__ . '/../logs/security.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $log_entry = sprintf(
            "[%s] IP: %s | Event: %s | Details: %s | User-Agent: %s\n",
            $timestamp,
            $ip,
            $event,
            $details,
            $user_agent
        );
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}
