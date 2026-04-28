<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý Banner';
$page_subtitle = 'Banner trang chủ và quảng cáo';

try {
    $db = getDB();
    
    $stmt = $db->query("SELECT * FROM banners ORDER BY sort_order ASC, created_at DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM banners");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Banners error: " . $e->getMessage());
    $banners = [];
    $stats = ['total' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-gray-500">Tổng banner</p>
        <p class="text-2xl font-bold"><?php echo $stats['total']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-gray-500">Đang hoạt động</p>
        <p class="text-2xl font-bold text-green-600"><?php echo count(array_filter($banners, fn($b) => $b['is_active'])); ?></p>
    </div>
</div>

<!-- Action Bar -->
<div class="mb-6 flex justify-end gap-3">
    <button onclick="openBannerModal()" class="btn btn-primary">
        <span class="material-symbols-outlined text-sm">add</span>
        Thêm Banner
    </button>
</div>

<!-- Banner Grid -->
<div class="card">
    <div class="card-body">
        <?php if (empty($banners)): ?>
            <div class="text-center py-12">
                <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">image</span>
                <p class="text-gray-500 mb-2">Chưa có banner nào</p>
                <button onclick="openBannerModal()" class="btn btn-primary">Thêm banner đầu tiên</button>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="bannerGrid">
                <?php foreach ($banners as $banner): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" data-banner-id="<?php echo $banner['banner_id']; ?>">
                        <div class="relative group">
                            <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjkwIiB2aWV3Qm94PSIwIDAgMTIwIDkwIiBmaWxsPSIjZTNlNmVmIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iOTAiIGZpbGw9IiNlM2U2ZWYiLz48dGV4dCB4PSI2MCIgeT0iNDUiIGZpbGw9IiM5Y2EzYWYiIGZvbnQtZmFtaWx5PSJJbnRlciIgZm9udC1zaXplPSIxMiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <button onclick="editBanner(<?php echo $banner['banner_id']; ?>)" 
                                        class="bg-white text-gray-700 px-3 py-2 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                    Sửa
                                </button>
                                <button onclick="toggleBannerStatus(<?php echo $banner['banner_id']; ?>, <?php echo $banner['is_active'] ? 0 : 1; ?>)" 
                                        class="bg-white text-gray-700 px-3 py-2 rounded-lg hover:bg-<?php echo $banner['is_active'] ? 'gray' : 'green'; ?>-600 hover:text-white transition-colors flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm"><?php echo $banner['is_active'] ? 'visibility_off' : 'visibility'; ?></span>
                                    <?php echo $banner['is_active'] ? 'Tắt' : 'Bật'; ?>
                                </button>
                                <button onclick="deleteBanner(<?php echo $banner['banner_id']; ?>)" 
                                        class="bg-white text-gray-700 px-3 py-2 rounded-lg hover:bg-red-600 hover:text-white transition-colors flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                    Xóa
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <h4 class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($banner['title']); ?></h4>
                            <?php if ($banner['subtitle']): ?>
                                <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                            <?php endif; ?>
                            <div class="flex items-center justify-between text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $banner['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                    <?php echo $banner['is_active'] ? '✓ Hoạt động' : '○ Tạm ngưng'; ?>
                                </span>
                                <span class="text-gray-400">STT: <?php echo $banner['sort_order']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Banner Modal -->
<div id="bannerModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="font-bold text-lg" id="modalTitle">Thêm Banner</h3>
            <button onclick="closeBannerModal()" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="bannerForm" class="p-4 space-y-4">
            <input type="hidden" name="banner_id" id="banner_id">
            
            <div>
                <label class="block text-sm font-medium mb-1">Tiêu đề *</label>
                <input type="text" name="title" id="banner_title" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Phụ đề</label>
                <input type="text" name="subtitle" id="banner_subtitle" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            
            <!-- Image Selection -->
            <div>
                <label class="block text-sm font-medium mb-1">Hình ảnh *</label>
                <div class="flex gap-2 mb-2">
                    <input type="text" name="image_url" id="banner_image_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required placeholder="URL hình ảnh...">
                    <button type="button" onclick="openGalleryPicker()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                        <span class="material-symbols-outlined text-sm">photo_library</span>
                        Gallery
                    </button>
                </div>
                <div id="imagePreview" class="hidden">
                    <img id="previewImg" class="w-full h-32 object-cover rounded-lg border border-gray-200">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Link đích (khi click)</label>
                <input type="url" name="link_url" id="banner_link_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="https://...">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" id="banner_sort_order" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="0" min="0">
                </div>
                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="banner_is_active" value="1" checked class="w-4 h-4">
                        <span>Hiển thị ngay</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeBannerModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Hủy
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">save</span>
                    Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Gallery Picker Modal -->
<div id="galleryModal" class="fixed inset-0 bg-black/50 z-[60] hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="font-bold text-lg">Chọn ảnh từ Gallery</h3>
            <button onclick="closeGalleryPicker()" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-4">
            <div class="flex gap-2 mb-4">
                <select id="galleryCategory" class="px-4 py-2 border border-gray-300 rounded-lg" onchange="loadGalleryImages()">
                    <option value="all">Tất cả danh mục</option>
                </select>
                <input type="text" id="gallerySearch" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Tìm kiếm..." oninput="loadGalleryImages()">
            </div>
            <div id="galleryGrid" class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                <div class="text-center py-8 text-gray-500">Đang tải...</div>
            </div>
        </div>
    </div>
</div>

<script>
let galleryImages = [];
let selectedImageUrl = '';

function openBannerModal(bannerId = null) {
    document.getElementById('bannerModal').classList.remove('hidden');
    document.getElementById('bannerForm').reset();
    document.getElementById('modalTitle').textContent = 'Thêm Banner';
    document.getElementById('banner_id').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
}

function closeBannerModal() {
    document.getElementById('bannerModal').classList.add('hidden');
}

function editBanner(bannerId) {
    fetch('api/get-banner.php?banner_id=' + bannerId)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const banner = data.banner;
                document.getElementById('modalTitle').textContent = 'Sửa Banner';
                document.getElementById('banner_id').value = banner.banner_id;
                document.getElementById('banner_title').value = banner.title;
                document.getElementById('banner_subtitle').value = banner.subtitle || '';
                document.getElementById('banner_image_url').value = banner.image_url;
                document.getElementById('banner_link_url').value = banner.link_url || '';
                document.getElementById('banner_sort_order').value = banner.sort_order;
                document.getElementById('banner_is_active').checked = banner.is_active == 1;
                
                document.getElementById('previewImg').src = banner.image_url;
                document.getElementById('imagePreview').classList.remove('hidden');
                
                openBannerModal();
            } else {
                alert(data.message || 'Không tìm thấy banner');
            }
        })
        .catch(err => {
            alert('Lỗi kết nối');
            console.error(err);
        });
}

