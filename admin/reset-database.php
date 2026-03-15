<?php
/**
 * Aurora Hotel Plaza - Reset Database
 * Entry point for reset database page (Controller)
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/ResetDatabaseController.php';

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Reset Database';
$page_subtitle = 'Xóa toàn bộ dữ liệu (giữ lại admin)';

// Handle Data & Actions
$result = handleResetDatabase();
$message = $result['message'];
$error = $result['error'];

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/reset-database.view.php';

// Load Footer
include 'includes/admin-footer.php';
