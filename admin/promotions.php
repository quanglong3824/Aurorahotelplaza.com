<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý khuyến mãi';
$page_subtitle = 'Quản lý mã giảm giá và chương trình khuyến mãi';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(promotion_code LIKE :search OR promotion_name LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    // Get promotions
    $sql = "
        SELECT p.*,
               (SELECT COUNT(*) FROM promotion_usage WHERE promotion_id = p.promotion_id) as usage_count
        FROM promotions p
        $where_sql
        ORDER BY p.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
        FROM promotions
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Promotions page error: " . $e->getMessage());
    $promotions = [];
    $counts = ['total' => 0, 'active' => 0, 'inactive' => 0, 'expired' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng khuyến mãi</p>
        <p class="text-2xl font-bold"><?php echo $counts['total']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đang hoạt động</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $counts['active']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tạm ngưng</p>
        <p class="text-2xl font-bold text-gray-600"><?php echo $counts['inactive']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đã hết hạn</p>
        <p class="text-2xl font-bold text-red-600"><?php echo $counts['expired']; ?></p>
    </div>
</div>

<!-- Action Bar -->
<div class="flex items-center justify-between mb-6">
    <form method="GET" class="flex gap-2">
        <div class="search-box">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm mã khuyến mãi..." class="form-input">
        </div>
        
        <select name="status" class="form-select">
            <option value="all">Tất cả trạng thái</option>
            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Tạm ngưng</option>
            <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Đã hết hạn</option>
        </select>
        
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">filter_alt</span>
            Lọc
        </button>
    </form>
    
    <button onclick="openPromotionModal()" class="btn btn-primary">
        <span class="material-symbols-outlined text-sm">add</span>
        Thêm khuyến mãi
    </button>
</div>

<!-- Promotions List -->
<?php if (empty($promotions)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <span class="empty-state-icon material-symbols-outlined">local_offer</span>
                <p class="empty-state-title">Chưa có khuyến mãi nào</p>
                <p class="empty-state-description">Tạo chương trình khuyến mãi đầu tiên</p>
                <button onclick="openPromotionModal()" class="btn btn-primary mt-4">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Thêm khuyến mãi
                </button>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($promotions as $promo): ?>
            <div class="card">
                <div class="card-body">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold"><?php echo htmlspecialchars($promo['promotion_name']); ?></h3>
                                <span class="badge badge-<?php echo $promo['status'] === 'active' ? 'success' : ($promo['status'] === 'expired' ? 'danger' : 'secondary'); ?>">
                                    <?php 
                                    $status_labels = ['active' => 'Hoạt động', 'inactive' => 'Tạm ngưng', 'expired' => 'Hết hạn'];
                                    echo $status_labels[$promo['status']] ?? $promo['status'];
                                    ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-3">
                                <div>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Mã khuyến mãi</p>
                                    <p class="font-mono font-bold text-lg text-accent"><?php echo htmlspecialchars($promo['promotion_code']); ?></p>
                                </div>
                                
                                <div>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Giảm giá</p>
                                    <p class="font-bold text-lg">
                                        <?php if ($promo['discount_type'] === 'percentage'): ?>
                                            <?php echo $promo['discount_value']; ?>%
                                        <?php else: ?>
                                            <?php echo number_format($promo['discount_value'], 0, ',', '.'); ?>đ
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đã sử dụng</p>
                                    <p class="font-bold text-lg">
                                        <?php echo $promo['used_count']; ?>
                                        <?php if ($promo['usage_limit']): ?>
                                            / <?php echo $promo['usage_limit']; ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Thời hạn</p>
                                    <p class="text-sm">
                                        <?php echo date('d/m/Y', strtotime($promo['start_date'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if ($promo['description']): ?>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-3">
                                    <?php echo htmlspecialchars($promo['description']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="flex flex-wrap gap-2 text-sm">
                                <?php if ($promo['min_booking_amount']): ?>
                                    <span class="badge badge-info">
                                        Đơn tối thiểu: <?php echo number_format($promo['min_booking_amount'], 0, ',', '.'); ?>đ
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($promo['max_discount']): ?>
                                    <span class="badge badge-warning">
                                        Giảm tối đa: <?php echo number_format($promo['max_discount'], 0, ',', '.'); ?>đ
                                    </span>
                                <?php endif; ?>
                                
                                <span class="badge badge-secondary">
                                    Áp dụng: <?php 
                                    $applicable = [
                                        'all' => 'Tất cả',
                                        'rooms' => 'Phòng',
                                        'apartments' => 'Căn hộ',
                                        'services' => 'Dịch vụ'
                                    ];
                                    echo $applicable[$promo['applicable_to']] ?? $promo['applicable_to'];
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 ml-4">
                            <button onclick='editPromotion(<?php echo json_encode($promo); ?>)' 
                                    class="btn btn-sm btn-primary">
                                <span class="material-symbols-outlined text-sm">edit</span>
                                Sửa
                            </button>
                            <button onclick="deletePromotion(<?php echo $promo['promotion_id']; ?>)" 
                                    class="btn btn-sm btn-danger">
                                <span class="material-symbols-outlined text-sm">delete</span>
                                Xóa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Promotion Modal -->
<div id="promotionModal" class="modal">
    <div class="modal-content max-w-3xl">
        <div class="modal-header">
            <h3 class="font-semibold" id="modalTitle">Thêm khuyến mãi mới</h3>
            <button onclick="closePromotionModal()" class="text-text-secondary-light dark:text-text-secondary-dark hover:text-text-primary-light dark:hover:text-text-primary-dark">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="promotionForm">
                <input type="hidden" id="promotion_id" name="promotion_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Mã khuyến mãi *</label>
                        <input type="text" id="promotion_code" name="promotion_code" class="form-input" 
                               placeholder="VD: SUMMER2024" required style="text-transform: uppercase;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tên chương trình *</label>
                        <input type="text" id="promotion_name" name="promotion_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Loại giảm giá *</label>
                        <select id="discount_type" name="discount_type" class="form-select" required onchange="updateDiscountLabel()">
                            <option value="percentage">Phần trăm (%)</option>
                            <option value="fixed_amount">Số tiền cố định (VNĐ)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" id="discount_label">Giá trị giảm (%) *</label>
                        <input type="number" id="discount_value" name="discount_value" class="form-input" 
                               min="0" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Đơn tối thiểu (VNĐ)</label>
                        <input type="number" id="min_booking_amount" name="min_booking_amount" class="form-input" 
                               min="0" step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Giảm tối đa (VNĐ)</label>
                        <input type="number" id="max_discount" name="max_discount" class="form-input" 
                               min="0" step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Giới hạn sử dụng</label>
                        <input type="number" id="usage_limit" name="usage_limit" class="form-input" 
                               min="0" placeholder="Để trống = không giới hạn">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Số lần/người</label>
                        <input type="number" id="usage_per_user" name="usage_per_user" class="form-input" 
                               min="1" value="1">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ngày bắt đầu *</label>
                        <input type="datetime-local" id="start_date" name="start_date" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ngày kết thúc *</label>
                        <input type="datetime-local" id="end_date" name="end_date" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Áp dụng cho *</label>
                        <select id="applicable_to" name="applicable_to" class="form-select" required>
                            <option value="all">Tất cả</option>
                            <option value="rooms">Chỉ phòng</option>
                            <option value="apartments">Chỉ căn hộ</option>
                            <option value="services">Chỉ dịch vụ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Trạng thái *</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Tạm ngưng</option>
                        </select>
                    </div>
                    
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Mô tả</label>
                        <textarea id="description" name="description" class="form-textarea" rows="3"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closePromotionModal()" class="btn btn-secondary">Hủy</button>
            <button type="button" onclick="submitPromotion()" class="btn btn-primary">Lưu khuyến mãi</button>
        </div>
    </div>
</div>

<script>
function updateDiscountLabel() {
    const type = document.getElementById('discount_type').value;
    const label = document.getElementById('discount_label');
    const input = document.getElementById('discount_value');
    
    if (type === 'percentage') {
        label.textContent = 'Giá trị giảm (%) *';
        input.max = '100';
        input.step = '0.01';
    } else {
        label.textContent = 'Giá trị giảm (VNĐ) *';
        input.removeAttribute('max');
        input.step = '1000';
    }
}

function openPromotionModal() {
    document.getElementById('modalTitle').textContent = 'Thêm khuyến mãi mới';
    document.getElementById('promotionForm').reset();
    document.getElementById('promotion_id').value = '';
    
    // Set default dates
    const now = new Date();
    const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    const nextMonth = new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000);
    
    document.getElementById('start_date').value = tomorrow.toISOString().slice(0, 16);
    document.getElementById('end_date').value = nextMonth.toISOString().slice(0, 16);
    
    document.getElementById('promotionModal').classList.add('active');
}

function closePromotionModal() {
    document.getElementById('promotionModal').classList.remove('active');
}

function editPromotion(promo) {
    document.getElementById('modalTitle').textContent = 'Sửa khuyến mãi';
    document.getElementById('promotion_id').value = promo.promotion_id;
    document.getElementById('promotion_code').value = promo.promotion_code;
    document.getElementById('promotion_name').value = promo.promotion_name;
    document.getElementById('discount_type').value = promo.discount_type;
    document.getElementById('discount_value').value = promo.discount_value;
    document.getElementById('min_booking_amount').value = promo.min_booking_amount || '';
    document.getElementById('max_discount').value = promo.max_discount || '';
    document.getElementById('usage_limit').value = promo.usage_limit || '';
    document.getElementById('usage_per_user').value = promo.usage_per_user;
    document.getElementById('start_date').value = promo.start_date.replace(' ', 'T').slice(0, 16);
    document.getElementById('end_date').value = promo.end_date.replace(' ', 'T').slice(0, 16);
    document.getElementById('applicable_to').value = promo.applicable_to;
    document.getElementById('status').value = promo.status;
    document.getElementById('description').value = promo.description || '';
    
    updateDiscountLabel();
    document.getElementById('promotionModal').classList.add('active');
}

function submitPromotion() {
    const form = document.getElementById('promotionForm');
    const formData = new FormData(form);
    
    fetch('api/save-promotion.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Lưu khuyến mãi thành công!', 'success');
            closePromotionModal();
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

function deletePromotion(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa khuyến mãi này?')) return;
    
    fetch('api/delete-promotion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'promotion_id=' + id
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

// Auto uppercase promotion code
document.getElementById('promotion_code')?.addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
});

// Close modal when clicking outside
document.getElementById('promotionModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePromotionModal();
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>
