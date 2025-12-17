<?php
/**
 * Download QR Code for Booking
 * Tạo và tải QR code cho booking sử dụng PHP QR Code Library
 */

session_start();
require_once '../../config/database.php';
require_once '../../config/phpqrcode/qrlib.php';

// Check authentication - Allow admin, receptionist, sale, and booking owner
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

try {
    $booking_id = $_GET['booking_id'] ?? 0;
    $booking_code = $_GET['code'] ?? '';

    if (!$booking_id && !$booking_code) {
        throw new Exception('Booking ID or code is required');
    }

    $db = getDB();

    // Get booking details - support both booking_id and booking_code
    if ($booking_id) {
        $stmt = $db->prepare("
            SELECT b.*, u.full_name, u.email, rt.type_name, r.room_number
            FROM bookings b
            JOIN users u ON b.user_id = u.user_id
            JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE b.booking_id = :booking_id
        ");
        $stmt->execute([':booking_id' => $booking_id]);
    } else {
        $stmt = $db->prepare("
            SELECT b.*, u.full_name, u.email, rt.type_name, r.room_number
            FROM bookings b
            JOIN users u ON b.user_id = u.user_id
            JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE b.booking_code = :booking_code
        ");
        $stmt->execute([':booking_code' => $booking_code]);
    }
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

    // Generate QR code data - Format dễ đọc
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

    // Create temp file for QR code
    $temp_file = tempnam(sys_get_temp_dir(), 'qr_');

    // Generate QR code
    // Parameters: data, filename, error correction level (L/M/Q/H), size, margin
    QRcode::png($qr_data, $temp_file, QR_ECLEVEL_M, 10, 2);

    // Read the generated QR code
    $image_data = file_get_contents($temp_file);

    // Delete temp file
    @unlink($temp_file);

    if ($image_data === false) {
        throw new Exception('Failed to generate QR code');
    }

    // Return as downloadable file
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="booking-' . $booking['booking_code'] . '-qrcode.png"');
    header('Content-Length: ' . strlen($image_data));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');

    echo $image_data;
    exit;

} catch (Exception $e) {
    // Return error as image
    $image = imagecreate(500, 200);
    $white = imagecolorallocate($image, 255, 255, 255);
    $red = imagecolorallocate($image, 255, 0, 0);

    imagefilledrectangle($image, 0, 0, 500, 200, $white);
    imagestring($image, 5, 50, 90, 'Error: ' . $e->getMessage(), $red);

    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
    exit;
}
