<?php
/**
 * API: Tạo conversation chat mới
 * POST /api/chat/create-conversation.php
 *
 * Body (JSON): { "subject": "...", "booking_id": null, "source": "website" }
 * Response: { "success": true, "conversation_id": 5 }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';

// Chỉ customer đã đăng nhập mới tạo được
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng chat']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Parse input
$input      = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$subject    = trim($input['subject']    ?? 'Hỗ trợ khách hàng');
$booking_id = isset($input['booking_id']) ? (int)$input['booking_id'] : null;
$source     = in_array($input['source'] ?? '', ['website','booking','profile'])
            ? $input['source']
            : 'website';

$customer_id = (int)$_SESSION['user_id'];

try {
    $db = getDB();
    if (!$db) throw new Exception('Không thể kết nối database');

    // ── 1. Kiểm tra đã có conv OPEN/ASSIGNED của user chưa ──────────────────
    // Không tạo trùng nếu đã có conv đang mở (trừ khi gắn booking_id khác nhau)
    $checkSql = "
        SELECT conversation_id FROM chat_conversations
        WHERE customer_id = :cid
          AND status != 'closed'
    ";
    $params = [':cid' => $customer_id];

    if ($booking_id) {
        $checkSql .= " AND booking_id = :bid";
        $params[':bid'] = $booking_id;
    } else {
        $checkSql .= " AND booking_id IS NULL";
    }
    $checkSql .= " LIMIT 1";

    $existing = $db->prepare($checkSql);
    $existing->execute($params);
    $found = $existing->fetch();

    if ($found) {
        // Trả về conv đã có, không tạo mới
        echo json_encode([
            'success'         => true,
            'conversation_id' => (int)$found['conversation_id'],
            'is_existing'     => true
        ]);
        exit;
    }

    // ── 2. Tạo conversation mới ──────────────────────────────────────────────
    $stmt = $db->prepare("
        INSERT INTO chat_conversations
            (customer_id, booking_id, subject, status, source, unread_staff,
             unread_customer, created_at, updated_at)
        VALUES
            (:cid, :bid, :subject, 'open', :source, 0, 0, NOW(), NOW())
    ");
    $stmt->execute([
        ':cid'     => $customer_id,
        ':bid'     => $booking_id,
        ':subject' => mb_substr($subject, 0, 255),
        ':source'  => $source,
    ]);
    $conv_id = (int)$db->lastInsertId();

    // ── 3. Auto-assign staff (ít việc nhất đang online) ─────────────────────
    $staffStmt = $db->prepare("
        SELECT u.user_id,
               COUNT(c.conversation_id) AS load
        FROM users u
        LEFT JOIN chat_conversations c
               ON c.staff_id = u.user_id AND c.status = 'assigned'
        WHERE u.user_role IN ('receptionist', 'sale')
          AND u.status = 'active'
          AND u.last_login >= NOW() - INTERVAL 30 MINUTE
        GROUP BY u.user_id
        HAVING load < 15
        ORDER BY load ASC
        LIMIT 1
    ");
    $staffStmt->execute();
    $staff = $staffStmt->fetch();

    if ($staff) {
        $db->prepare("
            UPDATE chat_conversations
            SET staff_id = :sid, status = 'assigned', updated_at = NOW()
            WHERE conversation_id = :cid
        ")->execute([':sid' => $staff['user_id'], ':cid' => $conv_id]);
    }

    // ── 4. Gửi tin chào tự động nếu được bật ────────────────────────────────
    $setting = $db->query("
        SELECT setting_value FROM chat_settings
        WHERE setting_key = 'auto_reply_enabled' LIMIT 1
    ")->fetchColumn();

    if ($setting === '1') {
        $autoMsg = $db->query("
            SELECT setting_value FROM chat_settings
            WHERE setting_key = 'auto_reply_message' LIMIT 1
        ")->fetchColumn();

        if ($autoMsg) {
            $db->prepare("
                INSERT INTO chat_messages
                    (conversation_id, sender_id, sender_type, message,
                     message_type, is_internal, is_read, created_at)
                VALUES
                    (:cid, 0, 'system', :msg, 'text', 0, 0, NOW())
            ")->execute([
                ':cid' => $conv_id,
                ':msg' => $autoMsg
            ]);

            // Cập nhật preview conversation
            $db->prepare("
                UPDATE chat_conversations
                SET last_message_at      = NOW(),
                    last_message_preview = :preview,
                    unread_customer      = 1,
                    updated_at           = NOW()
                WHERE conversation_id = :cid
            ")->execute([
                ':preview' => mb_substr($autoMsg, 0, 100),
                ':cid'     => $conv_id
            ]);
        }
    }

    echo json_encode([
        'success'         => true,
        'conversation_id' => $conv_id,
        'is_existing'     => false,
        'staff_assigned'  => $staff ? true : false
    ]);

} catch (Exception $e) {
    error_log('Chat create-conversation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server, vui lòng thử lại']);
}
