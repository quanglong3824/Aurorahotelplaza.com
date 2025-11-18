<?php
session_start();
require_once '../config/database.php';

$page_title = 'Báo cáo & Thống kê';
$page_subtitle = 'Phân tích dữ liệu và báo cáo';

// Get date range
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

try {
    $db = getDB();
    
    // Revenue statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_booking_value,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
        FROM bookings
        WHERE DATE(created_at) BETWEEN :date_from AND :date_to
    ");
    $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
    $revenue_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Daily revenue chart data
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as bookings,
            SUM(total_amount) as revenue
        FROM bookings
        WHERE DATE(created_at) BETWEEN :date_from AND :date_to
        AND status != 'cancelled'
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
    $daily_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Room type performance
    $stmt = $db->prepare("
        SELECT 
            rt.type_name,
            COUNT(b.booking_id) as bookings,
            SUM(b.total_amount) as revenue,
            AVG(b.total_nights) as avg_nights
        FROM bookings b
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        WHERE DATE(b.created_at) BETWEEN :date_from AND :date_to
        AND b.status != 'cancelled'
        GROUP BY rt.room_type_id
        ORDER BY revenue DESC
    ");
    $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
    $room_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top customers
    $stmt = $db->prepare("
        SELECT 
            u.full_name,
            u.email,
            COUNT(b.booking_id) as total_bookings,
            SUM(b.total_amount) as total_spent
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        WHERE DATE(b.created_at) BETWEEN :date_from AND :date_to
        AND b.status != 'cancelled'
        GROUP BY u.user_id
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
    $top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Booking status distribution
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM bookings
        WHERE DATE(created_at) BETWEEN :date_from AND :date_to
        GROUP BY status
    ");
    $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
    $status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payment method distribution
    $stmt = $db->prepare("
        SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM payments
        WHERE DATE(created_at) BETWEEN :date_from AND :date_to
        AND status = 'completed'
        GROUP BY payment_method
    ");
    $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Occupancy rate
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT r.room_id) as total_rooms,
            COUNT(DISTINCT CASE WHEN r.status = 'occupied' THEN r.room_id END) as occupied_rooms
        FROM rooms r
    ");
    $stmt->execute();
    $occupancy = $stmt->fetch(PDO::FETCH_ASSOC);
    $occupancy_rate = $occupancy['total_rooms'] > 0 ? 
        ($occupancy['occupied_rooms'] / $occupancy['total_rooms']) * 100 : 0;
    
} catch (Exception $e) {
    error_log("Reports page error: " . $e->getMessage());
    $revenue_stats = ['total_bookings' => 0, 'total_revenue' => 0, 'avg_booking_value' => 0, 'cancelled_bookings' => 0];
    $daily_revenue = [];
    $room_performance = [];
    $top_customers = [];
    $status_distribution = [];
    $payment_methods = [];
    $occupancy_rate = 0;
}

include 'includes/admin-header.php';
?>

<!-- Date Range Filter -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="form-group mb-0">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from" value="<?php echo $date_from; ?>" 
                       class="form-input" required>
            </div>
            
            <div class="form-group mb-0">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to" value="<?php echo $date_to; ?>" 
                       class="form-input" required>
            </div>
            
            <button type="submit" class="btn btn-primary mt-6">
                <span class="material-symbols-outlined text-sm">search</span>
                Xem báo cáo
            </button>
            
            <a href="?date_from=<?php echo date('Y-m-01'); ?>&date_to=<?php echo date('Y-m-d'); ?>" 
               class="btn btn-secondary mt-6">
                Tháng này
            </a>
            
            <a href="?date_from=<?php echo date('Y-01-01'); ?>&date_to=<?php echo date('Y-m-d'); ?>" 
               class="btn btn-secondary mt-6">
                Năm nay
            </a>
        </form>
    </div>
</div>

<!-- Overview Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="stat-card">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng doanh thu</p>
            <span class="material-symbols-outlined text-green-600">trending_up</span>
        </div>
        <p class="text-3xl font-bold text-green-600">
            <?php echo number_format($revenue_stats['total_revenue'], 0, ',', '.'); ?>đ
        </p>
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
            Từ <?php echo $revenue_stats['total_bookings']; ?> đơn đặt phòng
        </p>
    </div>
    
    <div class="stat-card">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Giá trị TB/đơn</p>
            <span class="material-symbols-outlined text-blue-600">receipt_long</span>
        </div>
        <p class="text-3xl font-bold text-blue-600">
            <?php echo number_format($revenue_stats['avg_booking_value'], 0, ',', '.'); ?>đ
        </p>
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
            Trung bình mỗi đơn
        </p>
    </div>
    
    <div class="stat-card">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tỷ lệ lấp đầy</p>
            <span class="material-symbols-outlined text-purple-600">hotel</span>
        </div>
        <p class="text-3xl font-bold text-purple-600">
            <?php echo number_format($occupancy_rate, 1); ?>%
        </p>
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
            <?php echo $occupancy['occupied_rooms']; ?>/<?php echo $occupancy['total_rooms']; ?> phòng
        </p>
    </div>
    
    <div class="stat-card">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đơn bị hủy</p>
            <span class="material-symbols-outlined text-red-600">cancel</span>
        </div>
        <p class="text-3xl font-bold text-red-600">
            <?php echo $revenue_stats['cancelled_bookings']; ?>
        </p>
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
            <?php 
            $cancel_rate = $revenue_stats['total_bookings'] > 0 ? 
                ($revenue_stats['cancelled_bookings'] / $revenue_stats['total_bookings']) * 100 : 0;
            echo number_format($cancel_rate, 1);
            ?>% tổng đơn
        </p>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Daily Revenue Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold">Doanh thu theo ngày</h3>
        </div>
        <div class="card-body">
            <?php if (empty($daily_revenue)): ?>
                <div class="empty-state py-8">
                    <span class="empty-state-icon material-symbols-outlined">show_chart</span>
                    <p class="empty-state-description">Không có dữ liệu trong khoảng thời gian này</p>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($daily_revenue as $day): ?>
                        <div class="flex items-center gap-3">
                            <span class="text-sm w-24"><?php echo date('d/m/Y', strtotime($day['date'])); ?></span>
                            <div class="flex-1 bg-background-light dark:bg-background-dark rounded-full h-8 relative overflow-hidden">
                                <div class="bg-accent h-full rounded-full flex items-center px-3 text-white text-sm font-medium"
                                     style="width: <?php echo min(100, ($day['revenue'] / max(array_column($daily_revenue, 'revenue'))) * 100); ?>%">
                                    <?php echo number_format($day['revenue'], 0, ',', '.'); ?>đ
                                </div>
                            </div>
                            <span class="text-sm w-16 text-right"><?php echo $day['bookings']; ?> đơn</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Status Distribution -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold">Phân bố trạng thái đơn</h3>
        </div>
        <div class="card-body">
            <?php if (empty($status_distribution)): ?>
                <div class="empty-state py-8">
                    <span class="empty-state-icon material-symbols-outlined">pie_chart</span>
                    <p class="empty-state-description">Không có dữ liệu</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php 
                    $total_status = array_sum(array_column($status_distribution, 'count'));
                    $status_labels = [
                        'pending' => 'Chờ xác nhận',
                        'confirmed' => 'Đã xác nhận',
                        'checked_in' => 'Đã nhận phòng',
                        'checked_out' => 'Đã trả phòng',
                        'cancelled' => 'Đã hủy',
                        'no_show' => 'Không đến'
                    ];
                    $status_colors = [
                        'pending' => 'bg-yellow-500',
                        'confirmed' => 'bg-blue-500',
                        'checked_in' => 'bg-green-500',
                        'checked_out' => 'bg-gray-500',
                        'cancelled' => 'bg-red-500',
                        'no_show' => 'bg-orange-500'
                    ];
                    foreach ($status_distribution as $status): 
                        $percentage = ($status['count'] / $total_status) * 100;
                    ?>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm"><?php echo $status_labels[$status['status']] ?? $status['status']; ?></span>
                                <span class="text-sm font-medium"><?php echo $status['count']; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                            </div>
                            <div class="w-full bg-background-light dark:bg-background-dark rounded-full h-2">
                                <div class="<?php echo $status_colors[$status['status']] ?? 'bg-gray-500'; ?> h-2 rounded-full"
                                     style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Room Performance -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="font-semibold">Hiệu suất theo loại phòng</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Loại phòng</th>
                    <th>Số đơn</th>
                    <th>Doanh thu</th>
                    <th>Số đêm TB</th>
                    <th>Doanh thu TB/đơn</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($room_performance)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-8 text-text-secondary-light dark:text-text-secondary-dark">
                            Không có dữ liệu
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($room_performance as $room): ?>
                        <tr>
                            <td class="font-medium"><?php echo htmlspecialchars($room['type_name']); ?></td>
                            <td><?php echo $room['bookings']; ?></td>
                            <td class="font-medium"><?php echo number_format($room['revenue'], 0, ',', '.'); ?>đ</td>
                            <td><?php echo number_format($room['avg_nights'], 1); ?> đêm</td>
                            <td><?php echo number_format($room['revenue'] / $room['bookings'], 0, ',', '.'); ?>đ</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Top Customers -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="font-semibold">Top 10 khách hàng</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Khách hàng</th>
                    <th>Email</th>
                    <th>Số đơn</th>
                    <th>Tổng chi tiêu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($top_customers)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-8 text-text-secondary-light dark:text-text-secondary-dark">
                            Không có dữ liệu
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($top_customers as $index => $customer): ?>
                        <tr>
                            <td class="font-bold"><?php echo $index + 1; ?></td>
                            <td class="font-medium"><?php echo htmlspecialchars($customer['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo $customer['total_bookings']; ?></td>
                            <td class="font-medium text-green-600"><?php echo number_format($customer['total_spent'], 0, ',', '.'); ?>đ</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payment Methods -->
<?php if (!empty($payment_methods)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold">Phương thức thanh toán</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php 
                $method_labels = [
                    'vnpay' => 'VNPay',
                    'cash' => 'Tiền mặt',
                    'bank_transfer' => 'Chuyển khoản',
                    'credit_card' => 'Thẻ tín dụng'
                ];
                foreach ($payment_methods as $method): 
                ?>
                    <div class="stat-card">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">
                            <?php echo $method_labels[$method['payment_method']] ?? $method['payment_method']; ?>
                        </p>
                        <p class="text-xl font-bold"><?php echo $method['count']; ?> giao dịch</p>
                        <p class="text-sm text-green-600 mt-1">
                            <?php echo number_format($method['total_amount'], 0, ',', '.'); ?>đ
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Export Button -->
<div class="flex justify-end mt-6 no-print">
    <button onclick="window.print()" class="btn btn-primary">
        <span class="material-symbols-outlined text-sm">print</span>
        In báo cáo
    </button>
</div>

<?php include 'includes/admin-footer.php'; ?>
