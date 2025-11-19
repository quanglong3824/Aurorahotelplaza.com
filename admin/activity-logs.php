<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/activity-logger.php';

$page_title = 'Nhật ký hoạt động';
$page_subtitle = 'Theo dõi mọi thao tác trên hệ thống';

// Get filters
$user_filter = $_GET['user'] ?? 'all';
$action_filter = $_GET['action'] ?? 'all';
$entity_filter = $_GET['entity'] ?? 'all';
$date_filter = $_GET['date'] ?? 'today';

try {
    $db = getDB();
    
    // Build query
    $where = [];
    $params = [];
    
    if ($user_filter !== 'all') {
        if ($user_filter === 'customers') {
            $where[] = "u.user_role = 'customer'";
        } elseif ($user_filter === 'staff') {
            $where[] = "u.user_role IN ('admin', 'receptionist', 'sale')";
        } else {
            $where[] = "al.user_id = :user_id";
            $params[':user_id'] = $user_filter;
        }
    }
    
    if ($action_filter !== 'all') {
        $where[] = "al.action LIKE :action";
        $params[':action'] = "%{$action_filter}%";
    }
    
    if ($entity_filter !== 'all') {
        $where[] = "al.entity_type = :entity_type";
        $params[':entity_type'] = $entity_filter;
    }
    
    // Date filter
    switch ($date_filter) {
        case 'today':
            $where[] = "DATE(al.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $where[] = "DATE(al.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $where[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
    
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get activities
    $stmt = $db->prepare("
        SELECT 
            al.*,
            u.full_name,
            u.email,
            u.user_role
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        $where_sql
        ORDER BY al.created_at DESC
        LIMIT 200
    ");
    
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats = ActivityLogger::getStatistics(7);
    
    // Get unique users for filter
    $stmt = $db->query("
        SELECT DISTINCT u.user_id, u.full_name, u.email, u.user_role
        FROM activity_logs al
        JOIN users u ON al.user_id = u.user_id
        ORDER BY u.full_name
        LIMIT 100
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count today's activities
    $stmt = $db->query("SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()");
    $today_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (Exception $e) {
    error_log("Activity logs page error: " . $e->getMessage());
    $activities = [];
    $stats = [];
    $users = [];
    $today_count = 0;
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="card">
        <div class="card-body text-center">
            <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-white">today</span>
            </div>
            <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo number_format($today_count); ?></div>
            <div class="text-sm text-gray-600">Hoạt động hôm nay</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body text-center">
            <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-white">hotel</span>
            </div>
            <div class="text-3xl font-bold text-green-600 mb-1">
                <?php 
                $booking_count = count(array_filter($activities, fn($a) => strpos($a['action'], 'booking') !== false));
                echo number_format($booking_count); 
                ?>
            </div>
            <div class="text-sm text-gray-600">Thao tác đặt phòng</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body text-center">
            <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-white">payments</span>
            </div>
            <div class="text-3xl font-bold text-yellow-600 mb-1">
                <?php 
                $payment_count = count(array_filter($activities, fn($a) => strpos($a['action'], 'payment') !== false));
                echo number_format($payment_count); 
                ?>
            </div>
            <div class="text-sm text-gray-600">Thao tác thanh toán</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body text-center">
            <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-white">people</span>
            </div>
            <div class="text-3xl font-bold text-purple-600 mb-1">
                <?php 
                $unique_users = count(array_unique(array_column($activities, 'user_id')));
                echo number_format($unique_users); 
                ?>
            </div>
            <div class="text-sm text-gray-600">Người dùng hoạt động</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="form-label text-sm">Người dùng</label>
                <select name="user" class="form-select">
                    <option value="all" <?php echo $user_filter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="customers" <?php echo $user_filter === 'customers' ? 'selected' : ''; ?>>Khách hàng</option>
                    <option value="staff" <?php echo $user_filter === 'staff' ? 'selected' : ''; ?>>Nhân viên</option>
                    <optgroup label="Người dùng cụ thể">
                        <?php foreach (array_slice($users, 0, 20) as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>" 
                                    <?php echo $user_filter == $user['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo $user['user_role']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            
            <div>
                <label class="form-label text-sm">Hành động</label>
                <select name="action" class="form-select">
                    <option value="all">Tất cả</option>
                    <option value="login" <?php echo $action_filter === 'login' ? 'selected' : ''; ?>>Đăng nhập</option>
                    <option value="booking" <?php echo $action_filter === 'booking' ? 'selected' : ''; ?>>Đặt phòng</option>
                    <option value="payment" <?php echo $action_filter === 'payment' ? 'selected' : ''; ?>>Thanh toán</option>
                    <option value="points" <?php echo $action_filter === 'points' ? 'selected' : ''; ?>>Điểm thưởng</option>
                    <option value="create" <?php echo $action_filter === 'create' ? 'selected' : ''; ?>>Tạo mới</option>
                    <option value="update" <?php echo $action_filter === 'update' ? 'selected' : ''; ?>>Cập nhật</option>
                    <option value="delete" <?php echo $action_filter === 'delete' ? 'selected' : ''; ?>>Xóa</option>
                </select>
            </div>
            
            <div>
                <label class="form-label text-sm">Đối tượng</label>
                <select name="entity" class="form-select">
                    <option value="all">Tất cả</option>
                    <option value="booking" <?php echo $entity_filter === 'booking' ? 'selected' : ''; ?>>Booking</option>
                    <option value="user" <?php echo $entity_filter === 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="payment" <?php echo $entity_filter === 'payment' ? 'selected' : ''; ?>>Payment</option>
                    <option value="room_pricing" <?php echo $entity_filter === 'room_pricing' ? 'selected' : ''; ?>>Giá phòng</option>
                    <option value="membership_tier" <?php echo $entity_filter === 'membership_tier' ? 'selected' : ''; ?>>Hạng TV</option>
                </select>
            </div>
            
            <div>
                <label class="form-label text-sm">Thời gian</label>
                <select name="date" class="form-select">
                    <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Hôm nay</option>
                    <option value="yesterday" <?php echo $date_filter === 'yesterday' ? 'selected' : ''; ?>>Hôm qua</option>
                    <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>7 ngày qua</option>
                    <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>30 ngày qua</option>
                    <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                </select>
            </div>
            
            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined text-sm">search</span>
                    Lọc
                </button>
                <a href="activity-logs.php" class="btn btn-secondary">
                    <span class="material-symbols-outlined text-sm">clear</span>
                    Xóa bộ lọc
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Activity List -->
<div class="card">
    <div class="card-header">
        <h3 class="font-bold text-lg">Nhật ký hoạt động (<?php echo count($activities); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($activities)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined empty-state-icon">history</span>
                <p class="empty-state-title">Không có hoạt động</p>
                <p class="empty-state-description">Chưa có hoạt động nào trong khoảng thời gian này</p>
            </div>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($activities as $activity): ?>
                    <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-slate-800 rounded-lg hover:shadow-md transition-shadow">
                        <!-- Icon -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                                    <?php 
                                    if (strpos($activity['action'], 'create') !== false) echo 'bg-green-100 text-green-600';
                                    elseif (strpos($activity['action'], 'delete') !== false) echo 'bg-red-100 text-red-600';
                                    elseif (strpos($activity['action'], 'update') !== false) echo 'bg-blue-100 text-blue-600';
                                    elseif (strpos($activity['action'], 'login') !== false) echo 'bg-purple-100 text-purple-600';
                                    elseif (strpos($activity['action'], 'payment') !== false) echo 'bg-yellow-100 text-yellow-600';
                                    else echo 'bg-gray-100 text-gray-600';
                                    ?>">
                            <span class="material-symbols-outlined text-sm">
                                <?php 
                                if (strpos($activity['action'], 'booking') !== false) echo 'hotel';
                                elseif (strpos($activity['action'], 'payment') !== false) echo 'payments';
                                elseif (strpos($activity['action'], 'login') !== false) echo 'login';
                                elseif (strpos($activity['action'], 'logout') !== false) echo 'logout';
                                elseif (strpos($activity['action'], 'points') !== false) echo 'stars';
                                elseif (strpos($activity['action'], 'create') !== false) echo 'add_circle';
                                elseif (strpos($activity['action'], 'update') !== false) echo 'edit';
                                elseif (strpos($activity['action'], 'delete') !== false) echo 'delete';
                                else echo 'history';
                                ?>
                            </span>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </p>
                                    <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                                        <?php if ($activity['full_name']): ?>
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-xs">person</span>
                                                <?php echo htmlspecialchars($activity['full_name']); ?>
                                                <span class="badge badge-<?php 
                                                    echo $activity['user_role'] === 'admin' ? 'danger' : 
                                                        ($activity['user_role'] === 'customer' ? 'info' : 'secondary'); 
                                                ?> text-xs ml-1">
                                                    <?php echo $activity['user_role']; ?>
                                                </span>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">Guest</span>
                                        <?php endif; ?>
                                        
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">schedule</span>
                                            <?php echo date('d/m/Y H:i:s', strtotime($activity['created_at'])); ?>
                                        </span>
                                        
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">computer</span>
                                            <?php echo htmlspecialchars($activity['ip_address']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <span class="badge badge-secondary text-xs whitespace-nowrap">
                                    <?php echo htmlspecialchars($activity['entity_type']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
