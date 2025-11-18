<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config/database.php';
require_once '../../models/Booking.php';
require_once '../../includes/qrcode-helper.php';

header('Content-Type: application/json');

try {
    $booking_code = $_GET['code'] ?? '';
    
    if (empty($booking_code)) {
        throw new Exception('Booking code is required');
    }
    
    $db = getDB();
    $bookingModel = new Booking($db);
    
    // Get booking details
    $booking = $bookingModel->getByCode($booking_code);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Check if booking belongs to user
    if ($booking['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized access to this booking');
    }
    
    // Check if booking is confirmed
    if ($booking['status'] !== 'confirmed' && $booking['status'] !== 'checked_in') {
        throw new Exception('QR code is only available for confirmed bookings');
    }
    
    // Generate QR code
    $qr_data = generateBookingQRData($booking);
    $qr_url = generateQRCodeImage($qr_data, 500);
    
    // Fetch QR code image
    $image_data = @file_get_contents($qr_url);
    
    if ($image_data === false) {
        throw new Exception('Failed to generate QR code');
    }
    
    // Return as downloadable file
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="booking-' . $booking_code . '-qrcode.png"');
    header('Content-Length: ' . strlen($image_data));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    echo $image_data;
    exit;
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
