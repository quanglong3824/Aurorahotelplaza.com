<?php
session_start();
require_once '../config/database.php';

$page_title = 'Thông báo';
$page_subtitle = 'Quản lý thông báo hệ thống';

// Get filter
$filter = $_GET['filter'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // Build query
    $where = ["user_id = :user_id"];
    $params = [':user_id' => $user_id];
    
    if ($filter === 'unread') {
        $where[] = "is_read = 0";
    } elseif ($filter === 'read') {
        $where[] = "is_read = 1";
    }
    
    if ($type_filter !== 'all') {
        $where[] = "type = :type";
        $params[':type'] = $type_filter;
    }
    
    $where_sql = implode(' AND ', $where);
    
    // Get notifications
    $stmt = $db->prepare("
        SELECT * FROM notifications
        WHERE $where_sql
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
            SUM(CASE WHEN type = 'booking' THEN 1 ELSE 0 END) as booking,
            SUM(CASE WHEN type = 'payment' THEN 1 ELSE 0 END) as payment,
            SUM(CASE WHEN type = 'review' THEN 1 ELSE 0 END) as review,
            SUM(CASE WHEN type = 'service' THEN 1 ELSE 0 END) as service
        FROM notifications
        WHERE user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Notifications page error: " . $e->getMessage());
    $notifications = [];
    $stats = ['total' => 0, 'unread' => 0, 'booking' => 0, 'payment' => 0, 'review' => 0, 'service' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng số</p>
        <p class="text-2xl font-bold"><?php echo $stats['total']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Chưa đọc</p>
        <p class="text-2xl font-bold text-red-600"><?php echo $stats['unread']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đặt phòng</p>
        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['booking']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Thanh toán</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $stats['payment']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đánh giá</p>
        <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['review']; ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Dịch vụ</p>
        <p class="text-2xl font-bold text-purple-600"><?php echo $stats['service']; ?></p>
    </div>
</div>

<!-- Filters -->
<div class="flex items-center justify-between mb-6 gap-4">
    <form method="GET" class="flex gap-2 flex-wrap">
        <select name="filter" class="form-select" onchange="this.form.submit()">
            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
            <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Chưa đọc</option>
            <option value="read" <?php echo $filter === 'read' ? 'selected' : ''; ?>>Đã đọc</option>
        </select>
        
        <select name="type" class="form-select" onchange="this.form.submit()">
            <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>Tất cả loại</option>
            <option value="booking" <?php echo $type_filter === 'booking' ? 'selected' : ''; ?>>Đặt phòng</option>
            <option value="payment" <?php echo $type_filter === 'payment' ? 'selected' : ''; ?>>Thanh toán</option>
            <option value="review" <?php echo $type_filter === 'review' ? 'selected' : ''; ?>>Đánh giá</option>
            <option value="service" <?php echo $type_filter === 'service' ? 'selected' : ''; ?>>Dịch vụ</option>
            <option value="system" <?php echo $type_filter === 'system' ? 'selected' : ''; ?>>Hệ thống</option>
            <option value="user" <?php echo $type_filter === 'user' ? 'selected' : ''; ?>>Người dùng</option>
        </select>
    </form>
    
    <div class="flex gap-2">
        <button onclick="markAllAsRead()" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">done_all</span>
            Đánh dấu tất cả đã đọc
        </button>
        <button onclick="deleteAllRead()" class="btn btn-danger">
            <span class="material-symbols-outlined text-sm">delete_sweep</span>
            Xóa đã đọc
        </button>
    </div>
</div>

<!-- Notifications List -->
<?php if (empty($notifications)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <span class="empty-state-icon material-symbols-outlined">notifications_off</span>
                <p class="empty-state-title">Không có thông báo</p>
                <p class="empty-state-description">Bạn chưa có thông báo nào</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body p-0">
            <div class="divide-y divide-border-light dark:divide-border-dark">
                <?php foreach ($notifications as $notif): 
                    $type_config = [
                        'booking' => ['color' => 'blue', 'bg' => 'bg-blue-100 dark:bg-blue-900'],
                        'payment' => ['color' => 'green', 'bg' => 'bg-green-100 dark:bg-green-900'],
                        'review' => ['color' => 'yellow', 'bg' => 'bg-yellow-100 dark:bg-yellow-900'],
                        'service' => ['color' => 'purple', 'bg' => 'bg-purple-100 dark:bg-purple-900'],
                        'system' => ['color' => 'gray', 'bg' => 'bg-gray-100 dark:bg-gray-900'],
                        'user' => ['color' => 'indigo', 'bg' => 'bg-indigo-100 dark:bg-indigo-900']
                    ];
                    $config = $type_config[$notif['type']] ?? $type_config['system'];
                    $time_ago = time_ago($notif['created_at']);
                ?>
                    <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?> p-4 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full <?php echo $config['bg']; ?> flex items-center justify-center">
                                    <span class="material-symbols-outlined text-<?php echo $config['color']; ?>-600">
                                        <?php echo htmlspecialchars($notif['icon']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-base mb-1 <?php echo !$notif['is_read'] ? 'text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300'; ?>">
                                            <?php echo htmlspecialchars($notif['title']); ?>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="inline-block w-2 h-2 bg-red-500 rounded-full ml-2"></span>
                                            <?php endif; ?>
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            <?php echo htmlspecialchars($notif['message']); ?>
                                        </p>
                                        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-500">
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">schedule</span>
                                                <?php echo $time_ago; ?>
                                            </span>
                                            <span class="badge badge-<?php echo $config['color']; ?>">
                                                <?php echo ucfirst($notif['type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex items-center gap-2">
                                        <?php if ($notif['link']): ?>
                                            <a href="<?php echo htmlspecialchars($notif['link']); ?>" 
                                               class="btn btn-sm btn-secondary"
                                               onclick="markAsRead(<?php echo $notif['notification_id']; ?>)">
                                                <span class="material-symbols-outlined text-sm">open_in_new</span>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!$notif['is_read']): ?>
                                            <button onclick="markAsRead(<?php echo $notif['notification_id']; ?>)" 
                                                    class="btn btn-sm btn-secondary"
                                                    title="Đánh dấu đã đọc">
                                                <span class="material-symbols-outlined text-sm">done</span>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button onclick="deleteNotification(<?php echo $notif['notification_id']; ?>)" 
                                                class="btn btn-sm btn-danger"
                                                title="Xóa">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.notification-item.unread {
    background: rgba(59, 130, 246, 0.05);
    border-left: 4px solid #3b82f6;
}

.dark .notification-item.unread {
    background: rgba(59, 130, 246, 0.1);
}

.badge-blue { background: #dbeafe; color: #1e40af; }
.badge-green { background: #dcfce7; color: #15803d; }
.badge-yellow { background: #fef3c7; color: #a16207; }
.badge-purple { background: #f3e8ff; color: #7c3aed; }
.badge-gray { background: #f3f4f6; color: #4b5563; }
.badge-indigo { background: #e0e7ff; color: #4f46e5; }

.dark .badge-blue { background: #1e3a8a; color: #93c5fd; }
.dark .badge-green { background: #14532d; color: #86efac; }
.dark .badge-yellow { background: #713f12; color: #fde047; }
.dark .badge-purple { background: #581c87; color: #d8b4fe; }
.dark .badge-gray { background: #374151; color: #d1d5db; }
.dark .badge-indigo { background: #3730a3; color: #a5b4fc; }
</style>

<script>
function markAsRead(notificationId) {
    fetch('api/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra', 'error');
    });
}

function markAllAsRead() {
    if (!confirm('Đánh dấu tất cả thông báo đã đọc?')) return;
    
    fetch('api/mark-all-notifications-read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Đã đánh dấu tất cả đã đọc', 'success');
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

function deleteNotification(notificationId) {
    if (!confirm('Bạn có chắc chắn muốn xóa thông báo này?')) return;
    
    fetch('api/delete-notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Xóa thành công', 'success');
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

function deleteAllRead() {
    if (!confirm('Xóa tất cả thông báo đã đọc?')) return;
    
    fetch('api/delete-read-notifications.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Đã xóa thông báo đã đọc', 'success');
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

<?php
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Vừa xong';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
    
    return date('d/m/Y H:i', $time);
}
?>

<?php include 'includes/admin-footer.php'; ?>
