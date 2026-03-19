<?php
/**
 * API: Lấy QR Code theo mã đặt phòng (Public, dùng cho chat widget)
 * GET /api/chat/get-booking-qr.php?code=BKA123
 * GET /api/chat/get-booking-qr.php?id=5
 * Trả về ảnh PNG của QR Code (không cần đăng nhập)
 */

require_once '../../config/database.php';
require_once '../../config/phpqrcode/qrlib.php';

try {
    $db = getDB();

    $booking_code = trim($_GET['code'] ?? '');
    $booking_id = intval($_GET['id'] ?? 0);

    if (empty($booking_code) && $booking_id <= 0) {
        throw new Exception('Cần cung cấp mã đặt phòng (code) hoặc booking_id (id)');
    }

    // Truy vấn booking
    if (!empty($booking_code)) {
        $stmt = $db->prepare("
            SELECT b.booking_id, b.booking_code, b.guest_name, b.check_in_date,
                   b.check_out_date, b.total_amount, b.status, rt.type_name
            FROM bookings b
            JOIN room_types rt ON b.room_type_id = rt.room_type_id
            WHERE b.booking_code = :code
            LIMIT 1
        ");
        $stmt->execute([':code' => $booking_code]);
    } else {
        $stmt = $db->prepare("
            SELECT b.booking_id, b.booking_code, b.guest_name, b.check_in_date,
                   b.check_out_date, b.total_amount, b.status, rt.type_name
            FROM bookings b
            JOIN room_types rt ON b.room_type_id = rt.room_type_id
            WHERE b.booking_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $booking_id]);
    }

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Không tìm thấy đặt phòng');
    }

    // Tạo nội dung QR
    $qr_data = "AURORA HOTEL PLAZA\n";
    $qr_data .= "Booking: {$booking['booking_code']}\n";
    $qr_data .= "Guest: {$booking['guest_name']}\n";
    $qr_data .= "Room: {$booking['type_name']}\n";
    $qr_data .= "Check-in: " . date('m/d/Y', strtotime($booking['check_in_date'])) . "\n";
    $qr_data .= "Check-out: " . date('m/d/Y', strtotime($booking['check_out_date'])) . "\n";
    $qr_data .= "Amount: " . number_format($booking['total_amount']) . " VND\n";
    $qr_data .= "Status: {$booking['status']}";

    // Output ảnh PNG
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600');
    QRcode::png($qr_data, false, QR_ECLEVEL_M, 10, 2);
    exit;

} catch (Exception $e) {
    // Trả ảnh báo lỗi
    $img = imagecreate(300, 100);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $red = imagecolorallocate($img, 200, 50, 50);
    imagefilledrectangle($img, 0, 0, 300, 100, $bg);
    imagestring($img, 3, 10, 40, 'QR Error: ' . substr($e->getMessage(), 0, 35), $red);
    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
    exit;
}
