<?php
/**
 * API: Tạo conversation mới cho guest
 * POST /api/chat/create-conversation.php
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

// Kiểm tra session
if (!isset($_SESSION['user_id']) && !isset($_SESSION['chat_guest_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$guest_id = $_SESSION['chat_guest_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Khách vãng lai';

// Nhận guest_id từ request body nếu có
$input = json_decode(file_get_contents('php://input'), true);
if ($input && isset($input['guest_id'])) {
    $guest_id = $input['guest_id'];
}

try {
    $db = getDB();
    if (!$db) throw new Exception('DB error');

    // Tạo conversation mới
    $stmt = $db->prepare("
        INSERT INTO chat_conversations 
            (customer_id, guest_id, subject, status, source, created_at)
        VALUES 
            (:customer_id, :guest_id, :subject, 'open', 'widget', NOW())
    ");
    
    $subject = $user_name . ' - Chat mới từ widget';
    $stmt->execute([
        ':customer_id' => $user_id,
        ':guest_id' => $guest_id,
        ':subject' => $subject
    ]);
    
    $conversation_id = $db->lastInsertId();

    // Tạo message welcome từ AI
    $welcomeMsg = "Xin chào! Tôi là trợ lý ảo của Aurora Hotel Plaza. Tôi có thể giúp gì cho bạn về đặt phòng, dịch vụ hoặc thông tin khách sạn?";
    
    $msgStmt = $db->prepare("
        INSERT INTO chat_messages 
            (conversation_id, sender_id, sender_type, content, created_at)
        VALUES 
            (:conv_id, NULL, 'ai', :content, NOW())
    ");
    $msgStmt->execute([
        ':conv_id' => $conversation_id,
        ':content' => $welcomeMsg
    ]);

    echo json_encode([
        'success' => true,
        'conversation_id' => $conversation_id,
        'message' => 'Conversation created successfully'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('create-conversation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server']);
}
