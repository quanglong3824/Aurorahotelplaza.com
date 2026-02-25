<?php
/**
 * API Admin: Gán / Đóng / Khoá conversation
 * POST /admin/api/manage-conversation.php
 * Body: {
 *   "action": "assign" | "close" | "lock" | "unlock" | "claim",
 *   "conversation_id": 5,
 *   "staff_id": 7    // chỉ dùng với action=assign
 * }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';
require_once '../../helpers/session-helper.php';

// Staff only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';
$conv_id = (int) ($input['conversation_id'] ?? 0);
$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['user_role'];

if (!$conv_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số']);
    exit;
}

// Kiểm tra quyền theo action
$perm_map = [
    'assign' => ['admin', 'receptionist'],
    'close' => ['admin', 'receptionist', 'sale'],
    'lock' => ['admin', 'receptionist'],
    'unlock' => ['admin', 'receptionist'],
    'claim' => ['admin', 'receptionist', 'sale'], // Tự claim về mình
];

if (!in_array($role, $perm_map[$action] ?? [])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền thực hiện hành động này']);
    exit;
}

try {
    $db = getDB();
    if (!$db)
        throw new Exception('DB error');

    // Kiểm tra conv tồn tại
    $conv = $db->prepare("SELECT * FROM chat_conversations WHERE conversation_id = ?");
    $conv->execute([$conv_id]);
    $conv = $conv->fetch();
    if (!$conv) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy conversation']);
        exit;
    }

    switch ($action) {

        // ── Gán cho staff cụ thể (admin/receptionist) ───────────────────────
        case 'assign':
            $staff_id = (int) ($input['staff_id'] ?? 0);
            if (!$staff_id) {
                echo json_encode(['success' => false, 'message' => 'Thiếu staff_id']);
                exit;
            }
            // Kiểm tra staff tồn tại và active
            $checkStaff = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND user_role IN ('admin','receptionist','sale') AND status = 'active'");
            $checkStaff->execute([$staff_id]);
            if (!$checkStaff->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Staff không hợp lệ']);
                exit;
            }
            $db->prepare("
                UPDATE chat_conversations
                SET staff_id = :sid, status = 'assigned', updated_at = NOW()
                WHERE conversation_id = :cid
            ")->execute([':sid' => $staff_id, ':cid' => $conv_id]);

            // Gửi system message
            $db->prepare("
                INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message, message_type, is_internal, created_at)
                VALUES (:cid, 0, 'system', :msg, 'system_note', 1, NOW())
            ")->execute([
                        ':cid' => $conv_id,
                        ':msg' => "Cuộc trò chuyện được gán cho nhân viên #{$staff_id}"
                    ]);

            echo json_encode(['success' => true, 'message' => 'Đã gán thành công']);
            break;

        // ── Claim về mình ────────────────────────────────────────────────────
        case 'claim':
            if ($conv['status'] === 'closed') {
                echo json_encode(['success' => false, 'message' => 'Cuộc trò chuyện đã đóng']);
                exit;
            }
            $db->prepare("
                UPDATE chat_conversations
                SET staff_id = :sid, status = 'assigned', updated_at = NOW()
                WHERE conversation_id = :cid
            ")->execute([':sid' => $user_id, ':cid' => $conv_id]);
            echo json_encode(['success' => true, 'message' => 'Đã nhận xử lý']);
            break;

        // ── Đóng conversation ────────────────────────────────────────────────
        case 'close':
            $db->prepare("
                UPDATE chat_conversations
                SET status = 'closed', updated_at = NOW()
                WHERE conversation_id = :cid
            ")->execute([':cid' => $conv_id]);
            // System message
            $db->prepare("
                INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message, message_type, is_internal, created_at)
                VALUES (:cid, :uid, 'system', 'Cuộc trò chuyện đã được đóng.', 'system_note', 0, NOW())
            ")->execute([':cid' => $conv_id, ':uid' => $user_id]);
            echo json_encode(['success' => true, 'message' => 'Đã đóng conversation']);
            break;

        // ── Khoá (chỉ admin/receptionist) ───────────────────────────────────
        case 'lock':
            $db->prepare("
                UPDATE chat_conversations
                SET locked_by = :uid, locked_at = NOW(), updated_at = NOW()
                WHERE conversation_id = :cid
            ")->execute([':uid' => $user_id, ':cid' => $conv_id]);
            echo json_encode(['success' => true, 'message' => 'Đã khoá conversation']);
            break;

        case 'unlock':
            $db->prepare("
                UPDATE chat_conversations
                SET locked_by = NULL, locked_at = NULL, updated_at = NOW()
                WHERE conversation_id = :cid
            ")->execute([':cid' => $conv_id]);
            echo json_encode(['success' => true, 'message' => 'Đã mở khoá']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }

} catch (Exception $e) {
    error_log('manage-conversation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server']);
}
