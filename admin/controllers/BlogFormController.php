<?php
/**
 * Aurora Hotel Plaza - Blog Form Controller
 * Handles data fetching for blog post creation and editing
 */

function getBlogFormData() {
    $post_id = $_GET['id'] ?? null;
    $is_edit = !empty($post_id);

    $post = null;
    $categories = [];
    $db_error = null;
    $uploaded_images = [];

    try {
        $db = getDB();
        
        // Fetch categories
        $stmt_cat = $db->query("SELECT * FROM blog_categories ORDER BY category_name ASC");
        $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

        if ($is_edit) {
            $stmt = $db->prepare("SELECT * FROM blog_posts WHERE post_id = :id");
            $stmt->execute([':id' => $post_id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$post) {
                $db_error = "Không tìm thấy bài viết với ID: " . htmlspecialchars($post_id);
            }
        }

        // Scan uploads folder for images
        $uploads_path = realpath(__DIR__ . '/../../uploads');
        if ($uploads_path && is_dir($uploads_path)) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            $files = scandir($uploads_path);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_ext)) {
                    $uploaded_images[] = $file;
                }
            }
            // Sort by modification time (newest first)
            usort($uploaded_images, function($a, $b) use ($uploads_path) {
                return filemtime($uploads_path . '/' . $b) - filemtime($uploads_path . '/' . $a);
            });
        }

    } catch (Exception $e) {
        error_log("Blog form controller error: " . $e->getMessage());
        $db_error = "Lỗi database: " . $e->getMessage();
    }

    return [
        'post' => $post,
        'categories' => $categories,
        'is_edit' => $is_edit,
        'db_error' => $db_error,
        'uploaded_images' => $uploaded_images
    ];
}
