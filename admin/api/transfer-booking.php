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
$transfer_to = $data['transfer_to'] ?? null;
$reason = $data['reason'] ?? '';

if (!$booking_id || !$transfer_to) {
    echo json_encode(['success' => false, 'message' => 'Booking ID and transfer target required']);
    exit;
}

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? 'Staff';

    // Check booking exists
    $stmt = $db->prepare("SELECT booking_id, booking_code FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    // Check current user is the assigned person or is admin
    $stmt = $db->prepare("
        SELECT ba.* FROM booking_assignments ba
        WHERE ba.booking_id = ? AND ba.status = 'active'
    ");
    $stmt->execute([$booking_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'No active assignment found']);
        exit;
    }

    // Only assigned user or admin can transfer
    if ($assignment['assigned_to'] != $user_id && $_SESSION['user_role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to transfer this booking']);
        exit;
    }

    // Check target user exists and is active staff
    $stmt = $db->prepare("
        SELECT user_id, full_name FROM users
        WHERE user_id = ? AND user_role IN ('admin', 'sale', 'receptionist') AND status = 'active'
    ");
    $stmt->execute([$transfer_to]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target_user) {
        echo json_encode(['success' => false, 'message' => 'Target user not found or inactive']);
        exit;
    }

    $db->beginTransaction();

    // Update assignment
    $stmt = $db->prepare("
        UPDATE booking_assignments
        SET assigned_to = ?, assigned_by = ?, transfer_reason = ?, transferred_at = NOW()
        WHERE booking_id = ? AND status = 'active'
    ");
    $stmt->execute([$transfer_to, $user_id, $reason, $booking_id]);

    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
        VALUES (?, 'transfer_booking', 'booking', ?, ?, ?)
    ");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $desc = "Chuyển đơn #{$booking['booking_code']} từ {$user_name} sang {$target_user['full_name']}";
    if ($reason) $desc .= " - Lý do: {$reason}";
    $stmt->execute([$user_id, $booking_id, $desc, $ip]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => "Đã chuyển đơn cho {$target_user['full_name']}",
        'new_assigned_name' => $target_user['full_name']
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Transfer booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
