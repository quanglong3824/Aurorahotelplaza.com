<?php
/**
 * API: Delete Seasonal Pricing
 */

session_start();
require_once '../../config/database.php';
require_once '../../helpers/security.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $db = getDB();
    
    $pricing_id = Security::sanitizeInt($_POST['pricing_id'] ?? 0);
    
    if (!$pricing_id) {
        throw new Exception('ID không hợp lệ');
    }
    
    $db->beginTransaction();
    
    // Get info for logging
    $stmt = $db->prepare("
        SELECT rp.*, rt.type_name 
        FROM room_pricing rp
        JOIN room_types rt ON rp.room_type_id = rt.room_type_id
        WHERE rp.pricing_id = :id
    ");
    $stmt->execute([':id' => $pricing_id]);
    $pricing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pricing) {
        throw new Exception('Giá không tồn tại');
    }
    
    // Delete
    $stmt = $db->prepare("DELETE FROM room_pricing WHERE pricing_id = :id");
    $stmt->execute([':id' => $pricing_id]);
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (
            user_id, action, entity_type, entity_id,
            description, ip_address, user_agent, created_at
        ) VALUES (
            :user_id, 'delete_pricing', 'room_pricing', :entity_id,
            :description, :ip_address, :user_agent, NOW()
        )
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':entity_id' => $pricing_id,
        ':description' => sprintf(
            'Xóa giá đặc biệt: %s (%s - %s)',
            $pricing['type_name'],
            date('d/m/Y', strtotime($pricing['start_date'])),
            date('d/m/Y', strtotime($pricing['end_date']))
        ),
        ':ip_address' => Security::getClientIP(),
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Xóa giá thành công'
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
