<?php
/**
 * Aurora Hotel Plaza - Gallery Management
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/GalleryController.php';

$page_title = 'Quản lý thư viện ảnh';
$page_subtitle = 'Quản lý hình ảnh khách sạn';

// Get Data from Controller
$controller = new GalleryController();
$data = $controller->getData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/gallery.view.php';

// Load Footer
include 'includes/admin-footer.php';
