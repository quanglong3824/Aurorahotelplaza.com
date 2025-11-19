<?php
/**
 * URL Checker Test Page
 * Trang kiểm tra URL và môi trường
 * 
 * QUAN TRỌNG: File này chỉ dùng cho development
 * Xóa hoặc bảo vệ file này trước khi deploy lên production!
 */

// Chỉ cho phép truy cập từ localhost
if ($_SERVER['HTTP_HOST'] !== 'localhost' && 
    $_SERVER['HTTP_HOST'] !== '127.0.0.1' && 
    strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    die('Access denied. This tool is only available in development environment.');
}

require_once __DIR__ . '/helpers/url-checker.php';

// Hiển thị trang
displayURLCheckerPage();
