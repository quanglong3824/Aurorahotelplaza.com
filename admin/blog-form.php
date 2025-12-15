<?php
session_start();
require_once '../config/database.php';

// Scan uploads folder for images
$uploads_path = realpath(__DIR__ . '/../uploads');
$uploaded_images = [];
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

$post_id = $_GET['id'] ?? null;
$is_edit = !empty($post_id);

$page_title = $is_edit ? 'Sửa bài viết' : 'Viết bài mới';
$page_subtitle = $is_edit ? 'Cập nhật nội dung bài viết' : 'Tạo bài viết mới';

$post = null;
$categories = [];
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
            header('Location: blog.php');
            exit;
        }
    }
} catch (Exception $e) {
    error_log("Load post/categories error: " . $e->getMessage());
    // Redirect or show an error, but for simplicity, we continue
    // so the rest of the page can render.
    if ($is_edit) {
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Slug (URL thân thiện)</label>
                    <input type="text" name="slug" class="form-input" 
                           value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>" 
                           placeholder="tự động tạo nếu để trống">
                </div>
                <div class="form-group">
                    <label class="form-label">Danh mục</label>
                    <select name="category_id" class="form-select">
                        <option value="">Chọn danh mục</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" 
                                <?php echo (isset($post['category_id']) && $post['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Mô tả ngắn</label>
                <textarea name="excerpt" class="form-textarea" rows="3"><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nội dung *</label>
                <textarea name="content" id="content-editor" class="form-textarea" rows="15" required><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Hỗ trợ HTML. Sử dụng trình soạn thảo để định dạng.</p>
            </div>
        </div>
    </div>
    
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="font-bold text-lg">Chọn Layout bài viết</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="layoutSelector">
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="standard" class="hidden" <?php echo ($post['layout'] ?? 'standard') === 'standard' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center">
                            <div class="w-full px-2">
                                <div class="h-8 bg-gray-400 dark:bg-slate-500 rounded mb-1"></div>
                                <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded mb-1 w-3/4"></div>
                                <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded w-1/2"></div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Tiêu chuẩn</p>
                    </div>
                </label>
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="hero" class="hidden" <?php echo ($post['layout'] ?? '') === 'hero' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center">
                            <div class="w-full">
                                <div class="h-10 bg-gray-400 dark:bg-slate-500 rounded-t"></div>
                                <div class="px-2 py-1">
                                    <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded mb-1"></div>
                                    <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded w-2/3"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Hero Banner</p>
                    </div>
                </label>
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="sidebar" class="hidden" <?php echo ($post['layout'] ?? '') === 'sidebar' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center">
                            <div class="w-full px-1 flex gap-1">
                                <div class="flex-1">
                                    <div class="h-3 bg-gray-400 dark:bg-slate-500 rounded mb-1"></div>
                                    <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded mb-1"></div>
                                    <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded w-3/4"></div>
                                </div>
                                <div class="w-1/3 bg-gray-400 dark:bg-slate-500 rounded"></div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Có Sidebar</p>
                    </div>
                </label>
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="gallery" class="hidden" <?php echo ($post['layout'] ?? '') === 'gallery' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center">
                            <div class="w-full px-1">
                                <div class="grid grid-cols-3 gap-0.5 mb-1">
                                    <div class="h-4 bg-gray-400 dark:bg-slate-500 rounded"></div>
                                    <div class="h-4 bg-gray-400 dark:bg-slate-500 rounded"></div>
                                    <div class="h-4 bg-gray-400 dark:bg-slate-500 rounded"></div>
                                </div>
                                <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded mb-1"></div>
                                <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded w-2/3"></div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Gallery</p>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold text-lg">Ảnh đại diện</h3>
            <button type="button" id="uploadNewBtn" class="btn btn-sm btn-secondary">
                <span class="material-symbols-outlined text-sm">upload</span>
                Upload ảnh mới
            </button>
        </div>
        <div class="card-body space-y-4">
            <!-- Selected Image Preview -->
            <div id="selectedImagePreview" class="mb-4 <?php echo empty($post['featured_image']) ? 'hidden' : ''; ?>">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ảnh đã chọn:</p>
                <div class="relative inline-block">
                    <img id="previewImg" src="<?php echo htmlspecialchars($post['featured_image'] ?? ''); ?>" 
                         alt="Preview" class="h-32 w-auto rounded-lg object-cover border-2 border-[#d4af37]">
                    <button type="button" id="clearImageBtn" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
            </div>
            
            <input type="hidden" name="featured_image" id="featuredImageInput" 
                   value="<?php echo htmlspecialchars($post['featured_image'] ?? ''); ?>">
            
            <!-- Upload Zone -->
            <div id="uploadZone" class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-xl p-6 text-center hover:border-[#d4af37] transition-colors cursor-pointer hidden">
                <input type="file" id="fileInput" accept="image/*" class="hidden">
                <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">cloud_upload</span>
                <p class="text-gray-600 dark:text-gray-400">Kéo thả ảnh vào đây hoặc <span class="text-[#d4af37] font-medium">click để chọn</span></p>
                <p class="text-xs text-gray-500 mt-1">Hỗ trợ: JPG, PNG, GIF, WebP (tối đa 5MB)</p>
            </div>
            
            <!-- Upload Progress -->
            <div id="uploadProgress" class="hidden">
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-2 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div id="progressBar" class="h-full bg-[#d4af37] transition-all" style="width: 0%"></div>
                    </div>
                    <span id="progressText" class="text-sm text-gray-600">0%</span>
                </div>
            </div>
            
            <!-- Image Gallery from /uploads -->
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Chọn từ thư viện ảnh đã upload:</p>
                <?php if (empty($uploaded_images)): ?>
                    <div class="text-center py-8 bg-gray-50 dark:bg-slate-800 rounded-xl">
                        <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">photo_library</span>
                        <p class="text-gray-500">Chưa có ảnh nào trong thư viện</p>
                        <p class="text-xs text-gray-400 mt-1">Upload ảnh mới để bắt đầu</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2 max-h-64 overflow-y-auto p-2 bg-gray-50 dark:bg-slate-800 rounded-xl" id="imageGallery">
                        <?php foreach ($uploaded_images as $img): ?>
                            <div class="image-thumb aspect-square rounded-lg overflow-hidden cursor-pointer border-2 border-transparent hover:border-[#d4af37] transition-all relative group"
                                 data-src="../uploads/<?php echo htmlspecialchars($img); ?>">
                                <img src="../uploads/<?php echo htmlspecialchars($img); ?>" 
                                     alt="<?php echo htmlspecialchars($img); ?>"
                                     class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white">check_circle</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="font-bold text-lg">SEO & Tags</h3>
        </div>
        <div class="card-body space-y-4">
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
                <label class="form-label">Ngày xuất bản</label>
                <input type="datetime-local" name="published_at" class="form-input"
                       value="<?php echo isset($post['published_at']) ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : ''; ?>">
                <p class="text-xs text-gray-500 mt-1">Để trống sẽ tự động đặt ngày giờ hiện tại khi xuất bản.</p>
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

<style>
.layout-option input:checked + .layout-card {
    border-color: #d4af37;
    background: rgba(212, 175, 55, 0.1);
}
.image-thumb.selected {
    border-color: #d4af37 !important;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const featuredImageInput = document.getElementById('featuredImageInput');
    const previewImg = document.getElementById('previewImg');
    const selectedImagePreview = document.getElementById('selectedImagePreview');
    const clearImageBtn = document.getElementById('clearImageBtn');
    const uploadNewBtn = document.getElementById('uploadNewBtn');
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const imageGallery = document.getElementById('imageGallery');
    
    // Toggle upload zone
    uploadNewBtn?.addEventListener('click', function() {
        uploadZone.classList.toggle('hidden');
    });
    
    // Click to upload
    uploadZone?.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Drag and drop
    uploadZone?.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-[#d4af37]', 'bg-[#d4af37]/5');
    });
    
    uploadZone?.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-[#d4af37]', 'bg-[#d4af37]/5');
    });
    
    uploadZone?.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-[#d4af37]', 'bg-[#d4af37]/5');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            uploadFile(files[0]);
        }
    });
    
    // File input change
    fileInput?.addEventListener('change', function() {
        if (this.files.length > 0) {
            uploadFile(this.files[0]);
        }
    });
    
    // Upload file function
    function uploadFile(file) {
        if (!file.type.startsWith('image/')) {
            alert('Vui lòng chọn file ảnh!');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('File quá lớn! Tối đa 5MB.');
            return;
        }
        
        const formData = new FormData();
        formData.append('image', file);
        
        uploadProgress.classList.remove('hidden');
        progressBar.style.width = '0%';
        progressText.textContent = '0%';
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/upload-image.php', true);
        
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressText.textContent = percent + '%';
            }
        };
        
        xhr.onload = function() {
            uploadProgress.classList.add('hidden');
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    selectImage(response.url);
                    // Add to gallery
                    addImageToGallery(response.url, response.filename);
                    uploadZone.classList.add('hidden');
                } else {
                    alert(response.message || 'Upload thất bại!');
                }
            } catch (e) {
                alert('Có lỗi xảy ra khi upload!');
            }
        };
        
        xhr.onerror = function() {
            uploadProgress.classList.add('hidden');
            alert('Có lỗi xảy ra khi upload!');
        };
        
        xhr.send(formData);
    }
    
    // Add image to gallery after upload
    function addImageToGallery(url, filename) {
        if (!imageGallery) return;
        
        // Remove empty state if exists
        const emptyState = imageGallery.closest('.card-body')?.querySelector('.text-center.py-8');
        if (emptyState) {
            emptyState.remove();
            // Create gallery container if not exists
            const galleryContainer = document.createElement('div');
            galleryContainer.className = 'grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2 max-h-64 overflow-y-auto p-2 bg-gray-50 dark:bg-slate-800 rounded-xl';
            galleryContainer.id = 'imageGallery';
            imageGallery.closest('.card-body')?.querySelector('div:last-child')?.appendChild(galleryContainer);
        }
        
        const thumb = document.createElement('div');
        thumb.className = 'image-thumb aspect-square rounded-lg overflow-hidden cursor-pointer border-2 border-transparent hover:border-[#d4af37] transition-all relative group selected';
        thumb.dataset.src = url;
        thumb.innerHTML = `
            <img src="${url}" alt="${filename}" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                <span class="material-symbols-outlined text-white">check_circle</span>
            </div>
        `;
        
        // Prepend to gallery
        const gallery = document.getElementById('imageGallery');
        if (gallery) {
            gallery.prepend(thumb);
            bindThumbClick(thumb);
        }
    }
    
    // Select image from gallery
    function selectImage(src) {
        featuredImageInput.value = src;
        previewImg.src = src;
        selectedImagePreview.classList.remove('hidden');
        
        // Update selected state in gallery
        document.querySelectorAll('.image-thumb').forEach(t => t.classList.remove('selected'));
        const selected = document.querySelector(`.image-thumb[data-src="${src}"]`);
        if (selected) selected.classList.add('selected');
    }
    
    // Clear selected image
    clearImageBtn?.addEventListener('click', function() {
        featuredImageInput.value = '';
        previewImg.src = '';
        selectedImagePreview.classList.add('hidden');
        document.querySelectorAll('.image-thumb').forEach(t => t.classList.remove('selected'));
    });
    
    // Bind click to gallery thumbnails
    function bindThumbClick(thumb) {
        thumb.addEventListener('click', function() {
            selectImage(this.dataset.src);
        });
    }
    
    document.querySelectorAll('.image-thumb').forEach(bindThumbClick);
    
    // Mark current selected image
    const currentImage = featuredImageInput.value;
    if (currentImage) {
        const currentThumb = document.querySelector(`.image-thumb[data-src="${currentImage}"]`);
        if (currentThumb) currentThumb.classList.add('selected');
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>
