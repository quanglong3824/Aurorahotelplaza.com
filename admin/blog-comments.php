<?php
/**
 * Aurora Hotel Plaza - Blog Comments Management
 * Entry point for blog comments page
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/BlogCommentsController.php';

// Auth check
// if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$page_title = 'Quản lý bình luận';
$page_subtitle = 'Duyệt và quản lý bình luận từ khách hàng';

// Get Data from Controller
$data = getBlogCommentsData();
$comments = $data['comments'];
$status_filter = $data['status_filter'];
$search = $data['search'];
$post_filter = $data['post_filter'];

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/blog-comments.view.php';

// Load Footer
include 'includes/admin-footer.php';
