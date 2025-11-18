<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$room_id = $_POST['room_id'] ?? null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Missing room_id']);
    exit;
}

try {
    $db = getDB();
    
    // Check if room has bookings
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE room_id = :room_id");
    $stmt->execute([':room_id' => $room_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa phòng đã có đơn đặt']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM rooms WHERE room_id = :room_id");
    $stmt->execute([':room_id' => $room_id]);
    
    echo json_encode(['success' => true, 'message' => 'Xóa phòng thành công']);
    
} catch (Exception $e) {
    error_log("Delete room error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
