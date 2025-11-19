<?php
/**
 * API: Save Seasonal Pricing
 * Lưu giá phòng theo mùa/sự kiện
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
    
    $pricing_id = Security::sanitizeInt($_POST['pricing_id'] ?? 0);
    $room_type_id = Security::sanitizeInt($_POST['room_type_id'] ?? 0);
    $start_date = Security::sanitizeString($_POST['start_date'] ?? '');
    $end_date = Security::sanitizeString($_POST['end_date'] ?? '');
    $price = Security::sanitizeFloat($_POST['price'] ?? 0);
    $notes = Security::sanitizeString($_POST['notes'] ?? '');
    
    // Validation
    if (!$room_type_id || !$start_date || !$end_date || !$price) {
        throw new Exception('Vui lòng điền đầy đủ thông tin');
    }
    
    if ($price < 0) {
        throw new Exception('Giá phải lớn hơn 0');
    }
    
    if (strtotime($end_date) < strtotime($start_date)) {
        throw new Exception('Ngày kết thúc phải sau ngày bắt đầu');
    }
    
    $db->beginTransaction();
    
    // Get room type info
    $stmt = $db->prepare("SELECT type_name FROM room_types WHERE room_type_id = :id");
    $stmt->execute([':id' => $room_type_id]);
    $room_type = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room_type) {
        throw new Exception('Loại phòng không tồn tại');
    }
    
    if ($pricing_id > 0) {
        // Update
        $stmt = $db->prepare("
            UPDATE room_pricing 
            SET room_type_id = :room_type_id,
                start_date = :start_date,
                end_date = :end_date,
                price = :price,
                description = :description
            WHERE pricing_id = :pricing_id
        ");
        
        $stmt->execute([
            ':pricing_id' => $pricing_id,
            ':room_type_id' => $room_type_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':price' => $price,
            ':description' => $notes
        ]);
        
        $message = 'Cập nhật giá thành công';
        $action = 'update_pricing';
        
    } else {
        // Insert
        $stmt = $db->prepare("
            INSERT INTO room_pricing (
                room_type_id, start_date, end_date, 
                price, pricing_type, description, created_at
            ) VALUES (
                :room_type_id, :start_date, :end_date,
                :price, 'seasonal', :description, NOW()
            )
        ");
        
        $stmt->execute([
            ':room_type_id' => $room_type_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':price' => $price,
            ':description' => $notes
        ]);
        
        $pricing_id = $db->lastInsertId();
        $message = 'Thêm giá mới thành công';
        $action = 'create_pricing';
    }
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (
            user_id, action, entity_type, entity_id,
            description, ip_address, user_agent, created_at
        ) VALUES (
            :user_id, :action, 'room_pricing', :entity_id,
            :description, :ip_address, :user_agent, NOW()
        )
    ");
    
    $log_desc = sprintf(
        '%s cho %s từ %s đến %s: %s VND',
        $message,
        $room_type['type_name'],
        date('d/m/Y', strtotime($start_date)),
        date('d/m/Y', strtotime($end_date)),
        number_format($price)
    );
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':action' => $action,
        ':entity_id' => $pricing_id,
        ':description' => $log_desc,
        ':ip_address' => Security::getClientIP(),
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
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
