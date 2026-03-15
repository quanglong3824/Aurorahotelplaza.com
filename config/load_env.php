<?php
/**
 * Simple .env loader functionality to securely load secrets
 */

if (!function_exists('loadEnvVariables')) {
    function loadEnvVariables() {
        // Cấu trúc thư mục tìm kiếm .env (Ưu tiên từ bên NGOÀI public_html trở vào trong)
        // Việc này ngăn chặn triệt để lộ lọt nếu tool scan thư mục public
        $paths = [
            // 1. Thư mục config nằm NGOÀI public_html (Ví dụ: /home/username/config/.env)
            dirname(__DIR__, 2) . '/config/.env',
            
            // 2. Nằm trực tiếp ở root của Server User (Ví dụ: /home/username/.env)
            dirname(__DIR__, 2) . '/.env',
            
            // 3. Dựa theo DOCUMENT ROOT (cách an toàn tuyệt đối nếu có phân cấp đặc biệt)
            dirname($_SERVER['DOCUMENT_ROOT'] ?? '') . '/config/.env',
            dirname($_SERVER['DOCUMENT_ROOT'] ?? '') . '/.env',
            
            // 4. Dành cho môi trường Local XAMPP như hiện tại
            dirname(__DIR__) . '/.env'
        ];

        $env_loaded = false;
        foreach ($paths as $path) {
            if ($path && file_exists($path)) {
                $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value, " \t\n\r\0\x0B\"'");
                        
                        if (!isset($_ENV[$name])) {
                            $_ENV[$name] = $value;
                            putenv(sprintf('%s=%s', $name, $value));
                        }
                    }
                }
                $env_loaded = true;
                break; // Dừng lại ở file .env ĐẦU TIÊN mà nó tìm thấy ở cấp độ bảo mật cao nhất
            }
        }
    }
    
    // Tự động load ngay khi được require_once
    loadEnvVariables();
}

if (!function_exists('env')) {
    /**
     * Helper function equivalent to getenv() with default value
     * Supports boolean casting for 'true', 'false', 'null', 'empty'
     */
    function env($key, $default = null) {
        $val = null;
        if (isset($_ENV[$key])) {
            $val = $_ENV[$key];
        } else {
            $val = getenv($key);
        }

        if ($val === false || $val === null) {
            return $default;
        }

        switch (strtolower($val)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $val;
    }
}
