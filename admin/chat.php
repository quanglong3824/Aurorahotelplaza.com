<?php
session_start();
require_once '../config/database.php';
require_once 'controllers/ChatController.php';

$page_title = 'Tin nhắn';
$page_subtitle = 'Hỗ trợ & chat trực tuyến với khách hàng';
$current_page = 'chat';

$data = getChatData();
extract($data);

include 'includes/admin-header.php';
include 'views/chat.view.php';
include 'includes/admin-footer.php';
