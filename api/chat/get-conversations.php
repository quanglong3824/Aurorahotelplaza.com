<?php
/**
 * API: Lấy danh sách conversations (cho staff)
 * GET /api/chat/get-conversations.php
 *   ?status=open|assigned|closed|all  (default: all không closed)
 *   &search=từ khóa
 *   &mine=1  (chỉ conv của mình)
 *   &page=1
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

// Customer chỉ xem conv của chính mình → redirect sang endpoint khác
if (!$is_staff) {
    // Trả về conv của customer này thôi
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT
                c.conversation_id, c.subject, c.status,
                c.last_message_at, c.last_message_preview,
                c.unread_customer, c.source, c.booking_id,
                b.booking_code,
                su.full_name AS staff_name
            FROM chat_conversations c
            LEFT JOIN bookings b ON c.booking_id = b.booking_id
            LEFT JOIN users su   ON c.staff_id   = su.user_id
            WHERE c.customer_id = :uid
            ORDER BY c.last_message_at DESC
            LIMIT 20
        ");
        $stmt->execute([':uid' => $user_id]);
        $convs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $convs, 'total' => count($convs)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi server']);
    }
    exit;
}

// Staff: filter params
$status = $_GET['status'] ?? 'active'; // active = open + assigned
$search = trim($_GET['search'] ?? '');
$mine = (bool) ($_GET['mine'] ?? false);
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 30;
$offset = ($page - 1) * $limit;

$where = ['1=1'];
$params = [];

// Status filter
if ($status === 'active') {
    $where[] = "c.status IN ('open', 'assigned')";
} elseif (in_array($status, ['open', 'assigned', 'closed'])) {
    $where[] = "c.status = :status";
    $params[':status'] = $status;
}
// Else: 'all' → không filter status

// Mine filter
if ($mine) {
    $where[] = "c.staff_id = :staff_id";
    $params[':staff_id'] = $user_id;
}

// Search
if ($search !== '') {
    $where[] = "(u.full_name LIKE :search OR u.phone LIKE :search OR c.subject LIKE :search OR b.booking_code LIKE :search)";
    $params[':search'] = "%$search%";
}

$whereClause = implode(' AND ', $where);

try {
    $db = getDB();
    if (!$db)
        throw new Exception('DB error');

    // Count total
    $countStmt = $db->prepare("
        SELECT COUNT(*) FROM chat_conversations c
        JOIN users u ON c.customer_id = u.user_id
        LEFT JOIN bookings b ON c.booking_id = b.booking_id
        WHERE $whereClause
    ");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Data
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    $stmt = $db->prepare("
        SELECT
            c.conversation_id,
            c.customer_id,
            c.booking_id,
            c.subject,
            c.status,
            c.staff_id,
            c.unread_staff,
            c.last_message_at,
            c.last_message_preview,
            c.source,
            c.created_at,
            u.full_name      AS customer_name,
            u.phone          AS customer_phone,
            u.avatar         AS customer_avatar,
            b.booking_code,
            su.full_name     AS staff_name,
            TIMESTAMPDIFF(MINUTE, c.last_message_at, NOW()) AS wait_minutes,
            (
                CASE WHEN c.staff_id IS NULL AND c.status = 'open' THEN 100 ELSE 0 END
                + LEAST(TIMESTAMPDIFF(MINUTE, c.last_message_at, NOW()) * 2, 60)
                + LEAST(c.unread_staff * 5, 50)
            ) AS priority_score
        FROM chat_conversations c
        JOIN  users u  ON c.customer_id = u.user_id
        LEFT JOIN bookings b  ON c.booking_id  = b.booking_id
        LEFT JOIN users su ON c.staff_id    = su.user_id
        WHERE $whereClause
        ORDER BY priority_score DESC, c.last_message_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->execute($params);
    $convs = $stmt->fetchAll();

    // Summary stats
    $stats = $db->query("
        SELECT
            SUM(CASE WHEN status = 'open'     THEN 1 ELSE 0 END) AS total_open,
            SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) AS total_assigned,
            SUM(CASE WHEN staff_id IS NULL AND status = 'open' THEN 1 ELSE 0 END) AS unassigned,
            COALESCE(SUM(unread_staff), 0) AS total_unread
        FROM chat_conversations
        WHERE status != 'closed'
    ")->fetch();

    echo json_encode([
        'success' => true,
        'data' => $convs,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'stats' => $stats,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('get-conversations error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server']);
}
