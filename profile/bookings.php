<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once '../config/database.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $db = getDB();
    
    // Build WHERE clause
    $where_conditions = ['b.user_id = ?'];
    $params = [$_SESSION['user_id']];
    
    if ($status_filter) {
        $where_conditions[] = 'b.status = ?';
        $params[] = $status_filter;
    }
    
    if ($date_from) {
        $where_conditions[] = 'b.check_in_date >= ?';
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where_conditions[] = 'b.check_in_date <= ?';
        $params[] = $date_to;
    }
    
    if ($search) {
        $where_conditions[] = '(b.booking_code LIKE ? OR b.guest_name LIKE ? OR rt.type_name LIKE ?)';
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM bookings b
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        $where_clause
    ";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_bookings = $stmt->fetch()['total'];
    $total_pages = ceil($total_bookings / $per_page);
    
    // Get bookings with pagination
    $sql = "
        SELECT b.*, rt.type_name, rt.category, r.room_number,
               p.status as payment_status, p.payment_method, p.paid_at
        FROM bookings b
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        $where_clause
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    // Get booking statistics
    $stats_sql = "
        SELECT 
            COUNT(*) as total_bookings,
            COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
            COUNT(CASE WHEN status = 'completed' OR status = 'checked_out' THEN 1 END) as completed_bookings,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
            SUM(CASE WHEN status IN ('confirmed', 'completed', 'checked_out') THEN total_amount ELSE 0 END) as total_spent
        FROM bookings 
        WHERE user_id = ?
    ";
    $stmt = $db->prepare($stats_sql);
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Bookings page error: " . $e->getMessage());
    $error = "Có lỗi xảy ra khi tải lịch sử đặt phòng: " . $e->getMessage();
    
    // Initialize empty data to prevent further errors
    $bookings = [];
    $total_bookings = 0;
    $total_pages = 0;
    $stats = [
        'total_bookings' => 0,
        'confirmed_bookings' => 0,
        'completed_bookings' => 0,
        'cancelled_bookings' => 0,
        'total_spent' => 0
    ];
}

