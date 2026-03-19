<?php
/**
 * Aurora Hotel Plaza - Banners Management
 * Entry point for banners page
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/BannersController.php';

// Auth check can be added here
// if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$page_title = 'Quản lý Banner';
$page_subtitle = 'Cấu hình banner hiển thị trên trang chủ';

// Get Data from Controller
$data = getBannersData();
$banners = $data['banners'];

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/banners.view.php';

// Load Footer
include 'includes/admin-footer.php';
