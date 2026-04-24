<?php
/**
 * Simple .env loader - Aurora Hotel Plaza
 * Tự động load biến môi trường từ file .env
 *
 * Cấu trúc hosting thường:
 * /home/user/public_html/    -> Website files
 * /home/user/config/.env     -> Environment file (bên ngoài public_html)
 */

if (!function_exists('loadEnvVariables')) {
    function loadEnvVariables() {
        $paths = [];
        $loaded = false;

        // 1. Từ config directory (nằm cùng cấp với public_html)
        // Đây là cấu trúc phổ biến trên hosting: /home/user/config/.env
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
            $parent_of_docroot = dirname($doc_root);

            // /home/user/config/.env (bên ngoài public_html)
            $paths[] = $parent_of_docroot . '/config/.env';
            $paths[] = $parent_of_docroot . '/config/env';
            $paths[] = $parent_of_docroot . '/.env';
            $paths[] = $parent_of_docroot . '/env';

            // /home/user/public_html/config/.env (fallback)
            $paths[] = $doc_root . '/config/.env';
            $paths[] = $doc_root . '/config/env';
            $paths[] = $doc_root . '/.env';
            $paths[] = $doc_root . '/env';

            // Go up one more level from parent_of_docroot
            $grandparent = dirname($parent_of_docroot);
            $paths[] = $grandparent . '/config/.env';
            $paths[] = $grandparent . '/.env';
        }

        // 2. Từ current file location (for CLI/local development)
        $current_dir = __DIR__;
        for ($i = 0; $i < 6; $i++) {
            $paths[] = $current_dir . '/config/.env';
            $paths[] = $current_dir . '/config/env';
            $paths[] = $current_dir . '/.env';
            $paths[] = $current_dir . '/env';

            $parent = dirname($current_dir);
            if ($parent === $current_dir || $parent === '/' || $parent === '\\') break;
            $current_dir = $parent;
        }

        // Remove duplicates
        $paths = array_unique($paths);

        // Try each path
        foreach ($paths as $path) {
            if ($path && file_exists($path) && is_readable($path)) {
                $content = file_get_contents($path);

                // Remove BOM if present
                $content = str_replace("\xEF\xBB\xBF", '', $content);
                $lines = explode("\n", str_replace("\r", "", $content));

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) continue;

                    if (strpos($line, '=') !== false) {
                        $parts = explode('=', $line, 2);
                        $name = trim($parts[0]);
                        $value = trim($parts[1], " \t\n\r\0\x0B\"'");

                        // Set to $_ENV (priority over existing values)
                        $_ENV[$name] = $value;

                        if (function_exists('putenv')) {
                            @putenv(sprintf('%s=%s', $name, $value));
                        }
                    }
                }

                $loaded = true;

                // Log which file was loaded (for debugging)
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("ENV Loader: Loaded from $path");
                }

                break; // Stop after first successful load
            }
        }

        return $loaded;
    }

    // Auto-load on include
    loadEnvVariables();
}

if (!function_exists('env')) {
    /**
     * Get environment variable value
     *
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function env($key, $default = null) {
        $val = null;

        // Priority: $_ENV (from .env file)
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            $val = $_ENV[$key];
        }
        // Fallback: $_SERVER
        elseif (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            $val = $_SERVER[$key];
        }
        // Fallback: getenv()
        elseif (function_exists('getenv')) {
            $val = @getenv($key);
        }

        if ($val === false || $val === null || $val === '') {
            return $default;
        }

        // Convert boolean strings
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

/**
 * Debug: Check which .env file would be loaded
 */
function debug_env_path() {
    $paths = [];

    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
        $parent_of_docroot = dirname($doc_root);

        $paths[] = $parent_of_docroot . '/config/.env';
        $paths[] = $parent_of_docroot . '/.env';
        $paths[] = $doc_root . '/config/.env';
        $paths[] = $doc_root . '/.env';
    }

    $current_dir = __DIR__;
    for ($i = 0; $i < 3; $i++) {
        $paths[] = $current_dir . '/config/.env';
        $paths[] = $current_dir . '/.env';
        $current_dir = dirname($current_dir);
    }

    $paths = array_unique($paths);

    $result = [];
    foreach ($paths as $path) {
        $result[] = [
            'path' => $path,
            'exists' => file_exists($path),
            'readable' => is_readable($path)
        ];
    }

    return $result;
}

// Load Composer Autoloader (for Gemini SDK)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}