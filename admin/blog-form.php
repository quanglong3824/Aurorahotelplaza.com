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

    <!-- ========== SIMPLE IMAGE PICKER - SHOW/HIDE BY LAYOUT ========== -->
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
        
        // Image picker labels
        $image_labels = [
            0 => 'Ảnh đại diện (Hero)',
            1 => 'Ảnh phụ 1',
            2 => 'Ảnh phụ 2', 
            3 => 'Ảnh phụ 3',
            4 => 'Ảnh phụ 4',
            5 => 'Ảnh phụ 5',
            6 => 'Ảnh phụ 6',
            7 => 'Ảnh phụ 7',
            8 => 'Ảnh phụ 8',
            9 => 'Ảnh phụ 9',
            10 => 'Ảnh phụ 10',
            11 => 'Ảnh phụ 11',
            12 => 'Ảnh phụ 12',
        ];
    ?>
    
    <div class="card mb-6" id="imagePickerSection">
        <div class="card-header flex items-center justify-between">
            <div>
                <h3 class="font-bold text-lg">Chọn ảnh bài viết</h3>
                <p class="text-xs text-gray-500 mt-1" id="imageCountHint">Layout hiện tại: 1 ảnh</p>
            </div>
            <button type="button" id="uploadNewBtn" class="btn btn-sm btn-secondary">
                <span class="material-symbols-outlined text-sm">upload</span>
                Upload ảnh mới
            </button>
        </div>
        <div class="card-body">
            
            <!-- Upload Zone -->
            <div id="uploadZone" class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-xl p-6 text-center hover:border-[#d4af37] transition-colors cursor-pointer hidden mb-6">
                <input type="file" id="fileInput" accept="image/*" multiple class="hidden">
                <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">cloud_upload</span>
                <p class="text-gray-600 dark:text-gray-400">Kéo thả ảnh hoặc <span class="text-[#d4af37] font-medium">click để chọn</span></p>
                <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF, WebP (tối đa 5MB)</p>
            </div>
            
            <!-- Upload Progress -->
            <div id="multiUploadProgress" class="hidden space-y-2 mb-6">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Đang upload <span id="uploadingCount">0</span> ảnh...</p>
                <div class="space-y-1" id="uploadProgressList"></div>
            </div>
            
            <!-- ========== IMAGE PICKERS - Each is a separate card ========== -->
            <div class="space-y-4" id="imagePickersContainer">
                <?php for ($i = 0; $i <= 12; $i++): 
                    $is_featured = ($i === 0);
                    $current_img = $is_featured ? $stored_img : ($gallery_arr[$i - 1] ?? '');
                    $display_current = $current_img;
                    if ($current_img && strpos($current_img, 'uploads/') === 0) {
                        $display_current = '../' . $current_img;
                    }
                    $has_image = !empty($current_img);
                ?>
                <div class="image-picker-card p-4 bg-gray-50 dark:bg-slate-800 rounded-xl <?php echo $i > 0 ? 'hidden' : ''; ?>" 
                     data-picker-index="<?php echo $i; ?>"
                     data-is-featured="<?php echo $is_featured ? '1' : '0'; ?>">
                    
                    <div class="flex items-start gap-4">
                        <!-- Preview Box -->
                        <div class="w-40 h-28 flex-shrink-0 bg-gray-200 dark:bg-slate-700 rounded-lg overflow-hidden relative group cursor-pointer border-2 <?php echo $has_image ? 'border-[#d4af37]' : 'border-dashed border-gray-300 dark:border-slate-600'; ?>"
                             onclick="openImageModal(<?php echo $i; ?>)">
                            <?php if ($has_image): ?>
                                <img src="<?php echo htmlspecialchars($display_current); ?>" 
                                     class="w-full h-full object-cover picker-preview" 
                                     id="pickerPreview_<?php echo $i; ?>">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white">edit</span>
                                </div>
                            <?php else: ?>
                                <div class="w-full h-full flex flex-col items-center justify-center picker-empty" id="pickerEmpty_<?php echo $i; ?>">
                                    <span class="material-symbols-outlined text-3xl text-gray-400">add_photo_alternate</span>
                                    <span class="text-xs text-gray-500 mt-1">Chọn ảnh</span>
                                </div>
                                <img src="" class="w-full h-full object-cover picker-preview hidden" id="pickerPreview_<?php echo $i; ?>">
                            <?php endif; ?>
                        </div>
                        
                        <!-- Info & Actions -->
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-1">
                                <?php echo $image_labels[$i]; ?>
                                <?php if ($i === 0): ?>
                                    <span class="text-xs text-[#d4af37] ml-1">(Bắt buộc)</span>
                                <?php endif; ?>
                            </h4>
                            <p class="text-xs text-gray-500 mb-3">
                                <?php echo $i === 0 ? 'Ảnh chính hiển thị đầu bài viết' : 'Ảnh bổ sung cho gallery/slider'; ?>
                            </p>
                            <div class="flex gap-2">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="openImageModal(<?php echo $i; ?>)">
                                    <span class="material-symbols-outlined text-sm">photo_library</span>
                                    Chọn ảnh
                                </button>
                                <button type="button" class="btn btn-sm btn-danger picker-clear-btn <?php echo $has_image ? '' : 'hidden'; ?>" 
                                        onclick="clearPicker(<?php echo $i; ?>)" id="pickerClearBtn_<?php echo $i; ?>">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                    Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden input -->
                    <?php if ($is_featured): ?>
                        <input type="hidden" name="featured_image" id="pickerInput_0" value="<?php echo htmlspecialchars($stored_img); ?>">
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
            
            <!-- Hidden gallery input (will be built from picker inputs) -->
            <input type="hidden" name="gallery_images" id="galleryImagesInput" value="<?php echo htmlspecialchars($post['gallery_images'] ?? ''); ?>">
        </div>
    </div>
    
    <!-- ========== IMAGE SELECTION MODAL ========== -->
    <div id="imageSelectModal" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden shadow-2xl">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-bold text-lg">Chọn ảnh từ thư viện</h3>
                <button type="button" onclick="closeImageModal()" class="w-8 h-8 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 flex items-center justify-center">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-4 overflow-y-auto" style="max-height: calc(80vh - 120px);">
                <?php if (empty($uploaded_images)): ?>
                    <div class="text-center py-12">
                        <span class="material-symbols-outlined text-5xl text-gray-400 mb-3">photo_library</span>
                        <p class="text-gray-500">Chưa có ảnh nào trong thư viện</p>
                        <p class="text-sm text-gray-400 mt-1">Hãy upload ảnh mới</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3" id="modalImageGrid">
                        <?php foreach ($uploaded_images as $img): ?>
                            <div class="modal-image-item aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-[#d4af37] transition-all relative group"
                                 data-src="uploads/<?php echo htmlspecialchars($img); ?>"
                                 data-filename="<?php echo htmlspecialchars($img); ?>">
                                <img src="../uploads/<?php echo htmlspecialchars($img); ?>" 
                                     alt="<?php echo htmlspecialchars($img); ?>"
                                     class="w-full h-full object-cover cursor-pointer"
                                     onclick="selectImageFromModal(this.parentElement)">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors pointer-events-none"></div>
                                <button type="button" class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full items-center justify-center hover:bg-red-600 opacity-0 group-hover:opacity-100 transition-opacity flex z-10"
                                        onclick="deleteImageFromServer(this, '<?php echo htmlspecialchars($img); ?>')">
                                    <span class="material-symbols-outlined text-xs">delete</span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                <button type="button" onclick="closeImageModal()" class="btn btn-secondary">Đóng</button>
            </div>
        </div>
    </div>
    
    <!-- ========== LAYOUT PREVIEW MODAL ========== -->
    <div id="layoutPreviewModal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden shadow-2xl">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-bold text-lg">
                    <span class="material-symbols-outlined align-middle mr-1">preview</span>
                    Xem trước Layout: <span id="previewLayoutName" class="text-[#d4af37]">Standard</span>
                </h3>
                <button type="button" onclick="closePreviewModal()" class="w-8 h-8 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 flex items-center justify-center">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-6 overflow-y-auto bg-gray-100 dark:bg-slate-800" style="max-height: calc(90vh - 80px);">
                <div id="previewContent" class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-lg">
                    <!-- Preview content will be injected here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Video URL Section (for video layout) -->
    <div class="card mb-6 hidden" id="videoUrlSection">
        <div class="card-header">
            <h3 class="font-bold text-lg">Video URL</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">URL Video (YouTube/Vimeo)</label>
                <input type="url" name="video_url" id="videoUrlInput" class="form-input" 
                       value="<?php echo htmlspecialchars($post['video_url'] ?? ''); ?>" 
                       placeholder="https://www.youtube.com/watch?v=...">
                <p class="text-xs text-gray-500 mt-1">Hỗ trợ: YouTube, Vimeo</p>
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
        <button type="button" onclick="openPreviewModal()" class="btn btn-info">
            <span class="material-symbols-outlined text-sm">preview</span>
            Xem trước
        </button>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadNewBtn = document.getElementById('uploadNewBtn');
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    
    // ========== LAYOUT IMAGE COUNT CONFIG ==========
    // Số lượng ảnh cần cho mỗi layout (0 = featured, 1-12 = gallery)
    const layoutImageCount = {
        'standard': 1,    // Chỉ 1 ảnh đại diện
        'hero': 1,        // 1 ảnh hero
        'fullwidth': 1,   // 1 ảnh full
        'sidebar': 1,     // 1 ảnh
        'apartment': 7,   // 1 hero + 6 gallery
        'gallery': 13,    // 1 hero + 12 gallery
        'slider': 7,      // 1 hero + 6 slides
        'masonry': 11,    // 1 hero + 10 masonry
        'magazine': 4,    // 1 hero + 3 phụ
        'timeline': 6,    // 1 hero + 5 timeline
        'video': 1        // 1 thumbnail
    };
    
    // ========== LAYOUT SELECTOR ==========
    const layoutDescription = document.getElementById('layoutDescription');
    const layoutDescriptions = {
        'standard': '<strong>Tiêu chuẩn:</strong> Bố cục cơ bản với 1 ảnh đại diện.',
        'hero': '<strong>Hero Banner:</strong> 1 ảnh lớn chiếm toàn bộ phần đầu.',
        'sidebar': '<strong>Có Sidebar:</strong> 1 ảnh + sidebar bên phải.',
        'gallery': '<strong>Gallery:</strong> 1 ảnh đại diện + 12 ảnh lưới.',
        'slider': '<strong>Slider:</strong> 1 ảnh chính + 6 slide trình chiếu.',
        'apartment': '<strong>Căn hộ:</strong> 1 ảnh hero + 6 ảnh thumbnail.',
        'fullwidth': '<strong>Full Width:</strong> 1 ảnh trải rộng toàn màn hình.',
        'magazine': '<strong>Magazine:</strong> 1 ảnh lớn + 3 ảnh phụ.',
        'video': '<strong>Video:</strong> 1 ảnh thumbnail + video YouTube/Vimeo.',
        'timeline': '<strong>Timeline:</strong> 1 ảnh đại diện + 5 ảnh theo mốc thời gian.',
        'masonry': '<strong>Masonry:</strong> 1 ảnh đại diện + 10 ảnh kiểu Pinterest.'
    };
    
    // ========== SHOW/HIDE IMAGE PICKERS BY LAYOUT ==========
    function updateImagePickers(layout) {
        const count = layoutImageCount[layout] || 1;
        const hint = document.getElementById('imageCountHint');
        if (hint) {
            hint.textContent = `Layout "${layout}": ${count} ảnh`;
        }
        
        // Show/hide picker cards
        document.querySelectorAll('.image-picker-card').forEach(card => {
            const index = parseInt(card.dataset.pickerIndex);
            if (index < count) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
        
        // Show/hide video URL section
        const videoSection = document.getElementById('videoUrlSection');
        if (videoSection) {
            videoSection.classList.toggle('hidden', layout !== 'video');
        }
    }
    
    document.querySelectorAll('input[name="layout"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const layout = this.value;
            if (layoutDescription && layoutDescriptions[layout]) {
                layoutDescription.innerHTML = `<p class="text-gray-600 dark:text-gray-400">${layoutDescriptions[layout]}</p>`;
            }
            updateImagePickers(layout);
        });
        
        if (radio.checked) {
            radio.dispatchEvent(new Event('change'));
        }
    });
    
    // ========== IMAGE MODAL FUNCTIONS ==========
    let currentPickerIndex = 0;
    
    window.openImageModal = function(index) {
        currentPickerIndex = index;
        document.getElementById('imageSelectModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };
    
    window.closeImageModal = function() {
        document.getElementById('imageSelectModal').classList.add('hidden');
        document.body.style.overflow = '';
    };
    
    window.selectImageFromModal = function(element) {
        const src = element.dataset.src;
        const displaySrc = src.startsWith('uploads/') ? '../' + src : src;
        
        // Update preview
        const preview = document.getElementById('pickerPreview_' + currentPickerIndex);
        const empty = document.getElementById('pickerEmpty_' + currentPickerIndex);
        const clearBtn = document.getElementById('pickerClearBtn_' + currentPickerIndex);
        
        if (preview) {
            preview.src = displaySrc;
            preview.classList.remove('hidden');
        }
        if (empty) {
            empty.classList.add('hidden');
        }
        if (clearBtn) {
            clearBtn.classList.remove('hidden');
        }
        
        // Update hidden input
        if (currentPickerIndex === 0) {
            document.getElementById('pickerInput_0').value = src;
        }
        
        // Update gallery images array
        updateGalleryFromPickers();
        
        // Update border
        const card = document.querySelector(`.image-picker-card[data-picker-index="${currentPickerIndex}"]`);
        if (card) {
            const box = card.querySelector('.w-40');
            if (box) {
                box.classList.remove('border-dashed', 'border-gray-300', 'dark:border-slate-600');
                box.classList.add('border-[#d4af37]');
            }
        }
        
        closeImageModal();
    };
    
    window.clearPicker = function(index) {
        const preview = document.getElementById('pickerPreview_' + index);
        const empty = document.getElementById('pickerEmpty_' + index);
        const clearBtn = document.getElementById('pickerClearBtn_' + index);
        
        if (preview) {
            preview.src = '';
            preview.classList.add('hidden');
        }
        if (empty) {
            empty.classList.remove('hidden');
        }
        if (clearBtn) {
            clearBtn.classList.add('hidden');
        }
        
        // Update hidden input
        if (index === 0) {
            document.getElementById('pickerInput_0').value = '';
        }
        
        // Update gallery
        updateGalleryFromPickers();
        
        // Reset border
        const card = document.querySelector(`.image-picker-card[data-picker-index="${index}"]`);
        if (card) {
            const box = card.querySelector('.w-40');
            if (box) {
                box.classList.add('border-dashed', 'border-gray-300', 'dark:border-slate-600');
                box.classList.remove('border-[#d4af37]');
            }
        }
    };
    
    // Build gallery_images from picker cards
    function updateGalleryFromPickers() {
        const gallery = [];
        document.querySelectorAll('.image-picker-card').forEach(card => {
            const index = parseInt(card.dataset.pickerIndex);
            if (index > 0) { // Skip featured image (index 0)
                const preview = card.querySelector('.picker-preview');
                if (preview && preview.src && !preview.classList.contains('hidden')) {
                    let src = preview.src;
                    // Convert display path back to storage path
                    if (src.includes('../uploads/')) {
                        src = src.split('../uploads/')[1];
                        src = 'uploads/' + src;
                    } else if (src.includes('/uploads/')) {
                        src = 'uploads/' + src.split('/uploads/')[1];
                    }
                    gallery.push(src);
                }
            }
        });
        document.getElementById('galleryImagesInput').value = JSON.stringify(gallery);
    }
    
    // Close modal on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageModal();
            closePreviewModal();
        }
    });
    
    // Close modal on backdrop click
    document.getElementById('imageSelectModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeImageModal();
    });
    
    document.getElementById('layoutPreviewModal')?.addEventListener('click', function(e) {
        if (e.target === this) closePreviewModal();
    });
    
    // ========== DELETE IMAGE FROM SERVER ==========
    window.deleteImageFromServer = function(btn, filename) {
        if (!confirm('Xóa ảnh "' + filename + '" khỏi server?\nHành động này không thể hoàn tác!')) {
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined text-xs animate-spin">sync</span>';
        
        fetch('api/delete-image.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'filename=' + encodeURIComponent(filename)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Remove from modal grid
                const item = btn.closest('.modal-image-item');
                if (item) {
                    item.style.transition = 'all 0.3s';
                    item.style.transform = 'scale(0)';
                    item.style.opacity = '0';
                    setTimeout(() => item.remove(), 300);
                }
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể xóa ảnh'));
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-xs">delete</span>';
            }
        })
        .catch(err => {
            alert('Lỗi kết nối: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined text-xs">delete</span>';
        });
    };
    
    // ========== LAYOUT PREVIEW ==========
    window.openPreviewModal = function() {
        const layout = document.querySelector('input[name="layout"]:checked')?.value || 'standard';
        const title = document.querySelector('input[name="title"]')?.value || 'Tiêu đề bài viết';
        const content = document.querySelector('textarea[name="content"]')?.value || '<p>Nội dung bài viết sẽ hiển thị ở đây...</p>';
        const excerpt = document.querySelector('textarea[name="excerpt"]')?.value || '';
        const videoUrl = document.getElementById('videoUrlInput')?.value || '';
        
        // Get images
        const images = [];
        document.querySelectorAll('.image-picker-card:not(.hidden)').forEach(card => {
            const preview = card.querySelector('.picker-preview');
            if (preview && preview.src && !preview.classList.contains('hidden')) {
                images.push(preview.src);
            }
        });
        
        // Layout names
        const layoutNames = {
            'standard': 'Tiêu chuẩn',
            'hero': 'Hero Banner',
            'fullwidth': 'Full Width',
            'sidebar': 'Có Sidebar',
            'gallery': 'Gallery',
            'slider': 'Slider',
            'apartment': 'Căn hộ',
            'masonry': 'Masonry',
            'magazine': 'Magazine',
            'timeline': 'Timeline',
            'video': 'Video'
        };
        
        document.getElementById('previewLayoutName').textContent = layoutNames[layout] || layout;
        
        // Generate preview HTML based on layout
        let previewHTML = generateLayoutPreview(layout, title, content, excerpt, images, videoUrl);
        document.getElementById('previewContent').innerHTML = previewHTML;
        
        document.getElementById('layoutPreviewModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };
    
    window.closePreviewModal = function() {
        document.getElementById('layoutPreviewModal').classList.add('hidden');
        document.body.style.overflow = '';
    };
    
    function generateLayoutPreview(layout, title, content, excerpt, images, videoUrl) {
        const featuredImg = images[0] || 'https://placehold.co/800x400/1a1a2e/d4af37?text=No+Image';
        const galleryImgs = images.slice(1);
        
        let html = '';
        
        switch(layout) {
            case 'hero':
                html = `
                    <div class="relative -mx-6 -mt-6 mb-6">
                        <img src="${featuredImg}" class="w-full h-64 object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <h1 class="absolute bottom-4 left-6 right-6 text-2xl font-bold text-white">${title}</h1>
                    </div>
                    <div class="prose max-w-none">${content}</div>
                `;
                break;
                
            case 'fullwidth':
                html = `
                    <div class="-mx-6 -mt-6 mb-6">
                        <img src="${featuredImg}" class="w-full h-auto">
                        <div class="h-1 bg-gradient-to-r from-transparent via-[#d4af37] to-transparent"></div>
                    </div>
                    <h1 class="text-2xl font-bold mb-4">${title}</h1>
                    <div class="prose max-w-none">${content}</div>
                `;
                break;
                
            case 'gallery':
                html = `
                    <h1 class="text-2xl font-bold mb-4">${title}</h1>
                    <img src="${featuredImg}" class="w-full h-48 object-cover rounded-lg mb-4">
                    <div class="grid grid-cols-4 gap-2 mb-6">
                        ${galleryImgs.slice(0, 12).map(img => `<img src="${img}" class="aspect-square object-cover rounded-lg">`).join('')}
                        ${galleryImgs.length < 4 ? '<div class="aspect-square bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-xs">Thêm ảnh</div>'.repeat(4 - galleryImgs.length) : ''}
                    </div>
                    <div class="prose max-w-none">${content}</div>
                `;
                break;
                
            case 'slider':
                html = `
                    <h1 class="text-2xl font-bold mb-4">${title}</h1>
                    <div class="relative mb-6 rounded-xl overflow-hidden">
                        <img src="${featuredImg}" class="w-full h-56 object-cover">
                        <div class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center cursor-pointer">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </div>
                        <div class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center cursor-pointer">
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </div>
                        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1">
                            <span class="w-2 h-2 rounded-full bg-[#d4af37]"></span>
                            ${galleryImgs.slice(0, 5).map(() => '<span class="w-2 h-2 rounded-full bg-white/60"></span>').join('')}
                        </div>
                    </div>
                    <div class="prose max-w-none">${content}</div>
                `;
                break;
                
            case 'apartment':
                html = `
                    <h1 class="text-2xl font-bold mb-4">${title}</h1>
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="col-span-2">
                            <img src="${featuredImg}" class="w-full h-48 object-cover rounded-xl">
                        </div>
                        <div class="space-y-3">
                            <img src="${galleryImgs[0] || 'https://placehold.co/400x200/eee/999?text=+'}" class="w-full h-[94px] object-cover rounded-xl">
                            <img src="${galleryImgs[1] || 'https://placehold.co/400x200/eee/999?text=+'}" class="w-full h-[94px] object-cover rounded-xl">
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-2 mb-6">
                        ${galleryImgs.slice(2, 6).map(img => `<img src="${img}" class="aspect-video object-cover rounded-lg">`).join('')}
                        ${galleryImgs.length < 6 ? '<div class="aspect-video bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-xs">+</div>'.repeat(Math.max(0, 4 - galleryImgs.slice(2).length)) : ''}
                    </div>
                    <div class="prose max-w-none">${content}</div>
                `;
                break;
                
            case 'masonry':
                html = `
                    <h1 class="text-2xl font-bold mb-4">${title}</h1>
                    <img src="${featuredImg}" class="w-full h-40 object-cover rounded-lg mb-4">
                    <div class="columns-3 gap-3 mb-6">
                        ${galleryImgs.slice(0, 10).map((img, i) => `<img src="${img}" class="w-full mb-3 rounded-lg ${i % 3 === 0 ? 'aspect-[3/4]' : i % 3 === 1 ? 'aspect-square' : 'aspect-[4/3]'} object-cover">`).join('')}
                    </div>
                    <div class="prose max-w-none">${content}</div>
                `;
                break;
                
            case 'magazine':
                html = `
                    <h1 class="text-2xl font-bold mb-4">${title}</h1>
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <img src="${featuredImg}" class="w-full aspect-[3/4] object-cover rounded-xl">
                        <div>
                            ${excerpt ? `<p class="text-lg italic border-l-4 border-[#d4af37] pl-4 mb-4">${excerpt}</p>` : ''}
                            <div class="grid grid-cols-3 gap-2">
                                ${galleryImgs.slice(0, 3).map(img => `<img src="${img}" class="aspect-square object-cover rounded-lg">`).join('')}
                            </div>
                        </div>
                    </div>
                    <div class="prose max-w-none">${content}</div>
                `;
                break;
                
            case 'timeline':
                html = `
                    <h1 class="text-2xl font-bold mb-4">${title}</h1>
                    <img src="${featuredImg}" class="w-full h-40 object-cover rounded-lg mb-6">
                    <div class="relative pl-6 border-l-2 border-[#d4af37] space-y-6 mb-6">
                        ${galleryImgs.slice(0, 5).map((img, i) => `
                            <div class="relative">
                                <div class="absolute -left-[29px] w-3 h-3 bg-[#d4af37] rounded-full border-2 border-white"></div>
                                <p class="text-xs text-gray-500 mb-1">Mốc ${i + 1}</p>
                                <img src="${img}" class="w-full h-32 object-cover rounded-lg">
                            </div>
                        `).join('')}
                    </div>
                    <div class="prose max-w-none">${content}</div>
                `;
                break;
                
            case 'video':
                let embedUrl = '';
                if (videoUrl) {
                    const ytMatch = videoUrl.match(/youtube\.com\/watch\?v=([^&]+)/) || videoUrl.match(/youtu\.be\/([^?]+)/);
                    const vimeoMatch = videoUrl.match(/vimeo\.com\/(\d+)/);
                    if (ytMatch) embedUrl = 'https://www.youtube.com/embed/' + ytMatch[1];
                    else if (vimeoMatch) embedUrl = 'https://player.vimeo.com/video/' + vimeoMatch[1];
                }
                html = `
                    <h1 class="text-2xl font-bold mb-4">${title}</h1>
                    <div class="aspect-video bg-black rounded-xl mb-4 overflow-hidden">
                        ${embedUrl ? `<iframe src="${embedUrl}" class="w-full h-full" frameborder="0" allowfullscreen></iframe>` : `<div class="w-full h-full flex items-center justify-center text-white"><span class="material-symbols-outlined text-5xl">play_circle</span></div>`}
                    </div>
                    ${featuredImg && !featuredImg.includes('placehold') ? `<img src="${featuredImg}" class="w-full h-32 object-cover rounded-lg mb-4">` : ''}
                    <div class="prose max-w-none">${content}</div>
                `;
                break;
                
            case 'sidebar':
                html = `
                    <div class="grid grid-cols-3 gap-6">
                        <div class="col-span-2">
                            <h1 class="text-2xl font-bold mb-4">${title}</h1>
                            <img src="${featuredImg}" class="w-full h-48 object-cover rounded-lg mb-4">
                            <div class="prose max-w-none">${content}</div>
                        </div>
                        <div class="bg-gray-100 dark:bg-slate-800 rounded-xl p-4">
                            <h3 class="font-bold mb-3">Sidebar</h3>
                            <div class="space-y-3 text-sm text-gray-600">
                                <div class="p-3 bg-white dark:bg-slate-700 rounded-lg">Widget 1</div>
                                <div class="p-3 bg-white dark:bg-slate-700 rounded-lg">Widget 2</div>
                                <div class="p-3 bg-white dark:bg-slate-700 rounded-lg">Bài viết liên quan</div>
                            </div>
                        </div>
                    </div>
                `;
                break;
                
            default: // standard
                html = `
                    <h1 class="text-2xl font-bold mb-4">${title}</h1>
                    <img src="${featuredImg}" class="w-full h-48 object-cover rounded-lg mb-6">
                    <div class="prose max-w-none">${content}</div>
                `;
        }
        
        return html;
    }
    
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
