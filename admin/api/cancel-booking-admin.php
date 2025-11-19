<?php
/**
 * API: Cancel Booking (Admin)
 * Admin hủy đặt phòng với quyền quyết định hoàn tiền
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/auth-middleware.php';
require_once '../../helpers/refund-policy.php';

header('Content-Type: application/json');

AuthMiddleware::requireStaff();

try {
    $booking_id = $_POST['booking_id'] ?? 0;
    $cancellation_reason = $_POST['reason'] ?? '';
    $force_refund = $_POST['force_refund'] ?? false; // Admin có thể force hoàn tiền
    $custom_refund_amount = $_POST['custom_refund_amount'] ?? null; // Admin có thể tùy chỉnh số tiền hoàn
    
    if (!$booking_id) {
        throw new Exception('Booking ID is required');
    }
    
    $db = getDB();
    
    // Get booking details
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name, u.full_name, u.email
        FROM bookings b
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_id = :booking_id
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Không tìm thấy đặt phòng');
    }
    
    // Check if already cancelled
    if ($booking['status'] === 'cancelled') {
        throw new Exception('Đặt phòng đã được hủy trước đó');
    }
    
    // Calculate refund amount (admin can override)
    $refund_info = calculateRefundAmount($booking);
    
    // Admin can force refund or set custom amount
    if ($force_refund && $custom_refund_amount !== null) {
        $refund_info['refund_amount'] = floatval($custom_refund_amount);
        $refund_info['refund_percentage'] = ($refund_info['refund_amount'] / $booking['total_amount']) * 100;
        $refund_info['policy_message'] = 'Admin quyết định hoàn tiền: ' . number_format($custom_refund_amount) . ' VNĐ';
    }
    
    // Process cancellation
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
        $refund_id = null;
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
                    approved_by,
                    requested_at,
                    approved_at
                ) VALUES (?, ?, ?, ?, ?, 'approved', ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $booking_id,
                $refund_info['refund_amount'],
                $refund_info['refund_percentage'],
                $refund_info['processing_fee'] ?? 0,
                $cancellation_reason ?: $refund_info['policy_message'],
                $booking['user_id'],
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
            'Admin hủy đặt phòng - ' . ($cancellation_reason ?: $refund_info['policy_message'])
        ]);
        
        // If room was assigned, make it available again
        if ($booking['room_id']) {
            $stmt = $db->prepare("
                UPDATE rooms 
                SET status = 'available' 
                WHERE room_id = ?
            ");
            $stmt->execute([$booking['room_id']]);
        }
        
        $db->commit();
        
        // Prepare response
        $response = [
            'success' => true,
            'message' => 'Đã hủy đặt phòng thành công',
            'booking_code' => $booking['booking_code'],
            'refund_info' => [
                'refund_amount' => $refund_info['refund_amount'],
                'refund_percentage' => $refund_info['refund_percentage'],
                'processing_fee' => $refund_info['processing_fee'] ?? 0,
                'policy_message' => $refund_info['policy_message']
            ]
        ];
        
        if ($refund_info['refund_amount'] > 0) {
            $response['message'] .= '. Số tiền hoàn lại: ' . number_format($refund_info['refund_amount']) . ' VNĐ';
            $response['refund_id'] = $refund_id;
        } else {
            $response['message'] .= '. Không có tiền hoàn lại.';
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
