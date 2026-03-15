<?php
/**
 * Aurora Hotel Plaza - Blog Post Form
 * Entry point for creating/editing blog posts
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/BlogFormController.php';

// Auth check
// if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

// Get Data from Controller
$data = getBlogFormData();
$post = $data['post'];
$categories = $data['categories'];
$is_edit = $data['is_edit'];
$db_error = $data['db_error'];
$uploaded_images = $data['uploaded_images'];

$page_title = $is_edit ? 'Chỉnh sửa bài viết' : 'Viết bài mới';
$page_subtitle = $is_edit ? 'Cập nhật nội dung cho bài viết hiện có' : 'Tạo bài viết mới cho blog khách sạn';

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/blog-form.view.php';

// Load Footer
include 'includes/admin-footer.php';
