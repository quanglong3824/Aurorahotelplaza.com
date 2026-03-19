<?php
session_start();
require_once 'controllers/SettingsController.php';

// Check admin role
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$controller = new SettingsController();
$data = $controller->getData();
extract($data);

$page_title = 'Cài đặt hệ thống';
$page_subtitle = 'Cấu hình và quản lý hệ thống';

include 'includes/admin-header.php';
include 'views/settings.view.php';
include 'includes/admin-footer.php';
