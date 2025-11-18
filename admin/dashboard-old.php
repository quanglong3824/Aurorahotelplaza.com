<?php
session_start();
require_once '../config/database.php';

$page_title = 'Dashboard';
$page_subtitle = 'Tổng quan hệ thống';

// Get statistics
try {
    $db = getDB();
    
    // Total bookings today
    $stmt = $db->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
               SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in
        FROM bookings 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $today_bookings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total revenue today
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as revenue
        FROM bookings 
        WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'
    ");
    $stmt->execute();
    $today_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
    
    // Total customers
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE user_role = 'customer'");
    $stmt->execute();
    $total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Available rooms
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
    $stmt->execute();
    $available_rooms = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent bookings
    $stmt = $db->prepare("
        SELECT b.*, u.full_name, rt.type_name, r.room_number
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check-ins today
    $stmt = $db->prepare("
        SELECT b.*, u.full_name, rt.type_name, r.room_number
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.check_in_date = CURDATE() AND b.status IN ('confirmed', 'pending')
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
    $today_checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check-outs today
    $stmt = $db->prepare("
        SELECT b.*, u.full_name, rt.type_name, r.room_number
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.check_out_date = CURDATE() AND b.status = 'checked_in'
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
    $today_checkouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly revenue (last 6 months)
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as revenue,
            COUNT(*) as bookings
        FROM bookings
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        AND status != 'cancelled'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute();
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $today_bookings = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'checked_in' => 0];
    $today_revenue = 0;
    $total_customers = 0;
    $available_rooms = 0;
    $recent_bookings = [];
    $today_checkins = [];
    $today_checkouts = [];
    $monthly_stats = [];
}

include 'includes/admin-header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <!-- Today's Bookings -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đặt phòng hôm nay</p>
                <p class="text-3xl font-bold"><?php echo $today_bookings['total']; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">book_online</span>
            </div>
        </div>
        <div class="flex gap-4 text-sm">
            <div>
                <span class="text-yellow-600">Chờ: <?php echo $today_bookings['pending']; ?></span>
            </div>
            <div>
                <span class="text-green-600">Đã xác nhận: <?php echo $today_bookings['confirmed']; ?></span>
            </div>
        </div>
    </div>

    <!-- Today's Revenue -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Doanh thu hôm nay</p>
                <p class="text-3xl font-bold"><?php echo number_format($today_revenue, 0, ',', '.'); ?>đ</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-green-600 dark:text-green-400">payments</span>
            </div>
        </div>
        <p class="text-sm text-green-600">
            <span class="material-symbols-outlined text-xs align-middle">trending_up</span>
            Tăng trưởng tốt
        </p>
    </div>

    <!-- Total Customers -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng khách hàng</p>
                <p class="text-3xl font-bold"><?php echo number_format($total_customers); ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">people</span>
            </div>
        </div>
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
            Khách hàng đã đăng ký
        </p>
    </div>

    <!-- Available Rooms -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Phòng trống</p>
                <p class="text-3xl font-bold"><?php echo $available_rooms; ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-orange-600 dark:text-orange-400">hotel</span>
            </div>
        </div>
        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
            Sẵn sàng cho khách
        </p>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <a href="bookings.php?action=create" class="card hover:shadow-md transition-shadow">
        <div class="card-body flex items-center gap-4">
            <div class="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-accent">add</span>
            </div>
            <div>
                <h3 class="font-semibold">Tạo đặt phòng mới</h3>
                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Thêm booking cho khách</p>
            </div>
        </div>
    </a>

    <a href="bookings.php?status=pending" class="card hover:shadow-md transition-shadow">
        <div class="card-body flex items-center gap-4">
            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-yellow-600">pending</span>
            </div>
            <div>
                <h3 class="font-semibold">Đơn chờ xác nhận</h3>
                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php echo $today_bookings['pending']; ?> đơn đang chờ</p>
            </div>
        </div>
    </a>

    <a href="reports.php" class="card hover:shadow-md transition-shadow">
        <div class="card-body flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600">analytics</span>
            </div>
            <div>
                <h3 class="font-semibold">Xem báo cáo</h3>
                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Thống kê chi tiết</p>
            </div>
        </div>
    </a>
</div>

<!-- Check-ins and Check-outs Today -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Check-ins Today -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-green-600">login</span>
                Check-in hôm nay (<?php echo count($today_checkins); ?>)
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($today_checkins)): ?>
                <div class="text-center py-8 text-text-secondary-light dark:text-text-secondary-dark">
                    <span class="material-symbols-outlined text-4xl mb-2 block">event_available</span>
                    <p>Không có check-in nào hôm nay</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($today_checkins as $booking): ?>
                        <div class="flex items-center justify-between p-3 bg-background-light dark:bg-background-dark rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                    <?php echo htmlspecialchars($booking['type_name']); ?>
                                    <?php if ($booking['room_number']): ?>
                                        - Phòng <?php echo htmlspecialchars($booking['room_number']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary">
                                Xem
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Check-outs Today -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">logout</span>
                Check-out hôm nay (<?php echo count($today_checkouts); ?>)
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($today_checkouts)): ?>
                <div class="text-center py-8 text-text-secondary-light dark:text-text-secondary-dark">
                    <span class="material-symbols-outlined text-4xl mb-2 block">event_busy</span>
                    <p>Không có check-out nào hôm nay</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($today_checkouts as $booking): ?>
                        <div class="flex items-center justify-between p-3 bg-background-light dark:bg-background-dark rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                    <?php echo htmlspecialchars($booking['type_name']); ?>
                                    <?php if ($booking['room_number']): ?>
                                        - Phòng <?php echo htmlspecialchars($booking['room_number']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary">
                                Xem
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="card">
    <div class="card-header flex items-center justify-between">
        <h3 class="font-semibold">Đặt phòng gần đây</h3>
        <a href="bookings.php" class="text-sm text-accent hover:underline">Xem tất cả</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Loại phòng</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_bookings)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-8 text-text-secondary-light dark:text-text-secondary-dark">
                            Chưa có đặt phòng nào
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_bookings as $booking): ?>
                        <tr>
                            <td class="font-medium"><?php echo htmlspecialchars($booking['booking_code']); ?></td>
                            <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['type_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></td>
                            <td><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>đ</td>
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
                                $status_class = $status_classes[$booking['status']] ?? 'badge-secondary';
                                $status_label = $status_labels[$booking['status']] ?? $booking['status'];
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo $status_label; ?>
                                </span>
                            </td>
                            <td>
                                <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>" class="action-btn" title="Xem chi tiết">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
