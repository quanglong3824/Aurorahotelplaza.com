<?php
session_start();
require_once '../config/database.php';
require_once 'controllers/NotificationsController.php';

$page_title = 'Thông báo';
$page_subtitle = 'Quản lý thông báo hệ thống';

$data = getNotificationsData();
extract($data);

include 'includes/admin-header.php';
include 'views/notifications.view.php';
include 'includes/admin-footer.php';
