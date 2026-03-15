<?php
/**
 * Aurora Hotel Plaza - Service & Packages Management
 * Entry point for service packages page
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/ServicePackagesController.php';

$page_title = 'Quản lý dịch vụ & gói';
$page_subtitle = 'Quản lý các dịch vụ lớn và gói dịch vụ chi tiết';

// Get Data from Controller
$data = getServicePackagesData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/service-packages.view.php';

// Load Footer
include 'includes/admin-footer.php';
