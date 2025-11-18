<?php
session_start();
require_once '../config/database.php';

$page_title = 'Dashboard';
$page_subtitle = 'Tổng quan hệ thống khách sạn';

try {
    $db = getDB();
    $today = date('Y-m-d');
    
    // Real-time stats
    $stmt = $db->query("
        SELECT 
            (SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()) as bookings_today,
            (SELECT SUM(total_amount) FROM bookings WHERE DATE(created_at) = CURDATE() AND status != 'cancelled') as revenue_today,
            (SELECT COUNT(*) FROM bookings WHERE status = 'pending') as pending_bookings,
            (SELECT COUNT(*) FROM rooms WHERE status = 'available') as available_rooms,
            (SELECT COUNT(*) FROM rooms) as total_rooms,
            (SELECT COUNT(*) FROM bookings WHERE check_in_date = CURDATE() AND status IN ('confirmed', 'checked_in')) as checkins_today,
            (SELECT COUNT(*) FROM bookings WHERE check_out_date = CURDATE() AND status = 'checked_in') as checkouts_today,
            (SELECT COUNT(*) FROM users WHERE user_role = 'customer' AND DATE(created_at) = CURDATE()) as new_customers_today
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Revenue comparison
    $stmt = $db->query("
        SELECT 
            (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'cancelled') as revenue_this_month,
            (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND status != 'cancelled') as revenue_last_month
    ");
    $revenue_comparison = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $revenue_growth = 0;
    if ($revenue_comparison['revenue_last_month'] > 0) {
        $revenue_growth = (($revenue_comparison['revenue_this_month'] - $revenue_comparison['revenue_last_month']) / $revenue_comparison['revenue_last_month']) * 100;
    }
    
    // Occupancy rate
    $occupancy_rate = ($stats['total_rooms'] > 0) ? 
        (($stats['total_rooms'] - $stats['available_rooms']) / $stats['total_rooms']) * 100 : 0;
    
    // Recent activities
    $stmt = $db->query("
        SELECT al.*, u.full_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top room types
    $stmt = $db->query("
        SELECT rt.type_name, COUNT(b.booking_id) as bookings, SUM(b.total_amount) as revenue
        FROM room_types rt
        LEFT JOIN bookings b ON rt.room_type_id = b.room_type_id AND MONTH(b.created_at) = MONTH(CURDATE())
        WHERE b.status != 'cancelled'
        GROUP BY rt.room_type_id
        ORDER BY revenue DESC
        LIMIT 5
    ");
    $top_room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Upcoming check-ins
    $stmt = $db->query("
        SELECT b.*, u.full_name, rt.type_name, r.room_number
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.check_in_date = CURDATE() AND b.status = 'confirmed'
        ORDER BY b.check_in_date ASC
        LIMIT 5
    ");
    $upcoming_checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $stats = ['bookings_today' => 0, 'revenue_today' => 0, 'pending_bookings' => 0, 'available_rooms' => 0, 'total_rooms' => 0, 'checkins_today' => 0, 'checkouts_today' => 0, 'new_customers_today' => 0];
    $revenue_growth = 0;
    $occupancy_rate = 0;
    $recent_activities = [];
    $top_room_types = [];
    $upcoming_checkins = [];
}

include 'includes/admin-header.php';
?>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Revenue Today -->
    <div class="stat-card bg-gradient-to-br from-[#d4af37]/10 to-[#b8941f]/10 border-2 border-[#d4af37]/30 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-[#d4af37]/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-[#d4af37] to-[#b8941f] rounded-xl flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-[#1a1a1a] text-xl font-bold">payments</span>
                </div>
                <span class="text-xs font-bold px-3 py-1 bg-green-100 text-green-700 rounded-full">+<?php echo number_format($revenue_growth, 1); ?>%</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-1">Doanh thu hôm nay</p>
            <p class="text-3xl font-bold mb-2" style="color: #d4af37;"><?php echo number_format($stats['revenue_today'], 0, ',', '.'); ?>đ</p>
            <p class="text-xs text-gray-500">Từ <?php echo $stats['bookings_today']; ?> đơn đặt phòng</p>
        </div>
    </div>
    
    <!-- Occupancy Rate -->
    <div class="stat-card bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-200/30 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-white text-xl">hotel</span>
                </div>
                <span class="text-xs font-bold px-3 py-1 bg-blue-100 text-blue-700 rounded-full"><?php echo number_format($occupancy_rate, 0); ?>%</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-1">Tỷ lệ lấp đầy</p>
            <p class="text-3xl font-bold text-blue-600 mb-2"><?php echo $stats['total_rooms'] - $stats['available_rooms']; ?>/<?php echo $stats['total_rooms']; ?></p>
            <p class="text-xs text-gray-500"><?php echo $stats['available_rooms']; ?> phòng còn trống</p>
        </div>
    </div>
    
    <!-- Check-ins Today -->
    <div class="stat-card bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-green-200/30 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-white text-xl">login</span>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-1">Check-in hôm nay</p>
            <p class="text-3xl font-bold text-green-600 mb-2"><?php echo $stats['checkins_today']; ?></p>
            <p class="text-xs text-gray-500"><?php echo $stats['checkouts_today']; ?> check-out</p>
        </div>
    </div>
    
    <!-- Pending Bookings -->
    <div class="stat-card bg-gradient-to-br from-orange-50 to-orange-100 border-2 border-orange-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-orange-200/30 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-white text-xl">pending</span>
                </div>
                <?php if ($stats['pending_bookings'] > 0): ?>
                    <span class="animate-pulse text-xs font-bold px-3 py-1 bg-red-100 text-red-700 rounded-full">Cần xử lý</span>
                <?php endif; ?>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-1">Đơn chờ duyệt</p>
            <p class="text-3xl font-bold text-orange-600 mb-2"><?php echo $stats['pending_bookings']; ?></p>
            <a href="bookings.php?status=pending" class="text-xs text-orange-600 hover:text-orange-700 font-medium">Xem chi tiết →</a>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Top Room Types -->
    <div class="lg:col-span-2 card">
        <div class="card-header flex items-center justify-between">
            <div>
                <h3 class="font-bold text-lg">Top loại phòng tháng này</h3>
                <p class="text-sm text-gray-500 mt-1">Doanh thu và số lượng đặt</p>
            </div>
            <span class="material-symbols-outlined text-[#d4af37]">trending_up</span>
        </div>
        <div class="card-body">
            <?php if (empty($top_room_types)): ?>
                <p class="text-center text-gray-500 py-8">Chưa có dữ liệu</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($top_room_types as $index => $room): ?>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-gradient-to-br from-[#d4af37] to-[#b8941f] rounded-lg flex items-center justify-center font-bold text-[#1a1a1a]">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold"><?php echo htmlspecialchars($room['type_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo $room['bookings']; ?> đơn đặt</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-lg" style="color: #d4af37;"><?php echo number_format($room['revenue'], 0, ',', '.'); ?>đ</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-bold text-lg">Thao tác nhanh</h3>
        </div>
        <div class="card-body space-y-3">
            <a href="bookings.php" class="flex items-center gap-3 p-4 bg-gradient-to-r from-[#d4af37]/10 to-[#b8941f]/10 rounded-xl hover:shadow-md transition-all group">
                <div class="w-10 h-10 bg-gradient-to-br from-[#d4af37] to-[#b8941f] rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-[#1a1a1a]">add</span>
                </div>
                <div class="flex-1">
                    <p class="font-semibold group-hover:text-[#d4af37] transition-colors">Tạo đặt phòng mới</p>
                    <p class="text-xs text-gray-500">Thêm booking mới</p>
                </div>
            </a>
            
            <a href="rooms.php" class="flex items-center gap-3 p-4 bg-blue-50 rounded-xl hover:shadow-md transition-all group">
                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white">hotel</span>
                </div>
                <div class="flex-1">
                    <p class="font-semibold group-hover:text-blue-600 transition-colors">Quản lý phòng</p>
                    <p class="text-xs text-gray-500">Cập nhật trạng thái</p>
                </div>
            </a>
            
            <a href="customers.php" class="flex items-center gap-3 p-4 bg-green-50 rounded-xl hover:shadow-md transition-all group">
                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white">people</span>
                </div>
                <div class="flex-1">
                    <p class="font-semibold group-hover:text-green-600 transition-colors">Khách hàng</p>
                    <p class="text-xs text-gray-500">Xem danh sách</p>
                </div>
            </a>
            
            <a href="reports.php" class="flex items-center gap-3 p-4 bg-purple-50 rounded-xl hover:shadow-md transition-all group">
                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white">analytics</span>
                </div>
                <div class="flex-1">
                    <p class="font-semibold group-hover:text-purple-600 transition-colors">Báo cáo</p>
                    <p class="text-xs text-gray-500">Xem thống kê</p>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Bottom Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Upcoming Check-ins -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold text-lg">Check-in hôm nay</h3>
            <a href="bookings.php" class="text-sm text-[#d4af37] hover:text-[#b8941f] font-medium">Xem tất cả →</a>
        </div>
        <div class="card-body">
            <?php if (empty($upcoming_checkins)): ?>
                <div class="text-center py-8">
                    <span class="material-symbols-outlined text-6xl text-gray-300">event_available</span>
                    <p class="text-gray-500 mt-2">Không có check-in nào hôm nay</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($upcoming_checkins as $booking): ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg hover:shadow-md transition-all">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-sm">person</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold"><?php echo htmlspecialchars($booking['full_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['room_number'] ?? $booking['type_name']); ?></p>
                            </div>
                            <a href="booking-detail.php?id=<?php echo $booking['booking_id']; ?>" 
                               class="btn btn-sm btn-primary">
                                Chi tiết
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold text-lg">Hoạt động gần đây</h3>
            <a href="logs.php" class="text-sm text-[#d4af37] hover:text-[#b8941f] font-medium">Xem tất cả →</a>
        </div>
        <div class="card-body">
            <?php if (empty($recent_activities)): ?>
                <p class="text-center text-gray-500 py-8">Chưa có hoạt động nào</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                        <div class="flex items-start gap-3 text-sm">
                            <div class="w-8 h-8 bg-gray-200 dark:bg-slate-700 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xs">history</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium"><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></p>
                                <p class="text-gray-500"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?php echo date('H:i - d/m/Y', strtotime($activity['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
