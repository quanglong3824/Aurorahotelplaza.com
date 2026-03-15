<?php
session_start();
require_once 'controllers/PromotionsController.php';

$controller = new PromotionsController();
$data = $controller->getData();
extract($data);

$page_title = 'Quản lý khuyến mãi';
$page_subtitle = 'Quản lý mã giảm giá và chương trình khuyến mãi';

include 'includes/admin-header.php';
include 'views/promotions.view.php';
include 'includes/admin-footer.php';
