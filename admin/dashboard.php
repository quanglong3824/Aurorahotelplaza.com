<?php
/**
 * Aurora Hotel Plaza - Admin Dashboard
 * Entry point for the dashboard page (Controller)
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/DashboardController.php';

// Check authentication if needed (should be handled by a middleware)
// if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$page_title = 'Dashboard';
$page_subtitle = 'Tổng quan hệ thống khách sạn';

// Get Data from Controller
$data = getDashboardData();

// Extract data for view
$stats = $data['stats'];
$revenue_growth = $data['revenue_growth'];
$occupancy_rate = $data['occupancy_rate'];
$recent_activities = $data['recent_activities'];
$top_room_types = $data['top_room_types'];
$upcoming_checkins = $data['upcoming_checkins'];

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/dashboard.view.php';

// Load Footer
include 'includes/admin-footer.php';
