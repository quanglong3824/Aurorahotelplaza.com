<?php
/**
 * Aurora Hotel Plaza - Calendar
 * Entry point for calendar page
 */

session_start();
require_once '../config/database.php';
require_once 'controllers/CalendarController.php';

// Get Data from Controller
$data = getCalendarData();

// Extract data for view
extract($data);

$page_title = 'Lịch đặt phòng';
$page_subtitle = 'Xem lịch đặt phòng theo tháng';

// Load Header
include 'includes/admin-header.php';

// Load View
include 'views/calendar.view.php';

// Load Footer
include 'includes/admin-footer.php';
