<?php
/**
 * Aurora Hotel Plaza - Permissions Management
 * Entry point for permissions page (Controller)
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/PermissionsController.php';

// Only admin can manage permissions
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$page_title = 'Quản lý phân quyền';
$page_subtitle = 'Cấu hình quyền truy cập cho Sale và Lễ tân';

// Get Data from Controller
$data = getPermissionsData();

// Extract data for view
$matrix = $data['matrix'];
$modules = $data['modules'];

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/permissions.view.php';

// Load Footer
include 'includes/admin-footer.php';
