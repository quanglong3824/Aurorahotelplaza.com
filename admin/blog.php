<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý Blog';
$page_subtitle = 'Bài viết và tin tức';

// Filters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(title LIKE :search OR content LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    $sql = "
        SELECT bp.*, u.full_name as author_name,
               (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.post_id) as comment_count
        FROM blog_posts bp
        LEFT JOIN users u ON bp.author_id = u.user_id
        $where_sql
        ORDER BY bp.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Blog error: " . $e->getMessage());
    $posts = [];
}

include 'includes/admin-header.php';
?>

<!-- Filters -->
<div class="mb-6 flex items-center gap-4">
    <form method="GET" class="flex-1 flex items-center gap-4">
        <div class="flex-1 search-box">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   class="form-input" placeholder="Tìm bài viết...">
        </div>
        
        <select name="status" class="form-select w-48">
            <option value="all">Tất cả trạng thái</option>
            <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Nháp</option>
            <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Đã xuất bản</option>
        </select>
        
        <button type="submit" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">filter_list</span>
            Lọc
        </button>
    </form>

    <a href="blog-comments.php" class="btn btn-secondary">
        <span class="material-symbols-outlined text-sm">comment</span>
        Bình luận
    </a>
    
    <a href="blog-form.php" class="btn btn-primary">
        <span class="material-symbols-outlined text-sm">add</span>
        Viết bài mới
    </a>
</div>

<!-- Posts List -->
<div class="card">
    <div class="card-body">
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined empty-state-icon">article</span>
                <p class="empty-state-title">Chưa có bài viết nào</p>
                <p class="empty-state-description">Tạo bài viết đầu tiên để chia sẻ với khách hàng</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($posts as $post): ?>
                    <div class="flex gap-4 p-4 bg-gray-50 dark:bg-slate-800 rounded-xl hover:shadow-md transition-all">
                        <?php 
                            $img_src = $post['featured_image'];
                            // Fix path for admin display
                            if ($img_src && strpos($img_src, 'uploads/') === 0) {
                                $img_src = '../' . $img_src;
                            }
                        ?>
                        <?php if ($post['featured_image']): ?>
                            <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                 alt="Featured" class="w-32 h-24 object-cover rounded-lg flex-shrink-0">
                        <?php else: ?>
                            <div class="w-32 h-24 bg-gray-200 dark:bg-slate-700 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-gray-400">image</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($post['title']); ?></h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                        <?php echo htmlspecialchars(mb_substr(strip_tags($post['content']), 0, 150)); ?>...
                                    </p>
                                </div>
                                <span class="badge badge-<?php echo $post['status'] === 'published' ? 'success' : 'warning'; ?>">
                                    <?php echo $post['status'] === 'published' ? 'Đã xuất bản' : 'Nháp'; ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center gap-4 text-sm text-gray-500 mb-3">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">person</span>
                                    <?php echo htmlspecialchars($post['author_name']); ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                                    <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">comment</span>
                                    <?php echo $post['comment_count']; ?> bình luận
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    <?php echo number_format($post['views']); ?> lượt xem
                                </span>
                            </div>
                            
                            <div class="flex gap-2">
                                <a href="blog-form.php?id=<?php echo $post['post_id']; ?>" 
                                   class="btn btn-sm btn-secondary">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                    Sửa
                                </a>
                                <a href="blog-comments.php?post_id=<?php echo $post['post_id']; ?>&status=all" 
                                   class="btn btn-sm btn-info">
                                    <span class="material-symbols-outlined text-sm">comment</span>
                                    <?php echo $post['comment_count']; ?>
                                </a>
                                <?php if ($post['status'] === 'draft'): ?>
                                    <button onclick="publishPost(<?php echo $post['post_id']; ?>)" 
                                            class="btn btn-sm btn-success">
                                        <span class="material-symbols-outlined text-sm">publish</span>
                                        Xuất bản
                                    </button>
                                <?php endif; ?>
                                <button onclick="deletePost(<?php echo $post['post_id']; ?>)" 
                                        class="btn btn-sm btn-danger">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                    Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function publishPost(id) {
    if (!confirm('Xuất bản bài viết này?')) return;
    
    fetch('api/update-post-status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `post_id=${id}&status=published`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    });
}

function deletePost(id) {
    if (!confirm('Bạn có chắc muốn xóa bài viết này?')) return;
    
    fetch('api/delete-post.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'post_id=' + id
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    });
}
</script>

<?php include 'includes/admin-footer.php'; ?>
