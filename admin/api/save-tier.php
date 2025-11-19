<?php
/**
 * API: Save Membership Tier
 * Tạo mới hoặc cập nhật hạng thành viên
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/security.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $db = getDB();
    
    // Get and validate input
    $tier_id = Security::sanitizeInt($_POST['tier_id'] ?? 0);
    $tier_name = Security::sanitizeString($_POST['tier_name'] ?? '');
    $min_points = Security::sanitizeInt($_POST['min_points'] ?? 0);
    $discount_percentage = Security::sanitizeFloat($_POST['discount_percentage'] ?? 0);
    $color_code = Security::sanitizeString($_POST['color_code'] ?? '#000000');
    $benefits = Security::sanitizeString($_POST['benefits'] ?? '');
    
    // Validation
    if (empty($tier_name)) {
        throw new Exception('Tên hạng không được để trống');
    }
    
    if ($min_points < 0) {
        throw new Exception('Điểm tối thiểu phải >= 0');
    }
    
    if ($discount_percentage < 0 || $discount_percentage > 100) {
        throw new Exception('Phần trăm giảm giá phải từ 0-100');
    }
    
    // Validate color code
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color_code)) {
        throw new Exception('Mã màu không hợp lệ');
    }
    
    $db->beginTransaction();
    
    if ($tier_id > 0) {
        // Update existing tier
        $stmt = $db->prepare("
            UPDATE membership_tiers 
            SET tier_name = :tier_name,
                min_points = :min_points,
                discount_percentage = :discount_percentage,
                color_code = :color_code,
                benefits = :benefits
            WHERE tier_id = :tier_id
        ");
        
        $stmt->execute([
            ':tier_id' => $tier_id,
            ':tier_name' => $tier_name,
            ':min_points' => $min_points,
            ':discount_percentage' => $discount_percentage,
            ':color_code' => $color_code,
            ':benefits' => $benefits
        ]);
        
        $message = 'Cập nhật hạng thành viên thành công';
        $action = 'update_tier';
        
    } else {
        // Get next tier level
        $stmt = $db->query("SELECT COALESCE(MAX(tier_level), 0) + 1 as next_level FROM membership_tiers");
        $next_level = $stmt->fetch(PDO::FETCH_ASSOC)['next_level'];
        
        // Insert new tier
        $stmt = $db->prepare("
            INSERT INTO membership_tiers (
                tier_name, tier_level, min_points, 
                discount_percentage, color_code, benefits, created_at
            ) VALUES (
                :tier_name, :tier_level, :min_points,
                :discount_percentage, :color_code, :benefits, NOW()
            )
        ");
        
        $stmt->execute([
            ':tier_name' => $tier_name,
            ':tier_level' => $next_level,
            ':min_points' => $min_points,
            ':discount_percentage' => $discount_percentage,
            ':color_code' => $color_code,
            ':benefits' => $benefits
        ]);
        
        $tier_id = $db->lastInsertId();
        $message = 'Tạo hạng thành viên thành công';
        $action = 'create_tier';
    }
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (
            user_id, action, entity_type, entity_id,
            description, ip_address, user_agent, created_at
        ) VALUES (
            :user_id, :action, 'membership_tier', :entity_id,
            :description, :ip_address, :user_agent, NOW()
        )
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':action' => $action,
        ':entity_id' => $tier_id,
        ':description' => $message . ': ' . $tier_name,
        ':ip_address' => Security::getClientIP(),
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'tier_id' => $tier_id
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
