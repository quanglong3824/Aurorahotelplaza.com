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

if (empty($post_id)) {
    echo json_encode(['success' => false, 'message' => 'ID bài viết không hợp lệ.']);
    exit;
}

try {
    $db = getDB();
    
    // Optional: Also delete related comments
    $stmt_comments = $db->prepare("DELETE FROM blog_comments WHERE post_id = :post_id");
    $stmt_comments->execute([':post_id' => $post_id]);
    
    // Delete post
    $stmt_post = $db->prepare("DELETE FROM blog_posts WHERE post_id = :post_id");
    $stmt_post->execute([':post_id' => $post_id]);
    
    if ($stmt_post->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Bài viết đã được xóa thành công.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài viết để xóa.']);
    }
    
} catch (PDOException $e) {
    error_log('Delete post error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa bài viết.']);
}
?>
