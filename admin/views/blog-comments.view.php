<?php
/**
 * Aurora Hotel Plaza - Blog Comments View
 * Displays blog comments management page
 */
?>

<div class="mb-6 flex flex-col md:flex-row items-center gap-4">
    <form method="GET" class="flex-1 flex flex-col md:flex-row items-center gap-4 w-full">
        <div class="flex-1 search-box w-full">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-input" placeholder="Tìm bình luận...">
        </div>

        <select name="status" class="form-select w-full md:w-56">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Đã duyệt</option>
            <option value="spam" <?php echo $status_filter === 'spam' ? 'selected' : ''; ?>>Spam</option>
            <option value="trash" <?php echo $status_filter === 'trash' ? 'selected' : ''; ?>>Thùng rác</option>
        </select>

        <button type="submit" class="btn btn-secondary w-full md:w-auto">
            <span class="material-symbols-outlined text-sm">filter_list</span>
            Lọc
        </button>
    </form>

    <a href="blog.php" class="btn btn-secondary w-full md:w-auto">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Quay lại Blog
    </a>
</div>

<div class="card shadow-sm border-gray-100">
    <div class="card-body p-0">
        <?php if (empty($comments)): ?>
            <div class="empty-state py-20">
                <span class="material-symbols-outlined empty-state-icon text-6xl text-gray-300 mb-4">comment</span>
                <p class="empty-state-title text-xl font-semibold text-gray-500">Chưa có bình luận nào</p>
                <p class="empty-state-description text-gray-400">Bình luận mới sẽ xuất hiện ở đây để bạn duyệt</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($comments as $c): ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex flex-col lg:flex-row items-start justify-between gap-6">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-3 mb-3">
                                    <span class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($c['author_name']); ?></span>
                                    <span class="text-sm text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"><?php echo htmlspecialchars($c['author_email']); ?></span>
                                    <span class="text-xs text-gray-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">schedule</span>
                                        <?php echo date('m/d/Y H:i', strtotime($c['created_at'])); ?>
                                    </span>
                                </div>

                                <div class="text-gray-700 leading-relaxed mb-4 bg-white p-4 rounded-lg border border-gray-50 italic whitespace-pre-line">
                                    "<?php echo htmlspecialchars($c['content']); ?>"
                                </div>

                                <div class="text-sm flex items-center gap-2 text-gray-500">
                                    <span class="material-symbols-outlined text-sm">article</span>
                                    Bài viết:
                                    <?php if (!empty($c['post_slug'])): ?>
                                        <a class="text-primary font-medium hover:underline flex items-center gap-1" href="../blog-detail.php?slug=<?php echo urlencode($c['post_slug']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($c['post_title'] ?? ''); ?>
                                            <span class="material-symbols-outlined text-xs">open_in_new</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="font-medium text-gray-700"><?php echo htmlspecialchars($c['post_title'] ?? ''); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex flex-row lg:flex-col items-center lg:items-end gap-3 w-full lg:w-auto">
                                <span class="badge badge-<?php 
                                    echo $c['status'] === 'approved' ? 'success' : 
                                        ($c['status'] === 'pending' ? 'warning' : 
                                        ($c['status'] === 'spam' ? 'danger' : 'secondary')); 
                                ?> py-1 px-3">
                                    <?php 
                                        $status_labels = [
                                            'pending' => 'Chờ duyệt',
                                            'approved' => 'Đã duyệt',
                                            'spam' => 'Spam',
                                            'trash' => 'Thùng rác'
                                        ];
                                        echo $status_labels[$c['status']] ?? $c['status'];
                                    ?>
                                </span>

                                <div class="flex flex-wrap gap-2 justify-end">
                                    <?php if ($c['status'] !== 'approved'): ?>
                                        <button class="btn btn-sm btn-success flex items-center gap-1" onclick="updateCommentStatus(<?php echo (int)$c['comment_id']; ?>,'approved')">
                                            <span class="material-symbols-outlined text-sm">check</span>
                                            Duyệt
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($c['status'] !== 'spam' && $c['status'] !== 'approved'): ?>
                                        <button class="btn btn-sm btn-danger flex items-center gap-1" onclick="updateCommentStatus(<?php echo (int)$c['comment_id']; ?>,'spam')">
                                            <span class="material-symbols-outlined text-sm">report</span>
                                            Spam
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($c['status'] !== 'trash' && $c['status'] !== 'approved'): ?>
                                        <button class="btn btn-sm btn-secondary flex items-center gap-1" onclick="updateCommentStatus(<?php echo (int)$c['comment_id']; ?>,'trash')">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                            Thùng rác
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-sm bg-red-100 text-red-600 hover:bg-red-200 border-none flex items-center gap-1" onclick="deleteComment(<?php echo (int)$c['comment_id']; ?>)">
                                        <span class="material-symbols-outlined text-sm">delete_forever</span>
                                        Xóa vĩnh viễn
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
    const statusText = {
        'approved': 'duyệt',
        'spam': 'đánh dấu spam',
        'trash': 'chuyển vào thùng rác'
    };
    
    Swal.fire({
        title: `Xác nhận?`,
        text: `Bạn muốn ${statusText[status]} bình luận này?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/update-comment-status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `comment_id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        showConfirmButton: false,
                        timer: 1000
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Lỗi', data.message || 'Có lỗi xảy ra', 'error');
                }
            });
        }
    });
}

function deleteComment(id) {
    Swal.fire({
        title: 'Xóa vĩnh viễn?',
        text: "Hành động này không thể hoàn tác!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        confirmButtonText: 'Xóa ngay',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/delete-comment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `comment_id=${encodeURIComponent(id)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã xóa',
                        showConfirmButton: false,
                        timer: 1000
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Lỗi', data.message || 'Có lỗi xảy ra', 'error');
                }
            });
        }
    });
}
</script>