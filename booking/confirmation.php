<?php
session_start();
require_once '../config/environment.php';
require_once '../config/database.php';
require_once '../helpers/language.php';
initLanguage();

$booking_code = $_GET['booking_code'] ?? '';

if (!$booking_code) {
    header('Location: ./index.php');
    exit;
}

try {
    $db = getDB();

    // Get booking details
    $stmt = $db->prepare("
        SELECT b.*, b.booking_type, b.duration_type, b.inquiry_message,
               b.extra_guest_fee, b.extra_bed_fee, b.extra_beds, 
               b.occupancy_type, b.price_type_used,
               rt.type_name as room_type_name, rt.description as room_description, rt.category,
               r.room_number, u.email as user_email
        FROM bookings b
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_code = ?
    ");
    $stmt->execute([$booking_code]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception(__('booking_confirmation.not_found'));
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html translate="no" class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('booking_confirmation.title'); ?></title>
    <script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
    <link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/liquid-glass.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./assets/css/booking.css?v=<?php echo time(); ?>">
</head>

<body class="booking-page">
    <?php include '../includes/header.php'; ?>
    <main class="booking-main" style="align-items: center;">
        <div class="booking-container" style="max-width: 40rem;">
            <?php
            $is_inquiry = ($booking['booking_type'] ?? 'instant') === 'inquiry';

            // Parse duration for display
            $duration_type = $booking['duration_type'] ?? '';
            $duration_display = '';

            // Check if it's a custom days format (custom_45_days)
            if (preg_match('/^custom_(\d+)_days$/', $duration_type, $matches)) {
                $days = (int) $matches[1];
                $duration_display = $days . ' ' . __('booking_confirmation.day');
            } elseif (preg_match('/^(\d+)_month/', $duration_type, $matches)) {
                $months = (int) $matches[1];
                if ($months == 12) {
                    $duration_display = $months . ' ' . __('booking_confirmation.month') . ' (1 ' . __('booking_confirmation.year') . ')';
                } elseif ($months == 24) {
                    $duration_display = $months . ' ' . __('booking_confirmation.month') . ' (2 ' . __('booking_confirmation.year') . ')';
                } else {
                    $duration_display = $months . ' ' . __('booking_confirmation.month');
                }
            } else {
                $duration_display = $duration_type ?: 'N/A';
            }
            ?>

            <!-- Header -->
            <div class="text-center mb-8">
                <?php if ($is_inquiry): ?>
                    <!-- Apartment Inquiry Success -->
                    <div class="icon-badge"
                        style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.3), rgba(168, 85, 247, 0.1)); border-color: rgba(168, 85, 247, 0.3);">
                        <span class="material-symbols-outlined" style="color: #a855f7;">apartment</span>
                    </div>
                    <h1 class="booking-title"><?php _e('booking_confirmation.inquiry_success'); ?></h1>
                    <p class="text-white/60 mt-2"><?php _e('booking_confirmation.contact_soon'); ?></p>
                <?php else: ?>
                    <!-- Room Booking Success -->
                    <div class="icon-badge"
                        style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(16, 185, 129, 0.1)); border-color: rgba(16, 185, 129, 0.3);">
                        <span class="material-symbols-outlined" style="color: #10b981;">check_circle</span>
                    </div>
                    <h1 class="booking-title"><?php _e('booking_confirmation.page_title'); ?></h1>
                <?php endif; ?>
            </div>

            <?php if (isset($error)): ?>
                <div class="booking-card" style="border-color: rgba(239, 68, 68, 0.3);">
                    <div class="flex items-center gap-3 text-red-400">
                        <span class="material-symbols-outlined">error</span>
                        <span><?php echo $error; ?></span>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($is_inquiry): ?>
                    <!-- ========== APARTMENT INQUIRY CONFIRMATION ========== -->
                    <div class="booking-card" style="border-color: rgba(168, 85, 247, 0.3);">
                        <div id="confirmationMessage" class="hidden mb-6 p-4 rounded-lg"></div>

                        <div class="space-y-4 mb-8">
                            <div class="flex justify-between items-center py-2 border-b border-purple-500/20">
                                <span class="text-white/70"><?php _e('booking_confirmation.inquiry_code'); ?>:</span>
                                <span class="font-semibold text-purple-400"><?php echo $booking['booking_code']; ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-purple-500/20">
                                <span class="text-white/70"><?php _e('booking_confirmation.apartment'); ?>:</span>
                                <span class="font-semibold"><?php echo $booking['room_type_name']; ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-purple-500/20">
                                <span class="text-white/70"><?php _e('booking_confirmation.est_checkin'); ?>:</span>
                                <span
                                    class="font-semibold"><?php echo date('m/d/Y', strtotime($booking['check_in_date'])); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-purple-500/20">
                                <span class="text-white/70"><?php _e('booking_confirmation.rent_duration'); ?>:</span>
                                <span class="font-semibold"><?php echo $duration_display; ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-purple-500/20">
                                <span class="text-white/70"><?php _e('booking_confirmation.num_guests'); ?>:</span>
                                <span class="font-semibold">
                                    <?php echo $booking['num_adults'] ?? 1; ?>         <?php _e('common.adults'); ?>
                                    <?php if (($booking['num_children'] ?? 0) > 0): ?>
                                        + <?php echo $booking['num_children']; ?>             <?php _e('common.children'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-white/70"><?php _e('common.type'); ?>:</span>
                                <span
                                    class="px-3 py-1 rounded-full text-sm font-semibold bg-purple-500/20 text-purple-400 border border-purple-500/30">
                                    <?php _e('booking_confirmation.contact_for_quote'); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Next Steps Info -->
                        <div class="bg-purple-500/10 border border-purple-500/30 rounded-lg p-4 mb-6">
                            <h4 class="font-semibold text-purple-400 flex items-center gap-2 mb-3">
                                <span class="material-symbols-outlined">info</span>
                                <?php _e('booking_confirmation.next_steps'); ?>
                            </h4>
                            <ul class="space-y-2 text-sm text-white/70">
                                <li class="flex items-start gap-2">
                                    <?php _e('booking_confirmation.step1'); ?>
                                </li>
                                <li class="flex items-start gap-2">
                                    <?php _e('booking_confirmation.step2'); ?>
                                </li>
                                <li class="flex items-start gap-2">
                                    <?php _e('booking_confirmation.step3'); ?>
                                </li>
                                <li class="flex items-start gap-2">
                                    <?php _e('booking_confirmation.step4'); ?>
                                </li>
                            </ul>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="../index.php" class="btn-secondary text-center">
                                <span class="material-symbols-outlined">home</span>
                                <?php _e('booking_confirmation.back_to_home'); ?>
                            </a>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <a href="../index.php?action=track_booking" class="btn-primary text-center justify-center"
                                    style="background: linear-gradient(135deg, #a855f7, #7c3aed);">
                                    <span class="material-symbols-outlined">list_alt</span>
                                    Xem danh sách
                                </a>
                            <?php else: ?>
                                <a href="../profile/bookings.php" class="btn-primary text-center justify-center"
                                    style="background: linear-gradient(135deg, #a855f7, #7c3aed);">
                                    <span class="material-symbols-outlined">list_alt</span>
                                    <?php _e('booking_confirmation.view_inquiry'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- ========== ROOM BOOKING CONFIRMATION ========== -->
                    <div class="booking-card">
                        <div id="confirmationMessage" class="hidden mb-6 p-4 rounded-lg"></div>

                        <div class="space-y-4 mb-8">
                            <div class="flex flex-col py-2 border-b border-white/10">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-white/70"><?php _e('booking_confirmation.booking_code'); ?>:</span>
                                    <span class="font-semibold text-accent text-lg tracking-wider">
                                        <?php 
                                        $full_code = $booking['booking_code'];
                                        $prefix = substr($full_code, 0, -6);
                                        $suffix = substr($full_code, -6);
                                        echo htmlspecialchars($prefix); 
                                        ?><span class="bg-accent text-black px-1.5 rounded-md shadow-sm ml-0.5 animate-pulse-slow"><?php echo htmlspecialchars($suffix); ?></span>
                                    </span>
                                </div>
                                <div class="flex items-start gap-1.5 text-[10px] text-accent/80 italic mt-0.5">
                                    <span class="material-symbols-outlined text-[12px] mt-0.5">info</span>
                                    <span>Ghi chú: 6 ký tự tô sáng cuối là mã rút gọn để tra cứu nhanh hoặc cung cấp cho lễ tân.</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-white/70"><?php _e('booking_confirmation.room_type'); ?>:</span>
                                <span class="font-semibold"><?php echo $booking['room_type_name']; ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-white/70"><?php _e('booking_confirmation.check_in'); ?>:</span>
                                <span
                                    class="font-semibold"><?php echo date('m/d/Y', strtotime($booking['check_in_date'])); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-white/70"><?php _e('booking_confirmation.check_out'); ?>:</span>
                                <span
                                    class="font-semibold"><?php echo date('m/d/Y', strtotime($booking['check_out_date'])); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-white/70"><?php _e('booking_confirmation.num_nights'); ?>:</span>
                                <span class="font-semibold"><?php echo $booking['total_nights']; ?>
                                    <?php _e('common.nights'); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-white/70"><?php _e('booking_confirmation.num_guests'); ?>:</span>
                                <span class="font-semibold">
                                    <?php echo $booking['num_adults'] ?? 2; ?>         <?php _e('common.adults'); ?>
                                    <?php if (($booking['num_children'] ?? 0) > 0): ?>
                                        + <?php echo $booking['num_children']; ?>             <?php _e('common.children'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <!-- Price Breakdown -->
                            <?php
                            // room_price in DB already stores total (per_night × nights)
                            // Calculate per-night rate for display
                            $room_total = (float) $booking['room_price'];
                            $total_nights = max(1, (int) $booking['total_nights']);
                            $room_per_night = $room_total / $total_nights;
                            ?>
                            <div class="py-2 border-b border-white/10">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-white/70"><?php _e('booking_confirmation.room_price_night'); ?>:</span>
                                    <span class="font-semibold"><?php echo number_format($room_per_night); ?>
                                        <?php _e('common.currency'); ?></span>
                                </div>
                                <div class="flex justify-between items-center text-sm text-white/50">
                                    <span><?php _e('booking_confirmation.room_total'); ?>
                                        (<?php echo $booking['total_nights']; ?>         <?php _e('common.nights'); ?>):</span>
                                    <span><?php echo number_format($room_total); ?>         <?php _e('common.currency'); ?></span>
                                </div>
                            </div>

                            <?php if (($booking['extra_guest_fee'] ?? 0) > 0): ?>
                                <div class="flex justify-between items-center py-2 border-b border-white/10">
                                    <span class="text-blue-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">person_add</span>
                                        <?php _e('booking_confirmation.extra_guest'); ?>:
                                    </span>
                                    <span
                                        class="font-semibold text-blue-400">+<?php echo number_format($booking['extra_guest_fee']); ?>
                                        <?php _e('common.currency'); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (($booking['extra_bed_fee'] ?? 0) > 0): ?>
                                <div class="flex justify-between items-center py-2 border-b border-white/10">
                                    <span class="text-orange-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">single_bed</span>
                                        <?php _e('booking_confirmation.extra_bed'); ?> (<?php echo $booking['extra_beds'] ?? 0; ?>):
                                    </span>
                                    <span
                                        class="font-semibold text-orange-400">+<?php echo number_format($booking['extra_bed_fee']); ?>
                                        <?php _e('common.currency'); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-white/70"><?php _e('booking_confirmation.total_amount'); ?>:</span>
                                <span
                                    class="font-bold text-xl text-accent"><?php echo number_format($booking['total_amount']); ?>
                                    <?php _e('common.currency'); ?></span>
                            </div>

                            <?php
                            // Price type badge
                            $price_type = $booking['price_type_used'] ?? 'double';
                            $price_type_labels = [
                                'single' => ['color' => 'bg-blue-500/20 text-blue-400'],
                                'double' => ['color' => 'bg-green-500/20 text-green-400'],
                                'short_stay' => ['color' => 'bg-purple-500/20 text-purple-400'],
                                'weekly' => ['color' => 'bg-amber-500/20 text-amber-400'],
                                'daily' => ['color' => 'bg-cyan-500/20 text-cyan-400']
                            ];
                            $type_info = $price_type_labels[$price_type] ?? $price_type_labels['double'];
                            ?>
                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-white/70"><?php _e('booking_confirmation.price_type'); ?>:</span>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $type_info['color']; ?>">
                                    <?php echo __('booking_confirmation.price_' . $price_type); ?>
                                </span>
                            </div>

                            <div class="flex justify-between items-center py-2">
                                <span class="text-white/70"><?php _e('booking_confirmation.status'); ?>:</span>
                                <span id="bookingStatus" class="px-3 py-1 rounded-full text-sm font-semibold
                                    <?php echo $booking['status'] === 'confirmed'
                                        ? 'bg-green-500/20 text-green-400 border border-green-500/30'
                                        : 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30'; ?>">
                                    <?php echo $booking['status'] === 'confirmed' ? __('booking_confirmation.confirmed') : __('booking_confirmation.pending'); ?>
                                </span>
                            </div>

                            <!-- Disclaimer Notes -->
                            <div class="mt-4 p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                                <p class="text-xs text-amber-400 flex items-start gap-1">
                                    <span class="material-symbols-outlined text-sm mt-0.5">info</span>
                                    <span><strong><?php _e('booking_confirmation.disclaimer_title'); ?>:</strong>
                                        <?php _e('booking_confirmation.disclaimer_text'); ?></span>
                                </p>
                            </div>
                        </div>

                        <?php if ($booking['status'] === 'pending'): ?>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" id="confirmBookingBtn" class="btn-primary">
                                    <span class="material-symbols-outlined">check_circle</span>
                                    <span><?php _e('booking_confirmation.confirm_btn'); ?></span>
                                </button>
                                <a href="../index.php" class="btn-secondary text-center justify-center">
                                    <?php _e('booking_confirmation.back_to_home'); ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="bg-green-500/15 border border-green-500/30 rounded-lg p-4 mb-4">
                                <p class="text-green-400 flex items-center gap-2">
                                    <span class="material-symbols-outlined">check_circle</span>
                                    <span><?php _e('booking_confirmation.success_message'); ?></span>
                                </p>
                            </div>
                            <a href="../index.php" class="btn-primary w-full justify-center">
                                <?php _e('booking_confirmation.back_to_home'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const confirmBtn = document.getElementById('confirmBookingBtn');
            const messageDiv = document.getElementById('confirmationMessage');
            const statusSpan = document.getElementById('bookingStatus');

            if (confirmBtn) {
                confirmBtn.addEventListener('click', async function () {
                    // Disable button and show loading
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span><span><?php _e('booking_confirmation.confirming'); ?></span>';

                    try {
                        const response = await fetch('./api/confirm-booking-user.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                booking_code: '<?php echo $booking['booking_code']; ?>'
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            // Show success message
                            messageDiv.className = 'mb-6 p-4 rounded-lg bg-green-500/15 border border-green-500/30 text-green-400';
                            messageDiv.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span>${result.message}</span>
                        </div>
                    `;
                            messageDiv.classList.remove('hidden');

                            // Update status badge
                            statusSpan.className = 'px-3 py-1 rounded-full text-sm font-semibold bg-green-500/20 text-green-400 border border-green-500/30';
                            statusSpan.textContent = '<?php echo addslashes(__('booking_confirmation.confirmed')); ?>';

                            // Hide button and show success state
                            setTimeout(() => {
                                confirmBtn.parentElement.innerHTML = `
                            <div class="bg-green-500/15 border border-green-500/30 rounded-lg p-4 mb-4">
                                <p class="text-green-400 flex items-center gap-2">
                                    <span class="material-symbols-outlined">check_circle</span>
                                    <span><?php echo addslashes(__('booking_confirmation.confirmed_mail_sent')); ?></span>
                                </p>
                            </div>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="../index.php?action=track_booking" class="btn-primary w-full justify-center">
                                Xem danh sách
                            </a>
                            <?php else: ?>
                            <a href="../profile/bookings.php" class="btn-primary w-full justify-center">
                                <?php echo addslashes(__('booking_confirmation.view_my_bookings')); ?>
                            </a>
                            <?php endif; ?>
                        `;
                            }, 1500);
                        } else {
                            const message = result.message || "<?php echo addslashes(__('booking_confirmation.error_occurred')); ?>";
                            // Show error message
                            messageDiv.className = 'mb-6 p-4 rounded-lg bg-red-500/15 border border-red-500/30 text-red-400';
                            messageDiv.innerHTML = `
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined">error</span>
                                    <span>${message}</span>
                                </div>
                            `;
                            messageDiv.classList.remove('hidden');

                            // Re-enable button
                            confirmBtn.disabled = false;
                            confirmBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span><span><?php _e('booking_confirmation.confirm_btn'); ?></span>';
                        }
                    } catch (error) {
                        // Show error message
                        messageDiv.className = 'mb-6 p-4 rounded-lg bg-red-500/15 border border-red-500/30 text-red-400';
                        messageDiv.innerHTML = `
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined">error</span>
                        <span>${error.message}</span>
                    </div>
                `;
                        messageDiv.classList.remove('hidden');

                        // Re-enable button
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span><span><?php _e('booking_confirmation.confirm_btn'); ?></span>';
                    }
                });
            }
        });
    </script>

</body>

</html>
