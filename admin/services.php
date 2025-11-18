<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý dịch vụ';
$page_subtitle = 'Quản lý các dịch vụ khách sạn';

// Get filter parameters
$category_filter = $_GET['category'] ?? 'all';
$status_filter = $_GET['available'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];
$params = [];

if ($category_filter !== 'all') {
    $where_clauses[] = "category = :category";
    $params[':category'] = $category_filter;
}

if ($status_filter !== 'all') {
    $where_clauses[] = "available = :available";
    $params[':available'] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(service_name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    // Get services
    $sql = "
        SELECT s.*,
               (SELECT COUNT(*) FROM service_bookings WHERE service_id = s.service_id) as total_bookings,
               (SELECT SUM(total_price) FROM service_bookings WHERE service_id = s.service_id AND status = 'completed') as total_revenue
        FROM services s
        $where_sql
        ORDER BY s.sort_order ASC, s.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN category = 'spa' THEN 1 ELSE 0 END) as spa,
            SUM(CASE WHEN category = 'restaurant' THEN 1 ELSE 0 END) as restaurant,
            SUM(CASE WHEN category = 'laundry' THEN 1 ELSE 0 END) as laundry,
            SUM(CASE WHEN category = 'transport' THEN 1 ELSE 0 END) as transport,
            SUM(CASE WHEN available = 1 THEN 1 ELSE 0 END) as available
        FROM services
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Services page error: " . $e->getMessage());
    $services = [];
    $counts = ['total' => 0, 'spa' => 0, 'restaurant' => 0, 'laundry' => 0, 'transport' => 0, 'available' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng dịch vụ</p>
        <p class="text-2xl font-bold"><?php echo $counts['total']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Spa</p>
        <p class="text-2xl font-bold text-purple-600"><?php echo $counts['spa']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Nhà hàng</p>
        <p class="text-2xl font-bold text-orange-600"><?php echo $counts['restaurant']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Giặt ủi</p>
        <p class="text-2xl font-bold text-blue-600"><?php echo $counts['laundry']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Vận chuyển</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $counts['transport']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Khả dụng</p>
        <p class="text-2xl font-bold text-accent"><?php echo $counts['available']; ?></p>
    </div>
</div>

<!-- Action Bar -->
<div class="flex items-center justify-between mb-6">
    <form method="GET" class="flex gap-2 flex-wrap">
        <div class="search-box">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm dịch vụ..." class="form-input">
        </div>
        
        <select name="category" class="form-select">
            <option value="all">Tất cả loại</option>
            <option value="spa" <?php echo $category_filter === 'spa' ? 'selected' : ''; ?>>Spa</option>
            <option value="restaurant" <?php echo $category_filter === 'restaurant' ? 'selected' : ''; ?>>Nhà hàng</option>
            <option value="laundry" <?php echo $category_filter === 'laundry' ? 'selected' : ''; ?>>Giặt ủi</option>
            <option value="transport" <?php echo $category_filter === 'transport' ? 'selected' : ''; ?>>Vận chuyển</option>
            <option value="other" <?php echo $category_filter === 'other' ? 'selected' : ''; ?>>Khác</option>
        </select>
        
        <select name="available" class="form-select">
            <option value="all">Tất cả trạng thái</option>
            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Khả dụng</option>
            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Tạm ngưng</option>
        </select>
        
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">filter_alt</span>
            Lọc
        </button>
    </form>
    
    <button onclick="openServiceModal()" class="btn btn-primary">
        <span class="material-symbols-outlined text-sm">add</span>
        Thêm dịch vụ
    </button>
</div>

<!-- Services Grid -->
<?php if (empty($services)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <span class="empty-state-icon material-symbols-outlined">room_service</span>
                <p class="empty-state-title">Chưa có dịch vụ nào</p>
                <p class="empty-state-description">Thêm dịch vụ đầu tiên để bắt đầu</p>
                <button onclick="openServiceModal()" class="btn btn-primary mt-4">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Thêm dịch vụ
                </button>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($services as $service): ?>
            <div class="card hover:shadow-lg transition-shadow">
                <!-- Image -->
                <?php if ($service['image']): ?>
                    <div class="h-48 overflow-hidden rounded-t-xl">
                        <img src="<?php echo htmlspecialchars($service['image']); ?>" 
                             alt="<?php echo htmlspecialchars($service['service_name']); ?>"
                             class="w-full h-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="h-48 bg-gray-200 dark:bg-gray-700 rounded-t-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-6xl text-gray-400">room_service</span>
                    </div>
                <?php endif; ?>
                
                <div class="card-body">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg mb-1">
                                <?php echo htmlspecialchars($service['service_name']); ?>
                            </h3>
                            <div class="flex gap-2">
                                <?php
                                $category_config = [
                                    'spa' => ['class' => 'badge-purple', 'label' => 'Spa'],
                                    'restaurant' => ['class' => 'badge-orange', 'label' => 'Nhà hàng'],
                                    'laundry' => ['class' => 'badge-blue', 'label' => 'Giặt ủi'],
                                    'transport' => ['class' => 'badge-green', 'label' => 'Vận chuyển'],
                                    'other' => ['class' => 'badge-secondary', 'label' => 'Khác']
                                ];
                                $cat_config = $category_config[$service['category']] ?? ['class' => 'badge-secondary', 'label' => $service['category']];
                                ?>
                                <span class="badge <?php echo $cat_config['class']; ?>">
                                    <?php echo $cat_config['label']; ?>
                                </span>
                                <span class="badge badge-<?php echo $service['available'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $service['available'] ? 'Khả dụng' : 'Tạm ngưng'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <?php if ($service['short_description']): ?>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-3 line-clamp-2">
                            <?php echo htmlspecialchars($service['short_description']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                        <div>
                            <span class="text-text-secondary-light dark:text-text-secondary-dark">Đã đặt:</span>
                            <span class="font-medium"><?php echo $service['total_bookings']; ?> lần</span>
                        </div>
                        <div>
                            <span class="text-text-secondary-light dark:text-text-secondary-dark">Doanh thu:</span>
                            <span class="font-medium text-green-600"><?php echo number_format($service['total_revenue'] ?? 0, 0, ',', '.'); ?>đ</span>
                        </div>
                    </div>
                    
                    <!-- Price -->
                    <div class="mb-3 pt-3 border-t border-border-light dark:border-border-dark">
                        <p class="text-xl font-bold text-accent">
                            <?php echo number_format($service['price'], 0, ',', '.'); ?>đ
                            <?php if ($service['unit']): ?>
                                <span class="text-sm font-normal text-text-secondary-light dark:text-text-secondary-dark">/<?php echo htmlspecialchars($service['unit']); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button onclick='editService(<?php echo json_encode($service); ?>)' 
                                class="btn btn-primary flex-1">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            Sửa
                        </button>
                        <button onclick="toggleServiceStatus(<?php echo $service['service_id']; ?>, <?php echo $service['available'] ? 0 : 1; ?>)" 
                                class="btn btn-secondary">
                            <span class="material-symbols-outlined text-sm"><?php echo $service['available'] ? 'visibility_off' : 'visibility'; ?></span>
                        </button>
                        <button onclick="deleteService(<?php echo $service['service_id']; ?>)" 
                                class="btn btn-danger">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Service Modal -->
<div id="serviceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="font-semibold" id="modalTitle">Thêm dịch vụ mới</h3>
            <button onclick="closeServiceModal()" class="text-text-secondary-light dark:text-text-secondary-dark hover:text-text-primary-light dark:hover:text-text-primary-dark">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="serviceForm">
                <input type="hidden" id="service_id" name="service_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Tên dịch vụ *</label>
                        <input type="text" id="service_name" name="service_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Loại dịch vụ *</label>
                        <select id="category" name="category" class="form-select" required>
                            <option value="spa">Spa</option>
                            <option value="restaurant">Nhà hàng</option>
                            <option value="laundry">Giặt ủi</option>
                            <option value="transport">Vận chuyển</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Giá (VNĐ) *</label>
                        <input type="number" id="price" name="price" class="form-input" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Đơn vị</label>
                        <input type="text" id="unit" name="unit" class="form-input" placeholder="VD: lần, người, kg...">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Thứ tự hiển thị</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-input" value="0" min="0">
                    </div>
                    
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Mô tả ngắn</label>
                        <input type="text" id="short_description" name="short_description" class="form-input" maxlength="255">
                    </div>
                    
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Mô tả chi tiết</label>
                        <textarea id="description" name="description" class="form-textarea" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group md:col-span-2">
                        <label class="form-label">URL hình ảnh</label>
                        <input type="url" id="image" name="image" class="form-input" placeholder="https://...">
                    </div>
                    
                    <div class="form-group md:col-span-2 mb-0">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="available" name="available" value="1" checked>
                            <span>Dịch vụ khả dụng</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeServiceModal()" class="btn btn-secondary">Hủy</button>
            <button type="button" onclick="submitService()" class="btn btn-primary">Lưu dịch vụ</button>
        </div>
    </div>
</div>

<style>
.badge-purple { background: #f3e8ff; color: #7c3aed; }
.badge-orange { background: #ffedd5; color: #ea580c; }
.badge-blue { background: #dbeafe; color: #2563eb; }
.badge-green { background: #dcfce7; color: #16a34a; }
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
function openServiceModal() {
    document.getElementById('modalTitle').textContent = 'Thêm dịch vụ mới';
    document.getElementById('serviceForm').reset();
    document.getElementById('service_id').value = '';
    document.getElementById('serviceModal').classList.add('active');
}

function closeServiceModal() {
    document.getElementById('serviceModal').classList.remove('active');
}

function editService(service) {
    document.getElementById('modalTitle').textContent = 'Sửa dịch vụ';
    document.getElementById('service_id').value = service.service_id;
    document.getElementById('service_name').value = service.service_name;
    document.getElementById('category').value = service.category;
    document.getElementById('price').value = service.price;
    document.getElementById('unit').value = service.unit || '';
    document.getElementById('sort_order').value = service.sort_order;
    document.getElementById('short_description').value = service.short_description || '';
    document.getElementById('description').value = service.description || '';
    document.getElementById('image').value = service.image || '';
    document.getElementById('available').checked = service.available == 1;
    document.getElementById('serviceModal').classList.add('active');
}

function submitService() {
    const form = document.getElementById('serviceForm');
    const formData = new FormData(form);
    
    // Add available checkbox value
    formData.set('available', document.getElementById('available').checked ? '1' : '0');
    
    fetch('api/save-service.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Lưu dịch vụ thành công!', 'success');
            closeServiceModal();
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

function toggleServiceStatus(id, status) {
    const formData = new FormData();
    formData.append('service_id', id);
    formData.append('available', status);
    
    fetch('api/toggle-service-status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Cập nhật thành công!', 'success');
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

function deleteService(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa dịch vụ này?')) return;
    
    fetch('api/delete-service.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'service_id=' + id
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

// Close modal when clicking outside
document.getElementById('serviceModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeServiceModal();
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>
