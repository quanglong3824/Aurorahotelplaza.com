<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;
$reason = $_POST['reason'] ?? '';

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Booking ID required']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    // Get current booking
    $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    if ($booking['status'] !== 'confirmed') {
        throw new Exception('Chỉ có thể hủy xác nhận đơn đã xác nhận');
    }

    // Update status back to pending
    $stmt = $db->prepare("
        UPDATE bookings 
        SET status = 'pending', updated_at = NOW()
        WHERE booking_id = ?
    ");
    $stmt->execute([$booking_id]);

    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
        VALUES (?, 'unconfirm_booking', 'booking', ?, ?, ?)
    ");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $desc = "Hủy xác nhận đơn #{$booking['booking_code']}";
    if ($reason) $desc .= " - Lý do: {$reason}";
    $stmt->execute([$_SESSION['user_id'], $booking_id, $desc, $ip]);

    // Send apology email to customer
    if (!empty($booking['guest_email'])) {
        try {
            require_once __DIR__ . '/../../includes/email-templates/booking-unconfirmed.php';
            $emailData = [
                'guest_name' => $booking['guest_name'],
                'booking_code' => $booking['booking_code'],
                'type_name' => $booking['type_name'] ?? 'Phòng đã đặt',
                'check_in_date' => $booking['check_in_date'],
                'check_out_date' => $booking['check_out_date'],
                'total_nights' => $booking['total_nights'],
                'total_amount' => number_format($booking['total_amount'], 0, ',', '.'),
            ];
            
            $subject = "Thông báo quan trọng về đơn đặt phòng #{$booking['booking_code']} - Aurora Hotel Plaza";
            $body = getBookingUnconfirmedEmail($emailData);
            
            require_once __DIR__ . '/../../helpers/mailer.php';
            $mailer = new Mailer();
            $mailer->send($booking['guest_email'], $subject, $body);
        } catch (Exception $emailErr) {
            error_log("Unconfirm email error: " . $emailErr->getMessage());
            // Don't fail the whole operation if email fails
        }
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Đã hủy xác nhận và gửi email thông báo cho khách hàng'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Unconfirm booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
