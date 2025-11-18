<?php
session_start();

// Check if user is logged in and is admin/staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'staff'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once '../../models/Booking.php';
require_once '../../includes/email-helper.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $booking_id = $data['booking_id'] ?? null;
    $room_id = $data['room_id'] ?? null; // Optional: assign specific room
    
    if (!$booking_id) {
        throw new Exception('Booking ID is required');
    }
    
    $db = getDB();
    $bookingModel = new Booking($db);
    
    // Get booking details
    $booking = $bookingModel->getById($booking_id);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Check if booking is in pending status
    if ($booking['status'] !== 'pending') {
        throw new Exception('Only pending bookings can be confirmed');
    }
    
    $db->beginTransaction();
    
    try {
        // Update booking status to confirmed
        $stmt = $db->prepare("
            UPDATE bookings 
            SET status = 'confirmed', 
                room_id = COALESCE(?, room_id),
                updated_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->execute([$room_id, $booking_id]);
        
        // Add to booking history
        $bookingModel->addHistory($booking_id, 'pending', 'confirmed', $_SESSION['user_id'], 'Booking confirmed by admin');
        
        // Update room status if room assigned
        if ($room_id) {
            $stmt = $db->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?");
            $stmt->execute([$room_id]);
        }
        
        $db->commit();
        
        // Get updated booking data for email
        $booking = $bookingModel->getById($booking_id);
        
        // Send confirmation email
        try {
            $email_result = sendBookingStatusUpdateEmail($booking, 'pending', 'confirmed');
            
            if ($email_result['success']) {
                error_log("Booking confirmation email sent successfully for: " . $booking['booking_code']);
            } else {
                error_log("Failed to send confirmation email: " . $email_result['message']);
            }
        } catch (Exception $emailError) {
            error_log("Email sending error: " . $emailError->getMessage());
            // Don't fail the confirmation if email fails
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking confirmed successfully',
            'booking' => $booking
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
