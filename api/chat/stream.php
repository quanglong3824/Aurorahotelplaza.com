<?php
/**
 * SSE Stream — Aurora Chat System
 * GET /api/chat/stream.php
 *
 * Mode 1 - Global (admin/staff):
 *   ?type=global
 *   → Push updates danh sách conversations, tổng unread
 *   → Poll mỗi 3 giây
 *
 * Mode 2 - Conversation (customer + staff):
 *   ?type=conv&id=5&last_id=12
 *   → Push tin nhắn mới trong conv cụ thể
 *   → Push typing indicator
 *   → Poll mỗi 2 giây
 */

session_start();
session_write_close();

// ── Kiểm tra auth trước khi giữ kết nối ─────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "data: " . json_encode(['type' => 'error', 'message' => 'Unauthorized']) . "\n\n";
    exit;
}

// ── SSE Headers ──────────────────────────────────────────────────────────────
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');     // Bypass Nginx buffer (cPanel)
header('Access-Control-Allow-Origin: *');

// Tắt output buffering hoàn toàn
if (ob_get_level())
    ob_end_clean();
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);

// Giới hạn thời gian (shared hosting thường 30-90s)
// Client JS sẽ tự reconnect khi timeout
set_time_limit(90);

// ── Config ───────────────────────────────────────────────────────────────────
$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'customer';
$is_staff = in_array($user_role, ['admin', 'receptionist', 'sale']);

$mode = $_GET['type'] ?? 'conv';         // 'global' | 'conv'
$conv_id = (int) ($_GET['id'] ?? 0);
$last_id = (int) ($_GET['last_id'] ?? 0);

// Global mode chỉ dành cho staff
if ($mode === 'global' && !$is_staff) {
    $mode = 'conv'; // Fallback
}

require_once '../../config/database.php';

// ── Helper: flush ─────────────────────────────────────────────────────────────
function sseFlush(): void
{
    ob_flush();
    flush();
}

function sseEvent(string $event, array $data, int $id = 0): void
{
    if ($id > 0)
        echo "id: $id\n";
    echo "event: $event\n";
    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    sseFlush();
}

function sseHeartbeat(): void
{
    echo ": ping " . time() . "\n\n";
    sseFlush();
}

