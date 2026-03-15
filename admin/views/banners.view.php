<?php
/**
 * Aurora Hotel Plaza - Banners View
 * Displays banners management page
 */
?>

<div class="mb-6 flex justify-end">
    <button onclick="openBannerModal()" class="btn btn-primary">
        <span class="material-symbols-outlined text-sm">add</span>
        Thêm Banner
    </button>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($banners)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined empty-state-icon">image</span>
                <p class="empty-state-title">Chưa có banner nào</p>
                <p class="empty-state-description">Thêm banner để hiển thị trên trang chủ</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($banners as $banner): ?>
                    <div class="card border border-gray-100 hover:shadow-lg transition-all duration-300">
                        <div class="relative group">
                            <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" 
                                 alt="Banner" class="w-full h-48 object-cover rounded-t-xl">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 rounded-t-xl">
                                <button onclick="editBanner(<?php echo $banner['banner_id']; ?>)" 
                                        class="btn btn-sm btn-primary">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <button onclick="deleteBanner(<?php echo $banner['banner_id']; ?>)" 
                                        class="btn btn-sm btn-danger">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <h4 class="font-semibold mb-1 truncate text-gray-800"><?php echo htmlspecialchars($banner['title']); ?></h4>
                            <?php if ($banner['subtitle']): ?>
                                <p class="text-xs text-gray-500 mb-3 truncate"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                            <?php endif; ?>
                            <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-50">
                                <span class="badge badge-<?php echo $banner['is_active'] ? 'success' : 'secondary'; ?> text-xs">
                                    <?php echo $banner['is_active'] ? 'Hoạt động' : 'Tạm ngưng'; ?>
                                </span>
                                <span class="text-xs text-gray-400 font-medium">Thứ tự: <?php echo $banner['sort_order']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Banner Modal -->
<div id="bannerModal" class="modal fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeBannerModal()"></div>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="modal-header px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900" id="modalTitle">Thêm Banner</h3>
                <button onclick="closeBannerModal()" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <form id="bannerForm" onsubmit="saveBanner(event)">
                <div class="modal-body px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                    <input type="hidden" name="banner_id" id="banner_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label block text-sm font-semibold text-gray-700 mb-1">Tiêu đề *</label>
                            <input type="text" name="title" id="form_title" class="form-input w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label block text-sm font-semibold text-gray-700 mb-1">Phụ đề</label>
                            <input type="text" name="subtitle" id="form_subtitle" class="form-input w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label block text-sm font-semibold text-gray-700 mb-1">URL hình ảnh *</label>
                        <div class="flex gap-2">
                            <input type="url" name="image_url" id="form_image_url" class="form-input flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required 
                                placeholder="https://..." onchange="previewBannerImage(this.value)">
                        </div>
                        <div id="imagePreview" class="mt-2 hidden">
                            <img src="" alt="Preview" class="w-full h-32 object-cover rounded-lg border">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label block text-sm font-semibold text-gray-700 mb-1">Link đích</label>
                        <input type="url" name="link_url" id="form_link_url" class="form-input w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" 
                               placeholder="https://...">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label block text-sm font-semibold text-gray-700 mb-1">Thứ tự hiển thị</label>
                            <input type="number" name="sort_order" id="form_sort_order" class="form-input w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" value="0" min="0">
                        </div>
                        
                        <div class="form-group flex items-center mt-6">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" id="form_is_active" value="1" checked 
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span class="text-sm text-gray-700">Hiển thị ngay</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50">
                    <button type="button" onclick="closeBannerModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">Hủy</button>
                    <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">Lưu Banner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openBannerModal() {
    document.getElementById('modalTitle').textContent = 'Thêm Banner';
    document.getElementById('banner_id').value = '';
    document.getElementById('bannerForm').reset();
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('bannerModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeBannerModal() {
    document.getElementById('bannerModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function previewBannerImage(url) {
    const preview = document.getElementById('imagePreview');
    const img = preview.querySelector('img');
    if (url) {
        img.src = url;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
}

function editBanner(id) {
    // Get banner data from current list to avoid extra API call
    const banners = <?php echo json_encode($banners); ?>;
    const banner = banners.find(b => b.banner_id == id);
    
    if (banner) {
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa Banner';
        document.getElementById('banner_id').value = banner.banner_id;
        document.getElementById('form_title').value = banner.title;
        document.getElementById('form_subtitle').value = banner.subtitle || '';
        document.getElementById('form_image_url').value = banner.image_url;
        document.getElementById('form_link_url').value = banner.link_url || '';
        document.getElementById('form_sort_order').value = banner.sort_order;
        document.getElementById('form_is_active').checked = banner.is_active == 1;
        
        previewBannerImage(banner.image_url);
        document.getElementById('bannerModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function saveBanner(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // Convert checkbox to value if not checked
    if (!formData.has('is_active')) {
        formData.append('is_active', '0');
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="animate-spin inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full mr-2"></span> Đang lưu...';
    
    fetch('api/save-banner.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: 'Banner đã được lưu thành công',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: data.message || 'Có lỗi xảy ra'
            });
            submitBtn.disabled = false;
            submitBtn.textContent = 'Lưu Banner';
        }
    })
    .catch(err => {
        console.error('Save error:', err);
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Không thể kết nối với máy chủ'
        });
        submitBtn.disabled = false;
        submitBtn.textContent = 'Lưu Banner';
    });
}

function deleteBanner(id) {
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: "Bạn sẽ không thể khôi phục lại banner này!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Có, xóa ngay!',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/delete-banner.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'banner_id=' + id
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã xóa!',
                        text: 'Banner đã được xóa thành công',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: data.message || 'Có lỗi xảy ra'
                    });
                }
            });
        }
    });
}
</script>