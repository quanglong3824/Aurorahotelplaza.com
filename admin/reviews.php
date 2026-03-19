<?php
/**
 * Aurora Hotel Plaza - Reviews Management
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/ReviewsController.php';

$page_title = 'Quản lý đánh giá';
$page_subtitle = 'Quản lý đánh giá từ khách hàng';

// Get Data from Controller
$controller = new ReviewsController();
$data = $controller->getData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/reviews.view.php';

// Load Footer
include 'includes/admin-footer.php';
