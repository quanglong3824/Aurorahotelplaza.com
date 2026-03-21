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

        // Bổ sung quét dự phòng ở ngoài Document Root của Webserver (tuyệt đối an toàn)
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
            $parent_root = dirname($doc_root);
            
            // Các vị trí phổ biến của thư mục config nằm ngoài public_html
            $paths[] = $parent_root . '/config/.env';
            $paths[] = $parent_root . '/.env';
            $paths[] = dirname($parent_root) . '/config/.env'; // Lên thêm 1 cấp nếu cần
            
            // Fallback cho cấu trúc website nằm sâu trong subfolder
            $paths[] = $doc_root . '/../config/.env';
            $paths[] = $doc_root . '/../../config/.env';
        }

        // Tạo hằng số để các helper khác biết chỗ lưu file log/index
        if (!defined('AI_CONFIG_PATH')) {
            $found_config_dir = __DIR__; // Mặc định
            if (!empty($_SERVER['DOCUMENT_ROOT'])) {
                $check_path = dirname(rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')) . '/config';
                if (is_dir($check_path) && is_writable($check_path)) {
                    $found_config_dir = $check_path;
                }
            }
            define('AI_CONFIG_PATH', $found_config_dir);
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
