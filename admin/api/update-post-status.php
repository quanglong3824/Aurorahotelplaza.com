<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit;
}

$post_id = $_POST['post_id'] ?? null;
$status = $_POST['status'] ?? null;

if (empty($post_id) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

if (!in_array($status, ['draft', 'published'])) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ.']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE blog_posts SET status = :status, updated_at = NOW() WHERE post_id = :post_id");
    $stmt->execute([
        ':status' => $status,
        ':post_id' => $post_id
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Trạng thái bài viết đã được cập nhật.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài viết hoặc không có gì thay đổi.']);
    }
    
} catch (PDOException $e) {
    error_log('Update post status error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật trạng thái.']);
}
?>
