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
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('profile_bookings.title'); ?></title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <link href="../assets/css/fonts.css" rel="stylesheet" />

    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="../assets/css/pages-glass.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
</head>

<body class="bg-slate-900 font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">

        <?php include '../includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Glass Page Wrapper -->
            <div class="glass-page-wrapper"
                style="background-image: url('../assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg');">

                <div class="w-full pt-[180px] pb-16 px-4">
                    <div class="mx-auto max-w-7xl">
                        <!-- Page Header -->
                        <div class="mb-8 pl-4 border-l-4 border-accent">
                            <div class="flex items-center gap-4 mb-2">
                                <a href="index.php"
                                    class="inline-flex items-center gap-2 text-white/70 hover:text-accent transition-colors text-sm">
                                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                                    <?php _e('profile_bookings.back'); ?>
                                </a>
                            </div>
                            <h1 class="text-3xl font-bold text-white uppercase tracking-wider">
                                <?php _e('profile_bookings.page_title'); ?>
                            </h1>
                            <p class="mt-1 text-white/60">
                                <?php _e('profile_bookings.page_subtitle'); ?>
                            </p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="mb-6 rounded-xl bg-red-500/10 border border-red-500/20 p-4 backdrop-blur-sm">
                                <div class="flex items-center">
                                    <span class="material-symbols-outlined text-red-400 mr-2">error</span>
                                    <p class="text-red-200"><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                            <div class="glass-card p-5 hover:-translate-y-1 transition-all">
                                <div class="flex items-center">
                                    <div class="p-3 bg-blue-500/20 rounded-lg">
                                        <span class="material-symbols-outlined text-blue-400">hotel</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs text-white/60 uppercase tracking-wider">
                                            <?php _e('profile_bookings.total_bookings'); ?>
                                        </p>
                                        <p class="text-2xl font-bold text-white"><?php echo $stats['total_bookings']; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card p-5 hover:-translate-y-1 transition-all">
                                <div class="flex items-center">
                                    <div class="p-3 bg-yellow-500/20 rounded-lg">
                                        <span class="material-symbols-outlined text-yellow-400">pending</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs text-white/60 uppercase tracking-wider">
                                            <?php _e('profile_bookings.pending'); ?>
                                        </p>
                                        <p class="text-2xl font-bold text-white">
                                            <?php echo $stats['pending_bookings']; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card p-5 hover:-translate-y-1 transition-all">
                                <div class="flex items-center">
                                    <div class="p-3 bg-green-500/20 rounded-lg">
                                        <span class="material-symbols-outlined text-green-400">check_circle</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs text-white/60 uppercase tracking-wider">
                                            <?php _e('profile_bookings.completed'); ?>
                                        </p>
                                        <p class="text-2xl font-bold text-white">
                                            <?php echo $stats['completed_bookings']; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card p-5 hover:-translate-y-1 transition-all">
                                <div class="flex items-center">
                                    <div class="p-3 bg-red-500/20 rounded-lg">
                                        <span class="material-symbols-outlined text-red-400">cancel</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs text-white/60 uppercase tracking-wider">
                                            <?php _e('profile_bookings.cancelled'); ?>
                                        </p>
                                        <p class="text-2xl font-bold text-white">
                                            <?php echo $stats['cancelled_bookings']; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card p-5 hover:-translate-y-1 transition-all">
                                <div class="flex items-center">
                                    <div class="p-3 bg-accent/20 rounded-lg">
                                        <span class="material-symbols-outlined text-accent">payments</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs text-white/60 uppercase tracking-wider">
                                            <?php _e('profile_bookings.total_spent'); ?>
                                        </p>
                                        <p class="text-lg font-bold text-accent">
                                            <?php echo number_format($stats['total_spent']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="glass-card p-6 mb-8">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                    <span class="material-symbols-outlined text-accent">filter_list</span>
                                    <?php _e('profile_bookings.filters'); ?>
                                </h2>
                                <a href="api/export-bookings.php?<?php echo http_build_query($filters); ?>"
                                    class="inline-flex items-center px-4 py-2 bg-green-600/80 hover:bg-green-600 text-white rounded-lg transition-colors border border-green-500/30 backdrop-blur-sm text-sm">
                                    <span class="material-symbols-outlined mr-2 text-sm">download</span>
                                    <?php _e('profile_bookings.export_csv'); ?>
                                </a>
                            </div>
                            <form method="GET" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                                    <!-- Search -->
                                    <div class="relative group md:col-span-2">
                                        <label
                                            class="block text-xs font-medium mb-2 text-white/70 uppercase tracking-wider"><?php _e('profile_bookings.search'); ?></label>
                                        <input type="text" name="search"
                                            value="<?php echo htmlspecialchars($filters['search']); ?>"
                                            placeholder="VD: 6C320B hoặc BK20251119..."
                                            title="Tìm kiếm thông minh: Nhập 6 ký tự cuối hoặc mã đầy đủ"
                                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition-all">

                                        <!-- Tooltip -->
                                        <div
                                            class="hidden group-hover:block absolute top-full left-0 mt-2 w-72 bg-slate-800 rounded-lg shadow-xl border border-white/10 p-4 z-50">
                                            <p class="font-semibold mb-2 text-xs text-accent">🔍 Tìm kiếm thông minh:
                                            </p>
                                            <ul class="text-xs space-y-2 text-white/70">
                                                <li>✅ <span class="font-mono bg-white/10 px-1 rounded">6C320B</span> - 6
                                                    ký tự cuối</li>
                                                <li>✅ <span class="font-mono bg-white/10 px-1 rounded">BK20251119</span>
                                                    -
                                                    Tất cả đơn trong ngày</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Status Filter -->
                                    <div>
                                        <label
                                            class="block text-xs font-medium mb-2 text-white/70 uppercase tracking-wider"><?php _e('profile_bookings.booking_status'); ?></label>
                                        <select name="status"
                                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition-all [&>option]:bg-slate-800 [&>option]:text-white">
                                            <option value=""><?php _e('profile_bookings.all'); ?></option>
                                            <?php foreach ($status_labels as $status => $info): ?>
                                                <option value="<?php echo $status; ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>>
                                                    <?php echo strip_tags($info['label']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Payment Status Filter -->
                                    <div>
                                        <label
                                            class="block text-xs font-medium mb-2 text-white/70 uppercase tracking-wider"><?php _e('profile_bookings.payment'); ?></label>
                                        <select name="payment_status"
                                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition-all [&>option]:bg-slate-800 [&>option]:text-white">
                                            <option value=""><?php _e('profile_bookings.all'); ?></option>
                                            <?php foreach ($payment_labels as $status => $info): ?>
                                                <option value="<?php echo $status; ?>" <?php echo $filters['payment_status'] === $status ? 'selected' : ''; ?>>
                                                    <?php echo strip_tags($info['label']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Date From -->
                                    <div>
                                        <label
                                            class="block text-xs font-medium mb-2 text-white/70 uppercase tracking-wider"><?php _e('profile_bookings.from_date'); ?></label>
                                        <input type="date" name="date_from"
                                            value="<?php echo htmlspecialchars($filters['date_from']); ?>"
                                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition-all [color-scheme:dark]">
                                    </div>

                                    <!-- Date To -->
                                    <div>
                                        <label
                                            class="block text-xs font-medium mb-2 text-white/70 uppercase tracking-wider"><?php _e('profile_bookings.to_date'); ?></label>
                                        <input type="date" name="date_to"
                                            value="<?php echo htmlspecialchars($filters['date_to']); ?>"
                                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition-all [color-scheme:dark]">
                                    </div>

                                    <!-- Filter Button -->
                                    <div class="flex items-end gap-2 md:col-span-full justify-end mt-2">
                                        <button type="submit"
                                            class="px-6 py-2 bg-accent hover:bg-accent-hover text-white rounded-lg transition-colors font-medium text-sm flex items-center">
                                            <span class="material-symbols-outlined mr-1 text-sm">search</span>
                                            <?php _e('profile_bookings.filter'); ?>
                                        </button>
                                        <a href="bookings.php"
                                            class="px-4 py-2 border border-white/20 text-white rounded-lg hover:bg-white/10 transition-colors">
                                            <span class="material-symbols-outlined text-sm">refresh</span>
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Bookings List -->
                        <div class="glass-card overflow-hidden min-h-[400px]">
                            <?php if (!empty($bookings)): ?>
                                <div class="divide-y divide-white/10">
                                    <?php foreach ($bookings as $booking): ?>
                                        <div class="p-6 hover:bg-white/5 transition-colors group">
                                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                                                <!-- Booking Info -->
                                                <div class="flex-1">
                                                    <div class="flex items-start gap-4">
                                                        <div class="p-4 bg-white/5 rounded-xl border border-white/10">
                                                            <span class="material-symbols-outlined text-accent text-3xl">
                                                                <?php echo $booking['category'] === 'apartment' ? 'apartment' : 'hotel'; ?>
                                                            </span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <div class="flex flex-wrap items-center gap-3 mb-3">
                                                                <h3 class="text-xl font-bold text-white">
                                                                    <?php echo htmlspecialchars($booking['type_name']); ?>
                                                                </h3>
                                                                <span
                                                                    class="px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                                                                    style="background: rgba(var(--status-<?php echo $booking['status']; ?>-color), 0.2); color: var(--status-<?php echo $booking['status']; ?>-text);">
                                                                    <?php echo $status_labels[$booking['status']]['label']; ?>
                                                                </span>

                                                                <?php
                                                                // Custom status color mapping if inline styles fail
                                                                $statusColor = 'bg-gray-500/20 text-gray-300';
                                                                if ($booking['status'] == 'confirmed')
                                                                    $statusColor = 'bg-blue-500/20 text-blue-300';
                                                                if ($booking['status'] == 'pending')
                                                                    $statusColor = 'bg-yellow-500/20 text-yellow-300';
                                                                if ($booking['status'] == 'checked_in')
                                                                    $statusColor = 'bg-green-500/20 text-green-300';
                                                                if ($booking['status'] == 'cancelled')
                                                                    $statusColor = 'bg-red-500/20 text-red-300';
                                                                ?>

                                                                <?php if (!empty($booking['payment_status'])): ?>
                                                                    <?php
                                                                    $payColor = 'bg-gray-500/20 text-gray-300';
                                                                    if ($booking['payment_status'] == 'paid')
                                                                        $payColor = 'bg-green-500/20 text-green-300';
                                                                    if ($booking['payment_status'] == 'unpaid')
                                                                        $payColor = 'bg-red-500/20 text-red-300';
                                                                    ?>
                                                                    <span
                                                                        class="px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wider <?php echo $payColor; ?>">
                                                                        <?php echo $payment_labels[$booking['payment_status']]['label']; ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>

                                                            <div
                                                                class="grid grid-cols-1 md:grid-cols-3 gap-y-2 gap-x-8 text-sm text-white/60">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="material-symbols-outlined text-sm">confirmation_number</span>
                                                                    <span
                                                                        class="font-mono text-accent"><?php echo BookingHelper::formatBookingCode($booking['booking_code'], true); ?></span>
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="material-symbols-outlined text-sm">calendar_month</span>
                                                                    <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>
                                                                    -
                                                                    <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <span class="material-symbols-outlined text-sm">group</span>
                                                                    <?php echo $booking['num_adults']; ?>
                                                                    <?php _e('profile_bookings.persons'); ?>
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <span class="material-symbols-outlined text-sm">bed</span>
                                                                    <?php _e('profile_bookings.num_nights'); ?>:
                                                                    <?php echo $booking['total_nights']; ?>
                                                                </div>
                                                                <?php if ($booking['room_number']): ?>
                                                                    <div class="flex items-center gap-2">
                                                                        <span
                                                                            class="material-symbols-outlined text-sm">door_front</span>
                                                                        <?php _e('profile_bookings.room'); ?>:
                                                                        <?php echo $booking['room_number']; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Price & Actions -->
                                                <div class="flex flex-col lg:items-end gap-4 min-w-[200px]">
                                                    <div class="text-left lg:text-right">
                                                        <p class="text-2xl font-bold text-accent">
                                                            <?php echo number_format($booking['total_amount']); ?> VNĐ
                                                        </p>
                                                        <p class="text-xs text-white/40 italic">
                                                            <?php _e('profile_bookings.booked_on'); ?>
                                                            <?php echo date('d/m/Y', strtotime($booking['created_at'])); ?>
                                                        </p>
                                                    </div>

                                                    <div class="flex flex-wrap gap-2 lg:justify-end">
                                                        <?php if ($booking['status'] === 'pending'): ?>
                                                            <a href="../booking/confirmation.php?booking_code=<?php echo urlencode($booking['booking_code']); ?>"
                                                                class="px-4 py-2 bg-gradient-to-r from-accent to-yellow-600 text-white rounded-lg hover:opacity-90 transition-all text-xs font-bold uppercase tracking-wider inline-flex items-center shadow-lg shadow-accent/20">
                                                                <span
                                                                    class="material-symbols-outlined mr-1 text-sm">check_circle</span>
                                                                <?php _e('profile_bookings.confirm'); ?>
                                                            </a>
                                                        <?php endif; ?>

                                                        <a href="booking-detail.php?code=<?php echo $booking['booking_code']; ?>"
                                                            class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors text-xs font-bold uppercase tracking-wider inline-flex items-center border border-white/10">
                                                            <span
                                                                class="material-symbols-outlined mr-1 text-sm">visibility</span>
                                                            <?php _e('profile_bookings.details'); ?>
                                                        </a>

                                                        <?php if ($booking['status'] === 'confirmed' && strtotime($booking['check_in_date']) > time()): ?>
                                                            <button
                                                                onclick="cancelBooking(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['booking_code']; ?>')"
                                                                class="px-4 py-2 border border-red-500/50 text-red-300 rounded-lg hover:bg-red-500/10 transition-colors text-xs font-bold uppercase tracking-wider inline-flex items-center">
                                                                <span class="material-symbols-outlined mr-1 text-sm">cancel</span>
                                                                <?php _e('profile_bookings.cancel'); ?>
                                                            </button>
                                                        <?php endif; ?>

                                                        <!-- QR Code Button -->
                                                        <?php if (in_array($booking['status'], ['confirmed', 'checked_in'])): ?>
                                                            <a href="api/download-qrcode.php?code=<?php echo $booking['booking_code']; ?>"
                                                                download="booking-<?php echo $booking['booking_code']; ?>-qrcode.png"
                                                                class="px-4 py-2 border border-accent/50 text-accent rounded-lg hover:bg-accent/10 transition-colors text-xs font-bold uppercase tracking-wider inline-flex items-center">
                                                                <span class="material-symbols-outlined mr-1 text-sm">qr_code</span>
                                                                <?php _e('profile_bookings.download_qr'); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <div class="px-6 py-4 border-t border-white/10 bg-white/5">
                                        <div class="flex items-center justify-between">
                                            <div class="text-sm text-white/50">
                                                <?php _e('profile_bookings.showing'); ?>
                                                <?php echo (($page - 1) * $per_page) + 1; ?> -
                                                <?php echo min($page * $per_page, $total_bookings); ?>
                                                <?php _e('profile_bookings.of_total'); ?>         <?php echo $total_bookings; ?>
                                                <?php _e('profile_bookings.bookings'); ?>
                                            </div>

                                            <div class="flex gap-2">
                                                <?php if ($page > 1): ?>
                                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                                                        class="w-10 h-10 flex items-center justify-center border border-white/10 rounded-lg hover:bg-white/10 text-white transition-colors">
                                                        <span class="material-symbols-outlined">chevron_left</span>
                                                    </a>
                                                <?php endif; ?>

                                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                                        class="w-10 h-10 flex items-center justify-center border rounded-lg <?php echo $i === $page ? 'bg-accent text-white border-accent' : 'border-white/10 text-white/70 hover:bg-white/10 hover:text-white'; ?> transition-colors font-medium">
                                                        <?php echo $i; ?>
                                                    </a>
                                                <?php endfor; ?>

                                                <?php if ($page < $total_pages): ?>
                                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                                                        class="w-10 h-10 flex items-center justify-center border border-white/10 rounded-lg hover:bg-white/10 text-white transition-colors">
                                                        <span class="material-symbols-outlined">chevron_right</span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <!-- Empty State -->
                                <div class="p-16 text-center">
                                    <div
                                        class="w-24 h-24 mx-auto bg-white/5 rounded-full flex items-center justify-center mb-6">
                                        <span class="material-symbols-outlined text-6xl text-white/20">hotel_class</span>
                                    </div>
                                    <h3 class="text-2xl font-bold text-white mb-2">
                                        <?php _e('profile_bookings.no_bookings'); ?>
                                    </h3>
                                    <p class="text-white/50 mb-8 max-w-md mx-auto">
                                        <?php _e('profile_bookings.no_bookings_desc'); ?>
                                    </p>
                                    <a href="../rooms.php"
                                        class="btn-glass-gold px-8 py-3 rounded-xl text-lg inline-flex items-center gap-2">
                                        <span class="material-symbols-outlined">calendar_add_on</span>
                                        <?php _e('profile_bookings.book_now'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
                    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm mr-1">progress_activity</span> Đang xử lý...';
                    btn.classList.add('opacity-70', 'cursor-not-allowed');

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
                                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Có lỗi xảy ra khi hủy đặt phòng. Vui lòng thử lại.');
                            btn.disabled = false;
                            btn.innerHTML = originalHTML;
                            btn.classList.remove('opacity-70', 'cursor-not-allowed');
                        });
                }
            }
        }

        // Auto-submit form when date changes
        document.addEventListener('DOMContentLoaded', function () {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            const selects = document.querySelectorAll('select');

            dateInputs.forEach(input => {
                input.addEventListener('change', function () {
                    setTimeout(() => {
                        document.querySelector('form').submit();
                    }, 500);
                });
            });

            selects.forEach(select => {
                select.addEventListener('change', function () {
                    document.querySelector('form').submit();
                });
            });
        });
    </script>
</body>

</html>