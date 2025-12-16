<?php
/**
 * Image Helper Functions
 * Xử lý đường dẫn ảnh từ database
 */

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

    // Convert spaces to hyphens in folder names
    $replacements = [
        'premium-deluxe' => 'premium-deluxe',
        'premium-twin' => 'premium-twin',
        'vip /' => 'vip/',
        'studio-apartment' => 'studio-apartment',
        'modern-studio-apartment' => 'modern-studio-apartment',
        'indochine-studio-apartment' => 'indochine-studio-apartment',
        'premium-apartment' => 'premium-apartment',
        'modern-premium-apartment' => 'modern-premium-apartment',
        'classical-premium-apartment' => 'classical-premium-apartment',
        'family-apartment' => 'family-apartment',
        'indochine-family-apartment' => 'indochine-family-apartment',
        'classical-family-apartment' => 'classical-family-apartment',
    ];

    foreach ($replacements as $search => $replace) {
        $path = str_replace($search, $replace, $path);
    }

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

    // Get base URL
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

