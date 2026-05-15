<?php
session_start();
require_once '../config/environment.php';
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
?>
<!DOCTYPE html>
<html translate="no" class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('booking_detail.title'); ?></title>
    <link href="../assets/css/tailwind-output.css" rel="stylesheet" />
    <link href="../assets/css/fonts.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="../assets/css/pages-glass.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
</head>

<body class="glass-page font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">

        <?php include '../includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Glass Page Wrapper -->
            <div class="glass-page-wrapper">

                <div class="w-full pt-[180px] pb-16 px-4">
                    <div class="mx-auto max-w-5xl">
                        <!-- Page Header -->
                        <div class="mb-8 pl-4 border-l-4 border-accent">
                            <div class="flex items-center gap-4 mb-2">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="<?php echo route('ho-so/dat-phong'); ?>"
                                        class="inline-flex items-center gap-2 text-white/70 hover:text-accent transition-colors text-sm">
                                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                                        <?php _e('booking_detail.back_to_list'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo route(''); ?>"
                                        class="inline-flex items-center gap-2 text-white/70 hover:text-accent transition-colors text-sm">
                                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                                        <?php _e('booking_detail.back_to_home'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <h1 class="text-3xl font-bold text-white uppercase tracking-wider">
                                <?php _e('booking_detail.page_title'); ?>
                            </h1>
                            <p class="mt-1 text-white/60 flex items-center gap-2 flex-wrap">
                                <?php _e('booking_detail.booking_code'); ?>: 
                                <span class="font-mono text-accent text-xl font-bold tracking-wider bg-white/5 px-2 py-0.5 rounded border border-white/10">
                                    <?php 
                                    $display_code = $booking['booking_code'] ?? $booking_code;
                                    $prefix = substr($display_code, 0, -6);
                                    $suffix = substr($display_code, -6);
                                    echo htmlspecialchars($prefix); ?><span class="bg-white text-black px-1.5 rounded font-bold shadow-sm"><?php echo htmlspecialchars($suffix); ?></span>
                                </span>
                                <span class="text-[10px] text-accent/70 italic flex items-center gap-1 bg-accent/5 px-2 py-1 rounded border border-accent/10">
                                    <span class="material-symbols-outlined text-[12px]">info</span>
                                    Dùng 6 ký tự tô sáng cuối để tra cứu nhanh hoặc báo lễ tân.
                                </span>
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
                                        <?php _e('booking_detail.booking_status'); ?>
                                    </h2>
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
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
                                    <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                        <span
                                            class="block text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.booked_date'); ?></span>
                                        <p class="text-white font-mono">
                                            <?php echo date('m/d/Y H:i', strtotime($booking['created_at'])); ?>
                                        </p>
                                    </div>
                                    <?php if ($booking['checked_in_at']): ?>
                                        <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                            <span
                                                class="block text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.checked_in_date'); ?></span>
                                            <p class="text-white font-mono">
                                                <?php echo date('m/d/Y H:i', strtotime($booking['checked_in_at'])); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($booking['checked_out_at']): ?>
                                        <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                            <span
                                                class="block text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.checked_out_date'); ?></span>
                                            <p class="text-white font-mono">
                                                <?php echo date('m/d/Y H:i', strtotime($booking['checked_out_at'])); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($booking['cancelled_at']): ?>
                                        <div class="p-3 bg-red-500/10 rounded-lg border border-red-500/20">
                                            <span
                                                class="block text-red-300/70 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.cancelled_date'); ?></span>
                                            <p class="text-red-300 font-mono">
                                                <?php echo date('m/d/Y H:i', strtotime($booking['cancelled_at'])); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($booking['cancellation_reason']): ?>
                                    <div class="mt-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                                        <span
                                            class="font-bold text-red-300"><?php _e('booking_detail.cancel_reason'); ?>:</span>
                                        <p class="mt-1 text-red-200/80">
                                            <?php echo htmlspecialchars($booking['cancellation_reason']); ?>
                                        </p>
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
                                                    <?php echo htmlspecialchars($booking['type_name']); ?>
                                                </h4>
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
                                                        <?php echo date('m/d/Y', strtotime($booking['check_in_date'])); ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.check_out'); ?></span>
                                                    <p class="text-lg font-bold text-white">
                                                        <?php echo date('m/d/Y', strtotime($booking['check_out_date'])); ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.num_guests'); ?></span>
                                                    <p class="text-white"><?php echo $booking['num_adults']; ?>
                                                        <?php _e('booking_detail.adults'); ?>
                                                        <?php echo $booking['num_children'] ? ', ' . $booking['num_children'] . ' ' . __('booking_detail.children') : ''; ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.num_nights'); ?></span>
                                                    <p class="text-white"><?php echo $booking['total_nights']; ?>
                                                        <?php _e('profile_bookings.nights'); ?>
                                                    </p>
                                                </div>
                                                 <?php
                                                 $booking_type_val = $booking['booking_type'] ?? 'standard';
                                                 $is_short_stay = $booking_type_val === 'short_stay';
                                                 ?>
                                                 <div>
                                                     <span
                                                         class="text-white/50 text-xs uppercase tracking-wider block mb-1">Loại
                                                         hình</span>
                                                     <span
                                                         class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium
                                                         <?php echo $is_short_stay ? 'bg-blue-500/20 text-blue-300' : 'bg-green-500/20 text-green-300'; ?>">
                                                         <span
                                                             class="material-symbols-outlined text-xs"><?php echo $is_short_stay ? 'schedule' : 'hotel'; ?></span>
                                                         <?php echo $is_short_stay ? 'Nghỉ ngắn hạn' : 'Nghỉ qua đêm'; ?>
                                                     </span>
                                                 </div>
                                                 <?php if ($booking['room_number']): ?>
                                                     <div
                                                         class="md:col-span-2 grid grid-cols-2 gap-6 pt-4 border-t border-white/10 mt-2">
                                                         <div>
                                                             <span
                                                                 class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.room_number'); ?></span>
                                                             <p class="text-xl font-bold text-accent">
                                                                 <?php echo $booking['room_number']; ?>
                                                             </p>
                                                         </div>
                                                         <div>
                                                             <span
                                                                 class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.floor'); ?></span>
                                                             <p class="text-white"><?php _e('booking_detail.floor'); ?>
                                                                 <?php echo $booking['floor']; ?>,
                                                                 <?php echo $booking['building']; ?>
                                                             </p>
                                                         </div>
                                                     </div>
                                                 <?php else: ?>
                                                     <div class="md:col-span-2 pt-4 border-t border-white/10 mt-2">
                                                         <p class="text-white/50 text-xs uppercase tracking-wider mb-2">Số phòng</p>
                                                         <p class="text-yellow-400 flex items-center gap-2">
                                                             <span class="material-symbols-outlined text-yellow-400">info</span>
                                                             Chưa phân phòng - Sẽ được cập nhật khi nhận phòng
                                                         </p>
                                                         <p class="text-white/40 text-xs mt-1">Room not assigned yet - Will be updated at check-in</p>
                                                     </div>
                                                 <?php endif; ?>
                                            </div>

                                            <?php if ($booking['amenities']): ?>
                                                <div>
                                                    <span
                                                        class="font-bold text-white block mb-2"><?php _e('booking_detail.amenities'); ?>:</span>
                                                    <p class="text-white/70 text-sm">
                                                        <?php echo htmlspecialchars($booking['amenities']); ?>
                                                    </p>
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
                                                    <?php echo htmlspecialchars($booking['guest_name']); ?>
                                                </p>
                                            </div>
                                            <div
                                                class="p-3 bg-white/5 rounded-lg border border-white/5 hover:bg-white/10 transition-colors">
                                                <span
                                                    class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.email'); ?></span>
                                                <p class="text-white font-mono text-sm">
                                                    <?php echo htmlspecialchars($booking['guest_email']); ?>
                                                </p>
                                            </div>
                                            <div
                                                class="p-3 bg-white/5 rounded-lg border border-white/5 hover:bg-white/10 transition-colors">
                                                <span
                                                    class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.phone'); ?></span>
                                                <p class="text-white font-mono">
                                                    <?php echo htmlspecialchars($booking['guest_phone']); ?>
                                                </p>
                                            </div>
                                            <?php if ($booking['guest_id_number']): ?>
                                                <div
                                                    class="p-3 bg-white/5 rounded-lg border border-white/5 hover:bg-white/10 transition-colors">
                                                    <span
                                                        class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.id_number'); ?></span>
                                                    <p class="text-white font-mono">
                                                        <?php echo htmlspecialchars($booking['guest_id_number']); ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sidebar -->
                                <div class="space-y-8">

                                    <!-- Actions -->
                                    <div class="glass-card p-6">
                                        <h3 class="text-xl font-bold mb-6 text-white border-b border-white/10 pb-4">
                                            <?php _e('booking_detail.actions'); ?>
                                        </h3>

                                        <div class="space-y-3">
                                            <button onclick="printBookingDetail()"
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
                                            <a href="<?php echo route('ho-so/ma-qr', ['id' => $booking['booking_id']]); ?>"
                                                class="w-full px-4 py-3 bg-white/5 border border-white/10 text-white rounded-xl hover:bg-white/10 transition-all font-semibold flex items-center justify-center gap-2">
                                                <span class="material-symbols-outlined text-accent">qr_code</span>
                                                <?php _e('booking_detail.view_qr'); ?>
                                            </a>

                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <!-- Chat về đặt phòng này -->
                                                <button id="chatAboutBookingBtn"
                                                    onclick="openChatAboutBooking(<?php echo (int) $booking['booking_id']; ?>, '<?php echo addslashes($booking['booking_code']); ?>')"
                                                    class="w-full px-4 py-3 relative overflow-hidden
                                                               bg-gradient-to-r from-amber-500/20 to-amber-400/10
                                                               border border-amber-500/40 text-amber-300 rounded-xl
                                                               hover:from-amber-500/30 hover:border-amber-400/60
                                                               transition-all font-semibold
                                                               flex items-center justify-center gap-2 group">
                                                    <div
                                                        class="absolute inset-0 bg-gradient-to-r from-transparent
                                                                via-white/5 to-transparent translate-x-[-100%]
                                                                group-hover:translate-x-[100%] transition-transform duration-700">
                                                    </div>
                                                    <span class="w-2.5 h-2.5 rounded-full bg-green-400 flex-shrink-0"
                                                        style="box-shadow:0 0 0 3px rgba(74,222,128,.25);animation:pulse 2s ease-in-out infinite"></span>
                                                    <span
                                                        class="material-symbols-outlined text-amber-400 text-lg relative z-10">chat</span>
                                                    <span class="relative z-10">Chat với nhân viên</span>
                                                </button>
                                            <?php endif; ?>


                                            <?php if ($can_cancel): ?>
                                                <div class="space-y-3 pt-4 border-t border-white/10 mt-4">
                                                    <!-- Cancel Button -->
                                                    <button onclick="showCancelModal()"
                                                        class="w-full px-4 py-3 border border-red-500/50 bg-red-500/10 text-red-400 rounded-xl hover:bg-red-500/20 transition-colors font-bold uppercase tracking-wider text-sm flex items-center justify-center gap-2">
                                                        <span class="material-symbols-outlined">cancel</span>
                                                        <?php _e('booking_detail.cancel_booking'); ?>
                                                    </button>
                                                </div>
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
                                                                    <?php echo date('m/d/Y H:i', strtotime($history['created_at'])); ?>
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
                                                                    <?php echo htmlspecialchars($history['notes']); ?>
                                                                </p>
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
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Đã sao chép link vào clipboard!');
                });
            }
        }

        // Mở chat widget về đơn đặt phòng
        function openChatAboutBooking(bookingId, bookingCode) {
            if (typeof ChatWidget === 'undefined') {
                alert('Vui lòng đăng nhập để sử dụng chat!');
                return;
            }
            // Mở panel
            if (!ChatWidget.isOpen) ChatWidget.open();

            // Nếu chưa có conv → tạo mới kèm booking_id
            if (!ChatWidget.convId) {
                ChatWidget.createOrGetConversation(
                    'Hỗ trợ đặt phòng #' + bookingCode,
                    bookingId
                );
            } else {
                // Đã có conv → focus input với prefill
                const input = document.getElementById('cwInput');
                if (input && !input.value) {
                    input.value = 'Tôi muốn hỏi về đặt phòng #' + bookingCode + ' ';
                    input.focus();
                    input.setSelectionRange(input.value.length, input.value.length);
                }
            }

            // Scroll to button để user thấy widget
            document.getElementById('chatAboutBookingBtn')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
                
                <!-- Cancel Policy Notice -->
                <div class="mb-6 p-4 bg-white/5 rounded-xl border border-white/10 text-sm text-white/70">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-amber-400 flex-shrink-0 mt-0.5">info</span>
                        <p>Sau khi hủy, đơn đặt phòng sẽ không thể khôi phục. Quý khách vui lòng liên hệ lễ tân để được hỗ trợ thêm.</p>
                    </div>
                </div>
                
                <!-- Cancellation Policy -->
                <div class="mb-6 p-4 bg-white/5 rounded-xl border border-white/10 text-white/70 text-sm max-h-40 overflow-y-auto">
                    <h5 class="font-bold text-white mb-2">Chính sách hủy phòng:</h5>
                    <?php echo getRefundPolicyText(); ?>
                </div>
                
                <!-- Reason Input -->
                <div class="mb-6">
                    <label class="block text-sm font-bold text-white mb-2">Lý do hủy / Cancellation reason <span class="text-white/40 font-normal">(không bắt buộc / optional)</span></label>
                    <textarea id="cancelReason" rows="3" 
                              class="w-full px-4 py-3 border border-white/10 rounded-xl bg-white/5 text-white placeholder-white/30 focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition-all"
                              placeholder="VD: Thay đổi kế hoạch / e.g. Change of plans..."></textarea>
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
                            Tôi đã đọc và đồng ý với chính sách hủy phòng / I agree to the cancellation policy.
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

        // New Print Function
        function printBookingDetail() {
            const printWindow = window.open('', '_blank', 'width=900,height=900');

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Booking Confirmation - <?php echo $booking['booking_code']; ?></title>
                    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { font-family: 'Roboto', Arial, sans-serif; padding: 0; color: #222; font-size: 13px; line-height: 1.5; }
                        .page { max-width: 210mm; margin: 0 auto; padding: 20mm; }
                        
                        /* Header */
                        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #d4af37; padding-bottom: 15px; margin-bottom: 20px; }
                        .header-left { flex: 1; }
                        .logo-text { font-size: 24px; font-weight: 700; color: #d4af37; text-transform: uppercase; letter-spacing: 2px; }
                        .hotel-address { font-size: 11px; color: #666; margin-top: 4px; }
                        .header-right { text-align: right; }
                        .doc-title { font-size: 16px; font-weight: 700; text-transform: uppercase; color: #333; letter-spacing: 1px; }
                        .doc-subtitle { font-size: 11px; color: #888; margin-top: 2px; }
                        .doc-date { font-size: 11px; color: #666; margin-top: 8px; }
                        
                        /* Booking Code */
                        .booking-code-strip { background: #f8f8f8; border: 1px solid #ddd; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
                        .booking-code-strip .label { font-size: 11px; text-transform: uppercase; color: #888; letter-spacing: 1px; }
                        .booking-code-strip .code { font-size: 22px; font-weight: 700; color: #333; letter-spacing: 3px; }
                        .booking-code-strip .status { font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 4px; text-transform: uppercase; }
                        .status-pending { background: #fef3c7; color: #92400e; }
                        .status-confirmed { background: #dcfce7; color: #166534; }
                        .status-checked_in { background: #dbeafe; color: #1e40af; }
                        .status-checked_out { background: #f3f4f6; color: #374151; }
                        .status-cancelled { background: #fee2e2; color: #991b1b; }
                        
                        /* Two Column Layout */
                        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin-bottom: 0; }
                        .col { padding: 15px 20px; }
                        .col-left { border-right: 1px solid #e5e5e5; }
                        .col-title { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #d4af37; letter-spacing: 1px; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 12px; }
                        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dotted #eee; font-size: 12px; }
                        .info-row:last-child { border-bottom: none; }
                        .info-label { color: #888; font-weight: 400; }
                        .info-value { font-weight: 500; text-align: right; color: #333; }
                        
                        /* Stay Duration */
                        .stay-strip { display: grid; grid-template-columns: 1fr auto 1fr; gap: 0; margin: 20px 0; border: 1px solid #e5e5e5; }
                        .stay-box { padding: 15px 20px; text-align: center; }
                        .stay-label { font-size: 10px; text-transform: uppercase; color: #888; letter-spacing: 1px; margin-bottom: 4px; }
                        .stay-date { font-size: 18px; font-weight: 700; color: #333; }
                        .stay-time { font-size: 10px; color: #888; margin-top: 2px; }
                        .stay-divider { display: flex; align-items: center; justify-content: center; background: #f8f8f8; border-left: 1px solid #e5e5e5; border-right: 1px solid #e5e5e5; padding: 0 15px; }
                        .stay-divider .nights { font-size: 14px; font-weight: 700; color: #d4af37; }
                        .stay-divider .nights-label { font-size: 10px; color: #888; text-transform: uppercase; }
                        
                        /* Room Details */
                        .room-strip { border: 1px solid #e5e5e5; padding: 15px 20px; margin-bottom: 20px; }
                        .room-strip .col-title { margin-bottom: 10px; }
                        
                        /* Pricing */
                        .pricing-strip { border: 1px solid #e5e5e5; padding: 15px 20px; margin-bottom: 20px; }
                        .pricing-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 12px; }
                        .pricing-row.total { border-top: 2px solid #333; padding-top: 10px; margin-top: 8px; }
                        .pricing-row.total .label { font-weight: 700; font-size: 14px; text-transform: uppercase; }
                        .pricing-row.total .value { font-weight: 700; font-size: 18px; color: #d4af37; }
                        
                        /* Signatures */
                        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; padding-top: 20px; }
                        .sig-box { text-align: center; }
                        .sig-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #666; margin-bottom: 50px; }
                        .sig-name { font-size: 12px; font-weight: 500; border-top: 1px solid #333; display: inline-block; padding-top: 4px; min-width: 150px; }
                        
                        /* Footer */
                        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e5e5; text-align: center; font-size: 10px; color: #888; }
                        .footer .hotel-name { font-size: 12px; font-weight: 700; color: #d4af37; margin-bottom: 2px; }
                        
                        /* Notes */
                        .notes { border: 1px solid #e5e5e5; padding: 12px 20px; margin-bottom: 20px; background: #fafafa; }
                        .notes-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #666; margin-bottom: 6px; }
                        .notes-text { font-size: 12px; color: #555; white-space: pre-wrap; }
                        
                        @media print {
                            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                            .page { padding: 15mm; }
                        }
                    </style>
                </head>
                <body>
                    <div class="page">
                        <!-- Header -->
                        <div class="header">
                            <div class="header-left">
                                <div class="logo-text">Aurora Hotel Plaza</div>
                                <div class="hotel-address">253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai</div>
                                <div class="hotel-address">Hotline: 0251 3511 888 | info@aurorahotelplaza.com</div>
                            </div>
                            <div class="header-right">
                                <div class="doc-title">Xác nhận đặt phòng</div>
                                <div class="doc-subtitle">Booking Confirmation</div>
                                <div class="doc-date">Ngày in/Printed: <?php echo date('H:i d/m/Y'); ?></div>
                            </div>
                        </div>
                        
                        <!-- Booking Code Strip -->
                        <div class="booking-code-strip">
                            <div>
                                <div class="label">Mã đặt phòng / Booking Code</div>
                                <div class="code"><?php echo $booking['booking_code']; ?></div>
                            </div>
                            <div class="status status-<?php echo $booking['status']; ?>">
                                <?php echo $status_labels[$booking['status']]['label']; ?>
                            </div>
                        </div>
                        
                        <!-- Two Column: Guest & Hotel -->
                        <div class="two-col">
                            <div class="col col-left">
                                <div class="col-title">Thông tin khách hàng / Guest Information</div>
                                <div class="info-row"><span class="info-label">Họ tên / Name:</span><span class="info-value"><?php echo htmlspecialchars($booking['guest_name']); ?></span></div>
                                <div class="info-row"><span class="info-label">SĐT / Phone:</span><span class="info-value"><?php echo htmlspecialchars($booking['guest_phone']); ?></span></div>
                                <div class="info-row"><span class="info-label">Email:</span><span class="info-value"><?php echo htmlspecialchars($booking['guest_email']); ?></span></div>
                                <?php if ($booking['guest_id_number']): ?>
                                <div class="info-row"><span class="info-label">CMND/CCCD / ID:</span><span class="info-value"><?php echo htmlspecialchars($booking['guest_id_number']); ?></span></div>
                                <?php endif; ?>
                                <div class="info-row"><span class="info-label">Ngày đặt / Booked:</span><span class="info-value"><?php echo date('H:i d/m/Y', strtotime($booking['created_at'])); ?></span></div>
                            </div>
                            <div class="col">
                                <div class="col-title">Thông tin khách sạn / Hotel Information</div>
                                <div class="info-row"><span class="info-label">Tên / Name:</span><span class="info-value">Aurora Hotel Plaza</span></div>
                                <div class="info-row"><span class="info-label">Địa chỉ / Address:</span><span class="info-value">253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai</span></div>
                                <div class="info-row"><span class="info-label">Điện thoại / Phone:</span><span class="info-value">0251 3511 888</span></div>
                                <div class="info-row"><span class="info-label">Email:</span><span class="info-value">info@aurorahotelplaza.com</span></div>
                                <div class="info-row"><span class="info-label">Website:</span><span class="info-value">aurorahotelplaza.com</span></div>
                            </div>
                        </div>
                        
                        <!-- Stay Duration Strip -->
                        <div class="stay-strip">
                            <div class="stay-box">
                                <div class="stay-label">Nhận phòng / Check-in</div>
                                <div class="stay-date"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></div>
                                <div class="stay-time">Từ 14:00 / From 14:00</div>
                            </div>
                            <div class="stay-divider">
                                <div>
                                    <div class="nights"><?php echo $booking['total_nights']; ?> đêm</div>
                                    <div class="nights-label">nights</div>
                                </div>
                            </div>
                            <div class="stay-box">
                                <div class="stay-label">Trả phòng / Check-out</div>
                                <div class="stay-date"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></div>
                                <div class="stay-time">Trước 12:00 / Before 12:00</div>
                            </div>
                        </div>
                        
                        <!-- Room Details -->
                        <div class="room-strip">
                            <div class="col-title">Thông tin phòng / Room Information</div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 20px;">
                                <div class="info-row"><span class="info-label">Loại phòng:</span><span class="info-value"><?php echo htmlspecialchars($booking['type_name']); ?></span></div>
                                <div class="info-row"><span class="info-label">Giường / Bed:</span><span class="info-value"><?php echo htmlspecialchars($booking['bed_type']); ?></span></div>
                                <div class="info-row"><span class="info-label">Số khách / Guests:</span><span class="info-value"><?php echo $booking['num_adults']; ?> người lớn<?php if ($booking['num_children'] > 0) echo ', ' . $booking['num_children'] . ' trẻ em'; ?></span></div>
                                <?php if ($booking['room_number']): ?>
                                <div class="info-row"><span class="info-label">Số phòng / Room:</span><span class="info-value" style="font-weight:700;color:#16a34a;"><?php echo $booking['room_number']; ?></span></div>
                                <div class="info-row"><span class="info-label">Tầng / Floor:</span><span class="info-value"><?php echo $booking['floor']; ?><?php if ($booking['building']) echo ' - ' . htmlspecialchars($booking['building']); ?></span></div>
                                <?php else: ?>
                                <div class="info-row" style="grid-column: span 3;"><span class="info-label">Phòng / Room:</span><span class="info-value" style="color:#b45309;">Chưa phân / Not assigned</span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($booking['special_requests'])): ?>
                        <div class="notes">
                            <div class="notes-title">Yêu cầu đặc biệt / Special Requests</div>
                            <div class="notes-text"><?php echo htmlspecialchars($booking['special_requests']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Pricing -->
                        <div class="pricing-strip">
                            <div class="col-title">Chi phí / Pricing</div>
                            <div class="pricing-row">
                                <span class="label">Đơn giá phòng / đêm × <?php echo $booking['total_nights']; ?> đêm</span>
                                <span class="value"><?php echo number_format($booking['room_price'] / max(1, $booking['total_nights']), 0, ',', '.'); ?> VND</span>
                            </div>
                            <?php if (($booking['extra_beds'] ?? 0) > 0): ?>
                            <div class="pricing-row">
                                <span class="label">Giường phụ / Extra beds (<?php echo $booking['extra_beds']; ?>)</span>
                                <span class="value"><?php echo number_format($booking['extra_bed_fee'] ?? 0, 0, ',', '.'); ?> VND</span>
                            </div>
                            <?php endif; ?>
                            <?php if ($booking['service_fee'] > 0): ?>
                            <div class="pricing-row">
                                <span class="label">Phí dịch vụ / Service fee</span>
                                <span class="value"><?php echo number_format($booking['service_fee'], 0, ',', '.'); ?> VND</span>
                            </div>
                            <?php endif; ?>
                            <?php if ($booking['discount_amount'] > 0): ?>
                            <div class="pricing-row">
                                <span class="label">Giảm giá / Discount</span>
                                <span class="value" style="color:#dc2626;">-<?php echo number_format($booking['discount_amount'], 0, ',', '.'); ?> VND</span>
                            </div>
                            <?php endif; ?>
                            <div class="pricing-row total">
                                <span class="label">Tổng cộng / Total</span>
                                <span class="value"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?> VND</span>
                            </div>
                        </div>
                        
                        <!-- Signatures -->
                        <div class="signatures">
                            <div class="sig-box">
                                <div class="sig-title">Khách hàng / Guest</div>
                                <div class="sig-name"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                            </div>
                            <div class="sig-box">
                                <div class="sig-title">Đại diện khách sạn / Hotel Representative</div>
                                <div class="sig-name">Aurora Hotel Plaza</div>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="footer">
                            <div class="hotel-name">Aurora Hotel Plaza</div>
                            253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai | Hotline: 0251 3511 888 | info@aurorahotelplaza.com | aurorahotelplaza.com
                        </div>
                    </div>
                </body>
                </html>
            `);

            printWindow.document.close(); // Necessary for IE >= 10
            printWindow.focus(); // Necessary for IE >= 10

            // Wait for images to load
            setTimeout(function () {
                printWindow.print();
                // printWindow.close(); // Optional: close after print
            }, 500);
        }
    </script>
</body>

</html>
