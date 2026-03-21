<?php
/**
 * API: Đánh dấu cuộc trò chuyện đã đọc
 * POST /api/chat/mark-read.php
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['chat_guest_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$conv_id = (int) ($input['conversation_id'] ?? 0);

if (!$conv_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu conversation_id']);
    exit;
}

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$guest_id = $_SESSION['chat_guest_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'customer';
$is_staff = in_array($user_role, ['admin', 'receptionist', 'sale']);

try {
    $db = getDB();
    if (!$db) throw new Exception('DB error');

    // Kiểm tra quyền và thực hiện đánh dấu
    if ($is_staff) {
        $stmt = $db->prepare("UPDATE chat_conversations SET unread_staff = 0, updated_at = NOW() WHERE conversation_id = ?");
        $stmt->execute([$conv_id]);
        
        $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1, read_at = NOW() WHERE conversation_id = ? AND sender_type = 'customer' AND is_read = 0");
        $stmt->execute([$conv_id]);
    } else {
        // Customer hoặc Guest
        if ($user_id) {
            $check = $db->prepare("SELECT conversation_id FROM chat_conversations WHERE conversation_id = ? AND customer_id = ?");
            $check->execute([$conv_id, $user_id]);
        } else {
            $check = $db->prepare("SELECT conversation_id FROM chat_conversations WHERE conversation_id = ? AND guest_id = ?");
            $check->execute([$conv_id, $guest_id]);
        }

        if (!$check->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
            exit;
        }

        $stmt = $db->prepare("UPDATE chat_conversations SET unread_customer = 0, updated_at = NOW() WHERE conversation_id = ?");
        $stmt->execute([$conv_id]);

        $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1, read_at = NOW() WHERE conversation_id = ? AND sender_type IN ('staff', 'bot') AND is_read = 0");
        $stmt->execute([$conv_id]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
