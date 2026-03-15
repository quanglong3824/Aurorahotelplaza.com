<?php
/**
 * Aurora Hotel Plaza - AI Stats
 * Entry point for AI stats page
 */

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once 'controllers/AiStatsController.php';

$page_title = 'Thống Kê Lưu Lượng AI (API Quota)';
$page_subtitle = 'Giám sát và phân tích lưu lượng sử dụng API Gemini của Khách Hàng và Quản Trị Viên';

// Get Data from Controller
$data = getAiStatsData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/ai-stats.view.php';

// Load Footer
include 'includes/admin-footer.php';
