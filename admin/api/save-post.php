<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/functions.php';

/**
 * Tạo slug từ title
 */
function create_slug($string) {
    $search = [
        '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#',
        '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
        '#(ì|í|ị|ỉ|ĩ)#',
        '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
        '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#',
        '#(ỳ|ý|ỵ|ỷ|ỹ)#',
        '#(đ)#',
        '#(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)#',
        '#(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)#',
        '#(Ì|Í|Ị|Ỉ|Ĩ)#',
        '#(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)#',
        '#(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)#',
        '#(Ỳ|Ý|Ỵ|Ỷ|Ỹ)#',
        '#(Đ)#',
        "/[^a-zA-Z0-9\s-]/",
        "/\s+/",
        "/-+/"
    ];
    $replace = [
        'a', 'e', 'i', 'o', 'u', 'y', 'd',
        'A', 'E', 'I', 'O', 'U', 'Y', 'D',
        '', '-', '-'
    ];
    $string = preg_replace($search, $replace, $string);
    $string = strtolower($string);
    $string = trim($string, '-');
    return $string;
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này.']);
}

if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'sale', 'receptionist'])) {
    jsonResponse(['success' => false, 'message' => 'Bạn không có quyền thực hiện chức năng này.']);
}

// Lấy dữ liệu từ POST
$post_id = !empty($_POST['post_id']) ? $_POST['post_id'] : null;
$title = sanitize($_POST['title']);
$slug = !empty($_POST['slug']) ? sanitize($_POST['slug']) : create_slug($title);
$category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$excerpt = sanitize($_POST['excerpt']);
$content = $_POST['content']; 
$status = sanitize($_POST['status']);
$featured_image = sanitize($_POST['featured_image']);
// $meta_description = sanitize($_POST['meta_description']);
$tags = sanitize($_POST['tags']);
$layout = sanitize($_POST['layout'] ?? 'standard');
$gallery_images = $_POST['gallery_images'] ?? '';
$is_featured = isset($_POST['is_featured']) ? 1 : 0;
$allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
$author_id = $_SESSION['user_id'];
$published_at = !empty($_POST['published_at']) ? date('Y-m-d H:i:s', strtotime($_POST['published_at'])) : null;

// Xác định nút nào được nhấn
$action = 'save_draft';
if (isset($_POST['publish'])) {
    $action = 'publish';
    $status = 'published';
    if (empty($published_at)) {
        $published_at = date('Y-m-d H:i:s');
    }
}

$db = getDB();

if ($post_id) {
    // Cập nhật bài viết
    $sql = "UPDATE blog_posts SET 
                title = :title, 
                slug = :slug, 
                category_id = :category_id,
                excerpt = :excerpt, 
                content = :content, 
                status = :status, 
                featured_image = :featured_image, 
                tags = :tags, 
                layout = :layout,
                gallery_images = :gallery_images,
                is_featured = :is_featured, 
                allow_comments = :allow_comments,
                published_at = :published_at,
                updated_at = NOW()
            WHERE post_id = :post_id";
    $stmt = $db->prepare($sql);
    $params = [
        ':title' => $title,
        ':slug' => $slug,
        ':category_id' => $category_id,
        ':excerpt' => $excerpt,
        ':content' => $content,
        ':status' => $status,
        ':featured_image' => $featured_image,
        ':tags' => $tags,
        ':layout' => $layout,
        ':gallery_images' => $gallery_images,
        ':is_featured' => $is_featured,
        ':allow_comments' => $allow_comments,
        ':published_at' => $published_at,
        ':post_id' => $post_id
    ];
} else {
    // Tạo bài viết mới
    $sql = "INSERT INTO blog_posts 
                (author_id, title, slug, category_id, excerpt, content, status, featured_image, tags, layout, gallery_images, is_featured, allow_comments, published_at, created_at, updated_at) 
            VALUES 
                (:author_id, :title, :slug, :category_id, :excerpt, :content, :status, :featured_image, :tags, :layout, :gallery_images, :is_featured, :allow_comments, :published_at, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    $params = [
        ':author_id' => $author_id,
        ':title' => $title,
        ':slug' => $slug,
        ':category_id' => $category_id,
        ':excerpt' => $excerpt,
        ':content' => $content,
        ':status' => $status,
        ':featured_image' => $featured_image,
        ':tags' => $tags,
        ':layout' => $layout,
        ':gallery_images' => $gallery_images,
        ':is_featured' => $is_featured,
        ':allow_comments' => $allow_comments,
        ':published_at' => $published_at,
    ];
}

try {
    $stmt->execute($params);
    $new_post_id = $post_id ? $post_id : $db->lastInsertId();
    
    $_SESSION['success_message'] = 'Bài viết đã được lưu thành công!';
    header('Location: ../blog-form.php?id=' . $new_post_id);
    exit();

} catch (PDOException $e) {
    error_log('Save post error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Có lỗi xảy ra khi lưu bài viết: ' . $e->getMessage();
    header('Location: ../blog-form.php' . ($post_id ? '?id=' . $post_id : ''));
    exit();
}
?>
