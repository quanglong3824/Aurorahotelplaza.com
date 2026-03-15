<?php
/**
 * Aurora Hotel Plaza - FAQs Management
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/FaqsController.php';

$page_title = 'Quản lý FAQs';
$page_subtitle = 'Câu hỏi thường gặp';

// Get Data from Controller
$controller = new FaqsController();
$data = $controller->getData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/faqs.view.php';

// Load Footer
include 'includes/admin-footer.php';
