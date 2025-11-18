<?php
session_start();
require_once '../config/database.php';

$page_title = 'Chương trình thành viên';
$page_subtitle = 'Quản lý hạng thành viên và điểm thưởng';

try {
    $db = getDB();
    
    // Get membership tiers
    $stmt = $db->query("
        SELECT mt.*,
               (SELECT COUNT(*) FROM user_loyalty WHERE tier_id = mt.tier_id) as member_count
        FROM membership_tiers mt
        ORDER BY mt.min_points ASC
    ");
    $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get loyalty stats
    $stmt = $db->query("
        SELECT 
            COUNT(DISTINCT ul.user_id) as total_members,
            SUM(ul.current_points) as total_points,
            AVG(ul.current_points) as avg_points
        FROM user_loyalty ul
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Loyalty page error: " . $e->getMessage());
    $tiers = [];
    $stats = ['total_members' => 0, 'total_points' => 0, 'avg_points' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="card">
        <div class="card-body text-center">
            <div class="w-12 h-12 bg-gradient-to-br from-[#d4af37] to-[#b8941f] rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-white">people</span>
            </div>
            <div class="text-3xl font-bold mb-1" style="color: #d4af37;"><?php echo number_format($stats['total_members']); ?></div>
            <div class="text-sm text-gray-600">Tổng thành viên</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body text-center">
            <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-white">stars</span>
            </div>
            <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo number_format($stats['total_points']); ?></div>
            <div class="text-sm text-gray-600">Tổng điểm tích lũy</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body text-center">
            <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-white">trending_up</span>
            </div>
            <div class="text-3xl font-bold text-green-600 mb-1"><?php echo number_format($stats['avg_points'], 0); ?></div>
            <div class="text-sm text-gray-600">Điểm TB/Thành viên</div>
        </div>
    </div>
</div>

<!-- Membership Tiers -->
<div class="card">
    <div class="card-header flex items-center justify-between">
        <div>
            <h3 class="font-bold text-lg">Hạng thành viên</h3>
            <p class="text-sm text-gray-500 mt-1">Cấu hình các hạng và quyền lợi</p>
        </div>
        <button onclick="openTierModal()" class="btn btn-primary btn-sm">
            <span class="material-symbols-outlined text-sm">add</span>
            Thêm hạng
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($tiers)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined empty-state-icon">workspace_premium</span>
                <p class="empty-state-title">Chưa có hạng thành viên</p>
                <p class="empty-state-description">Tạo các hạng thành viên để khuyến khích khách hàng trung thành</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($tiers as $tier): ?>
                    <div class="relative overflow-hidden rounded-2xl border-2 p-6 hover:shadow-xl transition-all"
                         style="border-color: <?php echo $tier['color_code']; ?>;">
                        <div class="absolute top-0 right-0 w-32 h-32 rounded-full -mr-16 -mt-16 opacity-10"
                             style="background-color: <?php echo $tier['color_code']; ?>;"></div>
                        
                        <div class="relative z-10">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4 mx-auto"
                                 style="background-color: <?php echo $tier['color_code']; ?>20;">
                                <span class="material-symbols-outlined text-3xl" 
                                      style="color: <?php echo $tier['color_code']; ?>;">workspace_premium</span>
                            </div>
                            
                            <h4 class="text-xl font-bold text-center mb-2"><?php echo htmlspecialchars($tier['tier_name']); ?></h4>
                            
                            <div class="text-center mb-4">
                                <p class="text-sm text-gray-600">Từ <?php echo number_format($tier['min_points']); ?> điểm</p>
                                <p class="text-2xl font-bold mt-2" style="color: <?php echo $tier['color_code']; ?>;">
                                    <?php echo $tier['discount_percentage']; ?>%
                                </p>
                                <p class="text-xs text-gray-500">Giảm giá</p>
                            </div>
                            
                            <div class="text-center mb-4">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium"
                                      style="background-color: <?php echo $tier['color_code']; ?>20; color: <?php echo $tier['color_code']; ?>;">
                                    <span class="material-symbols-outlined text-sm">people</span>
                                    <?php echo $tier['member_count']; ?> thành viên
                                </span>
                            </div>
                            
                            <div class="flex gap-2">
                                <button onclick="editTier(<?php echo $tier['tier_id']; ?>)" 
                                        class="flex-1 btn btn-sm btn-secondary">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <button onclick="deleteTier(<?php echo $tier['tier_id']; ?>)" 
                                        class="btn btn-sm btn-danger">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tier Modal -->
<div id="tierModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="font-bold text-lg">Thêm hạng thành viên</h3>
            <button onclick="closeTierModal()" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="tierForm" onsubmit="saveTier(event)">
            <div class="modal-body space-y-4">
                <input type="hidden" name="tier_id" id="tier_id">
                
                <div class="form-group">
                    <label class="form-label">Tên hạng *</label>
                    <input type="text" name="tier_name" class="form-input" required 
                           placeholder="VD: Bronze, Silver, Gold, Platinum">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Điểm tối thiểu *</label>
                    <input type="number" name="min_points" class="form-input" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phần trăm giảm giá (%) *</label>
                    <input type="number" name="discount_percentage" class="form-input" 
                           min="0" max="100" step="0.1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Màu sắc *</label>
                    <div class="flex gap-2">
                        <input type="color" name="color_code" class="w-16 h-10 rounded cursor-pointer" value="#d4af37">
                        <input type="text" name="color_code_text" class="form-input flex-1" 
                               placeholder="#d4af37" pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mô tả quyền lợi</label>
                    <textarea name="benefits" class="form-textarea" rows="3" 
                              placeholder="Mỗi quyền lợi một dòng"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeTierModal()" class="btn btn-secondary">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
// Sync color inputs
document.querySelector('input[name="color_code"]').addEventListener('input', function(e) {
    document.querySelector('input[name="color_code_text"]').value = e.target.value;
});

document.querySelector('input[name="color_code_text"]').addEventListener('input', function(e) {
    if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
        document.querySelector('input[name="color_code"]').value = e.target.value;
    }
});

function openTierModal() {
    document.getElementById('tierModal').classList.add('active');
    document.getElementById('tierForm').reset();
}

function closeTierModal() {
    document.getElementById('tierModal').classList.remove('active');
}

function saveTier(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('api/save-tier.php', {
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

function deleteTier(id) {
    if (!confirm('Bạn có chắc muốn xóa hạng này?')) return;
    
    fetch('api/delete-tier.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tier_id=' + id
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
