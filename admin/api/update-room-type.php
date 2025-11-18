<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$room_id = $_POST['room_id'] ?? null;
$room_type_id = $_POST['room_type_id'] ?? null;

if (!$room_id || !$room_type_id) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    $db = getDB();
    
    // Kiểm tra room_type_id có tồn tại không
    $stmt = $db->prepare("SELECT room_type_id FROM room_types WHERE room_type_id = :type_id");
    $stmt->execute([':type_id' => $room_type_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Loại phòng không tồn tại']);
        exit;
    }
    
    // Cập nhật room_type_id cho phòng
    $stmt = $db->prepare("
        UPDATE rooms 
        SET room_type_id = :room_type_id,
            updated_at = NOW()
        WHERE room_id = :room_id
    ");
    
    $stmt->execute([
        ':room_type_id' => $room_type_id,
        ':room_id' => $room_id
    ]);
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
        VALUES (:user_id, 'update_room_type', :description, :ip, NOW())
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':description' => "Đổi loại phòng ID: $room_id sang loại: $room_type_id",
        ':ip' => $_SERVER['REMOTE_ADDR']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật loại phòng thành công'
    ]);
    
} catch (Exception $e) {
    error_log("Update room type error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
