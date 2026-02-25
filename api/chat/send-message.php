<?php
/**
 * API: Gửi tin nhắn
 * POST /api/chat/send-message.php
 *
 * Body (JSON): {
 *   "conversation_id": 5,
 *   "message": "Xin chào!",
 *   "message_type": "text",   // text | image | file
 *   "is_internal": false       // ghi chú nội bộ (chỉ staff)
 * }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';

// Phải đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Parse input
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$conv_id = (int) ($input['conversation_id'] ?? 0);
$message = trim($input['message'] ?? '');
$msg_type = in_array($input['message_type'] ?? '', ['text', 'image', 'file', 'system_note'])
    ? $input['message_type']
    : 'text';
$is_internal = (bool) ($input['is_internal'] ?? false);

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'customer';

// Validate
if (!$conv_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu conversation_id']);
    exit;
}
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Tin nhắn không được để trống']);
    exit;
}
if (mb_strlen($message) > 5000) {
    echo json_encode(['success' => false, 'message' => 'Tin nhắn quá dài (tối đa 5000 ký tự)']);
    exit;
}
// Chỉ staff mới dùng is_internal
if ($is_internal && !in_array($user_role, ['admin', 'receptionist', 'sale'])) {
    $is_internal = false;
}

// Xác định sender_type
$sender_type = in_array($user_role, ['admin', 'receptionist', 'sale']) ? 'staff' : 'customer';

try {
    $db = getDB();
    if (!$db)
        throw new Exception('DB error');

    // ── 1. Kiểm tra quyền truy cập conversation ─────────────────────────────
    if ($sender_type === 'customer') {
        // Customer chỉ được gửi vào conv của mình
        $check = $db->prepare("
            SELECT conversation_id, status FROM chat_conversations
            WHERE conversation_id = :cid AND customer_id = :uid
        ");
    } else {
        // Staff xem tất cả
        $check = $db->prepare("
            SELECT conversation_id, status FROM chat_conversations
            WHERE conversation_id = :cid
        ");
    }
    $check->execute([':cid' => $conv_id, ':uid' => $user_id]);
    $conv = $check->fetch();

    if (!$conv) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
        exit;
    }
    if ($conv['status'] === 'closed') {
        echo json_encode(['success' => false, 'message' => 'Cuộc trò chuyện đã đóng']);
        exit;
    }

    // ── 2. Insert tin nhắn ───────────────────────────────────────────────────
    $insertMsg = $db->prepare("
        INSERT INTO chat_messages
            (conversation_id, sender_id, sender_type, message,
             message_type, is_internal, is_read, created_at)
        VALUES
            (:cid, :uid, :stype, :msg, :mtype, :internal, 0, NOW())
    ");
    $insertMsg->execute([
        ':cid' => $conv_id,
        ':uid' => $user_id,
        ':stype' => $sender_type,
        ':msg' => $message,
        ':mtype' => $msg_type,
        ':internal' => $is_internal ? 1 : 0,
    ]);
    $msg_id = (int) $db->lastInsertId();

    // ── 3. Cập nhật conversation (atomic counter) ────────────────────────────
    // Ghi chú nội bộ không cập nhật unread cho customer
    if ($is_internal) {
        $db->prepare("
            UPDATE chat_conversations
            SET last_message_at      = NOW(),
                updated_at           = NOW()
            WHERE conversation_id = :cid
        ")->execute([':cid' => $conv_id]);
    } elseif ($sender_type === 'customer') {
        // Customer gửi → tăng unread_staff, reset unread_customer
        $db->prepare("
            UPDATE chat_conversations
            SET unread_staff         = unread_staff + 1,
                unread_customer      = 0,
                last_message_at      = NOW(),
                last_message_preview = :preview,
                status               = IF(status = 'open', 'open', status),
                updated_at           = NOW()
            WHERE conversation_id = :cid
        ")->execute([
                    ':preview' => mb_substr($message, 0, 100),
                    ':cid' => $conv_id
                ]);
    } else {
        // Staff gửi → tăng unread_customer, reset unread_staff
        $db->prepare("
            UPDATE chat_conversations
            SET unread_customer      = unread_customer + 1,
                unread_staff         = 0,
                last_message_at      = NOW(),
                last_message_preview = :preview,
                updated_at           = NOW()
            WHERE conversation_id = :cid
        ")->execute([
                    ':preview' => mb_substr($message, 0, 100),
                    ':cid' => $conv_id
                ]);
    }

    // ── 4. Xóa trạng thái typing của người gửi ──────────────────────────────
    $db->prepare("
        DELETE FROM chat_typing
        WHERE conversation_id = :cid AND user_id = :uid
    ")->execute([':cid' => $conv_id, ':uid' => $user_id]);

    // ── 5. Response ──────────────────────────────────────────────────────────
    echo json_encode([
        'success' => true,
        'message_id' => $msg_id,
        'created_at' => date('Y-m-d H:i:s'),
        'sender_type' => $sender_type,
        'is_internal' => $is_internal,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Chat send-message error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server']);
}