// ── GLOBAL MODE ──────────────────────────────────────────────────────────────
if ($mode === 'global') {

    $last_check = date('Y-m-d H:i:s', time() - 5); // 5s buffer lần đầu

    while (!connection_aborted()) {
        try {
            $db = getDB();
            if (!$db)
                throw new Exception('DB error');

            // Conversations có thay đổi từ lần check trước
            $stmt = $db->prepare("
                SELECT
                    c.conversation_id,
                    c.customer_id,
                    c.booking_id,
                    c.subject,
                    c.status,
                    c.staff_id,
                    c.unread_staff,
                    c.unread_customer,
                    c.last_message_at,
                    c.last_message_preview,
                    c.source,
                    u.full_name      AS customer_name,
                    u.phone          AS customer_phone,
                    u.avatar         AS customer_avatar,
                    b.booking_code,
                    su.full_name     AS staff_name,
                    TIMESTAMPDIFF(MINUTE, c.last_message_at, NOW()) AS wait_minutes,
                    -- Điểm ưu tiên để sort
                    (
                        CASE WHEN c.staff_id IS NULL AND c.status = 'open'
                             THEN 100 ELSE 0 END
                        + LEAST(TIMESTAMPDIFF(MINUTE, c.last_message_at, NOW()) * 2, 60)
                        + LEAST(c.unread_staff * 5, 50)
                    ) AS priority_score
                FROM chat_conversations c
                JOIN  users u  ON c.customer_id = u.user_id
                LEFT JOIN bookings b  ON c.booking_id  = b.booking_id
                LEFT JOIN users su ON c.staff_id    = su.user_id
                WHERE c.updated_at >= :last_check
                  AND c.status != 'closed'
                ORDER BY priority_score DESC, c.last_message_at DESC
                LIMIT 30
            ");
            $stmt->execute([':last_check' => $last_check]);
            $changed = $stmt->fetchAll();

            // Tổng unread toàn hệ thống
            $total_unread = (int) $db->query("
                SELECT COALESCE(SUM(unread_staff), 0)
                FROM chat_conversations
                WHERE status != 'closed'
            ")->fetchColumn();

            // Đếm conv chưa được assign
            $unassigned = (int) $db->query("
                SELECT COUNT(*) FROM chat_conversations
                WHERE status = 'open' AND staff_id IS NULL
            ")->fetchColumn();

            if (!empty($changed) || true) { // Luôn push để client cập nhật badge
                sseEvent('list_update', [
                    'type' => 'list_update',
                    'conversations' => $changed,
                    'total_unread' => $total_unread,
                    'unassigned' => $unassigned,
                    'timestamp' => time(),
                ]);
            }

            $last_check = date('Y-m-d H:i:s');

        } catch (Exception $e) {
            error_log('SSE global error: ' . $e->getMessage());
        }

        sseHeartbeat();
        sleep(3); // Global: 3s interval
    }

    exit;
}

// ── CONVERSATION MODE ────────────────────────────────────────────────────────
if ($mode === 'conv') {

    if (!$conv_id) {
        sseEvent('error', ['message' => 'Thiếu conversation_id']);
        exit;
    }

    try {
        $db = getDB();
        if (!$db)
            throw new Exception('DB error');

        // Kiểm tra quyền truy cập conv
        if ($is_staff) {
            $authCheck = $db->prepare("
                SELECT conversation_id FROM chat_conversations
                WHERE conversation_id = :cid
            ");
            $authCheck->execute([':cid' => $conv_id]);
        } else {
            $authCheck = $db->prepare("
                SELECT conversation_id FROM chat_conversations
                WHERE conversation_id = :cid AND customer_id = :uid
            ");
            $authCheck->execute([':cid' => $conv_id, ':uid' => $user_id]);
        }

        if (!$authCheck->fetch()) {
            sseEvent('error', ['message' => 'Không có quyền truy cập']);
            exit;
        }

    } catch (Exception $e) {
        sseEvent('error', ['message' => 'Lỗi xác thực']);
        exit;
    }

    // Filter is_internal: customer không thấy ghi chú nội bộ
    $internal_filter = $is_staff ? '' : "AND m.is_internal = 0";

    while (!connection_aborted()) {
        try {
            $db = getDB();
            if (!$db)
                throw new Exception('DB error');

            // ── Lấy tin nhắn mới ────────────────────────────────────────────
            $msgStmt = $db->prepare("
                SELECT
                    m.message_id,
                    m.sender_id,
                    m.sender_type,
                    m.message,
                    m.message_type,
                    m.is_internal,
                    m.is_read,
                    m.created_at,
                    u.full_name  AS sender_name,
                    u.avatar     AS sender_avatar,
                    u.user_role  AS sender_role
                FROM chat_messages m
                LEFT JOIN users u ON m.sender_id = u.user_id
                WHERE m.conversation_id = :cid
                  AND m.message_id > :last_id
                  $internal_filter
                ORDER BY m.message_id ASC
                LIMIT 20
            ");
            $msgStmt->execute([':cid' => $conv_id, ':last_id' => $last_id]);
            $messages = $msgStmt->fetchAll();

            foreach ($messages as $msg) {
                $last_id = max($last_id, (int) $msg['message_id']);
                sseEvent('message', array_merge($msg, ['type' => 'message']), (int) $msg['message_id']);
            }

            // ── Lấy typing status ────────────────────────────────────────────
            // Chỉ hiển thị typing của phía đối diện
            $typing_type = $is_staff ? 'customer' : 'staff';
            $typingStmt = $db->prepare("
                SELECT user_id, user_type, is_typing, updated_at
                FROM chat_typing
                WHERE conversation_id = :cid
                  AND user_type = :utype
                  AND updated_at >= NOW() - INTERVAL 5 SECOND
                  AND is_typing = 1
                  AND user_id != :uid
            ");
            $typingStmt->execute([
                ':cid' => $conv_id,
                ':utype' => $typing_type,
                ':uid' => $user_id,
            ]);
            $typing = $typingStmt->fetchAll();

            if (!empty($typing)) {
                sseEvent('typing', [
                    'type' => 'typing',
                    'conv_id' => $conv_id,
                    'users' => $typing,
                ]);
            }

            // ── Tự động đánh dấu đã đọc ─────────────────────────────────────
            if (!empty($messages)) {
                if ($is_staff) {
                    $db->prepare("
                        UPDATE chat_conversations
                        SET unread_staff = 0, updated_at = NOW()
                        WHERE conversation_id = :cid
                    ")->execute([':cid' => $conv_id]);
                } else {
                    $db->prepare("
                        UPDATE chat_conversations
                        SET unread_customer = 0, updated_at = NOW()
                        WHERE conversation_id = :cid
                    ")->execute([':cid' => $conv_id]);
                }
            }

        } catch (Exception $e) {
            error_log('SSE conv error: ' . $e->getMessage());
        }

        sseHeartbeat();
        sleep(2); // Conv mode: 2s — nhanh hơn global
    }
}
