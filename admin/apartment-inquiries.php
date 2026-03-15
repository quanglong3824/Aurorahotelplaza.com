<?php
/**
 * Aurora Hotel Plaza - Apartment Inquiries
 * Entry point for apartment inquiries management (MVC)
 */

session_start();
require_once '../config/database.php';
require_once '../helpers/auth-middleware.php';
require_once 'controllers/ApartmentInquiriesController.php';

// Check access
AuthMiddleware::requireStaff();

$current_page = 'apartment-inquiries';
$page_title = 'Quản lý yêu cầu căn hộ';
$page_subtitle = 'Danh sách yêu cầu tư vấn căn hộ từ khách hàng';

// Get Data from Controller
$data = getApartmentInquiriesData();

// Extract data for view
$inquiries = $data['inquiries'];
$total_inquiries = $data['total_inquiries'];
$total_pages = $data['total_pages'];
$stats = $data['stats'];
$apartments = $data['apartments'];
$duration_labels = $data['duration_labels'];
$status_labels = $data['status_labels'];
$status_filter = $data['status_filter'];
$apartment_filter = $data['apartment_filter'];
$duration_filter = $data['duration_filter'];
$search = $data['search'];
$page = $data['page'];

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/apartment-inquiries.view.php';

// Load Footer
include 'includes/admin-footer.php';
