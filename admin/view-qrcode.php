<?php
/**
 * Aurora Hotel Plaza - View QR Code
 * Entry point for view-qrcode page
 */

session_start();
require_once '../config/database.php';
require_once '../helpers/auth-middleware.php';
require_once 'controllers/ViewQrCodeController.php';

AuthMiddleware::requireStaff();

// Get Data from Controller
$data = getViewQrCodeData();

if (!$data) {
    header('Location: bookings.php');
    exit;
}

// Extract data for view
extract($data);

$page_title = 'QR Code - ' . $booking['booking_code'];
$page_subtitle = 'Mã QR cho đặt phòng';

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/view-qrcode.view.php';

// Load Footer
include 'includes/admin-footer.php';
