<?php
/**
 * Environment Configuration
 * Tự động phát hiện môi trường (localhost hoặc production)
 */

// Phát hiện môi trường
function isLocalhost() {
    $whitelist = ['127.0.0.1', '::1', 'localhost'];
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', $whitelist) || 
           in_array($_SERVER['SERVER_NAME'] ?? '', $whitelist) ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
}

// Lấy base URL
function getBaseUrl() {
    if (isLocalhost()) {
        // Localhost - Tự động phát hiện đường dẫn
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        // Lấy root path của project (loại bỏ các thư mục con)
        $rootPath = preg_replace('#/(admin|auth|booking|payment|profile|services-pages|apartment-details|room-details).*#', '', $scriptName);
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $rootPath;
    } else {
        // Production - aurorahotelplaza.com
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'aurorahotelplaza.com';
        return $protocol . '://' . $host;
    }
}

// Lấy site URL (với trailing slash)
function getSiteUrl() {
    return rtrim(getBaseUrl(), '/') . '/';
}

// Lấy assets URL
function getAssetsUrl() {
    return getBaseUrl() . '/assets';
}

// Lấy uploads URL
function getUploadsUrl() {
    return getBaseUrl() . '/uploads';
}

// Lấy admin URL
function getAdminUrl() {
    return getBaseUrl() . '/admin';
}

// Lấy API URL
function getApiUrl() {
    return getBaseUrl() . '/api';
}

// Kiểm tra môi trường
function getEnvironment() {
    return isLocalhost() ? 'development' : 'production';
}

// Lấy domain chính (không có protocol)
function getDomain() {
    if (isLocalhost()) {
        return 'localhost';
    }
    return $_SERVER['HTTP_HOST'] ?? 'aurorahotelplaza.com';
}

// Constants
define('SITE_URL', getSiteUrl());
define('BASE_URL', getBaseUrl());
define('ASSETS_URL', getAssetsUrl());
define('UPLOADS_URL', getUploadsUrl());
define('ADMIN_URL', getAdminUrl());
define('API_URL', getApiUrl());
define('ENVIRONMENT', getEnvironment());
define('IS_LOCALHOST', isLocalhost());
define('DOMAIN', getDomain());

// Debug mode (chỉ bật trên localhost)
if (IS_LOCALHOST) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
}

// Session configuration
if (!IS_LOCALHOST) {
    // Production: Secure cookies
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
}

/**
 * Helper function để tạo URL
 * 
 * @param string $path Path relative to root (e.g., 'auth/login.php')
 * @return string Full URL
 */
function url($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

/**
 * Helper function để tạo asset URL
 * 
 * @param string $path Path relative to assets (e.g., 'css/style.css')
 * @return string Full asset URL
 */
function asset($path = '') {
    $path = ltrim($path, '/');
    return ASSETS_URL . '/' . $path;
}

/**
 * Redirect helper
 * 
 * @param string $path Path to redirect to
 * @param int $code HTTP status code
 */
function redirect($path, $code = 302) {
    $url = (strpos($path, 'http') === 0) ? $path : url($path);
    header("Location: $url", true, $code);
    exit;
}

/**
 * Get current URL
 * 
 * @return string Current full URL
 */
function currentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Check if current URL matches path
 * 
 * @param string $path Path to check
 * @return bool
 */
function isCurrentUrl($path) {
    $currentPath = parse_url(currentUrl(), PHP_URL_PATH);
    $checkPath = parse_url(url($path), PHP_URL_PATH);
    return $currentPath === $checkPath;
}
