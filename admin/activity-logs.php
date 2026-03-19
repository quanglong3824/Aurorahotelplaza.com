<?php
/**
 * Aurora Hotel Plaza - Activity Logs
 * Entry point for activity logs page
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/ActivityLogsController.php';

$page_title = 'Nhật ký hoạt động';
$page_subtitle = 'Theo dõi mọi thao tác trên hệ thống';

// Get Data from Controller
$data = getActivityLogsData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/activity-logs.view.php';

// Load Footer
include 'includes/admin-footer.php';
