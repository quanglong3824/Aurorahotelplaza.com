<?php
session_start();
require_once '../config/database.php';

$post_id = $_GET['id'] ?? null;
$is_edit = !empty($post_id);

$page_title = $is_edit ? 'Sửa bài viết' : 'Viết bài mới';
$page_subtitle = $is_edit ? 'Cập nhật nội dung bài viết' : 'Tạo bài viết mới';

$post = null;
if ($is_edit) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM blog_posts WHERE post_id = :id");
        $stmt->execute([':id' => $post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            header('Location: blog.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("Load post error: " . $e->getMessage());
        header('Location: blog.php');
        exit;
    }
}

include 'includes/admin-header.php';
?>

<div class="mb-6">
    <a href="blog.php" class="btn btn-secondary">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Quay lại
    </a>
</div>

<form action="api/save-post.php" method="POST" class="max-w-4xl">
    <input type="hidden" name="post_id" value="<?php echo $post['post_id'] ?? ''; ?>">
    
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="font-bold text-lg">Thông tin bài viết</h3>
        </div>
        <div class="card-body space-y-4">
            <div class="form-group">
                <label class="form-label">Tiêu đề *</label>
                <input type="text" name="title" class="form-input" 
                       value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Slug (URL thân thiện)</label>
                <input type="text" name="slug" class="form-input" 
                       value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>" 
                       placeholder="tu-dong-tao-neu-de-trong">
            </div>
            
            <div class="form-group">
                <label class="form-label">Mô tả ngắn</label>
                <textarea name="excerpt" class="form-textarea" rows="3"><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nội dung *</label>
                <textarea name="content" class="form-textarea" rows="15" required><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Hỗ trợ HTML</p>
            </div>
        </div>
    </div>
    
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="font-bold text-lg">Hình ảnh & SEO</h3>
        </div>
        <div class="card-body space-y-4">
            <div class="form-group">
                <label class="form-label">Ảnh đại diện (URL)</label>
                <input type="url" name="featured_image" class="form-input" 
                       value="<?php echo htmlspecialchars($post['featured_image'] ?? ''); ?>" 
                       placeholder="https://...">
            </div>
            
            <div class="form-group">
                <label class="form-label">Meta Description (SEO)</label>
                <textarea name="meta_description" class="form-textarea" rows="2"><?php echo htmlspecialchars($post['meta_description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tags (phân cách bằng dấu phẩy)</label>
                <input type="text" name="tags" class="form-input" 
                       value="<?php echo htmlspecialchars($post['tags'] ?? ''); ?>" 
                       placeholder="khách sạn, du lịch, nghỉ dưỡng">
            </div>
        </div>
    </div>
    
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="font-bold text-lg">Tùy chọn xuất bản</h3>
        </div>
        <div class="card-body space-y-4">
            <div class="form-group">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="draft" <?php echo ($post['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Nháp</option>
                    <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Xuất bản</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_featured" value="1" 
                           <?php echo ($post['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                    <span>Bài viết nổi bật</span>
                </label>
            </div>
            
            <div class="form-group">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="allow_comments" value="1" 
                           <?php echo ($post['allow_comments'] ?? 1) ? 'checked' : ''; ?>>
                    <span>Cho phép bình luận</span>
                </label>
            </div>
        </div>
    </div>
    
    <div class="flex justify-end gap-3">
        <a href="blog.php" class="btn btn-secondary">Hủy</a>
        <button type="submit" name="save_draft" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">save</span>
            Lưu nháp
        </button>
        <button type="submit" name="publish" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">publish</span>
            <?php echo $is_edit ? 'Cập nhật' : 'Xuất bản'; ?>
        </button>
    </div>
</form>

<?php include 'includes/admin-footer.php'; ?>
