<?php
/**
 * Aurora Hotel Plaza - Bookings Management
 * Entry point for bookings page
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/BookingsController.php';

$page_title = 'Quản lý đặt phòng';
$page_subtitle = 'Danh sách và quản lý các đơn đặt phòng';

// Get Data from Controller
$data = getBookingsData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/bookings.view.php';

// Load Footer
include 'includes/admin-footer.php';
