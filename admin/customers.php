<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý khách hàng';
$page_subtitle = 'Danh sách khách hàng và thông tin';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = ["user_role = 'customer'"];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(full_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

try {
    $db = getDB();
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM users $where_sql";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Get customers
    $sql = "
        SELECT u.*,
               ul.current_points, ul.lifetime_points,
               mt.tier_name, mt.color_code,
               (SELECT COUNT(*) FROM bookings WHERE user_id = u.user_id) as total_bookings,
               (SELECT SUM(total_amount) FROM bookings WHERE user_id = u.user_id AND status != 'cancelled') as total_spent
        FROM users u
        LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
        LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
        $where_sql
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get status counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned
        FROM users
        WHERE user_role = 'customer'
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Customers page error: " . $e->getMessage());
    $customers = [];
    $total_records = 0;
    $total_pages = 0;
    $counts = ['total' => 0, 'active' => 0, 'inactive' => 0, 'banned' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng khách hàng</p>
        <p class="text-2xl font-bold"><?php echo number_format($counts['total']); ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đang hoạt động</p>
        <p class="text-2xl font-bold text-green-600"><?php echo number_format($counts['active']); ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Không hoạt động</p>
        <p class="text-2xl font-bold text-gray-600"><?php echo number_format($counts['inactive']); ?></p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Bị khóa</p>
        <p class="text-2xl font-bold text-red-600"><?php echo number_format($counts['banned']); ?></p>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-4 w-full">
        <div class="search-box flex-1 min-w-[200px]">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm tên, email, SĐT..." class="form-input">
        </div>
        
        <select name="status" class="form-select w-auto">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Không hoạt động</option>
            <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>Bị khóa</option>
        </select>
        
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">filter_alt</span>
            Lọc
        </button>
        
        <a href="customers.php" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">refresh</span>
            Reset
        </a>
    </form>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-header flex items-center justify-between">
        <div>
            <h3 class="font-semibold">Danh sách khách hàng</h3>
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
                Hiển thị <?php echo count($customers); ?> / <?php echo $total_records; ?> khách hàng
            </p>
        </div>
        <a href="api/export-customers.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success btn-sm">
            <span class="material-symbols-outlined text-sm">download</span>
            Xuất Excel
        </a>
    </div>
    
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Khách hàng</th>
                    <th>Liên hệ</th>
                    <th>Hạng thành viên</th>
                    <th>Điểm tích lũy</th>
                    <th>Tổng đơn</th>
                    <th>Tổng chi tiêu</th>
                    <th>Trạng thái</th>
                    <th>Ngày đăng ký</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-8">
                            <div class="empty-state">
                                <span class="empty-state-icon material-symbols-outlined">people</span>
                                <p class="empty-state-title">Không tìm thấy khách hàng</p>
                                <p class="empty-state-description">Thử thay đổi bộ lọc hoặc tìm kiếm</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <?php if ($customer['avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($customer['avatar']); ?>" 
                                             alt="Avatar" class="w-10 h-10 rounded-full object-cover">
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-accent/20 rounded-full flex items-center justify-center">
                                            <span class="material-symbols-outlined text-accent">person</span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-medium"><?php echo htmlspecialchars($customer['full_name']); ?></p>
                                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark">
                                            ID: <?php echo $customer['user_id']; ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-sm">
                                    <p><?php echo htmlspecialchars($customer['email']); ?></p>
                                    <?php if ($customer['phone']): ?>
                                        <p class="text-text-secondary-light dark:text-text-secondary-dark">
                                            <?php echo htmlspecialchars($customer['phone']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($customer['tier_name']): ?>
                                    <span class="badge" style="background-color: <?php echo $customer['color_code']; ?>20; color: <?php echo $customer['color_code']; ?>">
                                        <?php echo htmlspecialchars($customer['tier_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Chưa có</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-sm">
                                    <p class="font-medium"><?php echo number_format($customer['current_points'] ?? 0); ?> điểm</p>
                                    <p class="text-text-secondary-light dark:text-text-secondary-dark text-xs">
                                        Tích lũy: <?php echo number_format($customer['lifetime_points'] ?? 0); ?>
                                    </p>
                                </div>
                            </td>
                            <td class="text-center font-medium"><?php echo $customer['total_bookings']; ?></td>
                            <td class="font-medium"><?php echo number_format($customer['total_spent'] ?? 0, 0, ',', '.'); ?>đ</td>
                            <td>
                                <?php
                                $status_config = [
                                    'active' => ['class' => 'badge-success', 'label' => 'Hoạt động'],
                                    'inactive' => ['class' => 'badge-secondary', 'label' => 'Không hoạt động'],
                                    'banned' => ['class' => 'badge-danger', 'label' => 'Bị khóa']
                                ];
                                $config = $status_config[$customer['status']] ?? ['class' => 'badge-secondary', 'label' => $customer['status']];
                                ?>
                                <span class="badge <?php echo $config['class']; ?>">
                                    <?php echo $config['label']; ?>
                                </span>
                            </td>
                            <td class="text-sm"><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="customer-detail.php?id=<?php echo $customer['user_id']; ?>" 
                                       class="action-btn" title="Xem chi tiết">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                    </a>
                                    <a href="bookings.php?user_id=<?php echo $customer['user_id']; ?>" 
                                       class="action-btn" title="Xem đơn hàng">
                                        <span class="material-symbols-outlined text-sm">receipt_long</span>
                                    </a>
                                    <?php if ($customer['status'] !== 'banned'): ?>
                                        <button onclick="banCustomer(<?php echo $customer['user_id']; ?>)" 
                                                class="action-btn text-red-600" title="Khóa tài khoản">
                                            <span class="material-symbols-outlined text-sm">block</span>
                                        </button>
                                    <?php else: ?>
                                        <button onclick="unbanCustomer(<?php echo $customer['user_id']; ?>)" 
                                                class="action-btn text-green-600" title="Mở khóa">
                                            <span class="material-symbols-outlined text-sm">check_circle</span>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="deleteCustomer(<?php echo $customer['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($customer['full_name'])); ?>')" 
                                            class="action-btn text-red-600" title="Xóa vĩnh viễn">
                                        <span class="material-symbols-outlined text-sm">delete_forever</span>
                                    </button>
                                </div>
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
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function banCustomer(userId) {
    const reason = prompt('Lý do khóa tài khoản:');
    if (reason === null) return;
    
    updateCustomerStatus(userId, 'banned', reason);
}

function unbanCustomer(userId) {
    if (confirm('Mở khóa tài khoản này?')) {
        updateCustomerStatus(userId, 'active');
    }
}

function updateCustomerStatus(userId, status, reason = '') {
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('status', status);
    if (reason) formData.append('reason', reason);
    
    fetch('api/update-customer-status.php', {
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

function deleteCustomer(userId, userName) {
    // Hiển thị confirm với thông tin chi tiết
    const confirmMsg = `⚠️ XÓA VĨNH VIỄN KHÁCH HÀNG ⚠️\n\n` +
        `Bạn sắp xóa khách hàng: ${userName}\n\n` +
        `Hành động này sẽ XÓA TẤT CẢ dữ liệu liên quan:\n` +
        `- Thông tin tài khoản\n` +
        `- Lịch sử đặt phòng\n` +
        `- Điểm tích lũy\n` +
        `- Đánh giá\n` +
        `- Thông báo\n` +
        `- Liên hệ\n\n` +
        `⛔ KHÔNG THỂ HOÀN TÁC!\n\n` +
        `Nhập "XOA" để xác nhận:`;
    
    const confirmation = prompt(confirmMsg);
    if (confirmation !== 'XOA') {
        if (confirmation !== null) {
            showToast('Bạn cần nhập "XOA" để xác nhận', 'warning');
        }
        return;
    }
    
    fetch('api/delete-customer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + encodeURIComponent(userId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Đã xóa khách hàng và tất cả dữ liệu liên quan!', 'success');
            setTimeout(() => location.reload(), 1500);
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
