<?php
session_start();
require_once 'controllers/ReportsController.php';

$controller = new ReportsController();
$data = $controller->getData();
extract($data);

$page_title = 'Báo cáo & Thống kê (Reports & Statistics)';
$page_subtitle = 'Phân tích dữ liệu và báo cáo (Data Analysis & Reports)';

include 'includes/admin-header.php';
include 'views/reports.view.php';
include 'includes/admin-footer.php';
