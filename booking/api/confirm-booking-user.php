<?php
/**
 * User Booking Confirmation API
 * Allows users to confirm their own bookings and receive confirmation email
 */

session_start();
ob_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../models/Booking.php';
require_once '../../includes/email-helper.php';

try {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);

    $booking_code = $data['booking_code'] ?? null;

    if (!$booking_code) {
        throw new Exception('Mã đặt phòng không hợp lệ');
    }

    $db = getDB();
    $bookingModel = new Booking($db);

    // Get booking details by booking code
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name, rt.description as room_description,
               u.email as guest_email, u.full_name as guest_name
        FROM bookings b
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_code = ?
    ");
    $stmt->execute([$booking_code]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Không tìm thấy đơn đặt phòng');
    }

    // Check if booking is in pending status
    if ($booking['status'] !== 'pending') {
        if ($booking['status'] === 'confirmed') {
            throw new Exception('Đặt phòng này đã được xác nhận trước đó');
        } else {
            throw new Exception('Không thể xác nhận đặt phòng với trạng thái hiện tại');
        }
    }

    // Calculate total nights
    $checkIn = new DateTime($booking['check_in_date']);
    $checkOut = new DateTime($booking['check_out_date']);
    $totalNights = $checkIn->diff($checkOut)->days;

    $db->beginTransaction();

    try {
        // Update booking status to confirmed
        $stmt = $db->prepare("
            UPDATE bookings 
            SET status = 'confirmed',
                updated_at = NOW()
            WHERE booking_code = ?
        ");
        $stmt->execute([$booking_code]);

        // Add to booking history
        $bookingModel->addHistory(
            $booking['booking_id'],
            'pending',
            'confirmed',
            $booking['user_id'],
            'Booking confirmed by user'
        );

        $db->commit();

        // Prepare booking data for email
        $booking['total_nights'] = $totalNights;
        $emailMessage = '';

        // ADDED: Wrap email sending in a separate try-catch block catching Throwable to prevent API crash
        try {
            if (function_exists('sendBookingStatusUpdateEmail')) {
                $email_result = sendBookingStatusUpdateEmail($booking, 'pending', 'confirmed');

                if ($email_result['success']) {
                    // Email sent successfully
                    $emailMessage = ' Email xác nhận đã được gửi đến địa chỉ email của bạn.';
                } else {
                    // Email sending returned false status
                    error_log("Failed to send user confirmation email: " . $email_result['message']);
                    // We do NOT show error to user as booking is confirmed
                }
            }
        } catch (Throwable $emailError) {
            // Catch ANY error during email sending (including Fatal Errors)
            error_log("CRITICAL EMAIL ERROR: " . $emailError->getMessage());
            // Do not fail the request, just log it
        }

        // Return success response independently of email result
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Đặt phòng đã được xác nhận thành công!' . $emailMessage,
            'booking' => [
                'booking_code' => $booking['booking_code'],
                'status' => 'confirmed'
            ]
        ]);

    } catch (Throwable $e) {
        // Catch any DB or Logic errors
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw new Exception($e->getMessage());
    }

} catch (Throwable $e) {
    // Catch-all for any unhandled errors
    http_response_code(200); // Return 200 but with success: false to let client handle the error message gracefully
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>