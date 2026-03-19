<?php
/**
 * Aurora Hotel Plaza - Rooms Management
 * Entry point for rooms page
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/RoomsController.php';

$page_title = 'Quản lý phòng';
$page_subtitle = 'Danh sách các phòng cụ thể';

// Get Data from Controller
$data = getRoomsData();

// Extract data for view
extract($data);

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/rooms.view.php';

// Load Footer
include 'includes/admin-footer.php';
