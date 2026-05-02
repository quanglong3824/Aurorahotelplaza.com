<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

try {
    $db = getDB();
    if (!$db) {
        throw new Exception("Không thể kết nối CSDL");
    }

    $user_id = $_SESSION['user_id'];

    // Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS admin_ai_history (
        user_id INT PRIMARY KEY,
        history_json LONGTEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['action']) && $input['action'] === 'clear') {
            $stmt = $db->prepare("DELETE FROM admin_ai_history WHERE user_id = ?");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'message' => 'Cleared']);
        } else {
            $history = $input['history'] ?? '[]';
            if (is_array($history)) {
                $history = json_encode($history, JSON_UNESCAPED_UNICODE);
            }
            
            $stmt = $db->prepare("INSERT INTO admin_ai_history (user_id, history_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE history_json = ?");
            $stmt->execute([$user_id, $history, $history]);
            echo json_encode(['success' => true]);
        }
    } else {
        // GET request to load history
        $stmt = $db->prepare("SELECT history_json FROM admin_ai_history WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true, 
            'history' => $result ? json_decode($result, true) : []
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
