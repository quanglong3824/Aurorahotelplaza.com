<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];

    // Get all pending bookings with assignment info
    $stmt = $db->prepare("
        SELECT b.booking_id, b.booking_code, b.guest_name, b.guest_phone, b.guest_email,
               b.check_in_date, b.check_out_date, b.total_nights, b.total_amount, b.created_at,
               b.status, b.booking_type, rt.type_name, r.room_number,
               ba.assigned_to, ba.accepted_at, ba.status as assignment_status,
               u.full_name as assigned_name, u.user_role as assigned_role
        FROM bookings b
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN booking_assignments ba ON b.booking_id = ba.booking_id AND ba.status = 'active'
        LEFT JOIN users u ON ba.assigned_to = u.user_id
        WHERE b.status = 'pending'
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get online staff list
    $stmt = $db->prepare("
        SELECT u.user_id, u.full_name, u.user_role,
               MAX(s.last_seen) as last_seen
        FROM users u
        LEFT JOIN staff_online s ON u.user_id = s.user_id
        WHERE u.user_role IN ('admin', 'sale', 'receptionist')
          AND u.status = 'active'
          AND u.user_id != ?
        GROUP BY u.user_id
        ORDER BY u.full_name
    ");
    $stmt->execute([$user_id]);
    $online_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unassigned pending bookings
    $unassigned_count = 0;
    foreach ($bookings as $b) {
        if (!$b['assigned_to']) $unassigned_count++;
    }

    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'online_staff' => $online_staff,
        'unassigned_count' => $unassigned_count,
        'current_user_id' => $user_id
    ]);

} catch (Exception $e) {
    error_log("Get pending bookings error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
