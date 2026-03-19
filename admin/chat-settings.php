<?php
session_start();
require_once '../config/database.php';
require_once 'controllers/ChatSettingsController.php';

$page_title = 'Cài đặt Chat';
$page_subtitle = 'Cấu hình hệ thống chat & quản lý mẫu trả lời nhanh';
$current_page = 'chat-settings';

$data = getChatSettingsData();
extract($data);

include 'includes/admin-header.php';
include 'views/chat-settings.view.php';
include 'includes/admin-footer.php';
