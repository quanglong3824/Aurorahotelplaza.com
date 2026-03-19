<?php
/**
 * Environment Configuration - Production Only
 * Cấu hình cho môi trường production trên hosting
 */

// Lấy base URL - Production with subdirectory support
function getBaseUrl() {
    // Production - Tự động phát hiện protocol và host
    $protocol = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 || $_SERVER['HTTPS'] === '1')) || 
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'aurorahotelplaza.com';
    
    // Lấy root path của project (loại bỏ các thư mục con như admin, auth, etc.)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = dirname($scriptName);
    
    // Danh sách các subdirectories cần loại bỏ để tìm root
    $subdirs = ['views', 'admin', 'auth', 'booking', 'payment', 'profile', 'services-pages', 'apartment-details', 'room-details', 'api'];
    $pattern = '#/(' . implode('|', $subdirs) . ').*#';
    $rootPath = preg_replace($pattern, '', $scriptDir);
    
    // Nếu ở root thì không thêm path
    if ($rootPath === '/' || $rootPath === '\\' || $rootPath === '') {
        return $protocol . '://' . $host;
    }
    
    // Trả về URL với subdirectory (ví dụ: https://aurorahotelplaza.com/2025)
    // Luôn rtrim để tránh double slash khi nối tiếp
    return rtrim($protocol . '://' . $host . $rootPath, '/');
}

// Lấy site URL (với trailing slash)
function getSiteUrl() {
    return rtrim(getBaseUrl(), '/') . '/';
}

// Lấy assets URL
function getAssetsUrl() {
    return rtrim(getBaseUrl(), '/') . '/assets';
}

// Lấy uploads URL
function getUploadsUrl() {
    return rtrim(getBaseUrl(), '/') . '/uploads';
}

// Lấy admin URL
function getAdminUrl() {
    return rtrim(getBaseUrl(), '/') . '/admin';
}

// Lấy API URL
function getApiUrl() {
    return rtrim(getBaseUrl(), '/') . '/api';
}

// Lấy domain chính (không có protocol)
function getDomain() {
    return $_SERVER['HTTP_HOST'] ?? 'aurorahotelplaza.com';
}

// Constants
define('SITE_URL', getSiteUrl());
define('BASE_URL', getBaseUrl());
define('ASSETS_URL', getAssetsUrl());
define('UPLOADS_URL', getUploadsUrl());
define('ADMIN_URL', getAdminUrl());
define('API_URL', getApiUrl());
define('ENVIRONMENT', 'production');
define('DOMAIN', getDomain());

// Load Security Helper
require_once __DIR__ . '/../helpers/security.php';

// Production: Disable error display, log only
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
define('DEBUG_MODE', false);

// Production: Secure cookies (only set if session not started yet)
if (session_status() === PHP_SESSION_NONE) {
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
    return rtrim(BASE_URL, '/') . '/' . $path;
}

/**
 * Helper function để tạo asset URL
 * 
 * @param string $path Path relative to assets (e.g., 'css/style.css')
 * @return string Full asset URL
 */
function asset($path = '') {
    $path = ltrim($path, '/');
    return rtrim(ASSETS_URL, '/') . '/' . $path;
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
