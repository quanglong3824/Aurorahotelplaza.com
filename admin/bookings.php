<?php
session_start();
require_once '../config/database.php';

$page_title = 'Quản lý đặt phòng';
$page_subtitle = 'Danh sách và quản lý các đơn đặt phòng';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "b.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(b.booking_code LIKE :search OR b.guest_name LIKE :search OR b.guest_email LIKE :search OR b.guest_phone LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($date_from)) {
    $where_clauses[] = "b.check_in_date >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "b.check_in_date <= :date_to";
    $params[':date_to'] = $date_to;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM bookings b $where_sql";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Get bookings
    $sql = "
        SELECT b.*, u.full_name as user_name, rt.type_name, r.room_number
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        $where_sql
        ORDER BY b.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get status counts
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in,
            SUM(CASE WHEN status = 'checked_out' THEN 1 ELSE 0 END) as checked_out,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings
    ");
    $status_counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Bookings page error: " . $e->getMessage());
    $bookings = [];
    $total_records = 0;
    $total_pages = 0;
    $status_counts = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'checked_in' => 0, 'checked_out' => 0, 'cancelled' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" class="flex flex-wrap items-center gap-4 w-full">
        <!-- Search -->
        <div class="search-box flex-1 min-w-[200px]">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm mã đơn, tên, email, SĐT..." class="form-input">
        </div>
        
        <!-- Status Filter -->
        <select name="status" class="form-select w-auto">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
            <option value="checked_in" <?php echo $status_filter === 'checked_in' ? 'selected' : ''; ?>>Đã nhận phòng</option>
            <option value="checked_out" <?php echo $status_filter === 'checked_out' ? 'selected' : ''; ?>>Đã trả phòng</option>
            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
        </select>
        
        <!-- Date From -->
        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
               placeholder="Từ ngày" class="form-input w-auto">
        
        <!-- Date To -->
        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
               placeholder="Đến ngày" class="form-input w-auto">
        
        <!-- Buttons -->
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined text-sm">filter_alt</span>
            Lọc
        </button>
        
        <a href="bookings.php" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">refresh</span>
            Reset
        </a>
    </form>
</div>

<!-- Status Tabs -->
<div class="tabs mb-6">
    <a href="?status=all" class="tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
        Tất cả (<?php echo $status_counts['total']; ?>)
    </a>
    <a href="?status=pending" class="tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
        Chờ xác nhận (<?php echo $status_counts['pending']; ?>)
    </a>
    <a href="?status=confirmed" class="tab <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">
        Đã xác nhận (<?php echo $status_counts['confirmed']; ?>)
    </a>
    <a href="?status=checked_in" class="tab <?php echo $status_filter === 'checked_in' ? 'active' : ''; ?>">
        Đã nhận phòng (<?php echo $status_counts['checked_in']; ?>)
    </a>
    <a href="?status=checked_out" class="tab <?php echo $status_filter === 'checked_out' ? 'active' : ''; ?>">
        Đã trả phòng (<?php echo $status_counts['checked_out']; ?>)
    </a>
    <a href="?status=cancelled" class="tab <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
        Đã hủy (<?php echo $status_counts['cancelled']; ?>)
    </a>
</div>

