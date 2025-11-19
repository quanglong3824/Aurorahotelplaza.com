<?php
/**
 * API: Adjust User Loyalty Points
 * Admin điều chỉnh điểm thưởng cho thành viên
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/security.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $db = getDB();
    
    $user_id = Security::sanitizeInt($_POST['user_id'] ?? 0);
    $adjustment_type = Security::sanitizeString($_POST['adjustment_type'] ?? '');
    $points = Security::sanitizeInt($_POST['points'] ?? 0);
    $reason = Security::sanitizeString($_POST['reason'] ?? '');
    
    // Validation
    if (!$user_id || !$points || empty($reason)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin');
    }
    
    if (!in_array($adjustment_type, ['add', 'subtract'])) {
        throw new Exception('Loại điều chỉnh không hợp lệ');
    }
    
    $db->beginTransaction();
    
    // Get user info
    $stmt = $db->prepare("SELECT full_name, email FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('Người dùng không tồn tại');
    }
    
    // Calculate points change
    $points_change = $adjustment_type === 'add' ? $points : -$points;
    
    // Check if user has loyalty record
    $stmt = $db->prepare("SELECT loyalty_id, current_points FROM user_loyalty WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $loyalty = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loyalty) {
        // Create loyalty record
        $stmt = $db->prepare("
            INSERT INTO user_loyalty (user_id, current_points, lifetime_points, created_at)
            VALUES (:user_id, :points, :lifetime, NOW())
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':points' => max(0, $points_change),
            ':lifetime' => max(0, $points_change)
        ]);
    } else {
        // Check if subtract would result in negative
        if ($adjustment_type === 'subtract' && $loyalty['current_points'] < $points) {
            throw new Exception('Không đủ điểm để trừ');
        }
        
        // Update loyalty record
        if ($adjustment_type === 'add') {
            $stmt = $db->prepare("
                UPDATE user_loyalty 
                SET current_points = current_points + :points,
                    lifetime_points = lifetime_points + :points,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ");
        } else {
            $stmt = $db->prepare("
                UPDATE user_loyalty 
                SET current_points = current_points - :points,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ");
        }
        $stmt->execute([':points' => $points, ':user_id' => $user_id]);
    }
    
    // Record transaction
    $stmt = $db->prepare("
        INSERT INTO points_transactions (
            user_id, points, transaction_type, reference_type,
            description, created_by, created_at
        ) VALUES (
            :user_id, :points, 'adjust', 'admin_adjustment',
            :description, :created_by, NOW()
        )
    ");
    
    $description = sprintf(
        '%s %s điểm. Lý do: %s',
        $adjustment_type === 'add' ? 'Cộng' : 'Trừ',
        number_format($points),
        $reason
    );
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':points' => $points_change,
        ':description' => $description,
        ':created_by' => $_SESSION['user_id']
    ]);
    
    // Update tier if needed
    $stmt = $db->prepare("
        SELECT current_points FROM user_loyalty WHERE user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $new_points = $stmt->fetch(PDO::FETCH_ASSOC)['current_points'];
    
    $stmt = $db->prepare("
        SELECT tier_id FROM membership_tiers
        WHERE min_points <= :points
        ORDER BY min_points DESC
        LIMIT 1
    ");
    $stmt->execute([':points' => $new_points]);
    $new_tier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($new_tier) {
        $stmt = $db->prepare("
            UPDATE user_loyalty 
            SET tier_id = :tier_id, tier_updated_at = NOW()
            WHERE user_id = :user_id
        ");
        $stmt->execute([
            ':tier_id' => $new_tier['tier_id'],
            ':user_id' => $user_id
        ]);
    }
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (
            user_id, action, entity_type, entity_id,
            description, ip_address, user_agent, created_at
        ) VALUES (
            :user_id, 'adjust_points', 'user', :entity_id,
            :description, :ip_address, :user_agent, NOW()
        )
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':entity_id' => $user_id,
        ':description' => sprintf(
            'Điều chỉnh điểm cho %s (%s): %s',
            $user['full_name'],
            $user['email'],
            $description
        ),
        ':ip_address' => Security::getClientIP(),
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    // Create notification for user
    $stmt = $db->prepare("
        INSERT INTO notifications (
            user_id, type, title, message, icon, created_at
        ) VALUES (
            :user_id, 'points_adjustment', :title, :message, 'stars', NOW()
        )
    ");
    
    $notif_title = $adjustment_type === 'add' ? 'Nhận điểm thưởng' : 'Điều chỉnh điểm';
    $notif_message = sprintf(
        '%s %s điểm. %s. Số điểm hiện tại: %s',
        $adjustment_type === 'add' ? 'Bạn đã nhận' : 'Đã trừ',
        number_format($points),
        $reason,
        number_format($new_points)
    );
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':title' => $notif_title,
        ':message' => $notif_message
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Điều chỉnh điểm thành công',
        'new_points' => $new_points
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
