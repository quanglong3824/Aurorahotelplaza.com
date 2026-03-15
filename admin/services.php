<?php
session_start();
require_once 'controllers/ServicesController.php';

$controller = new ServicesController();
$data = $controller->getData();
extract($data);

$page_title = 'Quản lý dịch vụ';
$page_subtitle = 'Quản lý các dịch vụ khách sạn';

include 'includes/admin-header.php';
include 'views/services.view.php';
include 'includes/admin-footer.php';
