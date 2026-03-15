<?php
/**
 * Aurora Hotel Plaza - Refunds Management
 * Entry point for refunds page (Controller)
 */

session_start();
require_once '../config/database.php';
require_once '../helpers/auth-middleware.php';
require_once 'controllers/RefundsController.php';

AuthMiddleware::requireStaff();

$page_title = 'Quản lý hoàn tiền';
$page_subtitle = 'Xử lý yêu cầu hoàn tiền khi hủy đặt phòng';

// Get Data from Controller
$filters = [
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'page' => $_GET['page'] ?? 1
];
$data = getRefundsData($filters);

// Extract data for view
$refunds = $data['refunds'];
$stats = $data['stats'];
$total = $data['total'];
$total_pages = $data['total_pages'];
$page = $data['page'];
$per_page = $data['per_page'];
$status_filter = $data['status_filter'];
$search = $data['search'];

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/refunds.view.php';

// Load Footer
include 'includes/admin-footer.php';
