<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;
$new_status = $_POST['status'] ?? null;
$reason = $_POST['reason'] ?? '';

if (!$booking_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$allowed_statuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Get current booking
    $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = :booking_id");
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    $old_status = $booking['status'];
    
    // Update booking status
    $update_data = [
        'status' => $new_status,
        'booking_id' => $booking_id
    ];
    
    $update_fields = ['status = :status'];
    
    // Add specific fields based on status
    if ($new_status === 'checked_in') {
        $update_fields[] = 'checked_in_at = NOW()';
        $update_fields[] = 'checked_in_by = :user_id';
        $update_data['user_id'] = $_SESSION['user_id'];
    } elseif ($new_status === 'checked_out') {
        $update_fields[] = 'checked_out_at = NOW()';
    } elseif ($new_status === 'cancelled') {
        $update_fields[] = 'cancelled_at = NOW()';
        $update_fields[] = 'cancelled_by = :user_id';
        $update_fields[] = 'cancellation_reason = :reason';
        $update_data['user_id'] = $_SESSION['user_id'];
        $update_data['reason'] = $reason;
        
        // Free up the room if assigned
        if ($booking['room_id']) {
            $stmt = $db->prepare("UPDATE rooms SET status = 'available' WHERE room_id = :room_id");
            $stmt->execute([':room_id' => $booking['room_id']]);
        }
    }
    
    $sql = "UPDATE bookings SET " . implode(', ', $update_fields) . " WHERE booking_id = :booking_id";
    $stmt = $db->prepare($sql);
    $stmt->execute($update_data);
    
    // Log status change in booking_history
    $stmt = $db->prepare("
        INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes, created_at)
        VALUES (:booking_id, :old_status, :new_status, :changed_by, :notes, NOW())
    ");
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':old_status' => $old_status,
        ':new_status' => $new_status,
        ':changed_by' => $_SESSION['user_id'],
        ':notes' => $reason ?: "Status changed from $old_status to $new_status"
    ]);
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at)
        VALUES (:user_id, :action, 'booking', :entity_id, :description, :ip_address, NOW())
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':action' => 'update_booking_status',
        ':entity_id' => $booking_id,
        ':description' => "Changed booking {$booking['booking_code']} status from $old_status to $new_status",
        ':ip_address' => $_SERVER['REMOTE_ADDR']
    ]);
    
    $db->commit();

    // Send confirmation emails when booking is confirmed
    if ($new_status === 'confirmed') {
        try {
            require_once __DIR__ . '/../../helpers/mailer.php';
            $mailer = getMailer();

            // Get full booking details for email
            $stmt = $db->prepare("
                SELECT b.*, rt.type_name, rt.category, rt.bed_type, rt.size_sqm,
                       r.room_number, r.floor, r.building
                FROM bookings b
                JOIN room_types rt ON b.room_type_id = rt.room_type_id
                LEFT JOIN rooms r ON b.room_id = r.room_id
                WHERE b.booking_id = :booking_id
            ");
            $stmt->execute([':booking_id' => $booking_id]);
            $fullBooking = $stmt->fetch(PDO::FETCH_ASSOC);

            $nights = $fullBooking['total_nights'];
            $perNight = $nights > 0 ? $fullBooking['room_price'] / $nights : $fullBooking['room_price'];

            $emailData = [
                'booking_id' => $booking_id,
                'booking_code' => $fullBooking['booking_code'],
                'guest_name' => $fullBooking['guest_name'],
                'guest_email' => $fullBooking['guest_email'],
                'guest_phone' => $fullBooking['guest_phone'],
                'type_name' => $fullBooking['type_name'],
                'category' => $fullBooking['category'],
                'bed_type' => $fullBooking['bed_type'],
                'room_number' => $fullBooking['room_number'],
                'floor' => $fullBooking['floor'],
                'building' => $fullBooking['building'],
                'check_in_date' => $fullBooking['check_in_date'],
                'check_out_date' => $fullBooking['check_out_date'],
                'total_nights' => $nights,
                'num_adults' => $fullBooking['num_adults'],
                'num_children' => $fullBooking['num_children'],
                'num_rooms' => $fullBooking['num_rooms'],
                'per_night' => number_format($perNight, 0, ',', '.'),
                'total_amount' => number_format($fullBooking['total_amount'], 0, ',', '.'),
                'special_requests' => $fullBooking['special_requests'],
                'created_at' => $fullBooking['created_at'],
                'booking_type' => $fullBooking['booking_type'] ?? 'instant'
            ];

            // Send to customer
            if (!empty($fullBooking['guest_email'])) {
                $customerSubject = "Xác nhận đặt phòng #{$fullBooking['booking_code']} - Aurora Hotel Plaza";
                require_once __DIR__ . '/../../includes/email-templates/booking-confirmed-customer.php';
                $customerBody = getBookingConfirmedCustomerEmail($emailData);
                $mailer->send($fullBooking['guest_email'], $customerSubject, $customerBody);
            }

            // Send to staff
            $staffEmail = defined('HOTEL_RECEIVE_EMAIL') ? HOTEL_RECEIVE_EMAIL : 'info@aurorahotelplaza.com';
            $staffSubject = "[ĐÃ XÁC NHẬN #{$fullBooking['booking_code']}] {$fullBooking['type_name']} - Khách: {$fullBooking['guest_name']}";
            require_once __DIR__ . '/../../includes/email-templates/booking-confirmed-staff.php';
            $staffBody = getBookingConfirmedStaffEmail($emailData);
            $mailer->send($staffEmail, $staffSubject, $staffBody);

        } catch (Exception $emailErr) {
            error_log("Confirmation email error: " . $emailErr->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật trạng thái thành công',
        'data' => [
            'booking_id' => $booking_id,
            'old_status' => $old_status,
            'new_status' => $new_status
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log("Update booking status error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
