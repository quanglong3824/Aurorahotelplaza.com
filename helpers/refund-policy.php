<?php
/**
 * Refund Policy Helper
 * Chính sách hoàn tiền chuẩn khách sạn 4 sao
 */

/**
 * Calculate refund amount based on cancellation time
 * 
 * Chính sách hoàn tiền:
 * - Hủy trước 7 ngày: Hoàn 100% (trừ phí xử lý 5%)
 * - Hủy trước 3-7 ngày: Hoàn 50%
 * - Hủy trước 1-3 ngày: Hoàn 25%
 * - Hủy trong vòng 24h: Không hoàn tiền
 * - Đã check-in: Không hoàn tiền
 * 
 * @param array $booking Booking data
 * @return array Refund information
 */
function calculateRefundAmount($booking)
{
    // Validation: Check if booking data is valid
    if (empty($booking) || !isset($booking['check_in_date']) || !isset($booking['total_amount'])) {
        return [
            'can_cancel' => false,
            'days_until_checkin' => 0,
            'total_amount' => 0,
            'refund_percentage' => 0,
            'refund_amount' => 0,
            'processing_fee' => 0,
            'policy_message' => __('refund.invalid_data'),
            'final_refund' => 0
        ];
    }

    $check_in_date = strtotime($booking['check_in_date']);
    $now = time();

    // Edge case: Invalid check-in date
    if ($check_in_date === false || $check_in_date < $now) {
        return [
            'can_cancel' => false,
            'days_until_checkin' => 0,
            'total_amount' => floatval($booking['total_amount']),
            'refund_percentage' => 0,
            'refund_amount' => 0,
            'processing_fee' => 0,
            'policy_message' => __('refund.invalid_checkin'),
            'final_refund' => 0
        ];
    }

    $days_until_checkin = ($check_in_date - $now) / (60 * 60 * 24);

    $total_amount = floatval($booking['total_amount']);

    // Edge case: Zero or negative amount
    if ($total_amount <= 0) {
        return [
            'can_cancel' => false,
            'days_until_checkin' => round($days_until_checkin, 1),
            'total_amount' => 0,
            'refund_percentage' => 0,
            'refund_amount' => 0,
            'processing_fee' => 0,
            'policy_message' => __('refund.invalid_amount'),
            'final_refund' => 0
        ];
    }

    $refund_amount = 0;
    $refund_percentage = 0;
    $processing_fee = 0;
    $policy_message = '';
    $can_cancel = false;

    // Check if already checked in or checked out
    if (in_array($booking['status'], ['checked_in', 'checked_out'])) {
        $policy_message = __('refund.already_checkedin');
        $can_cancel = false;
    }
    // Check if already cancelled
    elseif ($booking['status'] === 'cancelled') {
        $policy_message = __('refund.already_cancelled');
        $can_cancel = false;
    }
    // Edge case: Check-in date has passed (negative days)
    elseif ($days_until_checkin < 0) {
        $policy_message = __('refund.past_checkin');
        $refund_percentage = 0;
        $refund_amount = 0;
        $can_cancel = false;
    }
    // Cancellation within 24 hours of check-in
    elseif ($days_until_checkin >= 0 && $days_until_checkin < 1) {
        $policy_message = __('refund.policy_within_24h');
        $refund_percentage = 0;
        $refund_amount = 0;
        $can_cancel = true;
    }
    // Cancellation 1-3 days before check-in
    elseif ($days_until_checkin >= 1 && $days_until_checkin < 3) {
        $policy_message = __('refund.policy_1_3_days');
        $refund_percentage = 25;
        $refund_amount = $total_amount * 0.25;
        $can_cancel = true;
    }
    // Cancellation 3-7 days before check-in
    elseif ($days_until_checkin >= 3 && $days_until_checkin < 7) {
        $policy_message = __('refund.policy_3_7_days');
        $refund_percentage = 50;
        $refund_amount = $total_amount * 0.50;
        $can_cancel = true;
    }
    // Cancellation 7+ days before check-in
    elseif ($days_until_checkin >= 7) {
        $policy_message = __('refund.policy_7_plus_days');
        $refund_percentage = 100;
        $processing_fee = $total_amount * 0.05; // 5% processing fee
        $refund_amount = $total_amount - $processing_fee;
        $can_cancel = true;
    }

    return [
        'can_cancel' => $can_cancel,
        'days_until_checkin' => round($days_until_checkin, 1),
        'total_amount' => $total_amount,
        'refund_percentage' => $refund_percentage,
        'refund_amount' => $refund_amount,
        'processing_fee' => $processing_fee,
        'policy_message' => $policy_message,
        'final_refund' => $refund_amount
    ];
}

