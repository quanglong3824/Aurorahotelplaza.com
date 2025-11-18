<?php
/**
 * API Endpoint: Cancel Booking
 * Allows users to cancel their bookings
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện thao tác này'
    ]);
    exit;
}

require_once '../../config/database.php';
require_once '../../models/Booking.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin đặt phòng'
    ]);
    exit;
}

$booking_id = intval($data['booking_id']);
$reason = $data['reason'] ?? null;

try {
    $db = getDB();
    $bookingModel = new Booking($db);
    
    // Cancel the booking
    $result = $bookingModel->cancelBooking($booking_id, $_SESSION['user_id'], $reason);
    
    if ($result['success']) {
        http_response_code(200);
        
        // Log activity
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
            VALUES (?, 'cancel_booking', 'booking', ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $booking_id,
            'Hủy đặt phòng: ' . ($reason ?? 'Không có lý do'),
            $_SERVER['REMOTE_ADDR']
        ]);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Cancel booking API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi hủy đặt phòng. Vui lòng thử lại sau.'
    ]);
}
?>
