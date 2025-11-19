<?php
/**
 * Get QR Code Image for Display
 * Tạo QR code để hiển thị trên trang (không download)
 */

session_start();
require_once '../../config/database.php';
require_once '../../config/phpqrcode/qrlib.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

try {
    $booking_id = $_GET['booking_id'] ?? 0;
    
    if (!$booking_id) {
        throw new Exception('Booking ID is required');
    }
    
    $db = getDB();
    
    // Get booking details
    $stmt = $db->prepare("
        SELECT b.*, u.full_name, u.email, rt.type_name, r.room_number
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_id = :booking_id
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Check permission
    $is_staff = in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale']);
    $is_owner = $booking['user_id'] == $_SESSION['user_id'];
    
    if (!$is_staff && !$is_owner) {
        throw new Exception('Unauthorized access');
    }
    
    // Generate QR code data
    $qr_data = "AURORA HOTEL PLAZA\n";
    $qr_data .= "Booking: {$booking['booking_code']}\n";
    $qr_data .= "Guest: {$booking['guest_name']}\n";
    $qr_data .= "Room: {$booking['type_name']}\n";
    if ($booking['room_number']) {
        $qr_data .= "Room No: {$booking['room_number']}\n";
    }
    $qr_data .= "Check-in: " . date('d/m/Y', strtotime($booking['check_in_date'])) . "\n";
    $qr_data .= "Check-out: " . date('d/m/Y', strtotime($booking['check_out_date'])) . "\n";
    $qr_data .= "Amount: " . number_format($booking['total_amount']) . " VND\n";
    $qr_data .= "Status: {$booking['status']}";
    
    // Generate QR code directly to output
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600'); // Cache 1 hour
    
    // Output QR code directly
    QRcode::png($qr_data, false, QR_ECLEVEL_M, 10, 2);
    exit;
    
} catch (Exception $e) {
    // Return error as image
    $image = imagecreate(400, 200);
    $white = imagecolorallocate($image, 255, 255, 255);
    $red = imagecolorallocate($image, 255, 0, 0);
    
    imagefilledrectangle($image, 0, 0, 400, 200, $white);
    
    $error_text = 'Error: ' . substr($e->getMessage(), 0, 50);
    imagestring($image, 5, 20, 90, $error_text, $red);
    
    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
    exit;
}
