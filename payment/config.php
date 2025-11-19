<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * VNPay Configuration
 * Updated: 2025-11-19
 */

// Load environment helper
require_once __DIR__ . '/../config/environment.php';

// VNPay Credentials
$vnp_TmnCode = "ZWJBID1P"; // Mã định danh merchant kết nối (Terminal Id)
$vnp_HashSecret = "1M7ORN9810FICEZTMCJZJTEQ1FVM0P8N"; // Secret key

// VNPay URLs
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
$apiUrl = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";

// Return URL - Sử dụng hàm helper tự động detect môi trường
$vnp_Returnurl = getBaseUrl() . "/booking/vnpay_return.php";

// Config input format
// Expire time: 15 minutes
$startTime = date("YmdHis");
$expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));
