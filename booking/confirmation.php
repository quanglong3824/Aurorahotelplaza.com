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
        SELECT b.*, rt.type_name as room_type_name, rt.description as room_description,
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
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('booking_confirmation.title'); ?></title>
    <script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
    <link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet" />
    <script src="<?php echo asset('js/tailwind-config.js'); ?>?v=<?php echo time(); ?>"></script>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/liquid-glass.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./assets/css/booking.css?v=<?php echo time(); ?>">
</head>

<body class="booking-page">
    <?php include '../includes/header.php'; ?>
    <main class="booking-main" style="align-items: center;">
        <div class="booking-container" style="max-width: 40rem;">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="icon-badge"
                    style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(16, 185, 129, 0.1)); border-color: rgba(16, 185, 129, 0.3);">
                    <span class="material-symbols-outlined" style="color: #10b981;">check_circle</span>
                </div>
                <h1 class="booking-title"><?php _e('booking_confirmation.page_title'); ?></h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="booking-card" style="border-color: rgba(239, 68, 68, 0.3);">
                    <div class="flex items-center gap-3 text-red-400">
                        <span class="material-symbols-outlined">error</span>
                        <span><?php echo $error; ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="booking-card">
                    <div id="confirmationMessage" class="hidden mb-6 p-4 rounded-lg"></div>

                    <div class="space-y-4 mb-8">
                        <div class="flex justify-between items-center py-2 border-b border-white/10">
                            <span class="text-white/70"><?php _e('booking_confirmation.booking_code'); ?>:</span>
                            <span class="font-semibold text-accent"><?php echo $booking['booking_code']; ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/10">
                            <span class="text-white/70"><?php _e('booking_confirmation.room_type'); ?>:</span>
                            <span class="font-semibold"><?php echo $booking['room_type_name']; ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/10">
                            <span class="text-white/70"><?php _e('booking_confirmation.check_in'); ?>:</span>
                            <span
                                class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/10">
                            <span class="text-white/70"><?php _e('booking_confirmation.check_out'); ?>:</span>
                            <span
                                class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/10">
                            <span class="text-white/70"><?php _e('booking_confirmation.total_amount'); ?>:</span>
                            <span
                                class="font-bold text-xl text-accent"><?php echo number_format($booking['total_amount']); ?>
                                VNĐ</span>
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
                    </div>

                    <?php if ($booking['status'] === 'pending'): ?>
                        <div class="grid grid-cols-2 gap-3">
                            <button id="confirmBookingBtn" class="btn-primary">
                                <span class="material-symbols-outlined">check_circle</span>
                                <span><?php _e('booking_confirmation.confirm_btn'); ?></span>
                            </button>
                            <a href="../profile/bookings.php" class="btn-secondary text-center">
                                <?php _e('booking_confirmation.back'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="bg-green-500/15 border border-green-500/30 rounded-lg p-4 mb-4">
                            <p class="text-green-400 flex items-center gap-2">
                                <span class="material-symbols-outlined">check_circle</span>
                                <span><?php _e('booking_confirmation.success_message'); ?></span>
                            </p>
                        </div>
                        <a href="../profile/bookings.php" class="btn-primary w-full justify-center">
                            <?php _e('booking_confirmation.view_bookings'); ?>
                        </a>
                    <?php endif; ?>
                </div>
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
                    confirmBtn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span><span>Đang xử lý...</span>';

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
                            statusSpan.textContent = 'Đã xác nhận';

                            // Hide button and show success state
                            setTimeout(() => {
                                confirmBtn.parentElement.innerHTML = `
                            <div class="bg-green-500/15 border border-green-500/30 rounded-lg p-4 mb-4">
                                <p class="text-green-400 flex items-center gap-2">
                                    <span class="material-symbols-outlined">check_circle</span>
                                    <span>Đặt phòng của bạn đã được xác nhận. Email xác nhận đã được gửi đến địa chỉ email của bạn.</span>
                                </p>
                            </div>
                            <a href="../profile/bookings.php" class="btn-primary w-full justify-center">
                                Xem danh sách đặt phòng
                            </a>
                        `;
                            }, 1500);
                        } else {
                            throw new Error(result.message || 'Có lỗi xảy ra');
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
                        confirmBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span><span>Xác nhận</span>';
                    }
                });
            }
        });
    </script>

</body>

</html>