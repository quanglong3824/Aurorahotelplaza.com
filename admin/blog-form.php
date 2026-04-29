<?php
session_start();
require_once '../config/database.php';

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
$db_error = null;

try {
    $db = getDB();
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
} catch (Exception $e) {
    error_log("Load post/categories error: " . $e->getMessage());
    $db_error = "Lỗi database: " . $e->getMessage();
}

include 'includes/admin-header.php';
?>

<div class="mb-6">
    <a href="blog.php" class="btn btn-secondary">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Quay lại danh sách
    </a>
</div>

<?php if ($db_error): ?>
<div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-700 dark:text-red-300">
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <strong>Lỗi:</strong> <?php echo $db_error; ?>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-700 dark:text-red-300">
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
    </div>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-700 dark:text-green-300">
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
    </div>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<form action="api/save-post.php" method="POST" id="blogForm" class="space-y-6">
    <input type="hidden" name="post_id" value="<?php echo $post['post_id'] ?? ''; ?>">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">edit_note</span>
                        Nội dung bài viết
                    </h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="form-group">
                        <label class="form-label font-semibold">Tiêu đề bài viết <span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="titleInput" class="form-input text-xl font-semibold" 
                               value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" 
                               placeholder="Nhập tiêu đề bài viết..." required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Slug (URL)</label>
                            <input type="text" name="slug" id="slugInput" class="form-input" 
                                   value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>" 
                                   placeholder="tự-tao-slug-neu-de-trong">
                            <p class="text-xs text-gray-500 mt-1">Để trống sẽ tự tạo từ tiêu đề</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Danh mục</label>
                            <select name="category_id" class="form-select">
                                <option value="">-- Chọn danh mục --</option>
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
                        <label class="form-label font-semibold">Mô tả ngắn (Excerpt)</label>
                        <textarea name="excerpt" id="excerptInput" class="form-textarea" rows="3" 
                                  placeholder="Mô tả ngắn hiển thị ở danh sách bài viết..."><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Tối đa 500 ký tự, dùng cho SEO và preview</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label font-semibold">Nội dung bài viết <span class="text-red-500">*</span></label>
                        <textarea name="content" id="contentEditor" class="hidden"><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                        <div id="tinymceEditor"></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">style</span>
                        Layout hiển thị
                    </h3>
                    <p class="text-sm text-gray-500">Chọn kiểu hiển thị phù hợp với nội dung</p>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3" id="layoutSelector">
                        <?php 
                        $layouts = [
                            'standard' => ['name' => 'Tiêu chuẩn', 'desc' => 'Ảnh + nội dung'],
                            'hero' => ['name' => 'Hero Banner', 'desc' => 'Ảnh lớn trên đầu'],
                            'gallery' => ['name' => 'Gallery', 'desc' => 'Nhiều ảnh grid'],
                            'slider' => ['name' => 'Slider', 'desc' => 'Trình chiếu ảnh'],
                            'apartment' => ['name' => 'Căn hộ', 'desc' => 'Ảnh + thumbnail'],
                            'magazine' => ['name' => 'Magazine', 'desc' => 'Ảnh + excerpt'],
                            'video' => ['name' => 'Video', 'desc' => 'YouTube/Vimeo'],
                            'timeline' => ['name' => 'Timeline', 'desc' => 'Theo mốc thời gian'],
                            'masonry' => ['name' => 'Masonry', 'desc' => 'Pinterest style'],
                        ];
                        $current_layout = $post['layout'] ?? 'standard';
                        foreach ($layouts as $layout_key => $layout_info):
                        ?>
                        <label class="layout-option cursor-pointer block">
                            <input type="radio" name="layout" value="<?php echo $layout_key; ?>" 
                                   class="hidden" <?php echo $current_layout === $layout_key ? 'checked' : ''; ?>>
                            <div class="border-2 rounded-xl p-3 transition-all hover:border-[#d4af37] layout-card text-center">
                                <div class="w-12 h-12 mx-auto mb-2 bg-gray-200 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-gray-500 dark:text-gray-400">
                                        <?php echo $layout_key === 'video' ? 'play_circle' : 
                                               ($layout_key === 'gallery' || $layout_key === 'masonry' ? 'grid_view' :
                                               ($layout_key === 'slider' ? 'view_carousel' :
                                               ($layout_key === 'timeline' ? 'timeline' : 'image'))); ?>
                                    </span>
                                </div>
                                <p class="text-sm font-medium"><?php echo $layout_info['name']; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $layout_info['desc']; ?></p>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div id="videoUrlSection" class="mt-4 <?php echo $current_layout !== 'video' ? 'hidden' : ''; ?>">
                        <label class="form-label">URL Video (YouTube/Vimeo)</label>
                        <input type="url" name="video_url" id="videoUrlInput" class="form-input" 
                               value="<?php echo htmlspecialchars($post['video_url'] ?? ''); ?>" 
                               placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">image</span>
                        Hình ảnh
                    </h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="form-group">
                        <label class="form-label font-semibold">Ảnh đại diện <span class="text-red-500">*</span></label>
                        <div id="featuredImagePreview" class="w-full aspect-video bg-gray-100 dark:bg-slate-700 rounded-xl overflow-hidden mb-3 flex items-center justify-center cursor-pointer hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors relative group" onclick="openImageModal('featured')">
                            <?php 
                            $featured_img = $post['featured_image'] ?? '';
                            $display_featured = $featured_img;
                            if ($featured_img && strpos($featured_img, 'uploads/') === 0) {
                                $display_featured = '../' . $featured_img;
                            }
                            ?>
                            <?php if ($featured_img): ?>
                                <img src="<?php echo htmlspecialchars($display_featured); ?>" 
                                     class="w-full h-full object-cover" id="featuredPreviewImg">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-3xl">edit</span>
                                </div>
                            <?php else: ?>
                                <div class="text-center" id="featuredPlaceholder">
                                    <span class="material-symbols-outlined text-4xl text-gray-400">add_photo_alternate</span>
                                    <p class="text-sm text-gray-500 mt-1">Click để chọn ảnh</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="featured_image" id="featuredImageInput" value="<?php echo htmlspecialchars($featured_img); ?>">
                        <div class="flex gap-2">
                            <button type="button" class="btn btn-sm btn-secondary flex-1" onclick="openImageModal('featured')">
                                <span class="material-symbols-outlined text-sm">photo_library</span>
                                Chọn từ thư viện
                            </button>
                            <button type="button" id="clearFeaturedBtn" class="btn btn-sm btn-danger <?php echo !$featured_img ? 'hidden' : ''; ?>" onclick="clearFeaturedImage()">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700">

                    <div class="form-group" id="gallerySection">
                        <label class="form-label">Ảnh Gallery (cho Slider/Gallery/Apartment)</label>
                        <div id="galleryList" class="grid grid-cols-3 gap-2 mb-3 min-h-[100px]">
                            <?php 
                            $gallery_arr = [];
                            if (!empty($post['gallery_images'])) {
                                $gallery_arr = json_decode($post['gallery_images'], true) ?: [];
                            }
                            foreach ($gallery_arr as $index => $gimg):
                                $display_gimg = strpos($gimg, 'uploads/') === 0 ? '../' . $gimg : $gimg;
                            ?>
                            <div class="gallery-thumb aspect-square rounded-lg overflow-hidden relative group cursor-pointer" data-src="<?php echo htmlspecialchars($gimg); ?>">
                                <img src="<?php echo htmlspecialchars($display_gimg); ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <button type="button" class="w-6 h-6 bg-red-500 rounded-full text-white" onclick="removeGalleryImage(this)">
                                        <span class="material-symbols-outlined text-xs">close</span>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="galleryPlaceholder" class="text-center py-4 bg-gray-50 dark:bg-slate-800 rounded-lg <?php echo !empty($gallery_arr) ? 'hidden' : ''; ?>">
                            <span class="material-symbols-outlined text-gray-400">collections</span>
                            <p class="text-sm text-gray-500 mt-1">Chưa có ảnh gallery</p>
                        </div>
                        <input type="hidden" name="gallery_images" id="galleryImagesInput" value="<?php echo htmlspecialchars($post['gallery_images'] ?? ''); ?>">
                        <button type="button" class="btn btn-sm btn-secondary w-full" onclick="openImageModal('gallery')">
                            <span class="material-symbols-outlined text-sm">add_photo_alternate</span>
                            Thêm ảnh gallery
                        </button>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700">

                    <div class="form-group">
                        <label class="form-label">Upload ảnh mới</label>
                        <div id="uploadZone" class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-xl p-4 text-center hover:border-[#d4af37] transition-colors cursor-pointer">
                            <input type="file" id="fileInput" accept="image/*" multiple class="hidden">
                            <span class="material-symbols-outlined text-2xl text-gray-400">cloud_upload</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Kéo thả hoặc click</p>
                            <p class="text-xs text-gray-500">JPG, PNG, GIF, WebP (max 5MB)</p>
                        </div>
                        <div id="uploadProgress" class="hidden mt-2 space-y-1"></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">tag</span>
                        SEO & Tags
                    </h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="form-group">
                        <label class="form-label">Tags (phân cách bằng dấu phẩy)</label>
                        <input type="text" name="tags" class="form-input" 
                               value="<?php echo htmlspecialchars($post['tags'] ?? ''); ?>" 
                               placeholder="khách sạn, du lịch, biên hòa">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">settings</span>
                        Xuất bản
                    </h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?php echo ($post['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Nháp (Draft)</option>
                            <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Xuất bản (Published)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ngày xuất bản</label>
                        <input type="datetime-local" name="published_at" class="form-input"
                               value="<?php echo isset($post['published_at']) && $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : ''; ?>">
                        <p class="text-xs text-gray-500 mt-1">Để trống = thời gian hiện tại</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="flex items-center gap-3 cursor-pointer p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                            <input type="checkbox" name="is_featured" value="1" class="w-5 h-5 accent-[#d4af37]"
                                   <?php echo ($post['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                            <span class="material-symbols-outlined text-[#d4af37]">star</span>
                            <span>Bài viết nổi bật</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="flex items-center gap-3 cursor-pointer p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                            <input type="checkbox" name="allow_comments" value="1" class="w-5 h-5 accent-[#d4af37]"
                                   <?php echo ($post['allow_comments'] ?? 1) ? 'checked' : ''; ?>>
                            <span class="material-symbols-outlined text-[#d4af37]">comment</span>
                            <span>Cho phép bình luận</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <button type="submit" name="publish" class="btn btn-primary w-full">
                    <span class="material-symbols-outlined">publish</span>
                    <?php echo $is_edit ? 'Cập nhật bài viết' : 'Xuất bản ngay'; ?>
                </button>
                <button type="submit" name="save_draft" class="btn btn-secondary w-full">
                    <span class="material-symbols-outlined">save</span>
                    Lưu nháp
                </button>
                <a href="blog.php" class="btn btn-outline w-full text-center">
                    Hủy
                </a>
            </div>
        </div>
    </div>
</form>

<div id="imageSelectModal" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-4xl w-full max-h-[85vh] overflow-hidden shadow-2xl">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-[#d4af37]">photo_library</span>
                Chọn ảnh từ thư viện
            </h3>
            <button type="button" onclick="closeImageModal()" class="w-8 h-8 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-4 overflow-y-auto" style="max-height: calc(85vh - 80px);">
            <?php if (empty($uploaded_images)): ?>
                <div class="text-center py-12">
                    <span class="material-symbols-outlined text-5xl text-gray-400 mb-3">photo_library</span>
                    <p class="text-gray-500">Chưa có ảnh trong thư viện</p>
                    <p class="text-sm text-gray-400 mt-1">Upload ảnh mới bên dưới</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3" id="modalImageGrid">
                    <?php foreach ($uploaded_images as $img): ?>
                    <div class="modal-image-item aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-[#d4af37] transition-all relative group cursor-pointer"
                         data-src="uploads/<?php echo htmlspecialchars($img); ?>"
                         onclick="selectModalImage(this)">
                        <img src="../uploads/<?php echo htmlspecialchars($img); ?>" 
                             alt="<?php echo htmlspecialchars($img); ?>"
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-[#d4af37]/20 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                        <div class="absolute top-1 right-1 w-5 h-5 rounded-full bg-white/80 items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hidden selected-check">
                            <span class="material-symbols-outlined text-[#d4af37] text-xs">check</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-between">
            <span class="text-sm text-gray-500" id="modalImageCount"><?php echo count($uploaded_images); ?> ảnh</span>
            <button type="button" onclick="closeImageModal()" class="btn btn-secondary">Đóng</button>
        </div>
    </div>
</div>

<style>
.layout-option input:checked + .layout-card {
    border-color: #d4af37;
    background: rgba(212, 175, 55, 0.1);
}
.layout-card:hover {
    transform: translateY(-2px);
}
.gallery-thumb {
    border: 2px solid #d4af37;
}
.modal-image-item.selected {
    border-color: #d4af37;
    background: rgba(212, 175, 55, 0.1);
}
.modal-image-item.selected .selected-check {
    display: flex;
    opacity: 1;
}
</style>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#tinymceEditor',
        height: 400,
        language: 'vi_VN',
        language_url: 'https://cdn.jsdelivr.net/npm/tinymce-lang@7.7.1/langs7/vi_VN.js',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code fullscreen',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat | code fullscreen',
        menubar: true,
        images_upload_url: 'api/upload-image.php',
        automatic_uploads: true,
        images_upload_handler: function(blobInfo, success, failure) {
            var formData = new FormData();
            formData.append('image', blobInfo.blob(), blobInfo.filename());
            
            fetch('api/upload-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    success(result.url);
                } else {
                    failure(result.message || 'Upload failed');
                }
            })
            .catch(error => {
                failure('Network error: ' + error.message);
            });
        },
        setup: function(editor) {
            editor.on('change', function() {
                document.getElementById('contentEditor').value = editor.getContent();
            });
            editor.on('init', function() {
                var existingContent = document.getElementById('contentEditor').value;
                if (existingContent) {
                    editor.setContent(existingContent);
                }
            });
        },
        content_style: 'body { font-family: Be Vietnam Pro, Inter, sans-serif; font-size: 14px; line-height: 1.6; }'
    });

    var titleInput = document.getElementById('titleInput');
    var slugInput = document.getElementById('slugInput');
    
    titleInput.addEventListener('input', function() {
        if (!slugInput.value) {
            slugInput.value = generateSlug(this.value);
        }
    });
    
    function generateSlug(text) {
        return text.toLowerCase()
            .replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, 'a')
            .replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, 'e')
            .replace(/ì|í|ị|ỉ|ĩ/g, 'i')
            .replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, 'o')
            .replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, 'u')
            .replace(/ỳ|ý|ỵ|ỷ|ỹ/g, 'y')
            .replace(/đ/g, 'd')
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    }

    document.querySelectorAll('input[name="layout"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var videoSection = document.getElementById('videoUrlSection');
            videoSection.classList.toggle('hidden', this.value !== 'video');
        });
    });

    var uploadZone = document.getElementById('uploadZone');
    var fileInput = document.getElementById('fileInput');
    var uploadProgress = document.getElementById('uploadProgress');

    uploadZone.addEventListener('click', function() {
        fileInput.click();
    });

    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-[#d4af37]', 'bg-[#d4af37]/5');
    });

    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-[#d4af37]', 'bg-[#d4af37]/5');
    });

    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-[#d4af37]', 'bg-[#d4af37]/5');
        handleFileUpload(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', function() {
        handleFileUpload(this.files);
    });

    function handleFileUpload(files) {
        var validFiles = Array.from(files).filter(function(file) {
            return file.type.startsWith('image/') && file.size <= 5 * 1024 * 1024;
        });

        if (validFiles.length === 0) {
            alert('Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WebP) tối đa 5MB');
            return;
        }

        uploadProgress.classList.remove('hidden');
        uploadProgress.innerHTML = '';

        validFiles.forEach(function(file, index) {
            var progressItem = document.createElement('div');
            progressItem.className = 'flex items-center gap-2 p-2 bg-gray-50 dark:bg-slate-800 rounded-lg';
            progressItem.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin text-[#d4af37]">sync</span>' +
                                      '<span class="text-sm truncate">' + file.name + '</span>' +
                                      '<span class="text-xs text-gray-500 ml-auto">Uploading...</span>';
            uploadProgress.appendChild(progressItem);

            var formData = new FormData();
            formData.append('image', file);

            fetch('api/upload-image.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    progressItem.innerHTML = '<span class="material-symbols-outlined text-sm text-green-500">check_circle</span>' +
                                              '<span class="text-sm truncate">' + file.name + '</span>' +
                                              '<span class="text-xs text-green-500 ml-auto">Done</span>';
                    addImageToGallery(result.url, result.filename);
                } else {
                    progressItem.innerHTML = '<span class="material-symbols-outlined text-sm text-red-500">error</span>' +
                                              '<span class="text-sm truncate">' + file.name + '</span>' +
                                              '<span class="text-xs text-red-500 ml-auto">' + (result.message || 'Error') + '</span>';
                }
            })
            .catch(function(error) {
                progressItem.innerHTML = '<span class="material-symbols-outlined text-sm text-red-500">error</span>' +
                                          '<span class="text-sm truncate">' + file.name + '</span>' +
                                          '<span class="text-xs text-red-500 ml-auto">Network error</span>';
            });
        });

        setTimeout(function() {
            uploadProgress.classList.add('hidden');
        }, 3000);
    }

    function addImageToGallery(url, filename) {
        var modalGrid = document.getElementById('modalImageGrid');
        if (!modalGrid) return;

        var displayUrl = url.startsWith('uploads/') ? '../' + url : url;

        var newImg = document.createElement('div');
        newImg.className = 'modal-image-item aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-[#d4af37] transition-all relative group cursor-pointer';
        newImg.dataset.src = url;
        newImg.onclick = function() { selectModalImage(this); };
        newImg.innerHTML = '<img src="' + displayUrl + '" alt="' + filename + '" class="w-full h-full object-cover">' +
                           '<div class="absolute inset-0 bg-[#d4af37]/20 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>';
        
        modalGrid.prepend(newImg);
        
        var modalCount = document.getElementById('modalImageCount');
        if (modalCount) {
            var currentCount = parseInt(modalCount.textContent) || 0;
            modalCount.textContent = (currentCount + 1) + ' ảnh';
        }
    }
});

