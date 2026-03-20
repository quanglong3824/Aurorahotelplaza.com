<?php
/**
 * Simple .env loader functionality to securely load secrets
 */

if (!function_exists('loadEnvVariables')) {
    function loadEnvVariables() {
        $paths = [];
        $current_dir = __DIR__;
        
        // Quét ngược từ thư mục hiện tại lên tới thư mục gốc (tối đa 6 cấp)
        for ($i = 0; $i < 6; $i++) {
            $paths[] = $current_dir . '/config/.env';
            $paths[] = $current_dir . '/.env';
            
            $parent = dirname($current_dir);
            if ($parent === $current_dir || $parent === '/' || $parent === '\\') {
                break;
            }
            $current_dir = $parent;
        }

        // Bổ sung quét dự phòng ở ngoài Document Root của Webserver (tuyệt đối an toàn)
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
            $paths[] = dirname($doc_root) . '/config/.env';
            $paths[] = dirname($doc_root) . '/.env';
        }

        $paths = array_unique($paths);
        
        $env_loaded = false;
        foreach ($paths as $path) {
            if ($path && file_exists($path)) {
                $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines === false) continue;
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) continue;
                    
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value, " \t\n\r\0\x0B\"'");
                        
                        if (!isset($_ENV[$name])) {
                            $_ENV[$name] = $value;
                            // Kiểm tra function_exists để tránh LỖI 500 TRẮNG TRANG trên các Hosting cấm hàm putenv
                            if (function_exists('putenv')) {
                                @putenv(sprintf('%s=%s', $name, $value));
                            }
                        }
                    }
                }
                $env_loaded = true;
                // Tiếp tục quét các file .env khác để lấy thêm biến nếu chưa có (không break)
            }
        }
    }

    // Tự động load ngay khi được require_once
    loadEnvVariables();
}

if (!function_exists('env')) {
    /**
     * Tham số hỗ trợ lấy giá trị biến môi trường bảo mật
     */
    function env($key, $default = null) {
        $val = null;
        if (isset($_ENV[$key])) {
            $val = $_ENV[$key];
        } elseif (function_exists('getenv')) {
            $val = @getenv($key);
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
