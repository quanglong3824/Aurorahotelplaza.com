<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['chat_guest_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$conv_id = (int) ($input['conversation_id'] ?? 0);
$is_logged = isset($_SESSION['user_id']);
$user_id = $is_logged ? (int) $_SESSION['user_id'] : null;
$guest_id = $_SESSION['chat_guest_id'] ?? null;

if (!$conv_id) {
    echo json_encode(['success' => false, 'message' => 'Missing conversation_id']);
    exit;
}

try {
    $db = getDB();
    if (!$db)
        throw new Exception("Database connection failed");

    // Check if conversation belongs to user
    $stmt = $db->prepare("SELECT customer_id, guest_id FROM chat_conversations WHERE conversation_id = ?");
    $stmt->execute([$conv_id]);
    $conv = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conv) {
        throw new Exception("Conversation not found");
    }

    $is_owner = false;
    if ($is_logged && $conv['customer_id'] == $user_id) {
        $is_owner = true;
    } elseif (!$is_logged && $conv['guest_id'] === $guest_id) {
        $is_owner = true;
    }

    if (!$is_owner) {
        throw new Exception("Access denied");
    }

    // Delete all messages to reset the AI context
    $del = $db->prepare("DELETE FROM chat_messages WHERE conversation_id = ?");
    $del->execute([$conv_id]);

    // Reset conversation metadata
    $upd = $db->prepare("UPDATE chat_conversations SET last_message_preview = '', unread_staff = 0, unread_customer = 0 WHERE conversation_id = ?");
    $upd->execute([$conv_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>