<?php
session_start();
require_once '../config/database.php';

// Check admin role
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Nhật ký hoạt động';
$page_subtitle = 'Theo dõi các hoạt động trong hệ thống';

// Get filter parameters
$action_filter = $_GET['action'] ?? 'all';
$user_filter = $_GET['user_id'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params = [];

if ($action_filter !== 'all') {
    $where_clauses[] = "al.action = :action";
    $params[':action'] = $action_filter;
}

if ($user_filter !== 'all') {
    $where_clauses[] = "al.user_id = :user_id";
    $params[':user_id'] = $user_filter;
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(al.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(al.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM activity_logs al $where_sql";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Get logs
    $sql = "
        SELECT al.*, u.full_name, u.email
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        $where_sql
        ORDER BY al.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique actions for filter
    $stmt = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
    $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get users for filter
    $stmt = $db->query("
        SELECT DISTINCT u.user_id, u.full_name 
        FROM users u
        INNER JOIN activity_logs al ON u.user_id = al.user_id
        WHERE u.user_role != 'customer'
        ORDER BY u.full_name
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT DATE(created_at)) as unique_days
        FROM activity_logs
        WHERE DATE(created_at) BETWEEN :date_from AND :date_to
    ");
    $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Logs page error: " . $e->getMessage());
    $logs = [];
    $actions = [];
    $users = [];
    $total_records = 0;
    $total_pages = 0;
    $stats = ['total' => 0, 'unique_users' => 0, 'unique_days' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng hoạt động</p>
        <p class="text-2xl font-bold"><?php echo number_format($stats['total']); ?></p>
        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
            Trong khoảng thời gian đã chọn
        </p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Người dùng hoạt động</p>
        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['unique_users']; ?></p>
        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
            Người dùng khác nhau
        </p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Số ngày</p>
        <p class="text-2xl font-bold text-green-600"><?php echo $stats['unique_days']; ?></p>
        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
            Ngày có hoạt động
        </p>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="form-group mb-0">
                <label class="form-label">Hành động</label>
                <select name="action" class="form-select">
                    <option value="all">Tất cả</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?php echo htmlspecialchars($action); ?>" 
                                <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($action); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group mb-0">
                <label class="form-label">Người dùng</label>
                <select name="user_id" class="form-select">
                    <option value="all">Tất cả</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>" 
                                <?php echo $user_filter == $user['user_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group mb-0">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="form-input">
            </div>
            
            <div class="form-group mb-0">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="form-input">
            </div>
            
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary flex-1">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    Lọc
                </button>
                <a href="logs.php" class="btn btn-secondary">
                    <span class="material-symbols-outlined text-sm">refresh</span>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-header flex items-center justify-between">
        <div>
            <h3 class="font-semibold">Nhật ký hoạt động</h3>
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
                Hiển thị <?php echo count($logs); ?> / <?php echo $total_records; ?> bản ghi
            </p>
        </div>
        <button onclick="exportLogs()" class="btn btn-success btn-sm">
            <span class="material-symbols-outlined text-sm">download</span>
            Xuất CSV
        </button>
    </div>
    
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Người dùng</th>
                    <th>Hành động</th>
                    <th>Loại</th>
                    <th>ID</th>
                    <th>Mô tả</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-8">
                            <div class="empty-state">
                                <span class="empty-state-icon material-symbols-outlined">history</span>
                                <p class="empty-state-title">Không có nhật ký nào</p>
                                <p class="empty-state-description">Thử thay đổi bộ lọc</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-sm whitespace-nowrap">
                                <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td>
                                <?php if ($log['full_name']): ?>
                                    <div>
                                        <p class="font-medium"><?php echo htmlspecialchars($log['full_name']); ?></p>
                                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark">
                                            <?php echo htmlspecialchars($log['email']); ?>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <span class="text-text-secondary-light dark:text-text-secondary-dark">System</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $action_colors = [
                                    'create' => 'badge-success',
                                    'update' => 'badge-info',
                                    'delete' => 'badge-danger',
                                    'login' => 'badge-blue',
                                    'logout' => 'badge-secondary'
                                ];
                                
                                $action_parts = explode('_', $log['action']);
                                $action_type = $action_parts[0];
                                $badge_class = $action_colors[$action_type] ?? 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="text-sm">
                                <?php echo $log['entity_type'] ? htmlspecialchars($log['entity_type']) : '-'; ?>
                            </td>
                            <td class="text-sm">
                                <?php echo $log['entity_id'] ? htmlspecialchars($log['entity_id']) : '-'; ?>
                            </td>
                            <td class="text-sm max-w-xs truncate" title="<?php echo htmlspecialchars($log['description'] ?? ''); ?>">
                                <?php echo htmlspecialchars($log['description'] ?? '-'); ?>
                            </td>
                            <td class="text-sm font-mono">
                                <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="card-footer flex items-center justify-between">
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                Trang <?php echo $page; ?> / <?php echo $total_pages; ?>
            </p>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                       class="pagination-item">
                        <span class="material-symbols-outlined text-sm">first_page</span>
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="pagination-item">
                        <span class="material-symbols-outlined text-sm">chevron_left</span>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="pagination-item">
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" 
                       class="pagination-item">
                        <span class="material-symbols-outlined text-sm">last_page</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.badge-blue { background: #dbeafe; color: #2563eb; }
</style>

<script>
function exportLogs() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = 'api/export-logs.php?' + params.toString();
}
</script>

<?php include 'includes/admin-footer.php'; ?>
