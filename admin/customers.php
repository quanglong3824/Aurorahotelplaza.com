<?php
/**
 * Aurora Hotel Plaza - Customers Management
 * Entry point for customers page
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/CustomersController.php';

$page_title = 'Quản lý khách hàng';
$page_subtitle = 'Danh sách khách hàng và thông tin';

// Get Data from Controller
$data = getCustomersData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/customers.view.php';

// Load Footer
include 'includes/admin-footer.php';
