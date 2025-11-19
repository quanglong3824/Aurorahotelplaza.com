<?php
/**
 * API: Cancel Booking with Refund
 * Hủy đặt phòng với chính sách hoàn tiền
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/refund-policy.php';

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

try {
    // Validate input
    $booking_id = filter_var($_POST['booking_id'] ?? 0, FILTER_VALIDATE_INT);
    $cancellation_reason = trim($_POST['reason'] ?? '');
    
    if (!$booking_id || $booking_id <= 0) {
        throw new Exception('Booking ID không hợp lệ');
    }
    
    // Sanitize reason
    if (strlen($cancellation_reason) > 500) {
        $cancellation_reason = substr($cancellation_reason, 0, 500);
    }
    
    $db = getDB();
    
    // Get booking details
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name
        FROM bookings b
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        WHERE b.booking_id = :booking_id AND b.user_id = :user_id
    ");
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':user_id' => $_SESSION['user_id']
    ]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Không tìm thấy đặt phòng hoặc bạn không có quyền hủy');
    }
    
    // Check if booking can be cancelled
    if (in_array($booking['status'], ['cancelled', 'checked_out', 'no_show'])) {
        throw new Exception('Không thể hủy đặt phòng này');
    }
    
    if ($booking['status'] === 'checked_in') {
        throw new Exception('Không thể hủy sau khi đã nhận phòng');
    }
    
    // Calculate refund amount
    $refund_info = calculateRefundAmount($booking);
    
    if (!$refund_info['can_cancel']) {
        throw new Exception($refund_info['policy_message']);
    }
    
    // Process cancellation and refund
    $db->beginTransaction();
    
    try {
        // Update booking status
        $stmt = $db->prepare("
            UPDATE bookings 
            SET status = 'cancelled',
                cancelled_at = NOW(),
                cancellation_reason = ?
            WHERE booking_id = ?
        ");
        $stmt->execute([
            $cancellation_reason ?: $refund_info['policy_message'],
            $booking_id
        ]);
        
        // Create refund record if refund amount > 0
        if ($refund_info['refund_amount'] > 0) {
            $stmt = $db->prepare("
                INSERT INTO refunds (
                    booking_id,
                    refund_amount,
                    refund_percentage,
                    processing_fee,
                    refund_reason,
                    refund_status,
                    requested_by,
                    requested_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([
                $booking_id,
                $refund_info['refund_amount'],
                $refund_info['refund_percentage'],
                $refund_info['processing_fee'],
                $cancellation_reason ?: $refund_info['policy_message'],
                $_SESSION['user_id']
            ]);
            
            $refund_id = $db->lastInsertId();
        }
        
        // Add to booking history
        $stmt = $db->prepare("
            INSERT INTO booking_history (
                booking_id,
                status,
                changed_by,
                notes,
                created_at
            ) VALUES (?, 'cancelled', ?, ?, NOW())
        ");
        $stmt->execute([
            $booking_id,
            $_SESSION['user_id'],
            'Khách hàng hủy đặt phòng - ' . $refund_info['policy_message']
        ]);
        
        // If room was assigned, make it available again
        if (!empty($booking['room_id'])) {
            $stmt = $db->prepare("
                UPDATE rooms 
                SET status = 'available' 
                WHERE room_id = ? AND status IN ('reserved', 'occupied')
            ");
            $stmt->execute([$booking['room_id']]);
        }
        
        // Send email notification (optional - add later)
        // sendCancellationEmail($booking, $refund_info);
        
        $db->commit();
        
        // Prepare response
        $response = [
            'success' => true,
            'message' => 'Đã hủy đặt phòng thành công',
            'refund_info' => [
                'refund_amount' => $refund_info['refund_amount'],
                'refund_percentage' => $refund_info['refund_percentage'],
                'processing_fee' => $refund_info['processing_fee'],
                'policy_message' => $refund_info['policy_message']
            ]
        ];
        
        if ($refund_info['refund_amount'] > 0) {
            $response['message'] .= '. Số tiền hoàn lại: ' . number_format($refund_info['refund_amount']) . ' VNĐ';
            $response['refund_id'] = $refund_id ?? null;
        } else {
            $response['message'] .= '. Không có tiền hoàn lại theo chính sách hủy phòng.';
        }
        
        echo json_encode($response);
        
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
