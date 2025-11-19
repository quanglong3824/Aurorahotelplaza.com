<?php
/**
 * API: Delete Membership Tier
 * Xóa hạng thành viên
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
    
    $tier_id = Security::sanitizeInt($_POST['tier_id'] ?? 0);
    
    if (!$tier_id) {
        throw new Exception('ID hạng không hợp lệ');
    }
    
    $db->beginTransaction();
    
    // Check if tier has members
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_loyalty WHERE tier_id = :tier_id");
    $stmt->execute([':tier_id' => $tier_id]);
    $member_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($member_count > 0) {
        throw new Exception("Không thể xóa hạng này vì có {$member_count} thành viên đang sử dụng");
    }
    
    // Get tier name for logging
    $stmt = $db->prepare("SELECT tier_name FROM membership_tiers WHERE tier_id = :tier_id");
    $stmt->execute([':tier_id' => $tier_id]);
    $tier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tier) {
        throw new Exception('Hạng không tồn tại');
    }
    
    // Delete tier
    $stmt = $db->prepare("DELETE FROM membership_tiers WHERE tier_id = :tier_id");
    $stmt->execute([':tier_id' => $tier_id]);
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (
            user_id, action, entity_type, entity_id,
            description, ip_address, user_agent, created_at
        ) VALUES (
            :user_id, 'delete_tier', 'membership_tier', :entity_id,
            :description, :ip_address, :user_agent, NOW()
        )
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':entity_id' => $tier_id,
        ':description' => 'Xóa hạng thành viên: ' . $tier['tier_name'],
        ':ip_address' => Security::getClientIP(),
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Xóa hạng thành viên thành công'
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
