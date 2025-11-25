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
function normalizeImagePath($path) {
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
function getFirstImage($images) {
    if (empty($images)) {
        return '';
    }
    
    $imageArray = explode(',', $images);
    return trim($imageArray[0]);
}