/**
 * Get refund policy text for display
 * 
 * @return string HTML formatted policy text
 */
function getRefundPolicyText()
{
    return '
    <div class="space-y-3 text-sm">
        <h4 class="font-bold text-base">' . __('refund.title') . '</h4>
        
        <div class="space-y-2">
            <div class="flex items-start gap-2">
                <span class="text-green-600">✓</span>
                <div>
                    <strong>' . __('refund.before_7_days') . '</strong>
                    <p class="text-gray-600">' . __('refund.before_7_days_desc') . '</p>
                </div>
            </div>
            
            <div class="flex items-start gap-2">
                <span class="text-blue-600">✓</span>
                <div>
                    <strong>' . __('refund.before_3_7_days') . '</strong>
                    <p class="text-gray-600">' . __('refund.before_3_7_days_desc') . '</p>
                </div>
            </div>
            
            <div class="flex items-start gap-2">
                <span class="text-yellow-600">⚠</span>
                <div>
                    <strong>' . __('refund.before_1_3_days') . '</strong>
                    <p class="text-gray-600">' . __('refund.before_1_3_days_desc') . '</p>
                </div>
            </div>
            
            <div class="flex items-start gap-2">
                <span class="text-red-600">✗</span>
                <div>
                    <strong>' . __('refund.within_24h') . '</strong>
                    <p class="text-gray-600">' . __('refund.within_24h_desc') . '</p>
                </div>
            </div>
            
            <div class="flex items-start gap-2">
                <span class="text-red-600">✗</span>
                <div>
                    <strong>' . __('refund.after_checkin') . '</strong>
                    <p class="text-gray-600">' . __('refund.after_checkin_desc') . '</p>
                </div>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-blue-50 rounded-lg">
            <p class="text-xs text-blue-800">
                <strong>' . __('common.note') . ':</strong> ' . __('refund.note') . '
            </p>
        </div>
    </div>
    ';
}

/**
 * Process refund for cancelled booking
 * 
 * @param PDO $db Database connection
 * @param int $booking_id Booking ID
 * @param array $refund_info Refund information from calculateRefundAmount
 * @param int $cancelled_by User ID who cancelled
 * @return array Result with success status and message
 */
function processRefund($db, $booking_id, $refund_info, $cancelled_by)
{
    try {
        $db->beginTransaction();

        // Get booking details
        $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception('Booking not found');
        }

        // Update booking status
        $stmt = $db->prepare("
            UPDATE bookings 
            SET status = 'cancelled',
                cancelled_at = NOW(),
                cancellation_reason = ?
            WHERE booking_id = ?
        ");
        $stmt->execute([
            $refund_info['policy_message'],
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
                $refund_info['policy_message'],
                $cancelled_by
            ]);
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
            $cancelled_by,
            'Hủy đặt phòng - ' . $refund_info['policy_message']
        ]);

        $db->commit();

        return [
            'success' => true,
            'message' => __('profile_bookings.cancel_success'),
            'refund_amount' => $refund_info['refund_amount']
        ];

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Refund processing error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => __('common.error') . ': ' . $e->getMessage()
        ];
    }
}
