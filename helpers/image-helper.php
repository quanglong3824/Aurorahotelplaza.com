<?php
/**
 * Image Helper Functions
 * Xử lý đường dẫn ảnh từ database
 */

/**
 * Remove accents/diacritics from string
 */
function removeAccents($str)
{
    $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
    $str = preg_replace("/(đ)/", 'd', $str);
    $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
    $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
    $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
    $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
    $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
    $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
    $str = preg_replace("/(Đ)/", 'D', $str);
    return $str;
}

/**
 * Normalize image path from database
 * Chuyển đổi đường dẫn ảnh từ database sang đường dẫn thực tế
 * 
 * @param string $path Đường dẫn từ database (e.g., /2025/assets/img/premium deluxe/...)
 * @return string Đường dẫn thực tế (e.g., /assets/img/premium-deluxe/...)
 */
function normalizeImagePath($path)
{
    if (empty($path)) {
        return '';
    }

    // Remove /2025 prefix if exists
    $path = str_replace('/2025', '', $path);

    // Fix common double slashes
    $path = str_replace('//', '/', $path);

    // Decode URL encoded characters if any
    $path = urldecode($path);

    // Specific replacements for known issues
    $known_replacements = [
        'vip /' => 'vip/',
        'premium deluxe' => 'premium-deluxe',
        'premium twin' => 'premium-twin',
        'studio apartment' => 'studio-apartment',
        'modern studio apartment' => 'modern-studio-apartment',
        'indochine studio apartment' => 'indochine-studio-apartment',
        'premium apartment' => 'premium-apartment',
        'modern premium apartment' => 'modern-premium-apartment',
        'classical premium apartment' => 'classical-premium-apartment',
        'family apartment' => 'family-apartment',
        'indochine family apartment' => 'indochine-family-apartment',
        'classical family apartment' => 'classical-family-apartment',
    ];

    foreach ($known_replacements as $search => $replace) {
        $path = str_ireplace($search, $replace, $path); // Case insensitive replace
    }

    // Generic fixes:
    // 1. Convert spaces to hyphens (risky if filename has space but usually safe for web assets)
    // We only apply this to the directory part if possible, but let's be careful.
    // For now, assume web paths shouldn't have spaces.
    // $path = str_replace(' ', '-', $path);

    // LINUX COMPATIBILITY: Force lowercase
    // Filesystem on Linux is case-sensitive. If we ensure all assets are lowercase, 
    // and force requests to lowercase, we avoid broken images.
    $path = strtolower($path);

    return $path;
}

/**
 * Get first image from comma-separated list
 * 
 * @param string $images Comma-separated image paths
 * @return string First image path
 */
function getFirstImage($images)
{
    if (empty($images)) {
        return '';
    }

    $imageArray = explode(',', $images);
    return trim($imageArray[0]);
}

/**
 * Get absolute URL for image with fallback
 * Tạo URL tuyệt đối cho ảnh với fallback nếu ảnh không tồn tại
 * 
 * @param string $path Đường dẫn ảnh (từ database hoặc relative)
 * @param string $fallback Đường dẫn ảnh fallback nếu ảnh chính không tồn tại
 * @return string URL tuyệt đối của ảnh
 */
function imgUrl($path, $fallback = 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg')
{
    // Normalize path first
    $path = normalizeImagePath($path);

    if (empty($path)) {
        $path = $fallback;
    }

    // Remove leading slash if exists
    $path = ltrim($path, '/');

    // Check if path is already absolute URL
    if (strpos($path, 'http') === 0) {
        return $path;
    }

    // Use BASE_URL if defined (recommended)
    if (defined('BASE_URL')) {
        return BASE_URL . '/' . $path;
    }

    // Fallback if BASE_URL not defined
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

    // Build absolute URL
    $baseUrl = $protocol . '://' . $host . $basePath;

    return $baseUrl . '/' . $path;
}

/**
 * Get image source with onerror fallback
 * Tạo attribute src với onerror handler cho fallback
 * 
 * @param string $path Đường dẫn ảnh
 * @param string $fallback Đường dẫn ảnh fallback
 * @return string HTML attributes cho thẻ img
 */
function imgSrcWithFallback($path, $fallback = 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg')
{
    $src = imgUrl($path, $fallback);
    $fallbackUrl = imgUrl($fallback);

    return 'src="' . htmlspecialchars($src) . '" onerror="this.onerror=null; this.src=\'' . htmlspecialchars($fallbackUrl) . '\'"';
}

