<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * VNPay Configuration
 * Updated: 2025-11-19
 */

// VNPay Credentials
$vnp_TmnCode = "ZWJBID1P"; // Mã định danh merchant kết nối (Terminal Id)
$vnp_HashSecret = "1M7ORN9810FICEZTMCJZJTEQ1FVM0P8N"; // Secret key

// VNPay URLs
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
$apiUrl = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";

// Return URL - Tự động detect môi trường
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Xác định base URL
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    // Development environment
    $base_url = "http://localhost/GitHub/Aurorahotelplaza.com";
} elseif (strpos($host, 'aurorahotelplaza.com') !== false) {
    // Production environment
    $base_url = "https://aurorahotelplaza.com";
} else {
    // Fallback
    $base_url = $protocol . '://' . $host;
}

$vnp_Returnurl = $base_url . "/booking/vnpay_return.php";

// Config input format
// Expire time: 15 minutes
$startTime = date("YmdHis");
$expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));