document.getElementById('bannerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const bannerId = formData.get('banner_id');
    
    fetch('api/save-banner.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeBannerModal();
            refreshBannerGrid();
            showNotification(data.message, 'success');
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(err => {
        alert('Lỗi kết nối');
        console.error(err);
    });
});

function deleteBanner(bannerId) {
    if (!confirm('Bạn có chắc muốn xóa banner này?')) return;
    
    fetch('api/delete-banner.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'banner_id=' + bannerId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const card = document.querySelector('[data-banner-id="' + bannerId + '"]');
            if (card) card.remove();
            showNotification('Xóa banner thành công', 'success');
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(err => {
        alert('Lỗi kết nối');
        console.error(err);
    });
}

function toggleBannerStatus(bannerId, newStatus) {
    const formData = new FormData();
    formData.append('banner_id', bannerId);
    formData.append('is_active', newStatus);
    
    fetch('api/save-banner.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            refreshBannerGrid();
            showNotification('Cập nhật trạng thái thành công', 'success');
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(err => {
        alert('Lỗi kết nối');
        console.error(err);
    });
}

function refreshBannerGrid() {
    fetch('api/get-all-banners.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderBannerGrid(data.banners);
            }
        });
}

function renderBannerGrid(banners) {
    const grid = document.getElementById('bannerGrid');
    grid.innerHTML = banners.map(banner => `
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" data-banner-id="${banner.banner_id}">
            <div class="relative group">
                <img src="${banner.image_url}" alt="${banner.title}" class="w-full h-48 object-cover" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjkwIiB2aWV3Qm94PSIwIDAgMTIwIDkwIiBmaWxsPSIjZTNlNmVmIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iOTAiIGZpbGw9IiNlM2U2ZWYiLz48dGV4dCB4PSI2MCIgeT0iNDUiIGZpbGw9IiM5Y2EzYWYiIGZvbnQtZmFtaWx5PSJJbnRlciIgZm9udC1zaXplPSIxMiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                    <button onclick="editBanner(${banner.banner_id})" class="bg-white text-gray-700 px-3 py-2 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">edit</span>
                        Sửa
                    </button>
                    <button onclick="toggleBannerStatus(${banner.banner_id}, ${banner.is_active ? 0 : 1})" class="bg-white text-gray-700 px-3 py-2 rounded-lg hover:bg-${banner.is_active ? 'gray' : 'green'}-600 hover:text-white transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">${banner.is_active ? 'visibility_off' : 'visibility'}</span>
                        ${banner.is_active ? 'Tắt' : 'Bật'}
                    </button>
                    <button onclick="deleteBanner(${banner.banner_id})" class="bg-white text-gray-700 px-3 py-2 rounded-lg hover:bg-red-600 hover:text-white transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">delete</span>
                        Xóa
                    </button>
                </div>
            </div>
            <div class="p-4">
                <h4 class="font-semibold text-gray-900 mb-1">${banner.title}</h4>
                ${banner.subtitle ? `<p class="text-sm text-gray-500 mb-2">${banner.subtitle}</p>` : ''}
                <div class="flex items-center justify-between text-sm">
                    <span class="px-2 py-1 rounded-full text-xs font-medium ${banner.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">
                        ${banner.is_active ? '✓ Hoạt động' : '○ Tạm ngưng'}
                    </span>
                    <span class="text-gray-400">STT: ${banner.sort_order}</span>
                </div>
            </div>
        </div>
    `).join('');
}

