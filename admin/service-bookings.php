<?php
/**
 * Aurora Hotel Plaza - Service Bookings Management
 * Entry point for service bookings page
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/ServiceBookingsController.php';

$page_title = 'Đơn dịch vụ';
$page_subtitle = 'Quản lý đặt dịch vụ của khách hàng';

// Get Data from Controller
$data = getServiceBookingsData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/service-bookings.view.php';

// Load Footer
include 'includes/admin-footer.php';
