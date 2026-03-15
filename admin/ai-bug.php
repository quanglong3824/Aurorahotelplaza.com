<?php
/**
 * Aurora Hotel Plaza - Admin: AI Bug Tracker
 */
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once 'controllers/AiBugController.php';

$page_title = 'AI Bug Tracker';
$page_subtitle = 'Hệ thống phát hiện và phân tích lỗi toàn web bằng AI';

// Get Data from Controller
$data = getAiBugData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/ai-bug.view.php';

// Load Footer
include 'includes/admin-footer.php';