function showNotification(message, type) {
    const div = document.createElement('div');
    div.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-[70] ${type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'}`;
    div.textContent = message;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3000);
}

// Gallery Picker
function openGalleryPicker() {
    document.getElementById('galleryModal').classList.remove('hidden');
    loadGalleryImages();
}

function closeGalleryPicker() {
    document.getElementById('galleryModal').classList.add('hidden');
}

function loadGalleryImages() {
    const category = document.getElementById('galleryCategory').value;
    const search = document.getElementById('gallerySearch').value;
    
    fetch(`api/get-gallery-images.php?category=${encodeURIComponent(category)}&search=${encodeURIComponent(search)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                galleryImages = data.images;
                
                const categorySelect = document.getElementById('galleryCategory');
                categorySelect.innerHTML = '<option value="all">Tất cả danh mục</option>' + 
                    data.categories.map(cat => `<option value="${cat}">${cat}</option>`).join('');
                
                renderGalleryGrid(data.images);
            }
        });
}

function renderGalleryGrid(images) {
    const grid = document.getElementById('galleryGrid');
    
    if (images.length === 0) {
        grid.innerHTML = '<div class="text-center py-8 text-gray-500 col-span-full">Không có ảnh</div>';
        return;
    }
    
    grid.innerHTML = images.map(img => `
        <div onclick="selectGalleryImage('${img.image_url}')" class="cursor-pointer group relative">
            <img src="${img.thumbnail_url || img.image_url}" alt="${img.title || ''}" class="w-full h-24 object-cover rounded-lg border border-gray-200 group-hover:border-indigo-500 transition-colors">
            <div class="absolute inset-0 bg-indigo-600/0 group-hover:bg-indigo-600/20 transition-colors rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-white opacity-0 group-hover:opacity-100 transition-opacity">check_circle</span>
            </div>
        </div>
    `).join('');
}

function selectGalleryImage(url) {
    document.getElementById('banner_image_url').value = url;
    document.getElementById('previewImg').src = url;
    document.getElementById('imagePreview').classList.remove('hidden');
    closeGalleryPicker();
}

document.getElementById('banner_image_url').addEventListener('input', function() {
    const url = this.value;
    if (url) {
        document.getElementById('previewImg').src = url;
        document.getElementById('imagePreview').classList.remove('hidden');
    } else {
        document.getElementById('imagePreview').classList.add('hidden');
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>