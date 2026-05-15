<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? null;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Booking ID required']);
    exit;
}

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? 'Staff';

    // Check booking exists and is pending
    $stmt = $db->prepare("SELECT booking_id, status, booking_code FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    if ($booking['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending bookings can be accepted']);
        exit;
    }

    // Check if already assigned to someone else
    $stmt = $db->prepare("
        SELECT ba.*, u.full_name as assigned_name
        FROM booking_assignments ba
        LEFT JOIN users u ON ba.assigned_to = u.user_id
        WHERE ba.booking_id = ? AND ba.status = 'active'
    ");
    $stmt->execute([$booking_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing && $existing['assigned_to'] != $user_id) {
        echo json_encode([
            'success' => false,
            'message' => "Đơn này đã được {$existing['assigned_name']} tiếp nhận",
            'already_assigned' => true,
            'assigned_name' => $existing['assigned_name']
        ]);
        exit;
    }

    $db->beginTransaction();

    if ($existing) {
        // Re-assign to current user (transfer back or re-accept)
        $stmt = $db->prepare("
            UPDATE booking_assignments
            SET assigned_to = ?, assigned_by = ?, accepted_at = NOW(), status = 'active', transfer_reason = NULL
            WHERE booking_id = ?
        ");
        $stmt->execute([$user_id, $user_id, $booking_id]);
    } else {
        // New assignment
        $stmt = $db->prepare("
            INSERT INTO booking_assignments (booking_id, assigned_to, assigned_by, accepted_at, status)
            VALUES (?, ?, ?, NOW(), 'active')
        ");
        $stmt->execute([$booking_id, $user_id, $user_id]);
    }

    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
        VALUES (?, 'accept_booking', 'booking', ?, ?, ?)
    ");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->execute([$user_id, $booking_id, "Tiếp nhận đơn #{$booking['booking_code']}", $ip]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Tiếp nhận đơn thành công',
        'assigned_name' => $user_name
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Accept booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
