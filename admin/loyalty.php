<?php
session_start();
require_once '../config/database.php';
require_once 'controllers/LoyaltyController.php';

$page_title = 'Chương trình thành viên';
$page_subtitle = 'Quản lý hạng thành viên và điểm thưởng';

$data = getLoyaltyData();
extract($data);

include 'includes/admin-header.php';
include 'views/loyalty.view.php';
include 'includes/admin-footer.php';
