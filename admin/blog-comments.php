<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý bình luận';
$page_subtitle = 'Duyệt và quản lý bình luận blog';

$status_filter = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';
$post_filter = $_GET['post_id'] ?? '';

$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "c.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(c.content LIKE :search OR c.author_name LIKE :search OR c.author_email LIKE :search OR p.title LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($post_filter)) {
    $where_clauses[] = "c.post_id = :post_id";
    $params[':post_id'] = (int)$post_filter;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();

    $sql = "
        SELECT c.*, p.title as post_title, p.slug as post_slug
        FROM blog_comments c
        LEFT JOIN blog_posts p ON c.post_id = p.post_id
        $where_sql
        ORDER BY c.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Admin blog comments error: ' . $e->getMessage());
    $comments = [];
}

include 'includes/admin-header.php';
?>

<div class="mb-6 flex items-center gap-4">
    <form method="GET" class="flex-1 flex items-center gap-4">
        <div class="flex-1 search-box">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-input" placeholder="Tìm bình luận...">
        </div>

        <select name="status" class="form-select w-56">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Đã duyệt</option>
            <option value="spam" <?php echo $status_filter === 'spam' ? 'selected' : ''; ?>>Spam</option>
            <option value="trash" <?php echo $status_filter === 'trash' ? 'selected' : ''; ?>>Thùng rác</option>
        </select>

        <button type="submit" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">filter_list</span>
            Lọc
        </button>
    </form>

    <a href="blog.php" class="btn btn-secondary">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Quay lại Blog
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($comments)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined empty-state-icon">comment</span>
                <p class="empty-state-title">Chưa có bình luận nào</p>
                <p class="empty-state-description">Bình luận mới sẽ xuất hiện ở đây để bạn duyệt</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($comments as $c): ?>
                    <div class="p-4 bg-gray-50 dark:bg-slate-800 rounded-xl">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-3 mb-2">
                                    <span class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($c['author_name']); ?></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($c['author_email']); ?></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></span>
                                </div>

                                <div class="text-sm text-gray-700 dark:text-gray-300 mb-3 whitespace-pre-line"><?php echo htmlspecialchars($c['content']); ?></div>

                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Bài viết:
                                    <?php if (!empty($c['post_slug'])): ?>
                                        <a class="text-accent hover:underline" href="../blog-detail.php?slug=<?php echo urlencode($c['post_slug']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($c['post_title'] ?? ''); ?>
                                        </a>
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($c['post_title'] ?? ''); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <span class="badge badge-<?php echo $c['status'] === 'approved' ? 'success' : ($c['status'] === 'pending' ? 'warning' : ($c['status'] === 'spam' ? 'danger' : 'secondary')); ?>">
                                    <?php echo $c['status']; ?>
                                </span>

                                <div class="flex flex-wrap gap-2 justify-end">
                                    <?php if ($c['status'] !== 'approved'): ?>
                                        <button class="btn btn-sm btn-success" onclick="updateCommentStatus(<?php echo (int)$c['comment_id']; ?>,'approved')">
                                            <span class="material-symbols-outlined text-sm">check</span>
                                            Duyệt
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($c['status'] !== 'spam'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="updateCommentStatus(<?php echo (int)$c['comment_id']; ?>,'spam')">
                                            <span class="material-symbols-outlined text-sm">report</span>
                                            Spam
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($c['status'] !== 'trash'): ?>
                                        <button class="btn btn-sm btn-secondary" onclick="updateCommentStatus(<?php echo (int)$c['comment_id']; ?>,'trash')">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                            Thùng rác
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteComment(<?php echo (int)$c['comment_id']; ?>)">
                                        <span class="material-symbols-outlined text-sm">delete_forever</span>
                                        Xóa
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateCommentStatus(id, status) {
    fetch('api/update-comment-status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `comment_id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`
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

function deleteComment(id) {
    if (!confirm('Bạn có chắc muốn xóa bình luận này?')) return;

    fetch('api/delete-comment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `comment_id=${encodeURIComponent(id)}`
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