// Status labels and colors
$status_labels = [
    'pending' => ['label' => 'Chờ xác nhận', 'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
    'confirmed' => ['label' => 'Đã xác nhận', 'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
    'checked_in' => ['label' => 'Đã nhận phòng', 'color' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
    'checked_out' => ['label' => 'Đã trả phòng', 'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'],
    'cancelled' => ['label' => 'Đã hủy', 'color' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
    'no_show' => ['label' => 'Không đến', 'color' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200']
];

$payment_labels = [
    'unpaid' => ['label' => 'Chưa thanh toán', 'color' => 'bg-red-100 text-red-800'],
    'partial' => ['label' => 'Thanh toán một phần', 'color' => 'bg-yellow-100 text-yellow-800'],
    'paid' => ['label' => 'Đã thanh toán', 'color' => 'bg-green-100 text-green-800'],
    'refunded' => ['label' => 'Đã hoàn tiền', 'color' => 'bg-gray-100 text-gray-800']
];
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Lịch sử đặt phòng - Aurora Hotel Plaza</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-24 pb-16">
    <div class="mx-auto max-w-7xl px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="index.php" class="inline-flex items-center gap-2 text-text-secondary-light dark:text-text-secondary-dark hover:text-accent transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Quay lại
                </a>
            </div>
            <h1 class="text-3xl font-bold text-text-primary-light dark:text-text-primary-dark">
                Lịch sử đặt phòng
            </h1>
            <p class="mt-2 text-text-secondary-light dark:text-text-secondary-dark">
                Xem và quản lý các đặt phòng của bạn
            </p>
        </div>

        <?php if (isset($error)): ?>
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
            <div class="flex">
                <span class="material-symbols-outlined text-red-400 mr-2">error</span>
                <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">hotel</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng đặt phòng</p>
                        <p class="text-2xl font-bold"><?php echo $stats['total_bookings']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Hoàn thành</p>
                        <p class="text-2xl font-bold"><?php echo $stats['completed_bookings']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                        <span class="material-symbols-outlined text-red-600 dark:text-red-400">cancel</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Đã hủy</p>
                        <p class="text-2xl font-bold"><?php echo $stats['cancelled_bookings']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-accent/20 rounded-lg">
                        <span class="material-symbols-outlined text-accent">payments</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổng chi tiêu</p>
                        <p class="text-2xl font-bold text-accent"><?php echo number_format($stats['total_spent']); ?> VNĐ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6 mb-8">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Tìm kiếm</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Mã đặt phòng, tên khách..."
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Trạng thái</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                            <option value="">Tất cả</option>
                            <?php foreach ($status_labels as $status => $info): ?>
                            <option value="<?php echo $status; ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>>
                                <?php echo $info['label']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Từ ngày</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                    </div>
                    
                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Đến ngày</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                    </div>
                    
                    <!-- Filter Button -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-6 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors">
                            <span class="material-symbols-outlined mr-1">search</span>
                            Lọc
                        </button>
                        <a href="bookings.php" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <span class="material-symbols-outlined">refresh</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bookings List -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm overflow-hidden">
            <?php if (!empty($bookings)): ?>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($bookings as $booking): ?>
                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Booking Info -->
                        <div class="flex-1">
                            <div class="flex items-start gap-4">
                                <div class="p-3 bg-accent/10 rounded-lg">
                                    <span class="material-symbols-outlined text-accent">
                                        <?php echo $booking['category'] === 'apartment' ? 'apartment' : 'hotel'; ?>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-bold"><?php echo htmlspecialchars($booking['type_name']); ?></h3>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $status_labels[$booking['status']]['color']; ?>">
                                            <?php echo $status_labels[$booking['status']]['label']; ?>
                                        </span>
                                        <?php if ($booking['payment_status']): ?>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $payment_labels[$booking['payment_status']]['color']; ?>">
                                            <?php echo $payment_labels[$booking['payment_status']]['label']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                        <div>
                                            <span class="font-medium">Mã đặt phòng:</span>
                                            <span class="font-mono text-accent"><?php echo $booking['booking_code']; ?></span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Nhận phòng:</span>
                                            <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium">Trả phòng:</span>
                                            <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium">Số khách:</span>
                                            <?php echo $booking['num_adults']; ?> người
                                        </div>
                                        <div>
                                            <span class="font-medium">Số đêm:</span>
                                            <?php echo $booking['total_nights']; ?> đêm
                                        </div>
                                        <?php if ($booking['room_number']): ?>
                                        <div>
                                            <span class="font-medium">Phòng:</span>
                                            <?php echo $booking['room_number']; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Price & Actions -->
                        <div class="flex flex-col lg:items-end gap-4">
                            <div class="text-right">
                                <p class="text-2xl font-bold text-accent"><?php echo number_format($booking['total_amount']); ?> VNĐ</p>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                    Đặt ngày <?php echo date('d/m/Y', strtotime($booking['created_at'])); ?>
                                </p>
                            </div>
                            
                            <div class="flex gap-2">
                                <a href="booking-detail.php?code=<?php echo $booking['booking_code']; ?>" 
                                   class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors text-sm">
                                    <span class="material-symbols-outlined mr-1 text-sm">visibility</span>
                                    Chi tiết
                                </a>
                                
                                <?php if ($booking['status'] === 'confirmed' && strtotime($booking['check_in_date']) > time()): ?>
                                <button onclick="cancelBooking('<?php echo $booking['booking_code']; ?>')"
                                        class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors text-sm">
                                    <span class="material-symbols-outlined mr-1 text-sm">cancel</span>
                                    Hủy
                                </button>
                                <?php endif; ?>
                                
                                <!-- QR Code Button (Inactive) -->
                                <button disabled class="px-4 py-2 border border-gray-300 text-gray-400 rounded-lg cursor-not-allowed text-sm opacity-50">
                                    <span class="material-symbols-outlined mr-1 text-sm">qr_code</span>
                                    QR Code
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                        Hiển thị <?php echo $offset + 1; ?> - <?php echo min($offset + $per_page, $total_bookings); ?> 
                        trong tổng số <?php echo $total_bookings; ?> đặt phòng
                    </div>
                    
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                           class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="px-3 py-2 border rounded-lg <?php echo $i === $page ? 'bg-accent text-white border-accent' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                           class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <!-- Empty State -->
            <div class="p-12 text-center">
                <span class="material-symbols-outlined text-6xl text-gray-400 mb-4 block">hotel_class</span>
                <h3 class="text-xl font-bold text-text-primary-light dark:text-text-primary-dark mb-2">
                    Chưa có đặt phòng nào
                </h3>
                <p class="text-text-secondary-light dark:text-text-secondary-dark mb-6">
                    Bạn chưa có lịch sử đặt phòng. Hãy đặt phòng đầu tiên của bạn!
                </p>
                <a href="../booking/index.php" class="inline-flex items-center px-6 py-3 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors">
                    <span class="material-symbols-outlined mr-2">add</span>
                    Đặt phòng ngay
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</div>

<script src="../assets/js/main.js"></script>
<script>
// Cancel booking function
function cancelBooking(bookingCode) {
    if (confirm('Bạn có chắc chắn muốn hủy đặt phòng này?')) {
        // TODO: Implement cancel booking functionality
        alert('Tính năng hủy đặt phòng sẽ được triển khai sau.');
    }
}

// Auto-submit form when date changes
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const statusSelect = document.querySelector('select[name="status"]');
    
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Auto-submit after a short delay
            setTimeout(() => {
                document.querySelector('form').submit();
            }, 500);
        });
    });
    
    statusSelect.addEventListener('change', function() {
        document.querySelector('form').submit();
    });
});
</script>
</body>
</html>