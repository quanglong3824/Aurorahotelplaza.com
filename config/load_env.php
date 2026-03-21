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
            $paths[] = $current_dir . '/config/env';
            $paths[] = $current_dir . '/.env';
            $paths[] = $current_dir . '/env';
            
            $parent = dirname($current_dir);
            if ($parent === $current_dir || $parent === '/' || $parent === '\\') {
                break;
            }
            $current_dir = $parent;
        }

        // Cơ chế dò tìm thông minh: Quét ngược từ Document Root lên thư mục cha
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $start_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
            $current = $start_dir;
            
            // Quét ngược lên tối đa 3 cấp (ví dụ: từ public_html/2025/ -> public_html/ -> home/user/)
            for ($i = 0; $i < 4; $i++) {
                $paths[] = $current . '/config/.env';
                $paths[] = $current . '/.env';
                
                $parent = dirname($current);
                if ($parent === $current || $parent === '/' || $parent === '.') break;
                $current = $parent;
            }
        }

        // Tạo hằng số AI_CONFIG_PATH bằng cách tự động xác định thư mục config khả dụng
        if (!defined('AI_CONFIG_PATH')) {
            $config_dir = __DIR__; // Fallback
            if (!empty($_SERVER['DOCUMENT_ROOT'])) {
                $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
                $search_locations = [
                    dirname($doc_root) . '/config', // Vị trí chuẩn: /home/user/config
                    $doc_root . '/../config',
                    $doc_root . '/config',
                    dirname(dirname($doc_root)) . '/config' // Trường hợp website ở subfolder sâu
                ];
                
                foreach ($search_locations as $loc) {
                    if (is_dir($loc) && is_writable($loc)) {
                        $config_dir = $loc;
                        break;
                    }
                }
            }
            define('AI_CONFIG_PATH', $config_dir);
        }

        $paths = array_unique($paths);
        
        foreach ($paths as $path) {
            if ($path && file_exists($path) && is_readable($path)) {
                $content = file_get_contents($path);
                // Loại bỏ BOM nếu có
                $content = str_replace("\xEF\xBB\xBF", '', $content);
                $lines = explode("\n", str_replace("\r", "", $content));
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) continue;
                    
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value, " \t\n\r\0\x0B\"'");
                        
                        if (!isset($_ENV[$name]) || $_ENV[$name] === '') {
                            $_ENV[$name] = $value;
                            if (function_exists('putenv')) {
                                @putenv(sprintf('%s=%s', $name, $value));
                            }
                        }
                    }
                }
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
        } elseif (isset($_SERVER[$key])) {
            $val = $_SERVER[$key];
        } elseif (function_exists('getenv')) {
            $val = @getenv($key);
        }

        if ($val === false || $val === null || $val === '') {
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