<!-- Bookings Table -->
<div class="card">
    <div class="card-header flex items-center justify-between">
        <div>
            <h3 class="font-semibold">Danh sách đặt phòng</h3>
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
                Hiển thị <?php echo count($bookings); ?> / <?php echo $total_records; ?> đơn
            </p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="btn btn-secondary btn-sm no-print">
                <span class="material-symbols-outlined text-sm">print</span>
                In
            </button>
            <a href="api/export-bookings.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success btn-sm">
                <span class="material-symbols-outlined text-sm">download</span>
                Xuất Excel
            </a>
        </div>
    </div>
    
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Loại phòng</th>
                    <th>Phòng</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Số đêm</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thanh toán</th>
                    <th class="no-print">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-8">
                            <div class="empty-state">
                                <span class="empty-state-icon material-symbols-outlined">inbox</span>
                                <p class="empty-state-title">Không tìm thấy đặt phòng</p>
                                <p class="empty-state-description">Thử thay đổi bộ lọc hoặc tìm kiếm</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td class="font-medium">
                                <a href="booking-detail.php?id=<?php echo $booking['booking_id']; ?>" 
                                   class="text-accent hover:underline">
                                    <?php echo htmlspecialchars($booking['booking_code']); ?>
                                </a>
                            </td>
                            <td>
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                        <?php echo htmlspecialchars($booking['guest_phone']); ?>
                                    </p>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($booking['type_name']); ?></td>
                            <td>
                                <?php if ($booking['room_number']): ?>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($booking['room_number']); ?></span>
                                <?php else: ?>
                                    <span class="text-text-secondary-light dark:text-text-secondary-dark text-sm">Chưa phân</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></td>
                            <td class="text-center"><?php echo $booking['total_nights']; ?></td>
                            <td class="font-medium"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>đ</td>
                            <td>
                                <?php
                                $status_classes = [
                                    'pending' => 'badge-warning',
                                    'confirmed' => 'badge-info',
                                    'checked_in' => 'badge-success',
                                    'checked_out' => 'badge-secondary',
                                    'cancelled' => 'badge-danger',
                                    'no_show' => 'badge-danger'
                                ];
                                $status_labels = [
                                    'pending' => 'Chờ xác nhận',
                                    'confirmed' => 'Đã xác nhận',
                                    'checked_in' => 'Đã nhận phòng',
                                    'checked_out' => 'Đã trả phòng',
                                    'cancelled' => 'Đã hủy',
                                    'no_show' => 'Không đến'
                                ];
                                ?>
                                <span class="badge <?php echo $status_classes[$booking['status']] ?? 'badge-secondary'; ?>">
                                    <?php echo $status_labels[$booking['status']] ?? $booking['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $payment_classes = [
                                    'unpaid' => 'badge-danger',
                                    'partial' => 'badge-warning',
                                    'paid' => 'badge-success',
                                    'refunded' => 'badge-secondary'
                                ];
                                $payment_labels = [
                                    'unpaid' => 'Chưa thanh toán',
                                    'partial' => 'Thanh toán 1 phần',
                                    'paid' => 'Đã thanh toán',
                                    'refunded' => 'Đã hoàn tiền'
                                ];
                                ?>
                                <span class="badge <?php echo $payment_classes[$booking['payment_status']] ?? 'badge-secondary'; ?>">
                                    <?php echo $payment_labels[$booking['payment_status']] ?? $booking['payment_status']; ?>
                                </span>
                            </td>
                            <td class="no-print">
                                <div class="action-buttons">
                                    <a href="booking-detail.php?id=<?php echo $booking['booking_id']; ?>" 
                                       class="action-btn" title="Xem chi tiết">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                    </a>
                                    
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <button onclick="confirmBooking(<?php echo $booking['booking_id']; ?>)" 
                                                class="action-btn text-green-600" title="Xác nhận">
                                            <span class="material-symbols-outlined text-sm">check_circle</span>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['status'] === 'confirmed'): ?>
                                        <button onclick="checkinBooking(<?php echo $booking['booking_id']; ?>)" 
                                                class="action-btn text-blue-600" title="Check-in">
                                            <span class="material-symbols-outlined text-sm">login</span>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['status'] === 'checked_in'): ?>
                                        <button onclick="checkoutBooking(<?php echo $booking['booking_id']; ?>)" 
                                                class="action-btn text-orange-600" title="Check-out">
                                            <span class="material-symbols-outlined text-sm">logout</span>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                        <button onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)" 
                                                class="action-btn text-red-600" title="Hủy đơn">
                                            <span class="material-symbols-outlined text-sm">cancel</span>
                                        </button>
                                    <?php endif; ?>
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

<script>
function confirmBooking(id) {
    if (confirm('Xác nhận đơn đặt phòng này?')) {
        updateBookingStatus(id, 'confirmed');
    }
}

function checkinBooking(id) {
    if (confirm('Xác nhận khách đã check-in?')) {
        updateBookingStatus(id, 'checked_in');
    }
}

function checkoutBooking(id) {
    if (confirm('Xác nhận khách đã check-out?')) {
        updateBookingStatus(id, 'checked_out');
    }
}

function cancelBooking(id) {
    const reason = prompt('Lý do hủy đơn:');
    if (reason !== null) {
        updateBookingStatus(id, 'cancelled', reason);
    }
}

function updateBookingStatus(id, status, reason = '') {
    const formData = new FormData();
    formData.append('booking_id', id);
    formData.append('status', status);
    if (reason) formData.append('reason', reason);
    
    fetch('api/update-booking-status.php', {
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
</script>

<?php include 'includes/admin-footer.php'; ?>
