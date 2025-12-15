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

if (empty($comment_id)) {
    echo json_encode(['success' => false, 'message' => 'ID bình luận không hợp lệ.']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM blog_comments WHERE comment_id = :comment_id");
    $stmt->execute([':comment_id' => $comment_id]);

    echo json_encode(['success' => true, 'message' => 'Bình luận đã được xóa.']);
} catch (PDOException $e) {
    error_log('Delete comment error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa bình luận.']);
}
