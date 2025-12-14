<?php
/**
 * Admin Contacts API
 * Xử lý các thao tác quản lý liên hệ
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';
require_once '../../helpers/auth-middleware.php';
require_once '../../helpers/activity-logger.php';

// Kiểm tra quyền
AuthMiddleware::requireStaff();

$action = $_REQUEST['action'] ?? '';

try {
    $db = getDB();
    
    switch ($action) {
        case 'get':
            getContact($db);
            break;
        case 'update_status':
            updateStatus($db);
            break;
        case 'delete':
            deleteContact($db);
            break;
        case 'list':
            listContacts($db);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
} catch (Exception $e) {
    error_log("Contacts API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}

/**
 * Lấy chi tiết một liên hệ
 */
function getContact($db) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        return;
    }
    
    $stmt = $db->prepare("
        SELECT c.*, u.full_name as assigned_name,
               COALESCE(c.contact_code, LPAD(c.id, 8, '0')) as display_code
        FROM contact_submissions c
        LEFT JOIN users u ON c.assigned_to = u.user_id
        WHERE c.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy liên hệ']);
        return;
    }
    
    // Format date
    $contact['created_at'] = date('d/m/Y H:i', strtotime($contact['created_at']));
    
    echo json_encode(['success' => true, 'contact' => $contact]);
}

/**
 * Cập nhật trạng thái liên hệ
 */
function updateStatus($db) {
    $id = (int)($_POST['submission_id'] ?? $_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $note = trim($_POST['note'] ?? '');
    
    $valid_statuses = ['new', 'in_progress', 'resolved', 'closed'];
    
    if (!$id || !in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    // Lấy thông tin liên hệ hiện tại
    $stmt = $db->prepare("SELECT * FROM contact_submissions WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy liên hệ']);
        return;
    }
    
    // Cập nhật trạng thái
    $stmt = $db->prepare("
        UPDATE contact_submissions 
        SET status = :status, 
            assigned_to = :assigned_to,
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':status' => $status,
        ':assigned_to' => $_SESSION['user_id'],
        ':id' => $id
    ]);
    
    // Log activity
    if (function_exists('logActivity')) {
        $status_labels = [
            'new' => 'Mới',
            'in_progress' => 'Đang xử lý',
            'resolved' => 'Đã giải quyết',
            'closed' => 'Đã đóng'
        ];
        logActivity(
            'update_contact_status', 
            'contact', 
            $id, 
            "Cập nhật trạng thái liên hệ #{$id} thành: {$status_labels[$status]}" . ($note ? " - Ghi chú: {$note}" : "")
        );
    }
    
    echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
}

/**
 * Xóa liên hệ
 */
function deleteContact($db) {
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        return;
    }
    
    // Chỉ admin mới được xóa
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ admin mới có quyền xóa']);
        return;
    }
    
    // Lấy thông tin trước khi xóa
    $stmt = $db->prepare("SELECT * FROM contact_submissions WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy liên hệ']);
        return;
    }
    
    // Xóa
    $stmt = $db->prepare("DELETE FROM contact_submissions WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    // Log activity
    if (function_exists('logActivity')) {
        logActivity('delete_contact', 'contact', $id, "Xóa liên hệ #{$id} từ {$contact['name']} ({$contact['email']})");
    }
    
    echo json_encode(['success' => true, 'message' => 'Đã xóa liên hệ']);
}

/**
 * Danh sách liên hệ (cho AJAX)
 */
function listContacts($db) {
    $status = $_GET['status'] ?? 'all';
    $page = (int)($_GET['page'] ?? 1);
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    $where = "1=1";
    $params = [];
    
    if ($status !== 'all') {
        $where .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    // Count
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM contact_submissions WHERE {$where}");
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    
    // Get data
    $stmt = $db->prepare("
        SELECT * FROM contact_submissions 
        WHERE {$where}
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'contacts' => $contacts,
        'total' => $total,
        'pages' => ceil($total / $per_page)
    ]);
}
