<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý phòng';
$page_subtitle = 'Danh sách các phòng cụ thể';

// Get filter parameters
$type_filter = $_GET['type_id'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$floor_filter = $_GET['floor'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];
$params = [];

if ($type_filter !== 'all') {
    $where_clauses[] = "r.room_type_id = :type_id";
    $params[':type_id'] = $type_filter;
}

if ($status_filter !== 'all') {
    $where_clauses[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

if ($floor_filter !== 'all') {
    $where_clauses[] = "r.floor = :floor";
    $params[':floor'] = $floor_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(r.room_number LIKE :search OR r.building LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    // Get rooms
    $sql = "
        SELECT r.*, rt.type_name, rt.category
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.room_type_id
        $where_sql
        ORDER BY r.floor ASC, r.room_number ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get room types for filter
    $stmt = $db->query("SELECT room_type_id, type_name FROM room_types ORDER BY type_name");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get floors for filter
    $stmt = $db->query("SELECT DISTINCT floor FROM rooms WHERE floor IS NOT NULL ORDER BY floor");
    $floors = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get status counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
            SUM(CASE WHEN status = 'cleaning' THEN 1 ELSE 0 END) as cleaning
        FROM rooms
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Rooms page error: " . $e->getMessage());
    $rooms = [];
    $room_types = [];
    $floors = [];
    $counts = ['total' => 0, 'available' => 0, 'occupied' => 0, 'maintenance' => 0, 'cleaning' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Action Bar -->
<div class="flex items-center justify-between mb-6">
    <form method="GET" class="flex gap-2 flex-wrap">
        <div class="search-box">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm số phòng..." class="form-input">
        </div>
        
        <select name="type_id" class="form-select">
            <option value="all">Tất cả loại phòng</option>
            <?php foreach ($room_types as $type): ?>
                <option value="<?php echo $type['room_type_id']; ?>" 
                        <?php echo $type_filter == $type['room_type_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type['type_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="status" class="form-select">
            <option value="all">Tất cả trạng thái</option>
            <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Trống</option>
            <option value="occupied" <?php echo $status_filter === 'occupied' ? 'selected' : ''; ?>>Đang sử dụng</option>
            <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
            <option value="cleaning" <?php echo $status_filter === 'cleaning' ? 'selected' : ''; ?>>Đang dọn</option>
        </select>
        
        <?php if (!empty($floors)): ?>
            <select name="floor" class="form-select">
                <option value="all">Tất cả tầng</option>
                <?php foreach ($floors as $floor): ?>
                    <option value="<?php echo $floor; ?>" <?php echo $floor_filter == $floor ? 'selected' : ''; ?>>
                        Tầng <?php echo $floor; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">filter_alt</span>
            Lọc
        </button>
        
        <a href="rooms.php" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">refresh</span>
            Reset
        </a>
    </form>
    
    <a href="room-form.php" class="btn btn-primary">
        <span class="material-symbols-outlined text-sm">add</span>
        Thêm phòng
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng số phòng</p>
        <p class="text-2xl font-bold"><?php echo $counts['total']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Phòng trống</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $counts['available']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đang sử dụng</p>
        <p class="text-2xl font-bold text-blue-600"><?php echo $counts['occupied']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Bảo trì</p>
        <p class="text-2xl font-bold text-orange-600"><?php echo $counts['maintenance']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đang dọn</p>
        <p class="text-2xl font-bold text-yellow-600"><?php echo $counts['cleaning']; ?></p>
    </div>
</div>

<!-- Rooms Table -->
<div class="card">
    <div class="card-header">
        <h3 class="font-semibold">Danh sách phòng (<?php echo count($rooms); ?>)</h3>
    </div>
    
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Số phòng</th>
                    <th>Loại phòng</th>
                    <th>Tầng</th>
                    <th>Tòa nhà</th>
                    <th>Trạng thái</th>
                    <th>Lần dọn cuối</th>
                    <th>Ghi chú</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rooms)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-8">
                            <div class="empty-state">
                                <span class="empty-state-icon material-symbols-outlined">hotel</span>
                                <p class="empty-state-title">Không tìm thấy phòng</p>
                                <p class="empty-state-description">Thử thay đổi bộ lọc hoặc thêm phòng mới</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td class="font-bold text-lg"><?php echo htmlspecialchars($room['room_number']); ?></td>
                            <td>
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($room['type_name']); ?></p>
                                    <span class="badge badge-<?php echo $room['category'] === 'room' ? 'info' : 'success'; ?> text-xs">
                                        <?php echo $room['category'] === 'room' ? 'Phòng' : 'Căn hộ'; ?>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo $room['floor'] ? 'Tầng ' . $room['floor'] : '-'; ?></td>
                            <td><?php echo $room['building'] ? htmlspecialchars($room['building']) : '-'; ?></td>
                            <td>
                                <?php
                                $status_config = [
                                    'available' => ['class' => 'badge-success', 'label' => 'Trống', 'icon' => 'check_circle'],
                                    'occupied' => ['class' => 'badge-info', 'label' => 'Đang sử dụng', 'icon' => 'person'],
                                    'maintenance' => ['class' => 'badge-warning', 'label' => 'Bảo trì', 'icon' => 'build'],
                                    'cleaning' => ['class' => 'badge-secondary', 'label' => 'Đang dọn', 'icon' => 'cleaning_services']
                                ];
                                $config = $status_config[$room['status']] ?? ['class' => 'badge-secondary', 'label' => $room['status'], 'icon' => 'help'];
                                ?>
                                <span class="badge <?php echo $config['class']; ?>">
                                    <span class="material-symbols-outlined text-xs"><?php echo $config['icon']; ?></span>
                                    <?php echo $config['label']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($room['last_cleaned']): ?>
                                    <span class="text-sm"><?php echo date('d/m/Y H:i', strtotime($room['last_cleaned'])); ?></span>
                                <?php else: ?>
                                    <span class="text-text-secondary-light dark:text-text-secondary-dark text-sm">Chưa dọn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($room['notes']): ?>
                                    <span class="text-sm" title="<?php echo htmlspecialchars($room['notes']); ?>">
                                        <?php echo mb_substr(htmlspecialchars($room['notes']), 0, 30) . '...'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-text-secondary-light dark:text-text-secondary-dark text-sm">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="updateRoomStatus(<?php echo $room['room_id']; ?>)" 
                                            class="action-btn" title="Cập nhật trạng thái">
                                        <span class="material-symbols-outlined text-sm">sync</span>
                                    </button>
                                    <a href="room-form.php?id=<?php echo $room['room_id']; ?>" 
                                       class="action-btn" title="Sửa">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </a>
                                    <button onclick="deleteRoom(<?php echo $room['room_id']; ?>)" 
                                            class="action-btn text-red-600" title="Xóa">
                                        <span class="material-symbols-outlined text-sm">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="font-semibold">Cập nhật trạng thái phòng</h3>
            <button onclick="closeStatusModal()" class="text-text-secondary-light dark:text-text-secondary-dark hover:text-text-primary-light dark:hover:text-text-primary-dark">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="statusForm">
                <input type="hidden" id="room_id" name="room_id">
                
                <div class="form-group">
                    <label class="form-label">Trạng thái mới</label>
                    <select name="status" class="form-select" required>
                        <option value="available">Trống</option>
                        <option value="occupied">Đang sử dụng</option>
                        <option value="maintenance">Bảo trì</option>
                        <option value="cleaning">Đang dọn</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ghi chú (tùy chọn)</label>
                    <textarea name="notes" class="form-textarea" rows="3"></textarea>
                </div>
                
                <div class="form-group mb-0">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="mark_cleaned" value="1">
                        <span>Đánh dấu đã dọn phòng</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Hủy</button>
            <button type="button" onclick="submitStatusUpdate()" class="btn btn-primary">Cập nhật</button>
        </div>
    </div>
</div>

<script>
function updateRoomStatus(roomId) {
    document.getElementById('room_id').value = roomId;
    document.getElementById('statusModal').classList.add('active');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('active');
    document.getElementById('statusForm').reset();
}

function submitStatusUpdate() {
    const form = document.getElementById('statusForm');
    const formData = new FormData(form);
    
    fetch('api/update-room-status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Cập nhật thành công!', 'success');
            closeStatusModal();
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

function deleteRoom(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa phòng này?')) {
        return;
    }
    
    fetch('api/delete-room.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'room_id=' + id
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
document.getElementById('statusModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>
