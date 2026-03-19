<?php
session_start();
require_once 'controllers/UsersController.php';

// Check admin role
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$controller = new UsersController();
$data = $controller->getData();
extract($data);

$page_title = 'Quản lý người dùng';
$page_subtitle = 'Quản lý tài khoản nhân viên và admin';

include 'includes/admin-header.php';
include 'views/users.view.php';
include 'includes/admin-footer.php';
