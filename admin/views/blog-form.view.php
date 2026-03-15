<?php
/**
 * Aurora Hotel Plaza - Blog Form View
 * Displays blog post creation/editing form
 */
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

<form action="api/save-post.php" method="POST" class="max-w-4xl pb-10">
    <?php echo Security::getCSRFInput(); ?>
    <input type="hidden" name="post_id" value="<?php echo $post['post_id'] ?? ''; ?>">
    
    <div class="card mb-6 shadow-sm">
        <div class="card-header border-b border-gray-100 p-6">
            <h3 class="font-bold text-xl text-gray-800">Thông tin bài viết</h3>
        </div>
        <div class="card-body p-6 space-y-5">
            <div class="form-group">
                <label class="form-label block text-sm font-semibold text-gray-700 mb-2">Tiêu đề *</label>
                <input type="text" name="title" class="form-input w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-primary/20" 
                       value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label block text-sm font-semibold text-gray-700 mb-2">Slug (URL thân thiện)</label>
                    <input type="text" name="slug" class="form-input w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-primary/20" 
                           value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>" 
                           placeholder="tự động tạo nếu để trống">
                </div>
                <div class="form-group">
                    <label class="form-label block text-sm font-semibold text-gray-700 mb-2">Danh mục</label>
                    <select name="category_id" class="form-select w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-primary/20">
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
                <label class="form-label block text-sm font-semibold text-gray-700 mb-2">Mô tả ngắn</label>
                <textarea name="excerpt" class="form-textarea w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-primary/20" rows="3"><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label block text-sm font-semibold text-gray-700 mb-2">Nội dung *</label>
                <textarea name="content" id="content-editor" class="form-textarea w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-primary/20" rows="15" required><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">info</span>
                    Hỗ trợ HTML. Sử dụng trình soạn thảo để định dạng.
                </p>
            </div>
        </div>
    </div>
    
    <div class="card mb-6 shadow-sm">
        <div class="card-header border-b border-gray-100 p-6">
            <h3 class="font-bold text-xl text-gray-800">Bố cục hiển thị (Layout)</h3>
            <p class="text-sm text-gray-500 mt-1">Chọn kiểu hiển thị phù hợp với nội dung bài viết</p>
        </div>
        <div class="card-body p-6">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4" id="layoutSelector">
                <?php 
                    $layouts = [
                        'standard' => 'Tiêu chuẩn',
                        'hero' => 'Hero Banner',
                        'sidebar' => 'Có Sidebar',
                        'gallery' => 'Gallery',
                        'slider' => 'Slider ảnh',
                        'apartment' => 'Căn hộ',
                        'fullwidth' => 'Full Width',
                        'magazine' => 'Magazine',
                        'video' => 'Video',
                        'timeline' => 'Timeline',
                        'masonry' => 'Masonry'
                    ];
                    foreach ($layouts as $val => $label): 
                        $is_checked = ($post['layout'] ?? 'standard') === $val;
                ?>
                <label class="layout-option cursor-pointer group">
                    <input type="radio" name="layout" value="<?php echo $val; ?>" class="hidden" <?php echo $is_checked ? 'checked' : ''; ?>>
                    <div class="border-2 rounded-xl p-3 transition-all hover:shadow-md layout-card <?php echo $is_checked ? 'border-primary bg-primary/5' : 'border-gray-100'; ?>">
                        <div class="aspect-video bg-gray-100 dark:bg-slate-700 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                            <!-- Visual representation of layout -->
                            <div class="w-full px-2">
                                <?php if($val === 'standard'): ?>
                                    <div class="h-8 bg-gray-300 dark:bg-slate-500 rounded mb-1"></div>
                                    <div class="h-1.5 bg-gray-200 dark:bg-slate-600 rounded mb-1 w-3/4"></div>
                                    <div class="h-1.5 bg-gray-200 dark:bg-slate-600 rounded w-1/2"></div>
                                <?php elseif($val === 'hero'): ?>
                                    <div class="h-full w-full bg-gray-300 relative">
                                        <div class="absolute bottom-2 left-2 right-2 h-4 bg-white/50 rounded"></div>
                                    </div>
                                <?php elseif($val === 'gallery'): ?>
                                    <div class="grid grid-cols-3 gap-1 p-1">
                                        <div class="h-4 bg-gray-300 rounded"></div>
                                        <div class="h-4 bg-gray-300 rounded"></div>
                                        <div class="h-4 bg-gray-300 rounded"></div>
                                        <div class="h-4 bg-gray-300 rounded"></div>
                                        <div class="h-4 bg-gray-300 rounded"></div>
                                        <div class="h-4 bg-gray-300 rounded"></div>
                                    </div>
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-gray-400">dashboard</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-xs text-center font-bold text-gray-700"><?php echo $label; ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6 p-4 bg-primary/5 rounded-xl text-sm border border-primary/10" id="layoutDescription">
                <p class="text-gray-600">
                    <strong>Tiêu chuẩn:</strong> Bố cục cơ bản với ảnh đại diện và nội dung văn bản.
                </p>
            </div>
        </div>
    </div>

    <!-- Image Picker Section -->
    <?php 
        $stored_img = $post['featured_image'] ?? '';
        $gallery_arr = !empty($post['gallery_images']) ? (json_decode($post['gallery_images'], true) ?: []) : [];
        
        $image_labels = [
            0 => 'Ảnh đại diện (Hero)',
            1 => 'Ảnh phụ 1', 2 => 'Ảnh phụ 2', 3 => 'Ảnh phụ 3',
            4 => 'Ảnh phụ 4', 5 => 'Ảnh phụ 5', 6 => 'Ảnh phụ 6',
            7 => 'Ảnh phụ 7', 8 => 'Ảnh phụ 8', 9 => 'Ảnh phụ 9',
            10 => 'Ảnh phụ 10', 11 => 'Ảnh phụ 11', 12 => 'Ảnh phụ 12',
        ];
    ?>
    
    <div class="card mb-6 shadow-sm" id="imagePickerSection">
        <div class="card-header flex items-center justify-between border-b border-gray-100 p-6">
            <div>
                <h3 class="font-bold text-xl text-gray-800">Hình ảnh bài viết</h3>
                <p class="text-xs text-gray-500 mt-1" id="imageCountHint">Layout hiện tại: 1 ảnh</p>
            </div>
            <button type="button" id="uploadNewBtn" class="btn btn-sm btn-primary">
                <span class="material-symbols-outlined text-sm">upload</span>
                Upload ảnh mới
            </button>
        </div>
        <div class="card-body p-6">
            <div id="uploadZone" class="border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:border-primary hover:bg-primary/5 transition-all cursor-pointer hidden mb-8">
                <input type="file" id="fileInput" accept="image/*" multiple class="hidden">
                <span class="material-symbols-outlined text-5xl text-gray-400 mb-3">cloud_upload</span>
                <p class="text-gray-600 font-medium">Kéo thả ảnh hoặc <span class="text-primary underline">chọn từ máy tính</span></p>
                <p class="text-xs text-gray-400 mt-2">Định dạng hỗ trợ: JPG, PNG, GIF, WebP (tối đa 5MB)</p>
            </div>
            
            <div id="multiUploadProgress" class="hidden mb-8 bg-gray-50 p-4 rounded-xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-bold text-gray-700">Đang tải lên <span id="uploadingCount">0</span> tệp...</span>
                </div>
                <div class="space-y-3" id="uploadProgressList"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="imagePickersContainer">
                <?php for ($i = 0; $i <= 12; $i++): 
                    $is_featured = ($i === 0);
                    $current_img = $is_featured ? $stored_img : ($gallery_arr[$i - 1] ?? '');
                    $display_current = $current_img;
                    if ($current_img && strpos($current_img, 'uploads/') === 0) {
                        $display_current = '../' . $current_img;
                    }
                    $has_image = !empty($current_img);
                ?>
                <div class="image-picker-card p-4 bg-gray-50 rounded-xl border border-gray-100 <?php echo $i > 0 ? 'hidden' : ''; ?>" 
                     data-picker-index="<?php echo $i; ?>"
                     data-is-featured="<?php echo $is_featured ? '1' : '0'; ?>">
                    
                    <div class="flex items-center gap-4">
                        <div class="w-32 h-24 flex-shrink-0 bg-white rounded-lg overflow-hidden relative group cursor-pointer border-2 <?php echo $has_image ? 'border-primary shadow-sm' : 'border-dashed border-gray-200'; ?>"
                             onclick="openImageModal(<?php echo $i; ?>)">
                            <?php if ($has_image): ?>
                                <img src="<?php echo htmlspecialchars($display_current); ?>" 
                                     class="w-full h-full object-cover picker-preview" 
                                     id="pickerPreview_<?php echo $i; ?>">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white">edit</span>
                                </div>
                            <?php else: ?>
                                <div class="w-full h-full flex flex-col items-center justify-center picker-empty" id="pickerEmpty_<?php echo $i; ?>">
                                    <span class="material-symbols-outlined text-2xl text-gray-300">add_photo_alternate</span>
                                </div>
                                <img src="" class="w-full h-full object-cover picker-preview hidden" id="pickerPreview_<?php echo $i; ?>">
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-gray-800 truncate">
                                <?php echo $image_labels[$i]; ?>
                                <?php if ($i === 0): ?>
                                    <span class="text-[10px] bg-primary/10 text-primary px-1.5 py-0.5 rounded ml-1">REQUIRED</span>
                                <?php endif; ?>
                            </h4>
                            <div class="flex gap-2 mt-3">
                                <button type="button" class="btn btn-xs bg-white border-gray-200 text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="openImageModal(<?php echo $i; ?>)">
                                    <span class="material-symbols-outlined text-xs">photo_library</span>
                                    Thư viện
                                </button>
                                <button type="button" class="btn btn-xs btn-danger picker-clear-btn <?php echo $has_image ? '' : 'hidden'; ?>" 
                                        onclick="clearPicker(<?php echo $i; ?>)" id="pickerClearBtn_<?php echo $i; ?>">
                                    <span class="material-symbols-outlined text-xs">delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php if ($is_featured): ?>
                        <input type="hidden" name="featured_image" id="pickerInput_0" value="<?php echo htmlspecialchars($stored_img); ?>">
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
            
            <input type="hidden" name="gallery_images" id="galleryImagesInput" value="<?php echo htmlspecialchars($post['gallery_images'] ?? '[]'); ?>">
        </div>
    </div>
    
    <!-- Video URL (Hidden by default) -->
    <div class="card mb-6 shadow-sm hidden" id="videoUrlSection">
        <div class="card-header border-b border-gray-100 p-6">
            <h3 class="font-bold text-xl text-gray-800">Video URL</h3>
        </div>
        <div class="card-body p-6">
            <div class="form-group">
                <label class="form-label block text-sm font-semibold text-gray-700 mb-2">URL Video (YouTube/Vimeo)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400">play_circle</span>
                    <input type="url" name="video_url" id="videoUrlInput" class="form-input w-full pl-10 pr-4 py-2.5 rounded-lg border" 
                           value="<?php echo htmlspecialchars($post['video_url'] ?? ''); ?>" 
                           placeholder="https://www.youtube.com/watch?v=...">
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-6 shadow-sm">
        <div class="card-header border-b border-gray-100 p-6">
            <h3 class="font-bold text-xl text-gray-800">SEO & Phân loại</h3>
        </div>
        <div class="card-body p-6 space-y-4">
            <div class="form-group">
                <label class="form-label block text-sm font-semibold text-gray-700 mb-2">Tags (ngăn cách bằng dấu phẩy)</label>
                <input type="text" name="tags" class="form-input w-full px-4 py-2.5 rounded-lg border" 
                       value="<?php echo htmlspecialchars($post['tags'] ?? ''); ?>" 
                       placeholder="khách sạn, du lịch, nghỉ dưỡng">
            </div>
        </div>
    </div>
    
    <div class="card mb-6 shadow-sm">
        <div class="card-header border-b border-gray-100 p-6">
            <h3 class="font-bold text-xl text-gray-800">Cài đặt xuất bản</h3>
        </div>
        <div class="card-body p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label class="form-label block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                <select name="status" class="form-select w-full px-4 py-2.5 rounded-lg border">
                    <option value="draft" <?php echo ($post['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Lưu nháp</option>
                    <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Xuất bản ngay</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label block text-sm font-semibold text-gray-700 mb-2">Ngày giờ đăng</label>
                <input type="datetime-local" name="published_at" class="form-input w-full px-4 py-2.5 rounded-lg border"
                       value="<?php echo isset($post['published_at']) ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : ''; ?>">
            </div>
            
            <div class="flex items-center gap-6 md:col-span-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-gray-300 text-primary focus:ring-primary"
                           <?php echo ($post['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                    <span class="text-sm font-medium text-gray-700">Đánh dấu bài viết nổi bật</span>
                </label>
                
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="allow_comments" value="1" class="rounded border-gray-300 text-primary focus:ring-primary"
                           <?php echo ($post['allow_comments'] ?? 1) ? 'checked' : ''; ?>>
                    <span class="text-sm font-medium text-gray-700">Cho phép bình luận</span>
                </label>
            </div>
        </div>
    </div>
    
    <div class="sticky bottom-6 flex justify-end gap-3 bg-white/80 backdrop-blur-md p-4 rounded-2xl shadow-2xl border border-gray-100 z-40">
        <a href="blog.php" class="btn btn-secondary px-6">Hủy bỏ</a>
        <button type="button" onclick="openPreviewModal()" class="btn border-info text-info hover:bg-info hover:text-white px-6">
            <span class="material-symbols-outlined text-sm">preview</span>
            Xem trước
        </button>
        <button type="submit" name="save_draft" class="btn btn-secondary px-6">
            <span class="material-symbols-outlined text-sm">save</span>
            Lưu nháp
        </button>
        <button type="submit" name="publish" class="btn btn-primary px-8">
            <span class="material-symbols-outlined text-sm">publish</span>
            <?php echo $is_edit ? 'Cập nhật bài viết' : 'Đăng bài viết'; ?>
        </button>
    </div>
</form>

<!-- Modal & Scripts Section (Kept for functionality) -->
<div id="imageSelectModal" class="fixed inset-0 bg-black/70 z-[60] hidden flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-5xl w-full max-h-[85vh] overflow-hidden shadow-2xl animate-fade-in-up">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-2xl text-gray-800">Thư viện ảnh</h3>
            <button type="button" onclick="closeImageModal()" class="w-10 h-10 rounded-full hover:bg-gray-100 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 overflow-y-auto scrollbar-hide" style="max-height: calc(85vh - 160px);">
            <?php if (empty($uploaded_images)): ?>
                <div class="text-center py-20 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                    <span class="material-symbols-outlined text-7xl text-gray-300 mb-4">photo_library</span>
                    <p class="text-xl text-gray-500 font-medium">Thư viện đang trống</p>
                    <button type="button" onclick="closeImageModal(); document.getElementById('uploadNewBtn').click();" class="mt-4 text-primary font-bold underline">Tải ảnh lên ngay</button>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-4" id="modalImageGrid">
                    <?php foreach ($uploaded_images as $img): ?>
                        <div class="modal-image-item aspect-square rounded-2xl overflow-hidden border-4 border-transparent hover:border-primary transition-all relative group cursor-pointer"
                             data-src="uploads/<?php echo htmlspecialchars($img); ?>"
                             data-filename="<?php echo htmlspecialchars($img); ?>"
                             onclick="selectImageFromModal(this)">
                            <img src="../uploads/<?php echo htmlspecialchars($img); ?>" 
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></div>
                            <button type="button" class="absolute top-2 right-2 w-7 h-7 bg-red-500 text-white rounded-full items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-opacity flex z-10 hover:bg-red-600"
                                    onclick="event.stopPropagation(); deleteImageFromServer(this, '<?php echo htmlspecialchars($img); ?>')">
                                <span class="material-symbols-outlined text-xs">delete</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="layoutPreviewModal" class="fixed inset-0 bg-black/80 z-[60] hidden flex items-center justify-center p-4 backdrop-blur-md">
    <div class="bg-white rounded-3xl max-w-6xl w-full max-h-[95vh] overflow-hidden shadow-2xl flex flex-col">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <h3 class="font-bold text-2xl text-gray-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">visibility</span>
                Xem trước Layout: <span id="previewLayoutName" class="text-primary">Standard</span>
            </h3>
            <button type="button" onclick="closePreviewModal()" class="w-10 h-10 rounded-full hover:bg-gray-100 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-8 overflow-y-auto flex-grow bg-gray-100">
            <div id="previewContent" class="bg-white rounded-3xl shadow-xl overflow-hidden max-w-4xl mx-auto p-10 min-h-screen">
                <!-- Preview content -->
            </div>
        </div>
    </div>
</div>

<script>
// Scripts from blog-form.php integrated here
document.addEventListener('DOMContentLoaded', function() {
    // Layout image count config
    const layoutImageCount = {
        'standard': 1, 'hero': 1, 'fullwidth': 1, 'sidebar': 1, 'apartment': 7, 
        'gallery': 13, 'slider': 7, 'masonry': 11, 'magazine': 4, 'timeline': 6, 'video': 1
    };
    
    const layoutDescriptions = {
        'standard': '<strong>Tiêu chuẩn:</strong> Bố cục cơ bản với 1 ảnh đại diện.',
        'hero': '<strong>Hero Banner:</strong> 1 ảnh lớn chiếm toàn bộ phần đầu.',
        'gallery': '<strong>Gallery:</strong> 1 ảnh đại diện + 12 ảnh lưới.',
        'slider': '<strong>Slider:</strong> 1 ảnh chính + 6 slide trình chiếu.',
        'video': '<strong>Video:</strong> 1 ảnh thumbnail + video YouTube/Vimeo.'
        // ... rest of descriptions
    };

    function updateImagePickers(layout) {
        const count = layoutImageCount[layout] || 1;
        document.getElementById('imageCountHint').textContent = `Layout "${layout}": ${count} ảnh`;
        
        document.querySelectorAll('.image-picker-card').forEach(card => {
            const index = parseInt(card.dataset.pickerIndex);
            card.classList.toggle('hidden', index >= count);
        });
        
        document.getElementById('videoUrlSection').classList.toggle('hidden', layout !== 'video');
    }

    document.querySelectorAll('input[name="layout"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.layout-card').forEach(c => c.classList.remove('border-primary', 'bg-primary/5'));
            this.closest('.layout-card').classList.add('border-primary', 'bg-primary/5');
            updateImagePickers(this.value);
            if (layoutDescriptions[this.value]) {
                document.getElementById('layoutDescription').innerHTML = `<p class="text-gray-600">${layoutDescriptions[this.value]}</p>`;
            }
        });
        if (radio.checked) radio.dispatchEvent(new Event('change'));
    });

    let currentPickerIndex = 0;
    window.openImageModal = (i) => {
        currentPickerIndex = i;
        document.getElementById('imageSelectModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };
    window.closeImageModal = () => {
        document.getElementById('imageSelectModal').classList.add('hidden');
        document.body.style.overflow = '';
    };

    window.selectImageFromModal = (el) => {
        const src = el.dataset.src;
        const displaySrc = '../' + src;
        
        const preview = document.getElementById('pickerPreview_' + currentPickerIndex);
        const empty = document.getElementById('pickerEmpty_' + currentPickerIndex);
        const clearBtn = document.getElementById('pickerClearBtn_' + currentPickerIndex);
        const cardBox = document.querySelector(`.image-picker-card[data-picker-index="${currentPickerIndex}"] .w-32`);

        preview.src = displaySrc;
        preview.classList.remove('hidden');
        empty.classList.add('hidden');
        clearBtn.classList.remove('hidden');
        cardBox.classList.add('border-primary', 'shadow-sm');
        cardBox.classList.remove('border-dashed', 'border-gray-200');

        if (currentPickerIndex === 0) document.getElementById('pickerInput_0').value = src;
        updateGalleryFromPickers();
        closeImageModal();
    };

    window.clearPicker = (i) => {
        const preview = document.getElementById('pickerPreview_' + i);
        const empty = document.getElementById('pickerEmpty_' + i);
        const clearBtn = document.getElementById('pickerClearBtn_' + i);
        const cardBox = document.querySelector(`.image-picker-card[data-picker-index="${i}"] .w-32`);

        preview.src = '';
        preview.classList.add('hidden');
        empty.classList.remove('hidden');
        clearBtn.classList.add('hidden');
        cardBox.classList.remove('border-primary', 'shadow-sm');
        cardBox.classList.add('border-dashed', 'border-gray-200');

        if (i === 0) document.getElementById('pickerInput_0').value = '';
        updateGalleryFromPickers();
    };

    function updateGalleryFromPickers() {
        const gallery = [];
        document.querySelectorAll('.image-picker-card:not(.hidden)').forEach(card => {
            const index = parseInt(card.dataset.pickerIndex);
            if (index > 0) {
                const preview = card.querySelector('.picker-preview');
                if (preview && preview.src && !preview.classList.contains('hidden')) {
                    let s = preview.src;
                    if (s.includes('uploads/')) s = 'uploads/' + s.split('uploads/')[1];
                    gallery.push(s);
                }
            }
        });
        document.getElementById('galleryImagesInput').value = JSON.stringify(gallery);
    }

    // Upload logic
    const fileInput = document.getElementById('fileInput');
    const uploadZone = document.getElementById('uploadZone');
    document.getElementById('uploadNewBtn').onclick = () => uploadZone.classList.toggle('hidden');
    uploadZone.onclick = () => fileInput.click();
    fileInput.onchange = () => { if(fileInput.files.length) uploadMultipleFiles(fileInput.files); };

    function uploadMultipleFiles(files) {
        const valid = Array.from(files).filter(f => f.type.startsWith('image/') && f.size <= 5 * 1024 * 1024);
        if(!valid.length) return alert('File không hợp lệ');

        const progContainer = document.getElementById('multiUploadProgress');
        const list = document.getElementById('uploadProgressList');
        document.getElementById('uploadingCount').textContent = valid.length;
        progContainer.classList.remove('hidden');
        list.innerHTML = '';

        let done = 0;
        valid.forEach(f => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 text-sm';
            row.innerHTML = `<span class="truncate w-24 font-medium">${f.name}</span><div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden"><div class="bar h-full bg-primary transition-all" style="width:0%"></div></div><span class="pct text-[10px] font-bold">0%</span>`;
            list.appendChild(row);

            const bar = row.querySelector('.bar'), pct = row.querySelector('.pct');
            const fd = new FormData(); fd.append('image', f);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/upload-image.php', true);
            xhr.upload.onprogress = e => { if(e.lengthComputable) { const p = Math.round(e.loaded/e.total*100); bar.style.width=p+'%'; pct.textContent=p+'%'; }};
            xhr.onload = () => {
                const res = JSON.parse(xhr.responseText);
                if(res.success) {
                    bar.className = 'bar h-full bg-green-500'; pct.textContent = '✓';
                    addImageToModalGrid(res.url, res.filename);
                } else {
                    bar.className = 'bar h-full bg-red-500'; pct.textContent = '✗';
                }
                if(++done === valid.length) setTimeout(() => progContainer.classList.add('hidden'), 1500);
            };
            xhr.send(fd);
        });
    }

    function addImageToModalGrid(url, filename) {
        const grid = document.getElementById('modalImageGrid');
        if(!grid) location.reload();
        const item = document.createElement('div');
        item.className = 'modal-image-item aspect-square rounded-2xl overflow-hidden border-4 border-transparent hover:border-primary transition-all relative group cursor-pointer';
        item.dataset.src = url; item.dataset.filename = filename;
        item.onclick = () => selectImageFromModal(item);
        item.innerHTML = `<img src="../${url}" class="w-full h-full object-cover"><div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></div><button type="button" class="absolute top-2 right-2 w-7 h-7 bg-red-500 text-white rounded-full items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-opacity flex z-10 hover:bg-red-600" onclick="event.stopPropagation(); deleteImageFromServer(this, '${filename}')"><span class="material-symbols-outlined text-xs">delete</span></button>`;
        grid.prepend(item);
    }

    window.deleteImageFromServer = (btn, name) => {
        if(!confirm(`Xóa "${name}"?`)) return;
        fetch('api/delete-image.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'filename='+encodeURIComponent(name) })
        .then(r=>r.json()).then(d => { if(d.success) btn.closest('.modal-image-item').remove(); });
    };

    window.openPreviewModal = () => {
        const layout = document.querySelector('input[name="layout"]:checked').value;
        const title = document.querySelector('input[name="title"]').value;
        document.getElementById('previewLayoutName').textContent = layout.toUpperCase();
        document.getElementById('previewContent').innerHTML = `<h1 class="text-4xl font-black mb-6 text-gray-900">${title || 'Tiêu đề'}</h1><div class="prose max-w-none text-gray-700 leading-relaxed">${document.querySelector('textarea[name="content"]').value || 'Nội dung...'}</div>`;
        document.getElementById('layoutPreviewModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };
    window.closePreviewModal = () => {
        document.getElementById('layoutPreviewModal').classList.add('hidden');
        document.body.style.overflow = '';
    };
});
</script>