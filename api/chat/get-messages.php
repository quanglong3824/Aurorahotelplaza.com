<?php
/**
 * API: Lấy lịch sử tin nhắn của 1 conversation
 * GET /api/chat/get-messages.php?conversation_id=5&before_id=0&limit=30
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'customer';
$is_staff = in_array($user_role, ['admin', 'receptionist', 'sale']);

$conv_id = (int) ($_GET['conversation_id'] ?? 0);
$before_id = (int) ($_GET['before_id'] ?? 0); // Phân trang ngược (cuộn lên cũ hơn)
$limit = min((int) ($_GET['limit'] ?? 30), 50);

if (!$conv_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu conversation_id']);
    exit;
}

try {
    $db = getDB();
    if (!$db)
        throw new Exception('DB error');

    // Kiểm tra quyền
    if ($is_staff) {
        $auth = $db->prepare("SELECT conversation_id FROM chat_conversations WHERE conversation_id = ?");
        $auth->execute([$conv_id]);
    } else {
        $auth = $db->prepare("SELECT conversation_id FROM chat_conversations WHERE conversation_id = ? AND customer_id = ?");
        $auth->execute([$conv_id, $user_id]);
    }
    if (!$auth->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
        exit;
    }

    $internal_filter = $is_staff ? '' : 'AND m.is_internal = 0';
    $before_filter = $before_id > 0 ? 'AND m.message_id < :before_id' : '';
    $params = [':cid' => $conv_id, ':limit' => $limit];
    if ($before_id > 0)
        $params[':before_id'] = $before_id;

    $stmt = $db->prepare("
        SELECT
            m.message_id,
            m.sender_id,
            m.sender_type,
            m.message,
            m.message_type,
            m.is_internal,
            m.is_read,
            m.attachment,
            m.created_at,
            u.full_name  AS sender_name,
            u.avatar     AS sender_avatar,
            u.user_role  AS sender_role
        FROM chat_messages m
        LEFT JOIN users u ON m.sender_id = u.user_id
        WHERE m.conversation_id = :cid
          $internal_filter
          $before_filter
        ORDER BY m.message_id DESC
        LIMIT :limit
    ");
    $stmt->execute($params);
    $messages = array_reverse($stmt->fetchAll()); // Đảo lại: cũ → mới

    // Đánh dấu đã đọc
    if ($is_staff) {
        $db->prepare("UPDATE chat_conversations SET unread_staff = 0 WHERE conversation_id = ?")->execute([$conv_id]);
        $db->prepare("UPDATE chat_messages SET is_read = 1, read_at = NOW() WHERE conversation_id = ? AND sender_type = 'customer' AND is_read = 0")->execute([$conv_id]);
    } else {
        $db->prepare("UPDATE chat_conversations SET unread_customer = 0 WHERE conversation_id = ?")->execute([$conv_id]);
        $db->prepare("UPDATE chat_messages SET is_read = 1, read_at = NOW() WHERE conversation_id = ? AND sender_type = 'staff' AND is_read = 0")->execute([$conv_id]);
    }

    // Có thêm tin cũ hơn không?
    $has_more = count($messages) === $limit;

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'has_more' => $has_more,
        'count' => count($messages),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('get-messages error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server']);
}
