<?php
/**
 * Aurora Hotel Plaza - AI Activity Logs
 * Entry point for AI logs page
 */

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once 'controllers/AiLogsController.php';

$page_title = 'Nhật ký Hoạt động AI';
$page_subtitle = 'Theo dõi hiệu năng và lỗi của hệ thống Trợ lý ảo';

// Get Data from Controller
$data = getAiLogsData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/ai-logs.view.php';

// Load Footer
include 'includes/admin-footer.php';
