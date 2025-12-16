<?php
session_start();
require_once '../config/database.php';
require_once '../models/Booking.php';
require_once '../helpers/refund-policy.php';
require_once '../helpers/language.php';
initLanguage();

$booking_code = $_GET['code'] ?? '';
$booking_id = $_GET['id'] ?? 0;
$error = '';
$booking = null;
$booking_history = [];
$can_cancel = false;
$refund_info = null;

if (!$booking_code && !$booking_id) {
    $error = __('booking_detail.invalid_code');
} else {
    try {
        $db = getDB();
        $bookingModel = new Booking($db);

        // Get booking details by code or id
        if ($booking_id) {
            $stmt = $db->prepare("
                SELECT b.*, rt.type_name, rt.category, r.room_number,
                       u.full_name as guest_name, u.email, u.phone
                FROM bookings b
                JOIN room_types rt ON b.room_type_id = rt.room_type_id
                LEFT JOIN rooms r ON b.room_id = r.room_id
                LEFT JOIN users u ON b.user_id = u.user_id
                WHERE b.booking_id = :booking_id
            ");
            $stmt->execute([':booking_id' => $booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $booking = $bookingModel->getByCode($booking_code);
        }

        if (!$booking) {
            $error = __('booking_detail.not_found');
        } elseif (isset($_SESSION['user_id']) && $booking['user_id'] != $_SESSION['user_id']) {
            // If user is logged in but not the owner, don't show
            $error = __('booking_detail.no_permission');
        } else {
            // Get booking history
            $booking_history = $bookingModel->getHistory($booking['booking_id']);

            // Check if booking can be cancelled and calculate refund
            if (isset($_SESSION['user_id'])) {
                $can_cancel = $bookingModel->canBeCancelled($booking['booking_id']);
                if ($can_cancel) {
                    $refund_info = calculateRefundAmount($booking);
                }
            }
        }

    } catch (Exception $e) {
        error_log("Booking detail error: " . $e->getMessage());
        $error = __('booking_detail.error_loading');
    }
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
    <title><?php _e('booking_detail.title'); ?></title>
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
                    <div class="mx-auto max-w-5xl">
                        <!-- Page Header -->
                        <div class="mb-8 pl-4 border-l-4 border-accent">
                            <div class="flex items-center gap-4 mb-2">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="bookings.php"
                                        class="inline-flex items-center gap-2 text-white/70 hover:text-accent transition-colors text-sm">
                                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                                        <?php _e('booking_detail.back_to_list'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="../index.php"
                                        class="inline-flex items-center gap-2 text-white/70 hover:text-accent transition-colors text-sm">
                                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                                        <?php _e('booking_detail.back_to_home'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <h1 class="text-3xl font-bold text-white uppercase tracking-wider">
                                <?php _e('booking_detail.page_title'); ?>
                            </h1>
                            <p class="mt-1 text-white/60">
                                <?php _e('booking_detail.booking_code'); ?>: <span
                                    class="font-mono text-accent text-lg"><?php echo htmlspecialchars($booking_code); ?></span>
                            </p>
                        </div>

                        <?php if ($error): ?>
                            <div class="mb-6 rounded-xl bg-red-500/10 border border-red-500/20 p-4 backdrop-blur-sm">
                                <div class="flex items-center">
                                    <span class="material-symbols-outlined text-red-400 mr-2">error</span>
                                    <p class="text-red-200"><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>

                            <!-- Booking Code Lookup Form -->
                            <div class="glass-card p-6">
                                <h2 class="text-xl font-bold mb-4 text-white"><?php _e('booking_detail.lookup_title'); ?>
                                </h2>
                                <form method="GET" class="flex gap-4">
                                    <input type="text" name="code"
                                        placeholder="<?php _e('booking_detail.lookup_placeholder'); ?>"
                                        value="<?php echo htmlspecialchars($booking_code); ?>"
                                        class="flex-1 px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition-all">
                                    <button type="submit"
                                        class="px-6 py-3 bg-accent text-white font-bold rounded-xl hover:bg-accent/90 transition-all shadow-lg shadow-accent/20">
                                        <span class="material-symbols-outlined mr-2 align-middle">search</span>
                                        <?php _e('booking_detail.lookup_btn'); ?>
                                    </button>
                                </form>
                            </div>

                        <?php elseif ($booking): ?>

                            <!-- Booking Status -->
                            <div class="glass-card p-6 mb-8">
                                <div
                                    class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 border-b border-white/10 pb-6">
                                    <h2 class="text-xl font-bold text-white uppercase tracking-wider">
                                        <?php _e('booking_detail.booking_status'); ?></h2>
                                    <div class="flex gap-3">
                                        <span class="px-4 py-1.5 text-sm font-bold rounded-full border <?php
                                        $status = $booking['status'];
                                        echo ($status == 'confirmed') ? 'bg-green-500/20 text-green-400 border-green-500/30' :
                                            (($status == 'pending') ? 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30' :
                                                (($status == 'cancelled') ? 'bg-red-500/20 text-red-400 border-red-500/30' :
                                                    'bg-gray-500/20 text-gray-400 border-gray-500/30'));
                                        ?>">
                                            <?php echo $status_labels[$status]['label']; ?>
                                        </span>
                                        <?php if ($booking['payment_status']): ?>
                                            <span class="px-4 py-1.5 text-sm font-bold rounded-full border <?php
                                            $p_status = $booking['payment_status'];
                                            echo ($p_status == 'paid') ? 'bg-green-500/20 text-green-400 border-green-500/30' :
                                                (($p_status == 'unpaid') ? 'bg-red-500/20 text-red-400 border-red-500/30' :
                                                    'bg-yellow-500/20 text-yellow-400 border-yellow-500/30');
                                            ?>">
                                                <?php echo $payment_labels[$p_status]['label']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
                                    <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                        <span
                                            class="block text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.booked_date'); ?></span>
                                        <p class="text-white font-mono">
                                            <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></p>
                                    </div>
                                    <?php if ($booking['checked_in_at']): ?>
                                        <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                            <span
                                                class="block text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.checked_in_date'); ?></span>
                                            <p class="text-white font-mono">
                                                <?php echo date('d/m/Y H:i', strtotime($booking['checked_in_at'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($booking['checked_out_at']): ?>
                                        <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                            <span
                                                class="block text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.checked_out_date'); ?></span>
                                            <p class="text-white font-mono">
                                                <?php echo date('d/m/Y H:i', strtotime($booking['checked_out_at'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($booking['cancelled_at']): ?>
                                        <div class="p-3 bg-red-500/10 rounded-lg border border-red-500/20">
                                            <span
                                                class="block text-red-300/70 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.cancelled_date'); ?></span>
                                            <p class="text-red-300 font-mono">
                                                <?php echo date('d/m/Y H:i', strtotime($booking['cancelled_at'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($booking['cancellation_reason']): ?>
                                    <div class="mt-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                                        <span
                                            class="font-bold text-red-300"><?php _e('booking_detail.cancel_reason'); ?>:</span>
                                        <p class="mt-1 text-red-200/80">
                                            <?php echo htmlspecialchars($booking['cancellation_reason']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <!-- Main Booking Info -->
                                <div class="lg:col-span-2 space-y-8">

                                    <!-- Room Information -->
                                    <div class="glass-card p-6">
                                        <h3
                                            class="text-xl font-bold mb-6 flex items-center gap-3 text-white border-b border-white/10 pb-4">
                                            <span class="material-symbols-outlined text-accent">
                                                <?php echo $booking['category'] === 'apartment' ? 'apartment' : 'hotel'; ?>
                                            </span>
                                            <?php _e('booking_detail.room_info'); ?>
                                        </h3>

                                        <div class="space-y-6">
                                            <div>
                                                <h4 class="text-2xl font-bold text-accent">
                                                    <?php echo htmlspecialchars($booking['type_name']); ?></h4>
                                                <?php if ($booking['description']): ?>
                                                    <p class="text-white/60 mt-2 leading-relaxed">
                                                        <?php echo htmlspecialchars($booking['description']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>

                                            <div
                                                class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white/5 p-4 rounded-xl border border-white/5">
                                                <div>
                                                    <span
                                                        class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.check_in'); ?></span>
                                                    <p class="text-lg font-bold text-white">
                                                        <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.check_out'); ?></span>
                                                    <p class="text-lg font-bold text-white">
                                                        <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.num_guests'); ?></span>
                                                    <p class="text-white"><?php echo $booking['num_adults']; ?>
                                                        <?php _e('booking_detail.adults'); ?>    <?php echo $booking['num_children'] ? ', ' . $booking['num_children'] . ' ' . __('booking_detail.children') : ''; ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.num_nights'); ?></span>
                                                    <p class="text-white"><?php echo $booking['total_nights']; ?>
                                                        <?php _e('profile_bookings.nights'); ?></p>
                                                </div>
                                                <?php if ($booking['room_number']): ?>
                                                    <div
                                                        class="md:col-span-2 grid grid-cols-2 gap-6 pt-4 border-t border-white/10 mt-2">
                                                        <div>
                                                            <span
                                                                class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.room_number'); ?></span>
                                                            <p class="text-xl font-bold text-accent">
                                                                <?php echo $booking['room_number']; ?></p>
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.floor'); ?></span>
                                                            <p class="text-white"><?php _e('booking_detail.floor'); ?>
                                                                <?php echo $booking['floor']; ?>,
                                                                <?php echo $booking['building']; ?></p>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($booking['amenities']): ?>
                                                <div>
                                                    <span
                                                        class="font-bold text-white block mb-2"><?php _e('booking_detail.amenities'); ?>:</span>
                                                    <p class="text-white/70 text-sm">
                                                        <?php echo htmlspecialchars($booking['amenities']); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($booking['special_requests']): ?>
                                                <div>
                                                    <span
                                                        class="font-bold text-white block mb-2"><?php _e('booking_detail.special_requests'); ?>:</span>
                                                    <p
                                                        class="p-4 bg-white/5 rounded-xl text-white/80 border border-white/10 italic text-sm">
                                                        "<?php echo htmlspecialchars($booking['special_requests']); ?>"
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Guest Information -->
                                    <div class="glass-card p-6">
                                        <h3
                                            class="text-xl font-bold mb-6 flex items-center gap-3 text-white border-b border-white/10 pb-4">
                                            <span class="material-symbols-outlined text-accent">person</span>
                                            <?php _e('booking_detail.guest_info'); ?>
                                        </h3>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div
                                                class="p-3 bg-white/5 rounded-lg border border-white/5 hover:bg-white/10 transition-colors">
                                                <span
                                                    class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.full_name'); ?></span>
                                                <p class="text-lg font-bold text-white">
                                                    <?php echo htmlspecialchars($booking['guest_name']); ?></p>
                                            </div>
                                            <div
                                                class="p-3 bg-white/5 rounded-lg border border-white/5 hover:bg-white/10 transition-colors">
                                                <span
                                                    class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.email'); ?></span>
                                                <p class="text-white font-mono text-sm">
                                                    <?php echo htmlspecialchars($booking['guest_email']); ?></p>
                                            </div>
                                            <div
                                                class="p-3 bg-white/5 rounded-lg border border-white/5 hover:bg-white/10 transition-colors">
                                                <span
                                                    class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.phone'); ?></span>
                                                <p class="text-white font-mono">
                                                    <?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                                            </div>
                                            <?php if ($booking['guest_id_number']): ?>
                                                <div
                                                    class="p-3 bg-white/5 rounded-lg border border-white/5 hover:bg-white/10 transition-colors">
                                                    <span
                                                        class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.id_number'); ?></span>
                                                    <p class="text-white font-mono">
                                                        <?php echo htmlspecialchars($booking['guest_id_number']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sidebar -->
                                <div class="space-y-8">

                                    <!-- Price Breakdown -->
                                    <div class="glass-card p-6">
                                        <h3
                                            class="text-xl font-bold mb-6 flex items-center gap-3 text-white border-b border-white/10 pb-4">
                                            <span class="material-symbols-outlined text-accent">receipt</span>
                                            <?php _e('booking_detail.price_detail'); ?>
                                        </h3>

                                        <div class="space-y-4">
                                            <div class="flex justify-between text-white/80">
                                                <span><?php _e('booking_detail.room_price'); ?> <br><span
                                                        class="text-xs text-white/40">(<?php echo $booking['total_nights']; ?>
                                                        <?php _e('profile_bookings.nights'); ?>)</span></span>
                                                <span
                                                    class="font-mono"><?php echo number_format($booking['room_price'] * $booking['total_nights']); ?>
                                                    đ</span>
                                            </div>

                                            <?php if ($booking['service_charges'] > 0): ?>
                                                <div class="flex justify-between text-white/80">
                                                    <span><?php _e('booking_detail.service_charges'); ?></span>
                                                    <span
                                                        class="font-mono"><?php echo number_format($booking['service_charges']); ?>
                                                        đ</span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($booking['discount_amount'] > 0): ?>
                                                <div class="flex justify-between text-green-400">
                                                    <span><?php _e('booking_detail.discount'); ?></span>
                                                    <span
                                                        class="font-mono">-<?php echo number_format($booking['discount_amount']); ?>
                                                        đ</span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($booking['points_used'] > 0): ?>
                                                <div class="flex justify-between text-accent">
                                                    <span><?php _e('booking_detail.points_used'); ?></span>
                                                    <span class="font-mono">-<?php echo $booking['points_used']; ?>
                                                        <?php _e('profile_loyalty.points'); ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <div class="border-t border-white/10 pt-4 mt-2">
                                                <div class="flex justify-between text-lg font-bold text-white">
                                                    <span><?php _e('booking_detail.total'); ?></span>
                                                    <span
                                                        class="text-accent font-mono"><?php echo number_format($booking['total_amount']); ?>
                                                        VNĐ</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment Information -->
                                    <?php if ($booking['payment_method']): ?>
                                        <div class="glass-card p-6">
                                            <h3
                                                class="text-xl font-bold mb-6 flex items-center gap-3 text-white border-b border-white/10 pb-4">
                                                <span class="material-symbols-outlined text-accent">payment</span>
                                                <?php _e('booking_detail.payment_info'); ?>
                                            </h3>

                                            <div class="space-y-4">
                                                <div class="bg-white/5 p-3 rounded-lg border border-white/5">
                                                    <span
                                                        class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.payment_method'); ?></span>
                                                    <p class="text-white capitalize"><?php echo $booking['payment_method']; ?>
                                                    </p>
                                                </div>

                                                <?php if ($booking['transaction_id']): ?>
                                                    <div class="bg-white/5 p-3 rounded-lg border border-white/5">
                                                        <span
                                                            class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.transaction_id'); ?></span>
                                                        <p class="font-mono text-sm text-white break-all">
                                                            <?php echo htmlspecialchars($booking['transaction_id']); ?></p>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($booking['paid_at']): ?>
                                                    <div class="bg-white/5 p-3 rounded-lg border border-white/5">
                                                        <span
                                                            class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.paid_at'); ?></span>
                                                        <p class="text-white font-mono">
                                                            <?php echo date('d/m/Y H:i', strtotime($booking['paid_at'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Actions -->
                                    <div class="glass-card p-6">
                                        <h3 class="text-xl font-bold mb-6 text-white border-b border-white/10 pb-4">
                                            <?php _e('booking_detail.actions'); ?></h3>

                                        <div class="space-y-3">
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <a href="../booking/confirmation.php?booking_code=<?php echo urlencode($booking['booking_code']); ?>"
                                                    class="w-full px-4 py-3 bg-gradient-to-r from-accent to-yellow-600 text-white rounded-xl hover:shadow-[0_0_20px_rgba(var(--accent-rgb),0.3)] transition-all flex items-center justify-center gap-2 font-bold uppercase tracking-wider text-sm">
                                                    <span class="material-symbols-outlined">check_circle</span>
                                                    <?php _e('booking_detail.confirm_booking'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <button onclick="window.print()"
                                                class="w-full px-4 py-3 bg-white/10 text-white rounded-xl hover:bg-white/20 transition-all font-semibold flex items-center justify-center gap-2">
                                                <span class="material-symbols-outlined text-accent">print</span>
                                                <?php _e('booking_detail.print'); ?>
                                            </button>

                                            <button onclick="shareBooking()"
                                                class="w-full px-4 py-3 bg-white/5 border border-white/10 text-white rounded-xl hover:bg-white/10 transition-all font-semibold flex items-center justify-center gap-2">
                                                <span class="material-symbols-outlined text-accent">share</span>
                                                <?php _e('booking_detail.share'); ?>
                                            </button>

                                            <!-- QR Code Button -->
                                            <a href="view-qrcode.php?id=<?php echo $booking['booking_id']; ?>"
                                                class="w-full px-4 py-3 bg-white/5 border border-white/10 text-white rounded-xl hover:bg-white/10 transition-all font-semibold flex items-center justify-center gap-2">
                                                <span class="material-symbols-outlined text-accent">qr_code</span>
                                                <?php _e('booking_detail.view_qr'); ?>
                                            </a>

                                            <!-- Cancellation Policy & Refund Info -->
                                            <?php if ($can_cancel && $refund_info): ?>
                                                <div class="space-y-3 pt-4 border-t border-white/10 mt-4">
                                                    <!-- Refund Information -->
                                                    <div class="p-4 bg-blue-500/10 rounded-xl border border-blue-500/20">
                                                        <h4
                                                            class="font-bold text-sm mb-3 text-blue-300 flex items-center gap-2">
                                                            <span class="material-symbols-outlined text-sm">info</span>
                                                            <?php _e('booking_detail.refund_info'); ?>
                                                        </h4>
                                                        <div class="space-y-2 text-sm text-white/80">
                                                            <div class="flex justify-between">
                                                                <span><?php _e('booking_detail.time_remaining'); ?>:</span>
                                                                <span
                                                                    class="font-bold text-white"><?php echo round($refund_info['days_until_checkin'], 1); ?>
                                                                    <?php _e('booking_detail.days'); ?></span>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <span><?php _e('booking_detail.total_booking'); ?>:</span>
                                                                <span
                                                                    class="font-bold text-white"><?php echo number_format($refund_info['total_amount']); ?>
                                                                    đ</span>
                                                            </div>
                                                            <div class="flex justify-between text-green-400">
                                                                <span><?php _e('booking_detail.refund_amount'); ?>:</span>
                                                                <span
                                                                    class="font-bold text-lg"><?php echo number_format($refund_info['refund_amount']); ?>
                                                                    đ</span>
                                                            </div>
                                                            <?php if ($refund_info['processing_fee'] > 0): ?>
                                                                <div class="flex justify-between text-xs text-white/50">
                                                                    <span><?php _e('booking_detail.processing_fee'); ?>:</span>
                                                                    <span>-<?php echo number_format($refund_info['processing_fee']); ?>
                                                                        đ</span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="pt-2 border-t border-blue-500/20 mt-2">
                                                                <p class="text-xs text-blue-200/70 italic">
                                                                    <?php echo $refund_info['policy_message']; ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Cancel Button -->
                                                    <button onclick="showCancelModal()"
                                                        class="w-full px-4 py-3 border border-red-500/50 bg-red-500/10 text-red-400 rounded-xl hover:bg-red-500/20 transition-colors font-bold uppercase tracking-wider text-sm flex items-center justify-center gap-2">
                                                        <span class="material-symbols-outlined">cancel</span>
                                                        <?php _e('booking_detail.cancel_booking'); ?>
                                                    </button>
                                                </div>
                                            <?php elseif ($booking['status'] === 'confirmed' || $booking['status'] === 'pending'): ?>
                                                <div
                                                    class="mt-4 p-3 bg-white/5 rounded-xl text-sm text-white/60 border border-white/10 text-center italic">
                                                    <span
                                                        class="material-symbols-outlined text-sm mr-1 align-text-bottom">info</span>
                                                    <?php _e('booking_detail.cannot_cancel'); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Booking History Timeline -->
                                    <?php if (!empty($booking_history)): ?>
                                        <div class="glass-card p-6">
                                            <h3
                                                class="text-xl font-bold mb-6 flex items-center gap-3 text-white border-b border-white/10 pb-4">
                                                <span class="material-symbols-outlined text-accent">history</span>
                                                <?php _e('booking_detail.history'); ?>
                                            </h3>

                                            <div class="space-y-0">
                                                <?php foreach ($booking_history as $history): ?>
                                                    <div class="flex gap-4 relative">
                                                        <div class="flex flex-col items-center">
                                                            <div
                                                                class="w-3 h-3 bg-accent rounded-full shadow-[0_0_8px_rgba(var(--accent-rgb),0.8)] z-10">
                                                            </div>
                                                            <?php if ($history !== end($booking_history)): ?>
                                                                <div class="w-0.5 h-full bg-white/10 -mt-1 pb-4"></div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="flex-1 pb-6 relative -top-1.5">
                                                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                                                <span class="px-2 py-0.5 text-xs font-bold uppercase tracking-wider rounded border <?php
                                                                $h_status = $history['new_status'];
                                                                echo ($h_status == 'confirmed') ? 'bg-green-500/10 text-green-400 border-green-500/20' :
                                                                    (($h_status == 'pending') ? 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' :
                                                                        (($h_status == 'cancelled') ? 'bg-red-500/10 text-red-400 border-red-500/20' :
                                                                            'bg-gray-500/10 text-gray-400 border-gray-500/20'));
                                                                ?>">
                                                                    <?php echo $status_labels[$h_status]['label'] ?? $h_status; ?>
                                                                </span>
                                                                <span class="text-xs text-white/40 font-mono">
                                                                    <?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?>
                                                                </span>
                                                            </div>
                                                            <?php if ($history['changed_by_name']): ?>
                                                                <p class="text-xs text-white/50 mb-1">
                                                                    Bởi: <span
                                                                        class="text-white/80"><?php echo htmlspecialchars($history['changed_by_name']); ?></span>
                                                                </p>
                                                            <?php endif; ?>
                                                            <?php if ($history['notes']): ?>
                                                                <p
                                                                    class="text-sm text-white/80 bg-white/5 p-2 rounded border border-white/5 mt-1">
                                                                    <?php echo htmlspecialchars($history['notes']); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <?php include '../includes/footer.php'; ?>

    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function shareBooking() {
            if (navigator.share) {
                navigator.share({
                    title: 'Thông tin đặt phòng - Aurora Hotel Plaza',
                    text: 'Mã đặt phòng: <?php echo $booking_code; ?>',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Đã sao chép link vào clipboard!');
                });
            }
        }

        // Cancel Modal
        function showCancelModal() {
            const modal = document.createElement('div');
            modal.id = 'cancelModal';
            modal.className = 'fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4';
            modal.innerHTML = `
        <div class="bg-slate-900 border border-white/10 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto animate-fadeIn">
            <div class="p-6 md:p-8">
                <div class="flex items-center justify-between mb-6 border-b border-white/10 pb-4">
                    <h3 class="text-2xl font-bold flex items-center gap-3 text-white">
                        <span class="material-symbols-outlined text-red-500 text-3xl">cancel</span>
                        Xác nhận hủy đặt phòng
                    </h3>
                    <button onclick="closeCancelModal()" class="text-white/50 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-2xl">close</span>
                    </button>
                </div>
                
                <!-- Refund Summary -->
                <div class="mb-6 p-5 bg-gradient-to-br from-blue-900/40 to-slate-800 rounded-xl border border-blue-500/30">
                    <h4 class="font-bold mb-4 flex items-center gap-2 text-blue-300 uppercase tracking-wider text-sm">
                        <span class="material-symbols-outlined text-blue-400">payments</span>
                        Thông tin hoàn tiền
                    </h4>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-white/80">
                            <span>Tổng tiền đặt phòng:</span>
                            <span class="font-bold text-white font-mono"><?php echo number_format($refund_info['total_amount']); ?> VNĐ</span>
                        </div>
                        <?php if ($refund_info['processing_fee'] > 0): ?>
                        <div class="flex justify-between text-red-300">
                            <span>Phí xử lý (5%):</span>
                            <span class="font-mono">-<?php echo number_format($refund_info['processing_fee']); ?> VNĐ</span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-lg font-bold text-green-400 pt-3 border-t border-white/10 mt-2">
                            <span>Số tiền hoàn lại:</span>
                            <span class="font-mono"><?php echo number_format($refund_info['refund_amount']); ?> VNĐ</span>
                        </div>
                        <div class="pt-3 text-xs text-blue-200/70 border-t border-white/5 mt-2">
                            <p><strong>Chính sách:</strong> <?php echo $refund_info['policy_message']; ?></p>
                            <p class="mt-1"><strong>Thời gian hoàn tiền:</strong> 5-7 ngày làm việc</p>
                        </div>
                    </div>
                </div>
                
                <!-- Cancellation Policy -->
                <div class="mb-6 p-4 bg-white/5 rounded-xl border border-white/10 text-white/70 text-sm max-h-40 overflow-y-auto">
                    <h5 class="font-bold text-white mb-2">Chính sách hủy phòng:</h5>
                    <?php echo getRefundPolicyText(); ?>
                </div>
                
                <!-- Reason Input -->
                <div class="mb-6">
                    <label class="block text-sm font-bold text-white mb-2">Lý do hủy phòng <span class="text-white/40 font-normal">(không bắt buộc)</span></label>
                    <textarea id="cancelReason" rows="3" 
                              class="w-full px-4 py-3 border border-white/10 rounded-xl bg-white/5 text-white placeholder-white/30 focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition-all"
                              placeholder="VD: Thay đổi kế hoạch, có việc đột xuất..."></textarea>
                </div>
                
                <!-- Confirmation Checkbox -->
                <div class="mb-8">
                    <label class="flex items-start gap-4 cursor-pointer group">
                        <div class="relative flex items-center">
                            <input type="checkbox" id="confirmCancel" class="peer h-5 w-5 cursor-pointer appearance-none rounded border border-white/20 bg-white/5 checked:border-accent checked:bg-accent transition-all">
                            <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 text-white pointer-events-none">
                                <span class="material-symbols-outlined text-sm font-bold">check</span>
                            </span>
                        </div>
                        <span class="text-sm text-white/70 group-hover:text-white transition-colors">
                            Tôi đã đọc và đồng ý với chính sách hủy phòng. Tôi hiểu rằng số tiền hoàn lại sẽ là 
                            <strong class="text-green-400"><?php echo number_format($refund_info['refund_amount']); ?> VNĐ</strong>
                            và sẽ được xử lý trong vòng 5-7 ngày làm việc.
                        </span>
                    </label>
                </div>
                
                <!-- Actions -->
                <div class="flex gap-4">
                    <button onclick="closeCancelModal()" 
                            class="flex-1 px-6 py-3 border border-white/10 rounded-xl text-white hover:bg-white/5 transition-colors font-semibold uppercase tracking-wider text-sm">
                        Quay lại
                    </button>
                    <button onclick="confirmCancellation()" 
                            class="flex-1 px-6 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all font-bold uppercase tracking-wider text-sm shadow-lg shadow-red-900/30">
                        Xác nhận hủy phòng
                    </button>
                </div>
            </div>
        </div>
    `;
            document.body.appendChild(modal);
        }

        function closeCancelModal() {
            const modal = document.getElementById('cancelModal');
            if (modal) modal.remove();
        }

        function confirmCancellation() {
            const checkbox = document.getElementById('confirmCancel');
            const reason = document.getElementById('cancelReason').value;

            if (!checkbox.checked) {
                alert('Vui lòng xác nhận bạn đã đọc và đồng ý với chính sách hủy phòng');
                return;
            }

            // Disable button
            const btn = event.target;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Đang xử lý...';
            btn.classList.add('opacity-70', 'cursor-not-allowed');

            // Send cancel request
            const formData = new FormData();
            formData.append('booking_id', <?php echo $booking['booking_id']; ?>);
            formData.append('reason', reason);

            fetch('api/cancel-booking-with-refund.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('' + data.message);
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

        // Print styles
        const printStyles = `
    <style>
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
            /* Reset colors for printing */
            body.bg-slate-900 { background-color: white !important; color: black !important; }
            .glass-card { background: white !important; border: 1px solid #ccc !important; box-shadow: none !important; color: black !important; }
            .text-white { color: black !important; }
            .text-white\/50, .text-white\/60, .text-white\/70, .text-white\/80 { color: #555 !important; }
        }
    </style>
`;
        document.head.insertAdjacentHTML('beforeend', printStyles);
    </script>
</body>

</html>