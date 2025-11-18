<?php
session_start();
require_once '../config/database.php';

$page_title = 'Đơn dịch vụ';
$page_subtitle = 'Quản lý đặt dịch vụ của khách hàng';

// Filters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "sb.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(u.full_name LIKE :search OR s.service_name LIKE :search OR sb.booking_code LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $db = getDB();
    
    $sql = "
        SELECT sb.*, u.full_name, s.service_name, s.price as service_price
        FROM service_bookings sb
        LEFT JOIN users u ON sb.user_id = u.user_id
        LEFT JOIN services s ON sb.service_id = s.service_id
        $where_sql
        ORDER BY sb.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $service_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Stats
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END) as total_revenue
        FROM service_bookings
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Service bookings error: " . $e->getMessage());
    $service_bookings = [];
    $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'total_revenue' => 0];
}

include 'includes/admin-header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold mb-1" style="color: #d4af37;"><?php echo $stats['total']; ?></div>
            <div class="text-sm text-gray-600">Tổng đơn (30 ngày)</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-orange-600 mb-1"><?php echo $stats['pending']; ?></div>
            <div class="text-sm text-gray-600">Chờ xác nhận</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-green-600 mb-1"><?php echo $stats['completed']; ?></div>
            <div class="text-sm text-gray-600">Hoàn thành</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?>đ</div>
            <div class="text-sm text-gray-600">Doanh thu</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex items-center gap-4">
            <div class="flex-1 search-box">
                <span class="search-icon material-symbols-outlined">search</span>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="form-input" placeholder="Tìm theo tên, dịch vụ, mã đơn...">
            </div>
            
            <select name="status" class="form-select w-48">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>Đang thực hiện</option>
                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
            </select>
            
            <button type="submit" class="btn btn-primary">
                <span class="material-symbols-outlined text-sm">filter_list</span>
                Lọc
            </button>
        </form>
    </div>
</div>

<!-- Service Bookings List -->
<div class="card">
    <div class="card-header">
        <h3 class="font-bold text-lg">Danh sách đơn dịch vụ</h3>
    </div>
    <div class="card-body">
        <?php if (empty($service_bookings)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined empty-state-icon">receipt_long</span>
                <p class="empty-state-title">Không tìm thấy đơn dịch vụ</p>
                <p class="empty-state-description">Chưa có đơn đặt dịch vụ nào</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Dịch vụ</th>
                            <th>Số lượng</th>
                            <th>Tổng tiền</th>
                            <th>Ngày sử dụng</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($service_bookings as $booking): ?>
                            <tr>
                                <td class="font-mono font-semibold"><?php echo htmlspecialchars($booking['booking_code']); ?></td>
                                <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                <td><?php echo $booking['quantity']; ?></td>
                                <td class="font-bold" style="color: #d4af37;">
                                    <?php echo number_format($booking['total_price'], 0, ',', '.'); ?>đ
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($booking['service_date'])); ?></td>
                                <td>
                                    <?php
                                    $status_config = [
                                        'pending' => ['class' => 'badge-warning', 'label' => 'Chờ xác nhận'],
                                        'confirmed' => ['class' => 'badge-info', 'label' => 'Đã xác nhận'],
                                        'in_progress' => ['class' => 'badge-primary', 'label' => 'Đang thực hiện'],
                                        'completed' => ['class' => 'badge-success', 'label' => 'Hoàn thành'],
                                        'cancelled' => ['class' => 'badge-danger', 'label' => 'Đã hủy']
                                    ];
                                    $config = $status_config[$booking['status']] ?? ['class' => 'badge-secondary', 'label' => $booking['status']];
                                    ?>
                                    <span class="badge <?php echo $config['class']; ?>"><?php echo $config['label']; ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <button onclick="updateStatus(<?php echo $booking['service_booking_id']; ?>, 'confirmed')" 
                                                    class="action-btn text-green-600" title="Xác nhận">
                                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($booking['status'] === 'confirmed'): ?>
                                            <button onclick="updateStatus(<?php echo $booking['service_booking_id']; ?>, 'in_progress')" 
                                                    class="action-btn text-blue-600" title="Bắt đầu">
                                                <span class="material-symbols-outlined text-sm">play_circle</span>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($booking['status'] === 'in_progress'): ?>
                                            <button onclick="updateStatus(<?php echo $booking['service_booking_id']; ?>, 'completed')" 
                                                    class="action-btn text-green-600" title="Hoàn thành">
                                                <span class="material-symbols-outlined text-sm">task_alt</span>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                            <button onclick="updateStatus(<?php echo $booking['service_booking_id']; ?>, 'cancelled')" 
                                                    class="action-btn text-red-600" title="Hủy">
                                                <span class="material-symbols-outlined text-sm">cancel</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateStatus(id, status) {
    const messages = {
        'confirmed': 'Xác nhận đơn dịch vụ này?',
        'in_progress': 'Bắt đầu thực hiện dịch vụ?',
        'completed': 'Đánh dấu hoàn thành?',
        'cancelled': 'Hủy đơn dịch vụ này?'
    };
    
    if (!confirm(messages[status] || 'Cập nhật trạng thái?')) return;
    
    fetch('api/update-service-booking-status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `service_booking_id=${id}&status=${status}`
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
