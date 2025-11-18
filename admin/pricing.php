<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý giá phòng';
$page_subtitle = 'Cập nhật giá theo mùa và ngày đặc biệt';

try {
    $db = getDB();
    
    // Get all room types
    $stmt = $db->query("SELECT * FROM room_types WHERE status = 'active' ORDER BY category, type_name");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get seasonal pricing
    $stmt = $db->query("
        SELECT sp.*, rt.type_name
        FROM seasonal_pricing sp
        LEFT JOIN room_types rt ON sp.room_type_id = rt.room_type_id
        ORDER BY sp.start_date DESC
    ");
    $seasonal_pricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Pricing page error: " . $e->getMessage());
    $room_types = [];
    $seasonal_pricing = [];
}

include 'includes/admin-header.php';
?>

<!-- Room Type Base Pricing -->
<div class="card mb-6">
    <div class="card-header flex items-center justify-between">
        <h3 class="font-bold text-lg">Giá cơ bản theo loại phòng</h3>
        <a href="room-type-form.php" class="btn btn-primary btn-sm">
            <span class="material-symbols-outlined text-sm">add</span>
            Thêm loại phòng
        </a>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Loại phòng</th>
                        <th>Danh mục</th>
                        <th>Giá cơ bản</th>
                        <th>Sức chứa</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($room_types)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500">Chưa có loại phòng nào</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($room_types as $type): ?>
                            <tr>
                                <td class="font-semibold"><?php echo htmlspecialchars($type['type_name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $type['category'] === 'room' ? 'info' : 'success'; ?>">
                                        <?php echo $type['category'] === 'room' ? 'Phòng' : 'Căn hộ'; ?>
                                    </span>
                                </td>
                                <td class="font-bold" style="color: #d4af37;">
                                    <?php echo number_format($type['base_price'], 0, ',', '.'); ?>đ
                                </td>
                                <td><?php echo $type['max_occupancy']; ?> người</td>
                                <td>
                                    <span class="badge badge-<?php echo $type['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $type['status'] === 'active' ? 'Hoạt động' : 'Tạm ngưng'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="room-type-form.php?id=<?php echo $type['room_type_id']; ?>" 
                                           class="action-btn" title="Sửa">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Seasonal Pricing -->
<div class="card">
    <div class="card-header flex items-center justify-between">
        <div>
            <h3 class="font-bold text-lg">Giá theo mùa & Sự kiện</h3>
            <p class="text-sm text-gray-500 mt-1">Thiết lập giá đặc biệt cho các khoảng thời gian</p>
        </div>
        <button onclick="openSeasonalPricingModal()" class="btn btn-primary btn-sm">
            <span class="material-symbols-outlined text-sm">add</span>
            Thêm giá đặc biệt
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($seasonal_pricing)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined empty-state-icon">event</span>
                <p class="empty-state-title">Chưa có giá theo mùa</p>
                <p class="empty-state-description">Thêm giá đặc biệt cho các ngày lễ, cuối tuần hoặc mùa cao điểm</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($seasonal_pricing as $pricing): ?>
                    <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-slate-800 rounded-xl">
                        <div class="w-12 h-12 bg-gradient-to-br from-[#d4af37] to-[#b8941f] rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-white">event</span>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold"><?php echo htmlspecialchars($pricing['type_name']); ?></p>
                            <p class="text-sm text-gray-500">
                                <?php echo date('d/m/Y', strtotime($pricing['start_date'])); ?> - 
                                <?php echo date('d/m/Y', strtotime($pricing['end_date'])); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-lg" style="color: #d4af37;">
                                <?php echo number_format($pricing['price'], 0, ',', '.'); ?>đ
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php 
                                $diff = (($pricing['price'] - $pricing['base_price']) / $pricing['base_price']) * 100;
                                echo ($diff > 0 ? '+' : '') . number_format($diff, 0) . '%';
                                ?>
                            </p>
                        </div>
                        <div class="action-buttons">
                            <button onclick="editSeasonalPricing(<?php echo $pricing['pricing_id']; ?>)" 
                                    class="action-btn" title="Sửa">
                                <span class="material-symbols-outlined text-sm">edit</span>
                            </button>
                            <button onclick="deleteSeasonalPricing(<?php echo $pricing['pricing_id']; ?>)" 
                                    class="action-btn text-red-600" title="Xóa">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Seasonal Pricing Modal -->
<div id="seasonalPricingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="font-bold text-lg">Thêm giá theo mùa</h3>
            <button onclick="closeSeasonalPricingModal()" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="seasonalPricingForm" onsubmit="saveSeasonalPricing(event)">
            <div class="modal-body space-y-4">
                <input type="hidden" name="pricing_id" id="pricing_id">
                
                <div class="form-group">
                    <label class="form-label">Loại phòng *</label>
                    <select name="room_type_id" class="form-select" required>
                        <option value="">-- Chọn loại phòng --</option>
                        <?php foreach ($room_types as $type): ?>
                            <option value="<?php echo $type['room_type_id']; ?>">
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Ngày bắt đầu *</label>
                        <input type="date" name="start_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ngày kết thúc *</label>
                        <input type="date" name="end_date" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Giá đặc biệt (VNĐ) *</label>
                    <input type="number" name="price" class="form-input" min="0" step="1000" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ghi chú</label>
                    <input type="text" name="notes" class="form-input" placeholder="VD: Giá Tết, Giá cuối tuần...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeSeasonalPricingModal()" class="btn btn-secondary">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSeasonalPricingModal() {
    document.getElementById('seasonalPricingModal').classList.add('active');
    document.getElementById('seasonalPricingForm').reset();
}

function closeSeasonalPricingModal() {
    document.getElementById('seasonalPricingModal').classList.remove('active');
}

function saveSeasonalPricing(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('api/save-seasonal-pricing.php', {
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

function deleteSeasonalPricing(id) {
    if (!confirm('Bạn có chắc muốn xóa giá này?')) return;
    
    fetch('api/delete-seasonal-pricing.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'pricing_id=' + id
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
