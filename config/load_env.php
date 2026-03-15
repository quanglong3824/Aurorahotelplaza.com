<?php
/**
 * Simple .env loader functionality to securely load secrets
 */

if (!function_exists('loadEnvVariables')) {
    function loadEnvVariables() {
        $path = dirname(__DIR__) . '/.env';
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Bỏ qua comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            // Parse name=value
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                // Bỏ đi quotes (" hoặc ') ở 2 đầu nếu có
                $value = trim($value, " \t\n\r\0\x0B\"'");
                
                // Chỉ thiết lập khi chưa tồn tại
                if (!isset($_ENV[$name])) {
                    $_ENV[$name] = $value;
                    putenv(sprintf('%s=%s', $name, $value));
                }
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
