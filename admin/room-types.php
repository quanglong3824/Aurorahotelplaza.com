<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý loại phòng';
$page_subtitle = 'Danh sách các loại phòng và căn hộ';

// Get filter parameters
$category_filter = $_GET['category'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

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

if (!empty($search)) {
    $where_clauses[] = "(type_name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    // Get room types
    $sql = "
        SELECT rt.*,
               (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id) as total_rooms,
               (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id AND r.status = 'available') as available_rooms
        FROM room_types rt
        $where_sql
        ORDER BY rt.sort_order ASC, rt.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN category = 'room' THEN 1 ELSE 0 END) as rooms,
            SUM(CASE WHEN category = 'apartment' THEN 1 ELSE 0 END) as apartments,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
        FROM room_types
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Room types page error: " . $e->getMessage());
    $room_types = [];
    $counts = ['total' => 0, 'rooms' => 0, 'apartments' => 0, 'active' => 0, 'inactive' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold mb-1" style="color: #d4af37;"><?php echo $counts['total']; ?></div>
            <div class="text-sm text-gray-600">Tổng loại</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo $counts['rooms']; ?></div>
            <div class="text-sm text-gray-600">Phòng</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-purple-600 mb-1"><?php echo $counts['apartments']; ?></div>
            <div class="text-sm text-gray-600">Căn hộ</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-green-600 mb-1"><?php echo $counts['active']; ?></div>
            <div class="text-sm text-gray-600">Hoạt động</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-gray-600 mb-1"><?php echo $counts['inactive']; ?></div>
            <div class="text-sm text-gray-600">Tạm ngưng</div>
        </div>
    </div>
</div>

<!-- Action Bar -->
<div class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <form method="GET" class="flex gap-2">
            <div class="search-box">
                <span class="search-icon material-symbols-outlined">search</span>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tìm loại phòng..." class="form-input">
            </div>
            
            <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
            
            <select name="status" class="form-select">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Tạm ngưng</option>
            </select>
            
            <button type="submit" class="btn btn-secondary">
                <span class="material-symbols-outlined text-sm">search</span>
                Tìm
            </button>
        </form>
    
        <a href="room-type-form.php" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">add</span>
            Thêm loại phòng
        </a>
    </div>
    
    <!-- Category Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2">
        <a href="?category=all&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" 
           class="px-6 py-3 rounded-xl font-semibold transition-all whitespace-nowrap <?php echo $category_filter === 'all' ? 'bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'; ?>">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">apps</span>
                Tất cả (<?php echo $counts['total']; ?>)
            </span>
        </a>
        <a href="?category=room&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" 
           class="px-6 py-3 rounded-xl font-semibold transition-all whitespace-nowrap <?php echo $category_filter === 'room' ? 'bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'; ?>">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">hotel</span>
                Phòng (<?php echo $counts['rooms']; ?>)
            </span>
        </a>
        <a href="?category=apartment&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" 
           class="px-6 py-3 rounded-xl font-semibold transition-all whitespace-nowrap <?php echo $category_filter === 'apartment' ? 'bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'; ?>">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">apartment</span>
                Căn hộ (<?php echo $counts['apartments']; ?>)
            </span>
        </a>
    </div>
</div>

<!-- Room Types Grid -->
<?php if (empty($room_types)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <span class="empty-state-icon material-symbols-outlined">meeting_room</span>
                <p class="empty-state-title">Chưa có loại phòng nào</p>
                <p class="empty-state-description">Thêm loại phòng đầu tiên để bắt đầu</p>
                <a href="room-type-form.php" class="btn btn-primary mt-4">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Thêm loại phòng
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($room_types as $type): ?>
            <div class="card hover:shadow-lg transition-shadow">
                <!-- Image -->
                <?php if ($type['thumbnail']): ?>
                    <div class="h-48 overflow-hidden rounded-t-xl">
                        <img src="<?php echo htmlspecialchars($type['thumbnail']); ?>" 
                             alt="<?php echo htmlspecialchars($type['type_name']); ?>"
                             class="w-full h-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="h-48 bg-gray-200 dark:bg-gray-700 rounded-t-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-6xl text-gray-400">meeting_room</span>
                    </div>
                <?php endif; ?>
                
                <div class="card-body">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg mb-1">
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </h3>
                            <div class="flex gap-2">
                                <span class="badge badge-<?php echo $type['category'] === 'room' ? 'info' : 'success'; ?>">
                                    <?php echo $type['category'] === 'room' ? 'Phòng' : 'Căn hộ'; ?>
                                </span>
                                <span class="badge badge-<?php echo $type['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $type['status'] === 'active' ? 'Hoạt động' : 'Tạm ngưng'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <?php if ($type['short_description']): ?>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-3 line-clamp-2">
                            <?php echo htmlspecialchars($type['short_description']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Details -->
                    <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                        <div class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">people</span>
                            <span><?php echo $type['max_occupancy']; ?> khách</span>
                        </div>
                        <?php if ($type['size_sqm']): ?>
                            <div class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">square_foot</span>
                                <span><?php echo $type['size_sqm']; ?>m²</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($type['bed_type']): ?>
                            <div class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">bed</span>
                                <span><?php echo htmlspecialchars($type['bed_type']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">hotel</span>
                            <span><?php echo $type['available_rooms']; ?>/<?php echo $type['total_rooms']; ?> trống</span>
                        </div>
                    </div>
                    
                    <!-- Price -->
                    <div class="mb-3 pt-3 border-t border-border-light dark:border-border-dark">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Giá từ</p>
                        <p class="text-xl font-bold text-accent">
                            <?php echo number_format($type['base_price'], 0, ',', '.'); ?>đ
                            <span class="text-sm font-normal text-text-secondary-light dark:text-text-secondary-dark">/đêm</span>
                        </p>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a href="room-type-form.php?id=<?php echo $type['room_type_id']; ?>" 
                           class="btn btn-primary flex-1">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            Sửa
                        </a>
                        <a href="rooms.php?type_id=<?php echo $type['room_type_id']; ?>" 
                           class="btn btn-secondary flex-1">
                            <span class="material-symbols-outlined text-sm">list</span>
                            Phòng
                        </a>
                        <button onclick="deleteRoomType(<?php echo $type['room_type_id']; ?>)" 
                                class="btn btn-danger">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function deleteRoomType(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa loại phòng này?\nLưu ý: Tất cả phòng thuộc loại này cũng sẽ bị xóa.')) {
        return;
    }
    
    fetch('api/delete-room-type.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'room_type_id=' + id
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
</script>

<?php include 'includes/admin-footer.php'; ?>
