<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();
    
    if ($method === 'GET') {
        // Get all floor maintenance status
        $stmt = $db->query("
            SELECT * FROM floor_maintenance 
            ORDER BY floor ASC
        ");
        $floors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'floors' => $floors
        ]);
        
    } elseif ($method === 'POST') {
        $floor = intval($_POST['floor'] ?? 0);
        $is_maintenance = intval($_POST['is_maintenance'] ?? 0);
        $maintenance_note = trim($_POST['maintenance_note'] ?? '');
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        
        if ($floor < 7 || $floor > 12) {
            echo json_encode(['success' => false, 'message' => 'Tầng không hợp lệ']);
            exit;
        }
        
        // Update or insert floor maintenance
        $stmt = $db->prepare("
            INSERT INTO floor_maintenance (floor, is_maintenance, maintenance_note, start_date, end_date, created_by)
            VALUES (:floor, :is_maintenance, :note, :start_date, :end_date, :user_id)
            ON DUPLICATE KEY UPDATE 
                is_maintenance = :is_maintenance,
                maintenance_note = :note,
                start_date = :start_date,
                end_date = :end_date,
                created_by = :user_id,
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            ':floor' => $floor,
            ':is_maintenance' => $is_maintenance,
            ':note' => $maintenance_note ?: null,
            ':start_date' => $start_date ?: null,
            ':end_date' => $end_date ?: null,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        // If setting floor to maintenance, update all rooms on that floor
        if ($is_maintenance) {
            $stmt = $db->prepare("
                UPDATE rooms SET status = 'maintenance' 
                WHERE floor = :floor AND status = 'available'
            ");
            $stmt->execute([':floor' => $floor]);
        }
        
        // Log activity
        error_log("Floor $floor maintenance " . ($is_maintenance ? 'enabled' : 'disabled') . " by user " . $_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'message' => $is_maintenance ? "Đã bật bảo trì tầng $floor" : "Đã tắt bảo trì tầng $floor"
        ]);
    }
    
} catch (Exception $e) {
    error_log("Floor maintenance error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
}
