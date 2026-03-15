<?php
/**
 * Aurora Hotel Plaza - Booking Detail Entry Point
 */
session_start();
require_once 'controllers/BookingDetailController.php';
require_once '../helpers/security.php';

$controller = new BookingDetailController();
$data = $controller->getData();

// Extract data for the view
extract($data);

include 'includes/admin-header.php';
include 'views/booking-detail.view.php';
include 'includes/admin-footer.php';
