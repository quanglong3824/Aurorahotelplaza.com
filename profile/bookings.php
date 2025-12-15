<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once '../config/database.php';
require_once '../models/Booking.php';
require_once '../helpers/booking-helper.php';
require_once '../helpers/language.php';
initLanguage();

// Get filter parameters
$filters = [
    'status' => $_GET['status'] ?? '',
    'payment_status' => $_GET['payment_status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => trim($_GET['search'] ?? '')
];
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

try {
    $db = getDB();
    $bookingModel = new Booking($db);
    
    // Get bookings with filters and pagination
    $result = $bookingModel->getUserBookings($_SESSION['user_id'], $filters, $page, $per_page);
    $bookings = $result['bookings'];
    $total_bookings = $result['total'];
    $total_pages = $result['total_pages'];
    
    // Get booking statistics
    $stats = $bookingModel->getUserStatistics($_SESSION['user_id']);
    
} catch (Exception $e) {
    error_log("Bookings page error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $error = "Có lỗi xảy ra khi tải lịch sử đặt phòng: " . $e->getMessage();
    $bookings = [];
    $stats = [
        'total_bookings' => 0,
        'pending_bookings' => 0,
        'completed_bookings' => 0,
        'cancelled_bookings' => 0,
        'total_spent' => 0
    ];
}

// Status labels and colors
$status_labels = [
    'pending' => ['label' => __('booking_status.pending'), 'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
    'confirmed' => ['label' => __('booking_status.confirmed'), 'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
    'checked_in' => ['label' => __('booking_status.checked_in'), 'color' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
    'checked_out' => ['label' => __('booking_status.checked_out'), 'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'],
    'cancelled' => ['label' => __('booking_status.cancelled'), 'color' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
    'no_show' => ['label' => __('booking_status.no_show'), 'color' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200']
];

$payment_labels = [
    'unpaid' => ['label' => __('payment_status.unpaid'), 'color' => 'bg-red-100 text-red-800'],
    'partial' => ['label' => __('payment_status.partial'), 'color' => 'bg-yellow-100 text-yellow-800'],
    'paid' => ['label' => __('payment_status.paid'), 'color' => 'bg-green-100 text-green-800'],
    'refunded' => ['label' => __('payment_status.refunded'), 'color' => 'bg-gray-100 text-gray-800']
];
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php _e('profile_bookings.title'); ?></title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
<link href="../assets/css/fonts.css" rel="stylesheet"/>
    
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
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
                    <?php _e('profile_bookings.back'); ?>
                </a>
            </div>
            <h1 class="text-3xl font-bold text-text-primary-light dark:text-text-primary-dark">
                <?php _e('profile_bookings.page_title'); ?>
            </h1>
            <p class="mt-2 text-text-secondary-light dark:text-text-secondary-dark">
                <?php _e('profile_bookings.page_subtitle'); ?>
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
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">hotel</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_bookings.total_bookings'); ?></p>
                        <p class="text-2xl font-bold"><?php echo $stats['total_bookings']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400">pending</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_bookings.pending'); ?></p>
                        <p class="text-2xl font-bold"><?php echo $stats['pending_bookings']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_bookings.completed'); ?></p>
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
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_bookings.cancelled'); ?></p>
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
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_bookings.total_spent'); ?></p>
                        <p class="text-xl font-bold text-accent"><?php echo number_format($stats['total_spent']); ?> VNĐ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold"><?php _e('profile_bookings.filters'); ?></h2>
                <a href="api/export-bookings.php?<?php echo http_build_query($filters); ?>" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <span class="material-symbols-outlined mr-2 text-sm">download</span>
                    <?php _e('profile_bookings.export_csv'); ?>
                </a>
            </div>
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <!-- Search -->
                    <div class="relative group">
                        <label class="block text-sm font-medium mb-2"><?php _e('profile_bookings.search'); ?></label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>"
                               placeholder="VD: 6C320B hoặc BK20251119..."
                               title="Tìm kiếm thông minh: Nhập 6 ký tự cuối hoặc mã đầy đủ"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                        
                        <!-- Tooltip -->
                        <div class="hidden group-hover:block absolute top-full left-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-3 z-50">
                            <p class="font-semibold mb-2 text-xs">🔍 Tìm kiếm thông minh:</p>
                            <ul class="text-xs space-y-1 text-gray-600 dark:text-gray-400">
                                <li>✅ <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">6C320B</span> - Chỉ 6 ký tự cuối</li>
                                <li>✅ <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">BK20251119</span> - Tất cả đơn trong ngày</li>
                                <li>✅ <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">BK202511196C320B</span> - Mã đầy đủ</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium mb-2"><?php _e('profile_bookings.booking_status'); ?></label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                            <option value=""><?php _e('profile_bookings.all'); ?></option>
                            <?php foreach ($status_labels as $status => $info): ?>
                            <option value="<?php echo $status; ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>>
                                <?php echo $info['label']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Payment Status Filter -->
                    <div>
                        <label class="block text-sm font-medium mb-2"><?php _e('profile_bookings.payment'); ?></label>
                        <select name="payment_status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                            <option value=""><?php _e('profile_bookings.all'); ?></option>
                            <?php foreach ($payment_labels as $status => $info): ?>
                            <option value="<?php echo $status; ?>" <?php echo $filters['payment_status'] === $status ? 'selected' : ''; ?>>
                                <?php echo $info['label']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium mb-2"><?php _e('profile_bookings.from_date'); ?></label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                    </div>
                    
                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium mb-2"><?php _e('profile_bookings.to_date'); ?></label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                    </div>
                    
                    <!-- Filter Button -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-6 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors">
                            <span class="material-symbols-outlined mr-1">search</span>
                            <?php _e('profile_bookings.filter'); ?>
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
                                        <?php if (!empty($booking['payment_status']) && isset($payment_labels[$booking['payment_status']])): ?>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $payment_labels[$booking['payment_status']]['color']; ?>">
                                            <?php echo $payment_labels[$booking['payment_status']]['label']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                        <div>
                                            <span class="font-medium"><?php _e('profile_bookings.booking_code'); ?>:</span>
                                            <span class="font-mono text-accent"><?php echo BookingHelper::formatBookingCode($booking['booking_code'], true); ?></span>
                                            <div class="text-xs mt-1">
                                                <?php _e('profile_bookings.short_code'); ?>: <span class="font-mono font-bold"><?php echo BookingHelper::getShortCode($booking['booking_code']); ?></span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="font-medium"><?php _e('profile_bookings.check_in'); ?>:</span>
                                            <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium"><?php _e('profile_bookings.check_out'); ?>:</span>
                                            <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium"><?php _e('profile_bookings.num_guests'); ?>:</span>
                                            <?php echo $booking['num_adults']; ?> <?php _e('profile_bookings.persons'); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium"><?php _e('profile_bookings.num_nights'); ?>:</span>
                                            <?php echo $booking['total_nights']; ?> <?php _e('profile_bookings.nights'); ?>
                                        </div>
                                        <?php if ($booking['room_number']): ?>
                                        <div>
                                            <span class="font-medium"><?php _e('profile_bookings.room'); ?>:</span>
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
                                    <?php _e('profile_bookings.booked_on'); ?> <?php echo date('d/m/Y', strtotime($booking['created_at'])); ?>
                                </p>
                            </div>
                            
                            <div class="flex gap-2">
                                <?php if ($booking['status'] === 'pending'): ?>
                                <a href="../booking/confirmation.php?booking_code=<?php echo urlencode($booking['booking_code']); ?>" 
                                   class="px-4 py-2 bg-gradient-to-r from-primary to-purple-600 text-white rounded-lg hover:opacity-90 transition-all text-sm inline-flex items-center">
                                    <span class="material-symbols-outlined mr-1 text-sm">check_circle</span>
                                    <?php _e('profile_bookings.confirm'); ?>
                                </a>
                                <?php endif; ?>
                                
                                <a href="booking-detail.php?code=<?php echo $booking['booking_code']; ?>" 
                                   class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors text-sm">
                                    <span class="material-symbols-outlined mr-1 text-sm">visibility</span>
                                    <?php _e('profile_bookings.details'); ?>
                                </a>
                                
                                <?php if ($booking['status'] === 'confirmed' && strtotime($booking['check_in_date']) > time()): ?>
                                <button onclick="cancelBooking(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['booking_code']; ?>')"
                                        class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors text-sm">
                                    <span class="material-symbols-outlined mr-1 text-sm">cancel</span>
                                    <?php _e('profile_bookings.cancel'); ?>
                                </button>
                                <?php endif; ?>
                                
                                <!-- QR Code Button -->
                                <?php if (in_array($booking['status'], ['confirmed', 'checked_in'])): ?>
                                <a href="api/download-qrcode.php?code=<?php echo $booking['booking_code']; ?>" 
                                   download="booking-<?php echo $booking['booking_code']; ?>-qrcode.png"
                                   class="px-4 py-2 border border-accent text-accent rounded-lg hover:bg-accent hover:text-white transition-colors text-sm inline-flex items-center">
                                    <span class="material-symbols-outlined mr-1 text-sm">qr_code</span>
                                    <?php _e('profile_bookings.download_qr'); ?>
                                </a>
                                <?php else: ?>
                                <button disabled class="px-4 py-2 border border-gray-300 text-gray-400 rounded-lg cursor-not-allowed text-sm opacity-50">
                                    <span class="material-symbols-outlined mr-1 text-sm">qr_code</span>
                                    <?php _e('profile_bookings.qr_code'); ?>
                                </button>
                                <?php endif; ?>
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
                        <?php _e('profile_bookings.showing'); ?> <?php echo (($page - 1) * $per_page) + 1; ?> - <?php echo min($page * $per_page, $total_bookings); ?> 
                        <?php _e('profile_bookings.of_total'); ?> <?php echo $total_bookings; ?> <?php _e('profile_bookings.bookings'); ?>
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
                    <?php _e('profile_bookings.no_bookings'); ?>
                </h3>
                <p class="text-text-secondary-light dark:text-text-secondary-dark mb-6">
                    <?php _e('profile_bookings.no_bookings_desc'); ?>
                </p>
                <a href="../booking/index.php" class="inline-flex items-center px-6 py-3 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors">
                    <span class="material-symbols-outlined mr-2">add</span>
                    <?php _e('profile_bookings.book_now'); ?>
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
function cancelBooking(bookingId, bookingCode) {
    const reason = prompt('Vui lòng nhập lý do hủy đặt phòng (không bắt buộc):');
    
    if (reason !== null) { // User didn't click Cancel
        if (confirm('Bạn có chắc chắn muốn hủy đặt phòng ' + bookingCode + '?\n\nLưu ý: Bạn chỉ có thể hủy đặt phòng trước 24 giờ check-in.')) {
            // Show loading
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> Đang xử lý...';
            
            // Send cancel request
            fetch('api/cancel-booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    booking_id: bookingId,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Đã hủy đặt phòng thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi hủy đặt phòng. Vui lòng thử lại.');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
        }
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