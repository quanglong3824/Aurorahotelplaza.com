<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$room_type_id = $_POST['room_type_id'] ?? null;

if (!$room_type_id) {
    echo json_encode(['success' => false, 'message' => 'Missing room_type_id']);
    exit;
}

try {
    $db = getDB();
    
    // Check if room type has rooms
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM rooms WHERE room_type_id = :room_type_id");
    $stmt->execute([':room_type_id' => $room_type_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa loại phòng đã có phòng. Vui lòng xóa tất cả phòng thuộc loại này trước.']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM room_types WHERE room_type_id = :room_type_id");
    $stmt->execute([':room_type_id' => $room_type_id]);
    
    echo json_encode(['success' => true, 'message' => 'Xóa loại phòng thành công']);
    
} catch (Exception $e) {
    error_log("Delete room type error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
