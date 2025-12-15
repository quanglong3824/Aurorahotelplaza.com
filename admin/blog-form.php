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

    <!-- ========== DYNAMIC IMAGE FORM BASED ON LAYOUT ========== -->
    <?php 
        // Fix image paths for display
        $preview_img = $post['featured_image'] ?? '';
        $display_img = $preview_img;
        if ($preview_img && strpos($preview_img, 'uploads/') === 0) {
            $display_img = '../' . $preview_img;
        }
        $stored_img = $preview_img;
        if ($stored_img && strpos($stored_img, '../uploads/') === 0) {
            $stored_img = str_replace('../uploads/', 'uploads/', $stored_img);
        }
        
        // Parse gallery images
        $gallery_arr = [];
        if (!empty($post['gallery_images'])) {
            $gallery_arr = json_decode($post['gallery_images'], true) ?: [];
        }
    ?>
    
    <div class="card mb-6" id="dynamicImageSection">
        <div class="card-header flex items-center justify-between">
            <div>
                <h3 class="font-bold text-lg" id="imageFormTitle">Ảnh bài viết</h3>
                <p class="text-xs text-gray-500 mt-1" id="imageFormSubtitle">Chọn ảnh phù hợp với layout đã chọn</p>
            </div>
            <button type="button" id="uploadNewBtn" class="btn btn-sm btn-secondary">
                <span class="material-symbols-outlined text-sm">upload</span>
                Upload ảnh mới
            </button>
        </div>
        <div class="card-body space-y-6">
            
            <!-- Upload Zone -->
            <div id="uploadZone" class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-xl p-6 text-center hover:border-[#d4af37] transition-colors cursor-pointer hidden">
                <input type="file" id="fileInput" accept="image/*" multiple class="hidden">
                <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">cloud_upload</span>
                <p class="text-gray-600 dark:text-gray-400">Kéo thả ảnh vào đây hoặc <span class="text-[#d4af37] font-medium">click để chọn</span></p>
                <p class="text-xs text-gray-500 mt-1">Hỗ trợ: JPG, PNG, GIF, WebP (tối đa 5MB) - <strong>Có thể chọn nhiều ảnh</strong></p>
            </div>
            
            <!-- Upload Progress -->
            <div id="multiUploadProgress" class="hidden space-y-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Đang upload <span id="uploadingCount">0</span> ảnh...</p>
                <div class="space-y-1" id="uploadProgressList"></div>
            </div>
            
            <!-- ========== LAYOUT-SPECIFIC IMAGE FORMS ========== -->
            
            <!-- STANDARD/HERO/FULLWIDTH: Single Featured Image -->
            <div id="layoutForm_single" class="layout-form">
                <div class="mb-4">
                    <label class="form-label flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">image</span>
                        Ảnh đại diện (Hero)
                    </label>
                    <p class="text-xs text-gray-500 mb-3">Ảnh chính hiển thị đầu bài viết</p>
                </div>
                <div id="singleImageBox" class="aspect-video max-w-2xl bg-gray-100 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="featured">
                    <div class="empty-state text-center p-8">
                        <span class="material-symbols-outlined text-5xl text-gray-400 mb-2">add_photo_alternate</span>
                        <p class="text-gray-500">Click để chọn ảnh đại diện</p>
                    </div>
                    <img src="<?php echo htmlspecialchars($display_img); ?>" class="absolute inset-0 w-full h-full object-cover <?php echo empty($preview_img) ? 'hidden' : ''; ?>" id="singlePreview">
                    <button type="button" class="clear-slot-btn absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full items-center justify-center hover:bg-red-600 <?php echo empty($preview_img) ? 'hidden' : 'flex'; ?>">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
            </div>
            
            <!-- APARTMENT: Hero + 2 Side + 4 Bottom Thumbnails -->
            <div id="layoutForm_apartment" class="layout-form hidden">
                <div class="mb-4">
                    <label class="form-label flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">apartment</span>
                        Ảnh giới thiệu căn hộ/phòng
                    </label>
                    <p class="text-xs text-gray-500 mb-3">1 ảnh hero lớn + 2 ảnh bên + 4 ảnh thumbnail</p>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <!-- Main Hero -->
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh chính (Hero)</p>
                        <div class="image-slot aspect-video bg-gray-100 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="featured" data-index="0">
                            <div class="empty-state text-center">
                                <span class="material-symbols-outlined text-3xl text-gray-400">add_photo_alternate</span>
                                <p class="text-xs text-gray-500 mt-1">Ảnh chính</p>
                            </div>
                        </div>
                    </div>
                    <!-- Side Images -->
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh phụ 1</p>
                            <div class="image-slot aspect-video bg-gray-100 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="gallery" data-index="0">
                                <div class="empty-state text-center">
                                    <span class="material-symbols-outlined text-2xl text-gray-400">add</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh phụ 2</p>
                            <div class="image-slot aspect-video bg-gray-100 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="gallery" data-index="1">
                                <div class="empty-state text-center">
                                    <span class="material-symbols-outlined text-2xl text-gray-400">add</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Bottom Thumbnails -->
                <div class="mt-4">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh thumbnail (tùy chọn)</p>
                    <div class="grid grid-cols-4 gap-3">
                        <?php for ($i = 2; $i < 6; $i++): ?>
                        <div class="image-slot aspect-video bg-gray-100 dark:bg-slate-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="gallery" data-index="<?php echo $i; ?>">
                            <div class="empty-state text-center">
                                <span class="material-symbols-outlined text-xl text-gray-400">add</span>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- GALLERY: Grid of images -->
            <div id="layoutForm_gallery" class="layout-form hidden">
                <div class="mb-4">
                    <label class="form-label flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">grid_view</span>
                        Ảnh Gallery (Lưới)
                    </label>
                    <p class="text-xs text-gray-500 mb-3">1 ảnh đại diện + nhiều ảnh hiển thị dạng lưới</p>
                </div>
                <!-- Featured -->
                <div class="mb-4">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh đại diện</p>
                    <div class="image-slot aspect-video max-w-md bg-gray-100 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="featured" data-index="0">
                        <div class="empty-state text-center">
                            <span class="material-symbols-outlined text-3xl text-gray-400">add_photo_alternate</span>
                            <p class="text-xs text-gray-500 mt-1">Ảnh chính</p>
                        </div>
                    </div>
                </div>
                <!-- Gallery Grid -->
                <div>
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh gallery (tối đa 12 ảnh)</p>
                    <div class="grid grid-cols-4 md:grid-cols-6 gap-3" id="galleryGrid">
                        <?php for ($i = 0; $i < 12; $i++): ?>
                        <div class="image-slot aspect-square bg-gray-100 dark:bg-slate-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="gallery" data-index="<?php echo $i; ?>">
                            <div class="empty-state text-center">
                                <span class="material-symbols-outlined text-xl text-gray-400">add</span>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- SLIDER: Carousel images -->
            <div id="layoutForm_slider" class="layout-form hidden">
                <div class="mb-4">
                    <label class="form-label flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">view_carousel</span>
                        Ảnh Slider (Carousel)
                    </label>
                    <p class="text-xs text-gray-500 mb-3">Các ảnh sẽ hiển thị dạng trình chiếu tự động</p>
                </div>
                <div class="space-y-4">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Slide 1 (Ảnh đại diện)</p>
                    <div class="image-slot aspect-[21/9] max-w-3xl bg-gray-100 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="featured" data-index="0">
                        <div class="empty-state text-center">
                            <span class="material-symbols-outlined text-4xl text-gray-400">add_photo_alternate</span>
                            <p class="text-sm text-gray-500 mt-1">Slide 1 - Ảnh chính</p>
                        </div>
                    </div>
                    
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mt-4">Các slide tiếp theo</p>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="sliderGrid">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="image-slot aspect-[21/9] bg-gray-100 dark:bg-slate-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="gallery" data-index="<?php echo $i; ?>">
                            <div class="empty-state text-center">
                                <span class="material-symbols-outlined text-2xl text-gray-400">add</span>
                                <p class="text-xs text-gray-500">Slide <?php echo $i + 2; ?></p>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- MASONRY: Pinterest-style -->
            <div id="layoutForm_masonry" class="layout-form hidden">
                <div class="mb-4">
                    <label class="form-label flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">dashboard</span>
                        Ảnh Masonry (Pinterest)
                    </label>
                    <p class="text-xs text-gray-500 mb-3">Ảnh hiển thị dạng Pinterest với kích thước đa dạng</p>
                </div>
                <!-- Featured -->
                <div class="mb-4">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh đại diện</p>
                    <div class="image-slot aspect-video max-w-lg bg-gray-100 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="featured" data-index="0">
                        <div class="empty-state text-center">
                            <span class="material-symbols-outlined text-3xl text-gray-400">add_photo_alternate</span>
                        </div>
                    </div>
                </div>
                <!-- Masonry Grid -->
                <div>
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh masonry (tối đa 10 ảnh)</p>
                    <div class="grid grid-cols-3 md:grid-cols-5 gap-3" id="masonryGrid">
                        <?php for ($i = 0; $i < 10; $i++): ?>
                        <div class="image-slot <?php echo $i % 3 === 0 ? 'aspect-[3/4]' : ($i % 3 === 1 ? 'aspect-square' : 'aspect-[4/3]'); ?> bg-gray-100 dark:bg-slate-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="gallery" data-index="<?php echo $i; ?>">
                            <div class="empty-state text-center">
                                <span class="material-symbols-outlined text-xl text-gray-400">add</span>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- MAGAZINE: Side by side -->
            <div id="layoutForm_magazine" class="layout-form hidden">
                <div class="mb-4">
                    <label class="form-label flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">menu_book</span>
                        Ảnh Magazine
                    </label>
                    <p class="text-xs text-gray-500 mb-3">1 ảnh lớn bên trái + 3 ảnh nhỏ bên phải</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh chính</p>
                        <div class="image-slot aspect-[3/4] bg-gray-100 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="featured" data-index="0">
                            <div class="empty-state text-center">
                                <span class="material-symbols-outlined text-4xl text-gray-400">add_photo_alternate</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh phụ</p>
                        <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="image-slot aspect-video bg-gray-100 dark:bg-slate-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="gallery" data-index="<?php echo $i; ?>">
                            <div class="empty-state text-center">
                                <span class="material-symbols-outlined text-xl text-gray-400">add</span>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- TIMELINE: Vertical timeline -->
            <div id="layoutForm_timeline" class="layout-form hidden">
                <div class="mb-4">
                    <label class="form-label flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">timeline</span>
                        Ảnh Timeline
                    </label>
                    <p class="text-xs text-gray-500 mb-3">Ảnh hiển thị theo dòng thời gian dọc</p>
                </div>
                <!-- Featured -->
                <div class="mb-4">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh đại diện</p>
                    <div class="image-slot aspect-video max-w-lg bg-gray-100 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="featured" data-index="0">
                        <div class="empty-state text-center">
                            <span class="material-symbols-outlined text-3xl text-gray-400">add_photo_alternate</span>
                        </div>
                    </div>
                </div>
                <!-- Timeline items -->
                <div class="relative pl-8 border-l-2 border-[#d4af37] space-y-4">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="relative">
                        <div class="absolute -left-[25px] w-3 h-3 bg-[#d4af37] rounded-full border-2 border-white dark:border-gray-900"></div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Mốc <?php echo $i + 1; ?></p>
                        <div class="image-slot aspect-video bg-gray-100 dark:bg-slate-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="gallery" data-index="<?php echo $i; ?>">
                            <div class="empty-state text-center">
                                <span class="material-symbols-outlined text-xl text-gray-400">add</span>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- VIDEO: Video + thumbnail -->
            <div id="layoutForm_video" class="layout-form hidden">
                <div class="mb-4">
                    <label class="form-label flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">play_circle</span>
                        Video Layout
                    </label>
                    <p class="text-xs text-gray-500 mb-3">Nhúng video YouTube/Vimeo + ảnh thumbnail</p>
                </div>
                <!-- Video URL -->
                <div class="mb-4">
                    <label class="form-label">URL Video</label>
                    <input type="url" name="video_url" id="videoUrlInput" class="form-input" 
                           value="<?php echo htmlspecialchars($post['video_url'] ?? ''); ?>" 
                           placeholder="https://www.youtube.com/watch?v=... hoặc https://vimeo.com/...">
                    <p class="text-xs text-gray-500 mt-1">Hỗ trợ: YouTube, Vimeo</p>
                </div>
                <!-- Thumbnail -->
                <div>
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Ảnh thumbnail (hiển thị khi video chưa load)</p>
                    <div class="image-slot aspect-video max-w-lg bg-gray-100 dark:bg-slate-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center cursor-pointer hover:border-[#d4af37] transition-all overflow-hidden relative" data-slot="featured" data-index="0">
                        <div class="empty-state text-center">
                            <span class="material-symbols-outlined text-3xl text-gray-400">add_photo_alternate</span>
                            <p class="text-xs text-gray-500 mt-1">Thumbnail</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hidden inputs -->
            <input type="hidden" name="featured_image" id="featuredImageInput" value="<?php echo htmlspecialchars($stored_img); ?>">
            <input type="hidden" name="gallery_images" id="galleryImagesInput" value="<?php echo htmlspecialchars($post['gallery_images'] ?? ''); ?>">
            
            <!-- Image Library -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    <span class="material-symbols-outlined text-sm align-middle mr-1">photo_library</span>
                    Thư viện ảnh - Click để chọn
                </p>
                <?php if (empty($uploaded_images)): ?>
                    <div class="text-center py-8 bg-gray-50 dark:bg-slate-800 rounded-xl">
                        <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">photo_library</span>
                        <p class="text-gray-500">Chưa có ảnh nào</p>
                        <p class="text-xs text-gray-400 mt-1">Click "Upload ảnh mới" để thêm</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 lg:grid-cols-12 gap-2 max-h-48 overflow-y-auto p-3 bg-gray-50 dark:bg-slate-800 rounded-xl" id="imageLibrary">
                        <?php foreach ($uploaded_images as $img): ?>
                            <div class="library-thumb aspect-square rounded-lg overflow-hidden cursor-pointer border-2 border-transparent hover:border-[#d4af37] transition-all relative group"
                                 data-src="uploads/<?php echo htmlspecialchars($img); ?>">
                                <img src="../uploads/<?php echo htmlspecialchars($img); ?>" 
                                     alt="<?php echo htmlspecialchars($img); ?>"
                                     class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-sm">check</span>
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
            
            // Switch layout form
            switchLayoutForm(layout);
        });
        
        // Trigger for initially selected layout
        if (radio.checked) {
            radio.dispatchEvent(new Event('change'));
        }
    });
    
    // ========== DYNAMIC LAYOUT FORM SWITCHING ==========
    const layoutFormMap = {
        'standard': 'single',
        'hero': 'single',
        'fullwidth': 'single',
        'sidebar': 'single',
        'apartment': 'apartment',
        'gallery': 'gallery',
        'slider': 'slider',
        'masonry': 'masonry',
        'magazine': 'magazine',
        'timeline': 'timeline',
        'video': 'video'
    };
    
    function switchLayoutForm(layout) {
        // Hide all layout forms
        document.querySelectorAll('.layout-form').forEach(form => {
            form.classList.add('hidden');
        });
        
        // Show the appropriate form
        const formId = layoutFormMap[layout] || 'single';
        const targetForm = document.getElementById('layoutForm_' + formId);
        if (targetForm) {
            targetForm.classList.remove('hidden');
        }
        
        // Update title and subtitle
        const titles = {
            'single': { title: 'Ảnh đại diện', subtitle: 'Chọn 1 ảnh chính cho bài viết' },
            'apartment': { title: 'Ảnh căn hộ/phòng', subtitle: '1 ảnh hero + 6 ảnh thumbnail' },
            'gallery': { title: 'Ảnh Gallery', subtitle: '1 ảnh đại diện + tối đa 12 ảnh lưới' },
            'slider': { title: 'Ảnh Slider', subtitle: 'Các ảnh sẽ trình chiếu tự động' },
            'masonry': { title: 'Ảnh Masonry', subtitle: 'Ảnh hiển thị kiểu Pinterest' },
            'magazine': { title: 'Ảnh Magazine', subtitle: '1 ảnh lớn + 3 ảnh phụ' },
            'timeline': { title: 'Ảnh Timeline', subtitle: 'Ảnh theo dòng thời gian' },
            'video': { title: 'Video & Thumbnail', subtitle: 'Nhúng video YouTube/Vimeo' }
        };
        
        const info = titles[formId] || titles['single'];
        document.getElementById('imageFormTitle').textContent = info.title;
        document.getElementById('imageFormSubtitle').textContent = info.subtitle;
        
        // Populate existing images into slots
        populateImageSlots();
    }
    
    // ========== IMAGE SLOT MANAGEMENT ==========
    let activeSlot = null;
    let galleryImages = [];
    
    // Initialize gallery images from hidden input
    try {
        const savedGallery = document.getElementById('galleryImagesInput').value;
        if (savedGallery) {
            galleryImages = JSON.parse(savedGallery) || [];
        }
    } catch (e) {
        galleryImages = [];
    }
    
    // Populate image slots with existing data
    function populateImageSlots() {
        const featuredImg = document.getElementById('featuredImageInput').value;
        
        // Populate featured image slots
        document.querySelectorAll('.layout-form:not(.hidden) .image-slot[data-slot="featured"]').forEach(slot => {
            if (featuredImg) {
                setSlotImage(slot, featuredImg);
            }
        });
        
        // Populate gallery image slots
        document.querySelectorAll('.layout-form:not(.hidden) .image-slot[data-slot="gallery"]').forEach(slot => {
            const index = parseInt(slot.dataset.index);
            if (galleryImages[index]) {
                setSlotImage(slot, galleryImages[index]);
            }
        });
    }
    
    // Set image in a slot
    function setSlotImage(slot, src) {
        // Remove empty state
        const emptyState = slot.querySelector('.empty-state');
        if (emptyState) emptyState.classList.add('hidden');
        
        // Add or update image
        let img = slot.querySelector('img.slot-img');
        if (!img) {
            img = document.createElement('img');
            img.className = 'slot-img absolute inset-0 w-full h-full object-cover';
            slot.appendChild(img);
        }
        
        // Display path (add ../ for admin)
        const displaySrc = src.startsWith('uploads/') ? '../' + src : src;
        img.src = displaySrc;
        img.classList.remove('hidden');
        
        // Add clear button
        let clearBtn = slot.querySelector('.clear-slot-btn');
        if (!clearBtn) {
            clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'clear-slot-btn absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 z-10';
            clearBtn.innerHTML = '<span class="material-symbols-outlined text-xs">close</span>';
            clearBtn.onclick = (e) => {
                e.stopPropagation();
                clearSlot(slot);
            };
            slot.appendChild(clearBtn);
        }
        clearBtn.classList.remove('hidden');
        
        // Update border
        slot.classList.remove('border-dashed');
        slot.classList.add('border-solid', 'border-[#d4af37]');
    }
    
    // Clear a slot
    function clearSlot(slot) {
        const slotType = slot.dataset.slot;
        const index = parseInt(slot.dataset.index) || 0;
        
        if (slotType === 'featured') {
            document.getElementById('featuredImageInput').value = '';
            // Clear all featured slots across all forms
            document.querySelectorAll('.image-slot[data-slot="featured"]').forEach(s => {
                resetSlotUI(s);
            });
        } else {
            galleryImages[index] = null;
            updateGalleryInput();
            // Clear this specific gallery slot across all forms
            document.querySelectorAll(`.image-slot[data-slot="gallery"][data-index="${index}"]`).forEach(s => {
                resetSlotUI(s);
            });
        }
    }
    
    // Reset slot UI
    function resetSlotUI(slot) {
        const emptyState = slot.querySelector('.empty-state');
        if (emptyState) emptyState.classList.remove('hidden');
        
        const img = slot.querySelector('img.slot-img');
        if (img) img.classList.add('hidden');
        
        const clearBtn = slot.querySelector('.clear-slot-btn');
        if (clearBtn) clearBtn.classList.add('hidden');
        
        slot.classList.add('border-dashed');
        slot.classList.remove('border-solid', 'border-[#d4af37]');
    }
    
    // Update gallery images hidden input
    function updateGalleryInput() {
        // Filter out null/empty values but keep array structure
        const cleanGallery = galleryImages.filter(img => img);
        document.getElementById('galleryImagesInput').value = JSON.stringify(cleanGallery);
    }
    
    // Click on image slot to select
    document.querySelectorAll('.image-slot').forEach(slot => {
        slot.addEventListener('click', function(e) {
            if (e.target.closest('.clear-slot-btn')) return;
            activeSlot = this;
            // Highlight active slot
            document.querySelectorAll('.image-slot').forEach(s => s.classList.remove('ring-2', 'ring-[#d4af37]'));
            this.classList.add('ring-2', 'ring-[#d4af37]');
        });
    });
    
    // Click on library image to fill active slot
    document.querySelectorAll('.library-thumb').forEach(thumb => {
        thumb.addEventListener('click', function() {
            const src = this.dataset.src;
            if (!activeSlot) {
                // If no slot selected, find first empty slot in current form
                activeSlot = document.querySelector('.layout-form:not(.hidden) .image-slot:not(:has(img.slot-img:not(.hidden)))');
            }
            
            if (activeSlot) {
                const slotType = activeSlot.dataset.slot;
                const index = parseInt(activeSlot.dataset.index) || 0;
                
                if (slotType === 'featured') {
                    document.getElementById('featuredImageInput').value = src;
                    // Update all featured slots
                    document.querySelectorAll('.image-slot[data-slot="featured"]').forEach(s => {
                        setSlotImage(s, src);
                    });
                } else {
                    galleryImages[index] = src;
                    updateGalleryInput();
                    // Update this specific gallery slot
                    document.querySelectorAll(`.image-slot[data-slot="gallery"][data-index="${index}"]`).forEach(s => {
                        setSlotImage(s, src);
                    });
                }
                
                // Move to next empty slot
                const allSlots = document.querySelectorAll('.layout-form:not(.hidden) .image-slot');
                let foundCurrent = false;
                for (const slot of allSlots) {
                    if (slot === activeSlot) {
                        foundCurrent = true;
                        continue;
                    }
                    if (foundCurrent && !slot.querySelector('img.slot-img:not(.hidden)')) {
                        activeSlot = slot;
                        document.querySelectorAll('.image-slot').forEach(s => s.classList.remove('ring-2', 'ring-[#d4af37]'));
                        slot.classList.add('ring-2', 'ring-[#d4af37]');
                        break;
                    }
                }
            }
        });
    });
    
    // Handle single image box (for standard/hero/fullwidth layouts)
    const singleImageBox = document.getElementById('singleImageBox');
    if (singleImageBox) {
        singleImageBox.addEventListener('click', function(e) {
            if (e.target.closest('.clear-slot-btn')) return;
            activeSlot = this;
            document.querySelectorAll('.image-slot').forEach(s => s.classList.remove('ring-2', 'ring-[#d4af37]'));
            this.classList.add('ring-2', 'ring-[#d4af37]');
        });
    }
    
    // Initialize on page load
    setTimeout(() => {
        const selectedLayout = document.querySelector('input[name="layout"]:checked');
        if (selectedLayout) {
            switchLayoutForm(selectedLayout.value);
        }
    }, 100);
    
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
