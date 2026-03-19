<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý thư viện ảnh';
$page_subtitle = 'Quản lý hình ảnh khách sạn';

// Get filter parameters
$category_filter = $_GET['category'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Build query
$where_clauses = [];
$params = [];

if ($category_filter !== 'all') {
    $where_clauses[] = "category = :category";
    $params[':category'] = $category_filter;
}

if ($status_filter !== 'all') {
    $where_clauses[] = "status = :status";
    $params[':status'] = $status_filter;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    // Get gallery images
    $sql = "
        SELECT g.*, u.full_name as uploaded_by_name
        FROM gallery g
        LEFT JOIN users u ON g.uploaded_by = u.user_id
        $where_sql
        ORDER BY g.sort_order ASC, g.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories
    $stmt = $db->query("SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
        FROM gallery
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Gallery page error: " . $e->getMessage());
    $images = [];
    $categories = [];
    $counts = ['total' => 0, 'active' => 0, 'inactive' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng hình ảnh</p>
        <p class="text-2xl font-bold"><?php echo $counts['total']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đang hiển thị</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $counts['active']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đã ẩn</p>
        <p class="text-2xl font-bold text-gray-600"><?php echo $counts['inactive']; ?></p>
    </div>
</div>

<!-- Action Bar -->
<div class="flex items-center justify-between mb-6">
    <form method="GET" class="flex gap-2">
        <select name="category" class="form-select">
            <option value="all">Tất cả danh mục</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" 
                        <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="status" class="form-select">
            <option value="all">Tất cả trạng thái</option>
            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Đang hiển thị</option>
            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Đã ẩn</option>
        </select>
        
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">filter_alt</span>
            Lọc
        </button>
    </form>
    
    <button onclick="openGalleryModal()" class="btn btn-primary">
        <span class="material-symbols-outlined text-sm">add</span>
        Thêm hình ảnh
    </button>
</div>

<!-- Gallery Grid -->
<?php if (empty($images)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <span class="empty-state-icon material-symbols-outlined">photo_library</span>
                <p class="empty-state-title">Chưa có hình ảnh nào</p>
                <p class="empty-state-description">Thêm hình ảnh đầu tiên vào thư viện</p>
                <button onclick="openGalleryModal()" class="btn btn-primary mt-4">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Thêm hình ảnh
                </button>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($images as $image): ?>
            <div class="card p-0 overflow-hidden group">
                <!-- Image -->
                <div class="relative aspect-square overflow-hidden bg-gray-200 dark:bg-gray-700">
                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($image['title']); ?>"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                    
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <button onclick="viewImage('<?php echo htmlspecialchars($image['image_url']); ?>')" 
                                class="w-10 h-10 bg-white rounded-full flex items-center justify-center hover:bg-gray-100">
                            <span class="material-symbols-outlined text-gray-800">visibility</span>
                        </button>
                        <button onclick='editImage(<?php echo json_encode($image); ?>)' 
                                class="w-10 h-10 bg-white rounded-full flex items-center justify-center hover:bg-gray-100">
                            <span class="material-symbols-outlined text-gray-800">edit</span>
                        </button>
                        <button onclick="deleteImage(<?php echo $image['gallery_id']; ?>)" 
                                class="w-10 h-10 bg-white rounded-full flex items-center justify-center hover:bg-gray-100">
                            <span class="material-symbols-outlined text-red-600">delete</span>
                        </button>
                    </div>
                    
                    <!-- Status Badge -->
                    <div class="absolute top-2 right-2">
                        <span class="badge badge-<?php echo $image['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo $image['status'] === 'active' ? 'Hiển thị' : 'Ẩn'; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Info -->
                <div class="p-3">
                    <h4 class="font-medium text-sm mb-1 truncate"><?php echo htmlspecialchars($image['title']); ?></h4>
                    <?php if ($image['category']): ?>
                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark">
                            <?php echo htmlspecialchars($image['category']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Gallery Modal -->
<div id="galleryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="font-semibold" id="modalTitle">Thêm hình ảnh</h3>
            <button onclick="closeGalleryModal()" class="text-text-secondary-light dark:text-text-secondary-dark hover:text-text-primary-light dark:hover:text-text-primary-dark">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="galleryForm">
                <input type="hidden" id="gallery_id" name="gallery_id">
                
                <div class="form-group">
                    <label class="form-label">Tiêu đề *</label>
                    <input type="text" id="title" name="title" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">URL hình ảnh *</label>
                    <input type="url" id="image_url" name="image_url" class="form-input" 
                           placeholder="https://..." required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">URL thumbnail</label>
                    <input type="url" id="thumbnail_url" name="thumbnail_url" class="form-input" 
                           placeholder="https://...">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Danh mục</label>
                    <input type="text" id="category" name="category" class="form-input" 
                           list="categoryList" placeholder="VD: Phòng, Nhà hàng, Spa...">
                    <datalist id="categoryList">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea id="description" name="description" class="form-textarea" rows="3"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group mb-0">
                        <label class="form-label">Thứ tự hiển thị</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-input" value="0" min="0">
                    </div>
                    
                    <div class="form-group mb-0">
                        <label class="form-label">Trạng thái</label>
                        <select id="status" name="status" class="form-select">
                            <option value="active">Hiển thị</option>
                            <option value="inactive">Ẩn</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeGalleryModal()" class="btn btn-secondary">Hủy</button>
            <button type="button" onclick="submitGallery()" class="btn btn-primary">Lưu</button>
        </div>
    </div>
</div>

<!-- Image Viewer Modal -->
<div id="imageViewerModal" class="modal">
    <div class="modal-content max-w-4xl">
        <div class="modal-header">
            <h3 class="font-semibold">Xem hình ảnh</h3>
            <button onclick="closeImageViewer()" class="text-text-secondary-light dark:text-text-secondary-dark hover:text-text-primary-light dark:hover:text-text-primary-dark">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <img id="viewerImage" src="" alt="" class="w-full h-auto rounded-lg">
        </div>
    </div>
</div>

<script>
function openGalleryModal() {
    document.getElementById('modalTitle').textContent = 'Thêm hình ảnh';
    document.getElementById('galleryForm').reset();
    document.getElementById('gallery_id').value = '';
    document.getElementById('galleryModal').classList.add('active');
}

function closeGalleryModal() {
    document.getElementById('galleryModal').classList.remove('active');
}

function editImage(image) {
    document.getElementById('modalTitle').textContent = 'Sửa hình ảnh';
    document.getElementById('gallery_id').value = image.gallery_id;
    document.getElementById('title').value = image.title;
    document.getElementById('image_url').value = image.image_url;
    document.getElementById('thumbnail_url').value = image.thumbnail_url || '';
    document.getElementById('category').value = image.category || '';
    document.getElementById('description').value = image.description || '';
    document.getElementById('sort_order').value = image.sort_order;
    document.getElementById('status').value = image.status;
    document.getElementById('galleryModal').classList.add('active');
}

function submitGallery() {
    const form = document.getElementById('galleryForm');
    const formData = new FormData(form);
    
    fetch('api/save-gallery.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Lưu thành công!', 'success');
            closeGalleryModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra', 'error');
    });
}

function deleteImage(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')) return;
    
    fetch('api/delete-gallery.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'gallery_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Xóa thành công!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra', 'error');
    });
}

function viewImage(url) {
    document.getElementById('viewerImage').src = url;
    document.getElementById('imageViewerModal').classList.add('active');
}

function closeImageViewer() {
    document.getElementById('imageViewerModal').classList.remove('active');
}

// Close modals when clicking outside
document.getElementById('galleryModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeGalleryModal();
});

document.getElementById('imageViewerModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeImageViewer();
});
</script>

<?php include 'includes/admin-footer.php'; ?>
