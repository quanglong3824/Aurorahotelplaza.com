<?php
/**
 * Aurora Hotel Plaza - Blog Management
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/BlogController.php';

$page_title = 'Quản lý Blog';
$page_subtitle = 'Bài viết và tin tức';

// Get Data from Controller
$controller = new BlogController();
$data = $controller->getData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/blog.view.php';

// Load Footer
include 'includes/admin-footer.php';
