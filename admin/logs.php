<?php
session_start();
require_once '../config/database.php';
require_once 'controllers/LogsController.php';

$page_title = 'Nhật ký hoạt động';
$page_subtitle = 'Theo dõi các hoạt động trong hệ thống';

$data = getLogsData();
extract($data);

include 'includes/admin-header.php';
include 'views/logs.view.php';
include 'includes/admin-footer.php';
