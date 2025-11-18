<?php
session_start();
require_once '../config/database.php';

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header('Location: customers.php');
    exit;
}

try {
    $db = getDB();
    
    // Get customer info
    $stmt = $db->prepare("
        SELECT u.*, ul.current_points, ul.lifetime_points, ul.tier_id,
               mt.tier_name, mt.color_code, mt.discount_percentage
        FROM users u
        LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
        LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
        WHERE u.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        header('Location: customers.php');
        exit;
    }
    
    // Get booking stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as total_spent,
            AVG(CASE WHEN status != 'cancelled' THEN total_amount END) as avg_booking_value
        FROM bookings
        WHERE user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent bookings
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name, r.room_number
        FROM bookings b
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.user_id = :user_id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([':user_id' => $user_id]);
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get reviews
    $stmt = $db->prepare("
        SELECT r.*, rt.type_name
        FROM reviews r
        LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE r.user_id = :user_id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([':user_id' => $user_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Customer detail error: " . $e->getMessage());
    header('Location: customers.php');
    exit;
}

$page_title = 'Chi tiết khách hàng';
$page_subtitle = htmlspecialchars($customer['full_name']);

include 'includes/admin-header.php';
?>

<div class="mb-6">
    <a href="customers.php" class="btn btn-secondary">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Quay lại
    </a>
</div>

<!-- Customer Header Card -->
<div class="card mb-6">
    <div class="card-body">
        <div class="flex items-start gap-6">
            <!-- Avatar -->
            <div class="relative">
                <?php if ($customer['avatar']): ?>
                    <img src="<?php echo htmlspecialchars($customer['avatar']); ?>" 
                         alt="Avatar" class="w-24 h-24 rounded-2xl object-cover shadow-lg">
                <?php else: ?>
                    <div class="w-24 h-24 bg-gradient-to-br from-[#d4af37] to-[#b8941f] rounded-2xl flex items-center justify-center shadow-lg">
                        <span class="material-symbols-outlined text-[#1a1a1a] text-5xl font-bold">person</span>
                    </div>
                <?php endif; ?>
                <?php if ($customer['tier_name']): ?>
                    <div class="absolute -bottom-2 -right-2 px-3 py-1 rounded-lg text-xs font-bold shadow-lg" 
                         style="background-color: <?php echo $customer['color_code']; ?>; color: white;">
                        <?php echo htmlspecialchars($customer['tier_name']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Info -->
            <div class="flex-1">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($customer['full_name']); ?></h2>
                        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">email</span>
                                <?php echo htmlspecialchars($customer['email']); ?>
                            </span>
                            <?php if ($customer['phone']): ?>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">phone</span>
                                    <?php echo htmlspecialchars($customer['phone']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge badge-<?php echo $customer['status'] === 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo $customer['status'] === 'active' ? 'Hoạt động' : 'Không hoạt động'; ?>
                    </span>
                </div>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="p-4 bg-gradient-to-br from-[#d4af37]/10 to-[#b8941f]/10 rounded-xl border-2 border-[#d4af37]/30">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Điểm hiện tại</p>
                        <p class="text-2xl font-bold" style="color: #d4af37;"><?php echo number_format($customer['current_points'] ?? 0); ?></p>
                    </div>
                    <div class="p-4 bg-blue-50 rounded-xl border-2 border-blue-200">
                        <p class="text-sm text-gray-600 mb-1">Tổng đơn</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $booking_stats['total_bookings']; ?></p>
                    </div>
                    <div class="p-4 bg-green-50 rounded-xl border-2 border-green-200">
                        <p class="text-sm text-gray-600 mb-1">Tổng chi tiêu</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($booking_stats['total_spent'], 0, ',', '.'); ?>đ</p>
                    </div>
                    <div class="p-4 bg-purple-50 rounded-xl border-2 border-purple-200">
                        <p class="text-sm text-gray-600 mb-1">TB/Đơn</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo number_format($booking_stats['avg_booking_value'], 0, ',', '.'); ?>đ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Bookings -->
    <div class="lg:col-span-2 card">
        <div class="card-header">
            <h3 class="font-bold text-lg">Lịch sử đặt phòng</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recent_bookings)): ?>
                <div class="text-center py-8">
                    <span class="material-symbols-outlined text-6xl text-gray-300">receipt_long</span>
                    <p class="text-gray-500 mt-2">Chưa có đơn đặt phòng nào</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recent_bookings as $booking): ?>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-slate-800 rounded-xl hover:shadow-md transition-all">
                            <div class="w-12 h-12 bg-gradient-to-br from-[#d4af37] to-[#b8941f] rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#1a1a1a] font-bold">hotel</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold"><?php echo htmlspecialchars($booking['room_number'] ?? $booking['type_name']); ?></p>
                                <p class="text-sm text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?> - 
                                    <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <?php
                                $status_config = [
                                    'pending' => ['class' => 'badge-warning', 'label' => 'Chờ duyệt'],
                                    'confirmed' => ['class' => 'badge-info', 'label' => 'Đã xác nhận'],
                                    'checked_in' => ['class' => 'badge-success', 'label' => 'Đã nhận phòng'],
                                    'checked_out' => ['class' => 'badge-secondary', 'label' => 'Đã trả phòng'],
                                    'cancelled' => ['class' => 'badge-danger', 'label' => 'Đã hủy']
                                ];
                                $config = $status_config[$booking['status']] ?? ['class' => 'badge-secondary', 'label' => $booking['status']];
                                ?>
                                <span class="badge <?php echo $config['class']; ?> mb-2"><?php echo $config['label']; ?></span>
                                <p class="font-bold" style="color: #d4af37;"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>đ</p>
                            </div>
                            <a href="booking-detail.php?id=<?php echo $booking['booking_id']; ?>" 
                               class="btn btn-sm btn-secondary">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Loyalty Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-lg">Thông tin thành viên</h3>
            </div>
            <div class="card-body space-y-4">
                <div class="p-4 bg-gradient-to-br from-[#d4af37]/10 to-[#b8941f]/10 rounded-xl border-2 border-[#d4af37]/30">
                    <p class="text-sm text-gray-600 mb-2">Điểm tích lũy</p>
                    <p class="text-3xl font-bold mb-1" style="color: #d4af37;"><?php echo number_format($customer['current_points'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">Tổng tích lũy: <?php echo number_format($customer['lifetime_points'] ?? 0); ?> điểm</p>
                </div>
                
                <?php if ($customer['tier_name']): ?>
                    <div class="p-4 rounded-xl" style="background-color: <?php echo $customer['color_code']; ?>20;">
                        <p class="text-sm mb-2">Hạng thành viên</p>
                        <p class="text-xl font-bold mb-1" style="color: <?php echo $customer['color_code']; ?>;">
                            <?php echo htmlspecialchars($customer['tier_name']); ?>
                        </p>
                        <p class="text-xs">Giảm giá: <?php echo $customer['discount_percentage']; ?>%</p>
                    </div>
                <?php endif; ?>
                
                <div class="text-sm text-gray-600 space-y-2">
                    <p><strong>Ngày đăng ký:</strong> <?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></p>
                    <?php if ($customer['last_login']): ?>
                        <p><strong>Đăng nhập cuối:</strong> <?php echo date('d/m/Y H:i', strtotime($customer['last_login'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Reviews -->
        <?php if (!empty($reviews)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-lg">Đánh giá gần đây</h3>
                </div>
                <div class="card-body space-y-3">
                    <?php foreach ($reviews as $review): ?>
                        <div class="p-3 bg-gray-50 dark:bg-slate-800 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="material-symbols-outlined text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-500' : 'text-gray-300'; ?>">
                                        star
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <?php if ($review['comment']): ?>
                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars(mb_substr($review['comment'], 0, 100)); ?>...</p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mt-2"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
