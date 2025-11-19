<?php
/**
 * API: Confirm Payment and Award Loyalty Points
 * Admin xác nhận thanh toán và tự động cộng điểm thưởng cho user
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/security.php';

header('Content-Type: application/json');

// Check authentication and authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate CSRF token
if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Get and validate input
$booking_id = Security::sanitizeInt($_POST['booking_id'] ?? 0);
$payment_method = Security::sanitizeString($_POST['payment_method'] ?? 'cash');
$notes = Security::sanitizeString($_POST['notes'] ?? '');

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // 1. Get booking details
    $stmt = $db->prepare("
        SELECT 
            b.*,
            u.full_name,
            u.email,
            ul.loyalty_id,
            ul.current_points,
            ul.tier_id
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
        WHERE b.booking_id = :booking_id
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Check if already paid
    if ($booking['payment_status'] === 'paid') {
        throw new Exception('Booking has already been paid');
    }
    
    // Check if cancelled
    if ($booking['status'] === 'cancelled') {
        throw new Exception('Cannot confirm payment for cancelled booking');
    }
    
    // 2. Create payment record
    $stmt = $db->prepare("
        INSERT INTO payments (
            booking_id,
            payment_method,
            amount,
            currency,
            status,
            paid_at,
            notes,
            created_at
        ) VALUES (
            :booking_id,
            :payment_method,
            :amount,
            'VND',
            'completed',
            NOW(),
            :notes,
            NOW()
        )
    ");
    
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':payment_method' => $payment_method,
        ':amount' => $booking['total_amount'],
        ':notes' => $notes
    ]);
    
    $payment_id = $db->lastInsertId();
    
    // 3. Update booking payment status
    $stmt = $db->prepare("
        UPDATE bookings 
        SET payment_status = 'paid',
            updated_at = NOW()
        WHERE booking_id = :booking_id
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    
    // 4. Calculate loyalty points
    // Rule: 1% of total amount = points (e.g., 1,000,000 VND = 10,000 points)
    $points_earned = floor($booking['total_amount'] / 100);
    
    // Bonus points for confirmed status
    if ($booking['status'] === 'confirmed') {
        $points_earned = floor($points_earned * 1.1); // 10% bonus
    }
    
    // 5. Award loyalty points
    if ($points_earned > 0) {
        // Check if user has loyalty record
        if (!$booking['loyalty_id']) {
            // Create loyalty record
            $stmt = $db->prepare("
                INSERT INTO user_loyalty (
                    user_id,
                    current_points,
                    lifetime_points,
                    created_at
                ) VALUES (
                    :user_id,
                    :points,
                    :points,
                    NOW()
                )
            ");
            $stmt->execute([
                ':user_id' => $booking['user_id'],
                ':points' => $points_earned
            ]);
            
            $loyalty_id = $db->lastInsertId();
        } else {
            // Update existing loyalty record
            $stmt = $db->prepare("
                UPDATE user_loyalty 
                SET current_points = current_points + :points,
                    lifetime_points = lifetime_points + :points,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                ':points' => $points_earned,
                ':user_id' => $booking['user_id']
            ]);
            
            $loyalty_id = $booking['loyalty_id'];
        }
        
        // 6. Record points transaction
        $stmt = $db->prepare("
            INSERT INTO points_transactions (
                user_id,
                points,
                transaction_type,
                reference_type,
                reference_id,
                description,
                created_by,
                created_at
            ) VALUES (
                :user_id,
                :points,
                'earn',
                'booking_payment',
                :booking_id,
                :description,
                :created_by,
                NOW()
            )
        ");
        
        $description = sprintf(
            'Earned %s points from booking %s (Total: %s VND)',
            number_format($points_earned),
            $booking['booking_code'],
            number_format($booking['total_amount'])
        );
        
        $stmt->execute([
            ':user_id' => $booking['user_id'],
            ':points' => $points_earned,
            ':booking_id' => $booking_id,
            ':description' => $description,
            ':created_by' => $_SESSION['user_id']
        ]);
        
        // 7. Check and update membership tier
        $stmt = $db->prepare("
            SELECT ul.current_points, ul.tier_id, mt.tier_name, mt.tier_level
            FROM user_loyalty ul
            LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
            WHERE ul.user_id = :user_id
        ");
        $stmt->execute([':user_id' => $booking['user_id']]);
        $loyalty = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get appropriate tier based on points
        $stmt = $db->prepare("
            SELECT tier_id, tier_name, tier_level, min_points
            FROM membership_tiers
            WHERE min_points <= :points
            ORDER BY min_points DESC
            LIMIT 1
        ");
        $stmt->execute([':points' => $loyalty['current_points']]);
        $new_tier = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $tier_upgraded = false;
        if ($new_tier && (!$loyalty['tier_id'] || $new_tier['tier_level'] > $loyalty['tier_level'])) {
            // Update tier
            $stmt = $db->prepare("
                UPDATE user_loyalty 
                SET tier_id = :tier_id,
                    tier_updated_at = NOW()
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                ':tier_id' => $new_tier['tier_id'],
                ':user_id' => $booking['user_id']
            ]);
            
            $tier_upgraded = true;
            $tier_name = $new_tier['tier_name'];
        }
    }
    
    // 8. Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            entity_type,
            entity_id,
            description,
            ip_address,
            user_agent,
            created_at
        ) VALUES (
            :user_id,
            'confirm_payment',
            'booking',
            :booking_id,
            :description,
            :ip_address,
            :user_agent,
            NOW()
        )
    ");
    
    $log_description = sprintf(
        'Confirmed payment for booking %s. Amount: %s VND. Points awarded: %s',
        $booking['booking_code'],
        number_format($booking['total_amount']),
        number_format($points_earned)
    );
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':booking_id' => $booking_id,
        ':description' => $log_description,
        ':ip_address' => Security::getClientIP(),
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    // 9. Create notification for user
    $stmt = $db->prepare("
        INSERT INTO notifications (
            user_id,
            type,
            title,
            message,
            link,
            icon,
            created_at
        ) VALUES (
            :user_id,
            'payment_confirmed',
            :title,
            :message,
            :link,
            'payment',
            NOW()
        )
    ");
    
    $notif_title = 'Payment Confirmed';
    $notif_message = sprintf(
        'Your payment of %s VND for booking %s has been confirmed. You earned %s loyalty points!',
        number_format($booking['total_amount']),
        $booking['booking_code'],
        number_format($points_earned)
    );
    
    if ($tier_upgraded) {
        $notif_message .= sprintf(' Congratulations! You have been upgraded to %s tier!', $tier_name);
    }
    
    $stmt->execute([
        ':user_id' => $booking['user_id'],
        ':title' => $notif_title,
        ':message' => $notif_message,
        ':link' => '/profile/bookings.php?id=' . $booking_id
    ]);
    
    // Commit transaction
    $db->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Payment confirmed successfully',
        'data' => [
            'booking_id' => $booking_id,
            'booking_code' => $booking['booking_code'],
            'payment_id' => $payment_id,
            'amount' => $booking['total_amount'],
            'points_earned' => $points_earned,
            'tier_upgraded' => $tier_upgraded ?? false,
            'new_tier' => $tier_name ?? null,
            'customer_name' => $booking['full_name'],
            'customer_email' => $booking['email']
        ]
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    // Log error
    Security::logSecurityEvent('PAYMENT_CONFIRMATION_ERROR', $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
