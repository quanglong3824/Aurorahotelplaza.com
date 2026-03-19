<?php
session_start();
require_once '../config/database.php';
require_once 'controllers/MembersController.php';

$page_title = 'Danh sách thành viên';
$page_subtitle = 'Quản lý thành viên và điểm thưởng';

$data = getMembersData();
extract($data);

include 'includes/admin-header.php';
include 'views/members.view.php';
include 'includes/admin-footer.php';
