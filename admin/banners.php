<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý Banner';
$page_subtitle = 'Banner trang chủ và quảng cáo';

try {
    $db = getDB();
    
    $stmt = $db->query("
        SELECT * FROM banners
        ORDER BY sort_order ASC, created_at DESC
    ");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Banners error: " . $e->getMessage());
    $banners = [];
}

include 'includes/admin-header.php';
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
                    <div class="card">
                        <div class="relative group">
                            <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" 
                                 alt="Banner" class="w-full h-48 object-cover rounded-t-xl">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
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
                            <h4 class="font-semibold mb-2"><?php echo htmlspecialchars($banner['title']); ?></h4>
                            <?php if ($banner['subtitle']): ?>
                                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                            <?php endif; ?>
                            <div class="flex items-center justify-between text-sm">
                                <span class="badge badge-<?php echo $banner['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $banner['is_active'] ? 'Hoạt động' : 'Tạm ngưng'; ?>
                                </span>
                                <span class="text-gray-500">Thứ tự: <?php echo $banner['sort_order']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Banner Modal -->
<div id="bannerModal" class="modal">
    <div class="modal-content max-w-2xl">
        <div class="modal-header">
            <h3 class="font-bold text-lg">Thêm Banner</h3>
            <button onclick="closeBannerModal()" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="bannerForm" onsubmit="saveBanner(event)">
            <div class="modal-body space-y-4">
                <input type="hidden" name="banner_id" id="banner_id">
                
                <div class="form-group">
                    <label class="form-label">Tiêu đề *</label>
                    <input type="text" name="title" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phụ đề</label>
                    <input type="text" name="subtitle" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">URL hình ảnh *</label>
                    <input type="url" name="image_url" class="form-input" required 
                           placeholder="https://...">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Link đích</label>
                    <input type="url" name="link_url" class="form-input" 
                           placeholder="https://...">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Thứ tự hiển thị</label>
                        <input type="number" name="sort_order" class="form-input" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Hiển thị ngay</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeBannerModal()" class="btn btn-secondary">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBannerModal() {
    document.getElementById('bannerModal').classList.add('active');
    document.getElementById('bannerForm').reset();
}

function closeBannerModal() {
    document.getElementById('bannerModal').classList.remove('active');
}

function saveBanner(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('api/save-banner.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    });
}

function deleteBanner(id) {
    if (!confirm('Bạn có chắc muốn xóa banner này?')) return;
    
    fetch('api/delete-banner.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'banner_id=' + id
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    });
}
</script>

<?php include 'includes/admin-footer.php'; ?>
