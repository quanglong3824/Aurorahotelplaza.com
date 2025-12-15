<?php
session_start();
require_once '../../helpers/session-helper.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'sale', 'receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện chức năng này.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit;
}

$comment_id = $_POST['comment_id'] ?? null;
$status = $_POST['status'] ?? null;

if (empty($comment_id) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

if (!in_array($status, ['pending', 'approved', 'spam', 'trash'])) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ.']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE blog_comments SET status = :status, updated_at = NOW() WHERE comment_id = :comment_id");
    $stmt->execute([
        ':status' => $status,
        ':comment_id' => $comment_id,
    ]);

    echo json_encode(['success' => true, 'message' => 'Đã cập nhật trạng thái bình luận.']);
} catch (PDOException $e) {
    error_log('Update comment status error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật trạng thái.']);
}
