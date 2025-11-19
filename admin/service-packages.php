<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý dịch vụ & gói';
$page_subtitle = 'Quản lý các dịch vụ lớn và gói dịch vụ chi tiết';

// Get filter parameters
$service_filter = $_GET['service'] ?? 'all';
$search = $_GET['search'] ?? '';

try {
    $db = getDB();
    
    // Get all main services
    $stmt = $db->query("
        SELECT s.*,
               (SELECT COUNT(*) FROM service_packages WHERE service_id = s.service_id) as package_count
        FROM services s
        WHERE s.is_available = 1
        ORDER BY s.sort_order ASC
    ");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get packages for selected service
    $packages = [];
    if ($service_filter !== 'all') {
        $stmt = $db->prepare("
            SELECT * FROM service_packages
            WHERE service_id = :service_id
            ORDER BY sort_order ASC
        ");
        $stmt->execute([':service_id' => $service_filter]);
        $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get stats
    $stmt = $db->query("
        SELECT 
            COUNT(DISTINCT s.service_id) as total_services,
            COUNT(sp.package_id) as total_packages,
            SUM(CASE WHEN s.is_available = 1 THEN 1 ELSE 0 END) as available_services
        FROM services s
        LEFT JOIN service_packages sp ON s.service_id = sp.service_id
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Service packages page error: " . $e->getMessage());
    $services = [];
    $packages = [];
    $stats = ['total_services' => 0, 'total_packages' => 0, 'available_services' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng dịch vụ</p>
        <p class="text-2xl font-bold"><?php echo $stats['total_services']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng gói</p>
        <p class="text-2xl font-bold text-accent"><?php echo $stats['total_packages']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đang hoạt động</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $stats['available_services']; ?></p>
    </div>
</div>

<!-- Action Bar -->
<div class="flex items-center justify-between mb-6 gap-4">
    <form method="GET" class="flex gap-2 flex-wrap flex-1">
        <select name="service" class="form-select" onchange="this.form.submit()">
            <option value="all">Chọn dịch vụ...</option>
            <?php foreach ($services as $service): ?>
                <option value="<?php echo $service['service_id']; ?>" <?php echo $service_filter == $service['service_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($service['service_name']); ?> (<?php echo $service['package_count']; ?> gói)
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <div class="flex gap-2">
        <button onclick="openServiceModal()" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">add</span>
            Thêm dịch vụ
        </button>
        <?php if ($service_filter !== 'all'): ?>
        <button onclick="openPackageModal()" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">add</span>
            Thêm gói
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Services Grid -->
<?php if ($service_filter === 'all'): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($services as $service): ?>
            <div class="card hover:shadow-lg transition-shadow">
                <?php if ($service['thumbnail']): ?>
                    <div class="h-48 overflow-hidden rounded-t-xl">
                        <img src="<?php echo htmlspecialchars($service['thumbnail']); ?>" 
                             alt="<?php echo htmlspecialchars($service['service_name']); ?>"
                             class="w-full h-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="h-48 bg-gradient-to-br from-accent/20 to-accent/40 rounded-t-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-6xl text-accent"><?php echo htmlspecialchars($service['icon']); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg mb-1">
                                <?php echo htmlspecialchars($service['service_name']); ?>
                            </h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                <?php echo $service['package_count']; ?> gói dịch vụ
                            </p>
                        </div>
                        <span class="badge badge-<?php echo $service['is_available'] ? 'success' : 'secondary'; ?>">
                            <?php echo $service['is_available'] ? 'Hoạt động' : 'Tạm ngưng'; ?>
                        </span>
                    </div>
                    
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-4 line-clamp-2">
                        <?php echo htmlspecialchars($service['description']); ?>
                    </p>
                    
                    <div class="flex gap-2">
                        <button onclick='editService(<?php echo json_encode($service); ?>)' 
                                class="btn btn-primary flex-1">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            Sửa
                        </button>
                        <a href="?service=<?php echo $service['service_id']; ?>" class="btn btn-secondary flex-1">
                            <span class="material-symbols-outlined text-sm">visibility</span>
                            Xem gói
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <!-- Packages List -->
    <?php if (empty($packages)): ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <span class="empty-state-icon material-symbols-outlined">inventory_2</span>
                    <p class="empty-state-title">Chưa có gói dịch vụ nào</p>
                    <p class="empty-state-description">Thêm gói dịch vụ đầu tiên</p>
                    <button onclick="openPackageModal()" class="btn btn-primary mt-4">
                        <span class="material-symbols-outlined text-sm">add</span>
                        Thêm gói
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($packages as $package): ?>
                <div class="card hover:shadow-lg transition-shadow <?php echo $package['is_featured'] ? 'border-2 border-accent' : ''; ?>">
                    <div class="card-body">
                        <?php if ($package['is_featured']): ?>
                            <div class="mb-3">
                                <span class="badge badge-warning">
                                    <span class="material-symbols-outlined text-sm">star</span>
                                    Nổi bật
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="font-semibold text-xl mb-2">
                            <?php echo htmlspecialchars($package['package_name']); ?>
                        </h3>
                        
                        <div class="mb-4">
                            <span class="text-3xl font-bold text-accent">
                                <?php echo number_format($package['price'], 0, ',', '.'); ?>đ
                            </span>
                            <span class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                /<?php echo htmlspecialchars($package['price_unit']); ?>
                            </span>
                        </div>
                        
                        <?php if ($package['features']): ?>
                            <div class="mb-4">
                                <p class="text-sm font-medium mb-2">Tính năng:</p>
                                <ul class="text-sm space-y-1">
                                    <?php 
                                    $features = explode(',', $package['features']);
                                    foreach (array_slice($features, 0, 3) as $feature): 
                                    ?>
                                        <li class="flex items-start gap-2">
                                            <span class="material-symbols-outlined text-sm text-green-600">check_circle</span>
                                            <span class="text-text-secondary-light dark:text-text-secondary-dark"><?php echo htmlspecialchars(trim($feature)); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (count($features) > 3): ?>
                                        <li class="text-text-secondary-light dark:text-text-secondary-dark">
                                            +<?php echo count($features) - 3; ?> tính năng khác
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-2 mb-4 text-sm">
                            <span class="badge badge-<?php echo $package['is_available'] ? 'success' : 'secondary'; ?>">
                                <?php echo $package['is_available'] ? 'Khả dụng' : 'Tạm ngưng'; ?>
                            </span>
                            <span class="text-text-secondary-light dark:text-text-secondary-dark">
                                Thứ tự: <?php echo $package['sort_order']; ?>
                            </span>
                        </div>
                        
                        <div class="flex gap-2">
                            <button onclick='editPackage(<?php echo json_encode($package); ?>)' 
                                    class="btn btn-primary flex-1">
                                <span class="material-symbols-outlined text-sm">edit</span>
                                Sửa
                            </button>
                            <button onclick="deletePackage(<?php echo $package['package_id']; ?>)" 
                                    class="btn btn-danger">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Service Modal -->
<div id="serviceModal" class="modal">
    <div class="modal-content max-w-3xl">
        <div class="modal-header">
            <h3 class="font-semibold" id="serviceModalTitle">Thêm dịch vụ mới</h3>
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
                        <label class="form-label">Slug (URL) *</label>
                        <input type="text" id="slug" name="slug" class="form-input" required placeholder="vd: wedding-service">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Icon (Material Symbol)</label>
                        <input type="text" id="icon" name="icon" class="form-input" placeholder="vd: celebration">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Thứ tự hiển thị</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-input" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="is_available" name="is_available" value="1" checked>
                            <span>Dịch vụ khả dụng</span>
                        </label>
                    </div>
                    
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Mô tả</label>
                        <textarea id="description" name="description" class="form-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group md:col-span-2">
                        <label class="form-label">URL hình ảnh thumbnail</label>
                        <input type="url" id="thumbnail" name="thumbnail" class="form-input" placeholder="https://...">
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

<!-- Package Modal -->
<div id="packageModal" class="modal">
    <div class="modal-content max-w-3xl">
        <div class="modal-header">
            <h3 class="font-semibold" id="packageModalTitle">Thêm gói dịch vụ</h3>
            <button onclick="closePackageModal()" class="text-text-secondary-light dark:text-text-secondary-dark hover:text-text-primary-light dark:hover:text-text-primary-dark">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="packageForm">
                <input type="hidden" id="package_id" name="package_id">
                <input type="hidden" id="package_service_id" name="service_id" value="<?php echo htmlspecialchars($service_filter); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Tên gói *</label>
                        <input type="text" id="package_name" name="package_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Slug (URL) *</label>
                        <input type="text" id="package_slug" name="slug" class="form-input" required placeholder="vd: goi-co-ban">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Giá (VNĐ) *</label>
                        <input type="number" id="price" name="price" class="form-input" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Đơn vị giá *</label>
                        <input type="text" id="price_unit" name="price_unit" class="form-input" placeholder="vd: bàn, người, ngày" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Thứ tự hiển thị</label>
                        <input type="number" id="package_sort_order" name="sort_order" class="form-input" value="0" min="0">
                    </div>
                    
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Tính năng (mỗi dòng 1 tính năng)</label>
                        <textarea id="features" name="features" class="form-textarea" rows="6" placeholder="Menu 8 món Á - Âu&#10;Trang trí sảnh cơ bản&#10;Âm thanh ánh sáng"></textarea>
                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">Mỗi tính năng trên 1 dòng</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="is_featured" name="is_featured" value="1">
                            <span>Gói nổi bật</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="package_is_available" name="is_available" value="1" checked>
                            <span>Gói khả dụng</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closePackageModal()" class="btn btn-secondary">Hủy</button>
            <button type="button" onclick="submitPackage()" class="btn btn-primary">Lưu gói</button>
        </div>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
// Service Modal Functions
function openServiceModal() {
    document.getElementById('serviceModalTitle').textContent = 'Thêm dịch vụ mới';
    document.getElementById('serviceForm').reset();
    document.getElementById('service_id').value = '';
    document.getElementById('serviceModal').classList.add('active');
}

function closeServiceModal() {
    document.getElementById('serviceModal').classList.remove('active');
}

function editService(service) {
    document.getElementById('serviceModalTitle').textContent = 'Sửa dịch vụ';
    document.getElementById('service_id').value = service.service_id;
    document.getElementById('service_name').value = service.service_name;
    document.getElementById('slug').value = service.slug;
    document.getElementById('icon').value = service.icon || '';
    document.getElementById('sort_order').value = service.sort_order;
    document.getElementById('description').value = service.description || '';
    document.getElementById('thumbnail').value = service.thumbnail || '';
    document.getElementById('is_available').checked = service.is_available == 1;
    document.getElementById('serviceModal').classList.add('active');
}

function submitService() {
    const form = document.getElementById('serviceForm');
    const formData = new FormData(form);
    formData.set('is_available', document.getElementById('is_available').checked ? '1' : '0');
    
    fetch('api/save-service-package.php', {
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

// Package Modal Functions
function openPackageModal() {
    document.getElementById('packageModalTitle').textContent = 'Thêm gói dịch vụ';
    document.getElementById('packageForm').reset();
    document.getElementById('package_id').value = '';
    document.getElementById('package_service_id').value = '<?php echo htmlspecialchars($service_filter); ?>';
    document.getElementById('packageModal').classList.add('active');
}

function closePackageModal() {
    document.getElementById('packageModal').classList.remove('active');
}

function editPackage(pkg) {
    document.getElementById('packageModalTitle').textContent = 'Sửa gói dịch vụ';
    document.getElementById('package_id').value = pkg.package_id;
    document.getElementById('package_service_id').value = pkg.service_id;
    document.getElementById('package_name').value = pkg.package_name;
    document.getElementById('package_slug').value = pkg.slug;
    document.getElementById('price').value = pkg.price;
    document.getElementById('price_unit').value = pkg.price_unit;
    document.getElementById('package_sort_order').value = pkg.sort_order;
    document.getElementById('features').value = pkg.features ? pkg.features.replace(/,/g, '\n') : '';
    document.getElementById('is_featured').checked = pkg.is_featured == 1;
    document.getElementById('package_is_available').checked = pkg.is_available == 1;
    document.getElementById('packageModal').classList.add('active');
}

function submitPackage() {
    const form = document.getElementById('packageForm');
    const formData = new FormData(form);
    
    // Convert features from newlines to comma-separated
    const features = document.getElementById('features').value;
    formData.set('features', features.split('\n').filter(f => f.trim()).join(','));
    
    formData.set('is_featured', document.getElementById('is_featured').checked ? '1' : '0');
    formData.set('is_available', document.getElementById('package_is_available').checked ? '1' : '0');
    
    fetch('api/save-package.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Lưu gói thành công!', 'success');
            closePackageModal();
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

function deletePackage(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa gói này?')) return;
    
    fetch('api/delete-package.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'package_id=' + id
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

// Close modals when clicking outside
document.getElementById('serviceModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeServiceModal();
});

document.getElementById('packageModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePackageModal();
});
</script>

<?php include 'includes/admin-footer.php'; ?>
