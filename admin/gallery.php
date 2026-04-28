<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/image-helper.php';

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
    
    <div class="flex gap-2">
        <button onclick="openUploadModal()" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">cloud_upload</span>
            Upload ảnh
        </button>
        <button onclick="openGalleryModal()" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">add</span>
            Thêm từ URL
        </button>
    </div>
</div>

<!-- Bulk Upload Area -->
<div id="uploadModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="font-bold text-lg">Upload nhiều ảnh</h3>
            <button onclick="closeUploadModal()" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-4">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Danh mục</label>
                <select id="uploadCategory" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="general">General</option>
                    <option value="room">Phòng</option>
                    <option value="restaurant">Nhà hàng</option>
                    <option value="spa">Spa</option>
                    <option value="event">Sự kiện</option>
                    <option value="facility">Tiện ích</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Drag & Drop Area -->
            <div id="dropArea" class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center cursor-pointer hover:border-indigo-500 hover:bg-indigo-50 transition-colors">
                <input type="file" id="fileInput" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="hidden" onchange="handleFiles(this.files)">
                <div onclick="document.getElementById('fileInput').click()">
                    <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">cloud_upload</span>
                    <p class="text-gray-600 font-medium mb-2">Kéo thả ảnh vào đây hoặc click để chọn</p>
                    <p class="text-sm text-gray-400">JPG, PNG, GIF, WebP (tối đa 10MB mỗi file)</p>
                    <p class="text-xs text-gray-400 mt-1">Có thể chọn nhiều ảnh cùng lúc</p>
                </div>
            </div>
            
            <!-- Progress Area -->
            <div id="uploadProgressArea" class="mt-4 hidden">
                <h4 class="font-medium mb-2">Tiến trình upload:</h4>
                <div id="uploadList" class="space-y-2 max-h-60 overflow-y-auto"></div>
            </div>
            
            <!-- Preview Area -->
            <div id="previewArea" class="mt-4 hidden">
                <h4 class="font-medium mb-2">Ảnh đã upload:</h4>
                <div id="previewGrid" class="grid grid-cols-4 gap-2"></div>
            </div>
        </div>
        <div class="p-4 border-t border-gray-200 flex justify-end gap-2">
            <button onclick="closeUploadModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Đóng
            </button>
        </div>
    </div>
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
                    <img src="<?php echo imgUrl($image['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($image['title']); ?>"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                         onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); ?>'">
                    
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <button onclick="viewImage('<?php echo imgUrl($image['image_url']); ?>')" 
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
// Upload Modal
function openUploadModal() {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadProgressArea').classList.add('hidden');
    document.getElementById('previewArea').classList.add('hidden');
    document.getElementById('uploadList').innerHTML = '';
    document.getElementById('previewGrid').innerHTML = '';
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
}

// Drag & Drop
const dropArea = document.getElementById('dropArea');
let uploadedCount = 0;
let totalCount = 0;

dropArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropArea.classList.add('border-indigo-500', 'bg-indigo-50');
});

dropArea.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropArea.classList.remove('border-indigo-500', 'bg-indigo-50');
});

dropArea.addEventListener('drop', (e) => {
    e.preventDefault();
    dropArea.classList.remove('border-indigo-500', 'bg-indigo-50');
    
    const files = e.dataTransfer.files;
    handleFiles(files);
});

function handleFiles(files) {
    if (!files || files.length === 0) return;
    
    const category = document.getElementById('uploadCategory').value;
    totalCount = files.length;
    uploadedCount = 0;
    
    document.getElementById('uploadProgressArea').classList.remove('hidden');
    document.getElementById('uploadList').innerHTML = '';
    
    Array.from(files).forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            uploadGalleryImage(file, category, index);
        }
    });
}

function uploadGalleryImage(file, category, index) {
    if (file.size > 10 * 1024 * 1024) {
        addUploadStatus(index, file.name, 'error', 'File quá lớn (max 10MB)');
        return;
    }
    
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        addUploadStatus(index, file.name, 'error', 'Không hỗ trợ định dạng này');
        return;
    }
    
    addUploadStatus(index, file.name, 'progress', '0%');
    
    const formData = new FormData();
    formData.append('image', file);
    formData.append('category', category);
    formData.append('title', pathinfo(file.name).filename);
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            updateUploadProgress(index, percent + '%');
        }
    });
    
    xhr.addEventListener('load', () => {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                updateUploadStatus(index, file.name, 'success', 'Done');
                addPreviewImage(response.image_url, response.title);
                uploadedCount++;
                
                if (uploadedCount === totalCount) {
                    showNotification('Upload hoàn tất! ' + uploadedCount + ' ảnh', 'success');
                    setTimeout(() => {
                        closeUploadModal();
                        location.reload();
                    }, 1500);
                }
            } else {
                updateUploadStatus(index, file.name, 'error', response.message);
            }
        } else {
            updateUploadStatus(index, file.name, 'error', 'Lỗi kết nối');
        }
    });
    
    xhr.addEventListener('error', () => {
        updateUploadStatus(index, file.name, 'error', 'Lỗi kết nối');
    });
    
    xhr.open('POST', 'api/upload-gallery-image.php');
    xhr.send(formData);
}

function pathinfo(filename) {
    const parts = filename.split('.');
    const ext = parts.length > 1 ? parts.pop() : '';
    const name = parts.join('.');
    return { filename: name, extension: ext };
}

function addUploadStatus(index, name, status, message) {
    const list = document.getElementById('uploadList');
    const id = 'upload-' + index;
    const color = status === 'success' ? 'text-green-600' : status === 'error' ? 'text-red-600' : 'text-indigo-600';
    const icon = status === 'success' ? 'check_circle' : status === 'error' ? 'error' : 'hourglass_empty';
    
    const html = `<div id="${id}" class="flex items-center gap-2 p-2 bg-gray-50 rounded ${color}">
        <span class="material-symbols-outlined text-sm">${icon}</span>
        <span class="text-sm truncate flex-1">${name}</span>
        <span class="text-xs">${message}</span>
    </div>`;
    list.insertAdjacentHTML('beforeend', html);
}

function updateUploadProgress(index, progress) {
    const el = document.getElementById('upload-' + index);
    if (el) {
        el.querySelector('.text-xs').textContent = progress;
    }
}

function updateUploadStatus(index, name, status, message) {
    const el = document.getElementById('upload-' + index);
    if (el) {
        const color = status === 'success' ? 'text-green-600' : 'text-red-600';
        const icon = status === 'success' ? 'check_circle' : 'error';
        el.className = `flex items-center gap-2 p-2 bg-gray-50 rounded ${color}`;
        el.querySelector('.material-symbols-outlined').textContent = icon;
        el.querySelector('.text-xs').textContent = message;
    }
}

function addPreviewImage(url, title) {
    document.getElementById('previewArea').classList.remove('hidden');
    const grid = document.getElementById('previewGrid');
    const html = `<div class="aspect-square overflow-hidden rounded-lg border border-gray-200">
        <img src="${url}" alt="${title}" class="w-full h-full object-cover">
    </div>`;
    grid.insertAdjacentHTML('beforeend', html);
}

function showNotification(message, type) {
    const div = document.createElement('div');
    div.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-[70] ${type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'}`;
    div.textContent = message;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3000);
}

// Original Gallery Modal
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
