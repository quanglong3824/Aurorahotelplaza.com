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
$db_error = null;
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
} catch (Exception $e) {
    error_log("Load post/categories error: " . $e->getMessage());
    $db_error = "Lỗi database: " . $e->getMessage();
}

include 'includes/admin-header.php';
?>

<div class="mb-6">
    <a href="blog.php" class="btn btn-secondary">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Quay lại
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
        <strong>Lỗi lưu bài:</strong> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
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
            <p class="text-sm text-gray-500 mt-1">Chọn kiểu hiển thị phù hợp với nội dung bài viết</p>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4" id="layoutSelector">
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
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="slider" class="hidden" <?php echo ($post['layout'] ?? '') === 'slider' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center relative overflow-hidden">
                            <div class="w-full h-full bg-gray-400 dark:bg-slate-500 rounded"></div>
                            <div class="absolute left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-white/80 rounded-full"></div>
                            <div class="absolute right-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-white/80 rounded-full"></div>
                            <div class="absolute bottom-1 left-1/2 -translate-x-1/2 flex gap-0.5">
                                <div class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                <div class="w-1.5 h-1.5 bg-white/50 rounded-full"></div>
                                <div class="w-1.5 h-1.5 bg-white/50 rounded-full"></div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Slider ảnh</p>
                    </div>
                </label>
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="apartment" class="hidden" <?php echo ($post['layout'] ?? '') === 'apartment' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center">
                            <div class="w-full px-1">
                                <div class="h-6 bg-gray-400 dark:bg-slate-500 rounded mb-1"></div>
                                <div class="grid grid-cols-4 gap-0.5">
                                    <div class="h-3 bg-gray-300 dark:bg-slate-600 rounded"></div>
                                    <div class="h-3 bg-gray-300 dark:bg-slate-600 rounded"></div>
                                    <div class="h-3 bg-gray-300 dark:bg-slate-600 rounded"></div>
                                    <div class="h-3 bg-gray-300 dark:bg-slate-600 rounded"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Căn hộ</p>
                    </div>
                </label>
                
                <!-- New Layouts -->
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="fullwidth" class="hidden" <?php echo ($post['layout'] ?? '') === 'fullwidth' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center">
                            <div class="w-full">
                                <div class="h-12 bg-gray-400 dark:bg-slate-500 rounded-t"></div>
                                <div class="h-1 bg-[#d4af37]"></div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Full Width</p>
                    </div>
                </label>
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="magazine" class="hidden" <?php echo ($post['layout'] ?? '') === 'magazine' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center">
                            <div class="w-full px-1 flex gap-1">
                                <div class="w-1/2 h-10 bg-gray-400 dark:bg-slate-500 rounded"></div>
                                <div class="w-1/2 flex flex-col gap-0.5">
                                    <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded"></div>
                                    <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded w-3/4"></div>
                                    <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded w-1/2"></div>
                                    <div class="h-2 bg-[#d4af37]/50 rounded w-1/3 mt-auto"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Magazine</p>
                    </div>
                </label>
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="video" class="hidden" <?php echo ($post['layout'] ?? '') === 'video' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center relative">
                            <div class="w-full h-full bg-gray-400 dark:bg-slate-500 rounded flex items-center justify-center">
                                <div class="w-4 h-4 bg-white/80 rounded-full flex items-center justify-center">
                                    <div class="w-0 h-0 border-l-[6px] border-l-gray-600 border-y-[4px] border-y-transparent ml-0.5"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Video</p>
                    </div>
                </label>
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="timeline" class="hidden" <?php echo ($post['layout'] ?? '') === 'timeline' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center">
                            <div class="w-full px-2 flex">
                                <div class="w-0.5 bg-[#d4af37] mr-2"></div>
                                <div class="flex-1 space-y-1">
                                    <div class="flex items-center gap-1">
                                        <div class="w-1.5 h-1.5 bg-[#d4af37] rounded-full"></div>
                                        <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded flex-1"></div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-1.5 h-1.5 bg-[#d4af37] rounded-full"></div>
                                        <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded flex-1"></div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-1.5 h-1.5 bg-[#d4af37] rounded-full"></div>
                                        <div class="h-2 bg-gray-300 dark:bg-slate-600 rounded flex-1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Timeline</p>
                    </div>
                </label>
                <label class="layout-option cursor-pointer">
                    <input type="radio" name="layout" value="masonry" class="hidden" <?php echo ($post['layout'] ?? '') === 'masonry' ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-lg p-3 transition-all hover:border-[#d4af37] layout-card">
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded mb-2 flex items-center justify-center">
                            <div class="w-full px-1 grid grid-cols-3 gap-0.5">
                                <div class="space-y-0.5">
                                    <div class="h-5 bg-gray-400 dark:bg-slate-500 rounded"></div>
                                    <div class="h-3 bg-gray-400 dark:bg-slate-500 rounded"></div>
                                </div>
                                <div class="space-y-0.5">
                                    <div class="h-3 bg-gray-400 dark:bg-slate-500 rounded"></div>
                                    <div class="h-5 bg-gray-400 dark:bg-slate-500 rounded"></div>
                                </div>
                                <div class="space-y-0.5">
                                    <div class="h-4 bg-gray-400 dark:bg-slate-500 rounded"></div>
                                    <div class="h-4 bg-gray-400 dark:bg-slate-500 rounded"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-center font-medium">Masonry</p>
                    </div>
                </label>
            </div>
            
            <!-- Layout descriptions -->
            <div class="mt-4 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm" id="layoutDescription">
                <p class="text-gray-600 dark:text-gray-400">
                    <strong>Tiêu chuẩn:</strong> Bố cục cơ bản với ảnh đại diện và nội dung văn bản.
                </p>
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
            <?php 
                // Fix image path for display in admin
                $preview_img = $post['featured_image'] ?? '';
                $display_img = $preview_img;
                if ($preview_img && strpos($preview_img, 'uploads/') === 0) {
                    $display_img = '../' . $preview_img; // uploads/ -> ../uploads/ for admin display
                }
                // Normalize stored value to uploads/ format
                $stored_img = $preview_img;
                if ($stored_img && strpos($stored_img, '../uploads/') === 0) {
                    $stored_img = str_replace('../uploads/', 'uploads/', $stored_img);
                }
            ?>
            <div id="selectedImagePreview" class="mb-4 <?php echo empty($post['featured_image']) ? 'hidden' : ''; ?>">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ảnh đã chọn:</p>
                <div class="relative inline-block">
                    <img id="previewImg" src="<?php echo htmlspecialchars($display_img); ?>" 
                         alt="Preview" class="h-32 w-auto rounded-lg object-cover border-2 border-[#d4af37]">
                    <button type="button" id="clearImageBtn" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
            </div>
            
            <input type="hidden" name="featured_image" id="featuredImageInput" 
                   value="<?php echo htmlspecialchars($stored_img); ?>">
            
            <!-- Upload Zone - Multiple files support -->
            <div id="uploadZone" class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-xl p-6 text-center hover:border-[#d4af37] transition-colors cursor-pointer hidden">
                <input type="file" id="fileInput" accept="image/*" multiple class="hidden">
                <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">cloud_upload</span>
                <p class="text-gray-600 dark:text-gray-400">Kéo thả ảnh vào đây hoặc <span class="text-[#d4af37] font-medium">click để chọn</span></p>
                <p class="text-xs text-gray-500 mt-1">Hỗ trợ: JPG, PNG, GIF, WebP (tối đa 5MB mỗi ảnh) - <strong>Có thể chọn nhiều ảnh</strong></p>
            </div>
            
            <!-- Multiple Upload Progress -->
            <div id="multiUploadProgress" class="hidden space-y-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Đang upload <span id="uploadingCount">0</span> ảnh...</p>
                <div class="space-y-1" id="uploadProgressList"></div>
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
                                 data-src="uploads/<?php echo htmlspecialchars($img); ?>">
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

    <!-- Gallery Images Section (for slider/apartment layouts) -->
    <div class="card mb-6" id="galleryImagesSection">
        <div class="card-header flex items-center justify-between">
            <div>
                <h3 class="font-bold text-lg">Ảnh Gallery / Slider</h3>
                <p class="text-xs text-gray-500 mt-1">Chọn nhiều ảnh cho layout Slider hoặc Căn hộ. Kéo thả để sắp xếp thứ tự.</p>
            </div>
            <span class="badge badge-info" id="galleryCount">0 ảnh</span>
        </div>
        <div class="card-body space-y-4">
            <!-- Selected Gallery Images -->
            <div id="selectedGalleryImages" class="min-h-[80px] p-3 bg-gray-50 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600">
                <p class="text-sm text-gray-500 text-center py-4" id="galleryPlaceholder">Click vào ảnh bên dưới để thêm vào gallery</p>
                <div id="galleryImagesList" class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2"></div>
            </div>
            
            <input type="hidden" name="gallery_images" id="galleryImagesInput" 
                   value="<?php echo htmlspecialchars($post['gallery_images'] ?? ''); ?>">
            
            <!-- Available Images -->
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Click để thêm ảnh vào gallery:</p>
                <?php if (empty($uploaded_images)): ?>
                    <div class="text-center py-6 bg-gray-100 dark:bg-slate-700 rounded-xl">
                        <span class="material-symbols-outlined text-3xl text-gray-400 mb-2">photo_library</span>
                        <p class="text-gray-500 text-sm">Chưa có ảnh. Upload ảnh mới ở phần trên.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 gap-2 max-h-48 overflow-y-auto p-2 bg-gray-100 dark:bg-slate-700 rounded-xl" id="gallerySourceImages">
                        <?php foreach ($uploaded_images as $img): ?>
                            <div class="gallery-source-thumb aspect-square rounded-lg overflow-hidden cursor-pointer border-2 border-transparent hover:border-[#d4af37] transition-all relative"
                                 data-src="uploads/<?php echo htmlspecialchars($img); ?>">
                                <img src="../uploads/<?php echo htmlspecialchars($img); ?>" 
                                     alt="<?php echo htmlspecialchars($img); ?>"
                                     class="w-full h-full object-cover">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Video URL Section (for video layout) -->
    <div class="card mb-6" id="videoUrlSection" style="display: none;">
        <div class="card-header">
            <h3 class="font-bold text-lg">Video URL</h3>
            <p class="text-xs text-gray-500 mt-1">Dành cho layout Video - nhập link YouTube hoặc Vimeo</p>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">URL Video (YouTube/Vimeo)</label>
                <input type="url" name="video_url" class="form-input" 
                       value="<?php echo htmlspecialchars($post['video_url'] ?? ''); ?>" 
                       placeholder="https://www.youtube.com/watch?v=... hoặc https://vimeo.com/...">
                <p class="text-xs text-gray-500 mt-1">Hỗ trợ: YouTube, Vimeo. Video sẽ được nhúng tự động.</p>
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
    
    // ========== LAYOUT SELECTOR ==========
    const layoutDescription = document.getElementById('layoutDescription');
    const layoutDescriptions = {
        'standard': '<strong>Tiêu chuẩn:</strong> Bố cục cơ bản với ảnh đại diện và nội dung văn bản. Phù hợp cho bài viết tin tức thông thường.',
        'hero': '<strong>Hero Banner:</strong> Ảnh đại diện lớn chiếm toàn bộ phần đầu bài viết. Tạo ấn tượng mạnh cho bài viết nổi bật.',
        'sidebar': '<strong>Có Sidebar:</strong> Nội dung chính bên trái, sidebar bên phải hiển thị thông tin bổ sung, bài viết liên quan.',
        'gallery': '<strong>Gallery:</strong> Hiển thị nhiều ảnh dạng lưới. Phù hợp cho bài viết giới thiệu phòng, sự kiện có nhiều hình ảnh.',
        'slider': '<strong>Slider ảnh:</strong> Trình chiếu ảnh tự động với điều hướng. Lý tưởng cho tour ảo, giới thiệu không gian.',
        'apartment': '<strong>Căn hộ:</strong> Layout chuyên biệt cho giới thiệu căn hộ/phòng với ảnh hero lớn và gallery thumbnail bên dưới.',
        'fullwidth': '<strong>Full Width:</strong> Nội dung trải rộng toàn màn hình, không có sidebar. Tạo trải nghiệm đọc immersive.',
        'magazine': '<strong>Magazine:</strong> Bố cục kiểu tạp chí với ảnh và text song song. Phù hợp cho bài phỏng vấn, feature story.',
        'video': '<strong>Video:</strong> Tập trung vào video chính với nội dung mô tả bên dưới. Dành cho bài viết có video YouTube/Vimeo.',
        'timeline': '<strong>Timeline:</strong> Hiển thị nội dung theo dòng thời gian. Phù hợp cho lịch sử khách sạn, sự kiện theo ngày.',
        'masonry': '<strong>Masonry:</strong> Bố cục ảnh kiểu Pinterest với các ảnh kích thước khác nhau. Tạo hiệu ứng thị giác độc đáo.'
    };
    
    document.querySelectorAll('input[name="layout"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const layout = this.value;
            if (layoutDescription && layoutDescriptions[layout]) {
                layoutDescription.innerHTML = `<p class="text-gray-600 dark:text-gray-400">${layoutDescriptions[layout]}</p>`;
            }
            
            // Show/hide gallery section based on layout
            const gallerySection = document.getElementById('galleryImagesSection');
            if (gallerySection) {
                const needsGallery = ['gallery', 'slider', 'apartment', 'masonry'].includes(layout);
                gallerySection.style.display = needsGallery ? 'block' : 'none';
            }
            
            // Show/hide video URL section based on layout
            const videoSection = document.getElementById('videoUrlSection');
            if (videoSection) {
                videoSection.style.display = layout === 'video' ? 'block' : 'none';
            }
        });
        
        // Trigger for initially selected layout
        if (radio.checked) {
            radio.dispatchEvent(new Event('change'));
        }
    });
    
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
            uploadMultipleFiles(files);
        }
    });
    
    // File input change - support multiple files
    fileInput?.addEventListener('change', function() {
        if (this.files.length > 0) {
            uploadMultipleFiles(this.files);
        }
    });
    
    // Upload multiple files
    const multiUploadProgress = document.getElementById('multiUploadProgress');
    const uploadProgressList = document.getElementById('uploadProgressList');
    const uploadingCount = document.getElementById('uploadingCount');
    
    function uploadMultipleFiles(files) {
        const validFiles = Array.from(files).filter(file => {
            if (!file.type.startsWith('image/')) {
                console.warn(`${file.name} is not an image`);
                return false;
            }
            if (file.size > 5 * 1024 * 1024) {
                console.warn(`${file.name} is too large`);
                return false;
            }
            return true;
        });
        
        if (validFiles.length === 0) {
            alert('Không có file ảnh hợp lệ! (Tối đa 5MB mỗi ảnh)');
            return;
        }
        
        // Show multi-upload progress
        multiUploadProgress.classList.remove('hidden');
        uploadingCount.textContent = validFiles.length;
        uploadProgressList.innerHTML = '';
        
        let completedCount = 0;
        let firstUploadedUrl = null;
        
        validFiles.forEach((file, index) => {
            // Create progress item
            const progressItem = document.createElement('div');
            progressItem.className = 'flex items-center gap-2 text-sm';
            progressItem.innerHTML = `
                <span class="truncate w-32">${file.name}</span>
                <div class="flex-1 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="progress-bar h-full bg-[#d4af37] transition-all" style="width: 0%"></div>
                </div>
                <span class="progress-text w-10 text-right">0%</span>
            `;
            uploadProgressList.appendChild(progressItem);
            
            const progressBar = progressItem.querySelector('.progress-bar');
            const progressText = progressItem.querySelector('.progress-text');
            
            // Upload file
            uploadSingleFile(file, progressBar, progressText, (url, filename) => {
                completedCount++;
                if (!firstUploadedUrl) {
                    firstUploadedUrl = url;
                    selectImage(url);
                }
                addImageToGallery(url, filename);
                
                // Also add to gallery images for slider/masonry layouts
                addToGallery(url);
                
                if (completedCount === validFiles.length) {
                    setTimeout(() => {
                        multiUploadProgress.classList.add('hidden');
                        uploadZone.classList.add('hidden');
                    }, 1000);
                }
            });
        });
    }
    
    // Upload single file with progress callback
    function uploadSingleFile(file, progressBar, progressText, onSuccess) {
        const formData = new FormData();
        formData.append('image', file);
        
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
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    progressBar.classList.remove('bg-[#d4af37]');
                    progressBar.classList.add('bg-green-500');
                    progressText.textContent = '✓';
                    if (onSuccess) onSuccess(response.url, response.filename);
                } else {
                    progressBar.classList.remove('bg-[#d4af37]');
                    progressBar.classList.add('bg-red-500');
                    progressText.textContent = '✗';
                }
            } catch (e) {
                progressBar.classList.add('bg-red-500');
                progressText.textContent = '✗';
            }
        };
        
        xhr.onerror = function() {
            progressBar.classList.add('bg-red-500');
            progressText.textContent = '✗';
        };
        
        xhr.send(formData);
    }
    
    // Legacy single file upload (kept for compatibility)
    function uploadFile(file) {
        uploadMultipleFiles([file]);
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
        
        // url is uploads/filename.jpg, need ../uploads/ for admin display
        let displayUrl = url.startsWith('uploads/') ? '../' + url : url;
        
        const thumb = document.createElement('div');
        thumb.className = 'image-thumb aspect-square rounded-lg overflow-hidden cursor-pointer border-2 border-transparent hover:border-[#d4af37] transition-all relative group selected';
        thumb.dataset.src = url; // Store original path for saving to DB
        thumb.innerHTML = `
            <img src="${displayUrl}" alt="${filename}" class="w-full h-full object-cover">
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
        // For preview in admin, add ../ prefix if needed
        let displaySrc = src;
        if (src.startsWith('uploads/')) {
            displaySrc = '../' + src;
        }
        previewImg.src = displaySrc;
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
    
    // ========== GALLERY IMAGES (for slider/apartment layouts) ==========
    const galleryImagesInput = document.getElementById('galleryImagesInput');
    const galleryImagesList = document.getElementById('galleryImagesList');
    const galleryPlaceholder = document.getElementById('galleryPlaceholder');
    const galleryCount = document.getElementById('galleryCount');
    const gallerySourceImages = document.getElementById('gallerySourceImages');
    
    let galleryImages = [];
    
    // Load existing gallery images
    if (galleryImagesInput.value) {
        try {
            galleryImages = JSON.parse(galleryImagesInput.value);
            renderGalleryImages();
        } catch (e) {
            galleryImages = [];
        }
    }
    
    // Add image to gallery
    function addToGallery(src) {
        if (galleryImages.includes(src)) {
            // Remove if already exists
            galleryImages = galleryImages.filter(img => img !== src);
        } else {
            galleryImages.push(src);
        }
        updateGalleryInput();
        renderGalleryImages();
    }
    
    // Remove from gallery
    function removeFromGallery(src) {
        galleryImages = galleryImages.filter(img => img !== src);
        updateGalleryInput();
        renderGalleryImages();
    }
    
    // Update hidden input
    function updateGalleryInput() {
        galleryImagesInput.value = JSON.stringify(galleryImages);
        galleryCount.textContent = galleryImages.length + ' ảnh';
    }
    
    // Render gallery images
    function renderGalleryImages() {
        if (galleryImages.length === 0) {
            galleryPlaceholder.classList.remove('hidden');
            galleryImagesList.innerHTML = '';
            return;
        }
        
        galleryPlaceholder.classList.add('hidden');
        galleryImagesList.innerHTML = galleryImages.map((src, index) => {
            // For display in admin, add ../ prefix if needed
            let displaySrc = src.startsWith('uploads/') ? '../' + src : src;
            return `
            <div class="gallery-item aspect-square rounded-lg overflow-hidden relative group border-2 border-[#d4af37]" data-src="${src}" data-index="${index}">
                <img src="${displaySrc}" alt="Gallery ${index + 1}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                    <span class="text-white text-xs font-bold bg-black/50 px-1 rounded">${index + 1}</span>
                    <button type="button" class="remove-gallery-btn w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600" data-src="${src}">
                        <span class="material-symbols-outlined text-xs">close</span>
                    </button>
                </div>
            </div>
        `}).join('');
        
        // Bind remove buttons
        galleryImagesList.querySelectorAll('.remove-gallery-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                removeFromGallery(this.dataset.src);
            });
        });
        
        // Update source images selected state
        updateSourceImagesState();
    }
    
    // Update source images selected state
    function updateSourceImagesState() {
        document.querySelectorAll('.gallery-source-thumb').forEach(thumb => {
            if (galleryImages.includes(thumb.dataset.src)) {
                thumb.classList.add('ring-2', 'ring-[#d4af37]', 'ring-offset-2');
            } else {
                thumb.classList.remove('ring-2', 'ring-[#d4af37]', 'ring-offset-2');
            }
        });
    }
    
    // Bind click to source images
    document.querySelectorAll('.gallery-source-thumb').forEach(thumb => {
        thumb.addEventListener('click', function() {
            addToGallery(this.dataset.src);
        });
    });
    
    // Initial render
    updateSourceImagesState();
});
</script>

<?php include 'includes/admin-footer.php'; ?>
