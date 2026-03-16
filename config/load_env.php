<?php
/**
 * Simple .env loader functionality to securely load secrets
 * Optimized for Production Host: /home/user/config/.env (outside public_html)
 */

if (!function_exists('loadEnvVariables')) {
    function loadEnvVariables() {
        $paths = [];
        
        // 1. Dùng DOCUMENT_ROOT để tìm thư mục config nằm ngoài public_html (Cách chuẩn trên Host)
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
            $paths[] = dirname($doc_root) . '/config/.env';
        }

        // 2. Dự phòng bằng cách quét ngược từ thư mục hiện tại (Dành cho cấu hình phức tạp hoặc sub-folder)
        $current = __DIR__;
        for ($i = 0; $i < 6; $i++) {
            $paths[] = $current . '/config/.env';
            $paths[] = $current . '/.env';
            $parent = dirname($current);
            if ($parent === $current || $parent === '/' || $parent === '\\') break;
            $current = $parent;
        }

        $paths = array_unique($paths);
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
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
                            if (function_exists('putenv')) {
                                @putenv(sprintf('%s=%s', $name, $value));
                            }
                        }
                    }
                }
                return true; // Dừng khi load thành công file đầu tiên
            }
        }
        return false;
    }
    loadEnvVariables();
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        $val = $_ENV[$key] ?? (function_exists('getenv') ? getenv($key) : false);
        if ($val === false || $val === null) return $default;
        switch (strtolower($val)) {
            case 'true': return true;
            case 'false': return false;
            case 'null': return null;
            case 'empty': return '';
        }
        return $val;
    }
}