var currentSelectMode = 'featured';

function openImageModal(mode) {
    currentSelectMode = mode;
    document.getElementById('imageSelectModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('imageSelectModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function selectModalImage(element) {
    var src = element.dataset.src;
    var displaySrc = src.startsWith('uploads/') ? '../' + src : src;

    if (currentSelectMode === 'featured') {
        var preview = document.getElementById('featuredPreviewImg');
        var placeholder = document.getElementById('featuredPlaceholder');
        var input = document.getElementById('featuredImageInput');
        var clearBtn = document.getElementById('clearFeaturedBtn');
        var previewContainer = document.getElementById('featuredImagePreview');

        if (!preview) {
            preview = document.createElement('img');
            preview.id = 'featuredPreviewImg';
            preview.className = 'w-full h-full object-cover';
            previewContainer.appendChild(preview);
        }
        
        if (placeholder) {
            placeholder.remove();
        }

        preview.src = displaySrc;
        input.value = src;
        clearBtn.classList.remove('hidden');

        var checkDiv = previewContainer.querySelector('.selected-check');
        if (!checkDiv) {
            checkDiv = document.createElement('div');
            checkDiv.className = 'absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center';
            checkDiv.innerHTML = '<span class="material-symbols-outlined text-white text-3xl">edit</span>';
            previewContainer.appendChild(checkDiv);
        }
    } else if (currentSelectMode === 'gallery') {
        var galleryInput = document.getElementById('galleryImagesInput');
        var galleryList = document.getElementById('galleryList');
        var galleryPlaceholder = document.getElementById('galleryPlaceholder');

        var currentGallery = [];
        if (galleryInput.value) {
            try {
                currentGallery = JSON.parse(galleryInput.value);
            } catch(e) {
                currentGallery = [];
            }
        }

        if (currentGallery.includes(src)) {
            alert('Ảnh này đã có trong gallery!');
            return;
        }

        currentGallery.push(src);
        galleryInput.value = JSON.stringify(currentGallery);

        if (galleryPlaceholder) {
            galleryPlaceholder.classList.add('hidden');
        }

        var newThumb = document.createElement('div');
        newThumb.className = 'gallery-thumb aspect-square rounded-lg overflow-hidden relative group cursor-pointer';
        newThumb.dataset.src = src;
        newThumb.innerHTML = '<img src="' + displaySrc + '" class="w-full h-full object-cover">' +
                              '<div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">' +
                              '<button type="button" class="w-6 h-6 bg-red-500 rounded-full text-white" onclick="removeGalleryImage(this)">' +
                              '<span class="material-symbols-outlined text-xs">close</span></button></div>';
        galleryList.appendChild(newThumb);
    }

    closeImageModal();
}

function clearFeaturedImage() {
    var preview = document.getElementById('featuredPreviewImg');
    var input = document.getElementById('featuredImageInput');
    var clearBtn = document.getElementById('clearFeaturedBtn');
    var previewContainer = document.getElementById('featuredImagePreview');

    if (preview) {
        preview.remove();
    }

    input.value = '';
    clearBtn.classList.add('hidden');

    var checkDiv = previewContainer.querySelector('.absolute');
    if (checkDiv) {
        checkDiv.remove();
    }

    var placeholder = document.createElement('div');
    placeholder.id = 'featuredPlaceholder';
    placeholder.className = 'text-center';
    placeholder.innerHTML = '<span class="material-symbols-outlined text-4xl text-gray-400">add_photo_alternate</span>' +
                             '<p class="text-sm text-gray-500 mt-1">Click để chọn ảnh</p>';
    previewContainer.appendChild(placeholder);
}

function removeGalleryImage(btn) {
    var thumb = btn.closest('.gallery-thumb');
    var src = thumb.dataset.src;
    var galleryInput = document.getElementById('galleryImagesInput');
    var galleryList = document.getElementById('galleryList');
    var galleryPlaceholder = document.getElementById('galleryPlaceholder');

    var currentGallery = [];
    if (galleryInput.value) {
        try {
            currentGallery = JSON.parse(galleryInput.value);
        } catch(e) {
            currentGallery = [];
        }
    }

    currentGallery = currentGallery.filter(function(img) { return img !== src; });
    galleryInput.value = JSON.stringify(currentGallery);

    thumb.remove();

    if (currentGallery.length === 0 && galleryPlaceholder) {
        galleryPlaceholder.classList.remove('hidden');
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});

document.getElementById('imageSelectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>