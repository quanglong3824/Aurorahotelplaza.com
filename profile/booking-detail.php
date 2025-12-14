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
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php _e('booking_detail.title'); ?></title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
<link href="../assets/css/fonts.css" rel="stylesheet"/>
    
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="bookings.php" class="inline-flex items-center gap-2 text-text-secondary-light dark:text-text-secondary-dark hover:text-accent transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                    <?php _e('booking_detail.back_to_list'); ?>
                </a>
                <?php else: ?>
                <a href="../index.php" class="inline-flex items-center gap-2 text-text-secondary-light dark:text-text-secondary-dark hover:text-accent transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                    <?php _e('booking_detail.back_to_home'); ?>
                </a>
                <?php endif; ?>
            </div>
            <h1 class="text-3xl font-bold text-text-primary-light dark:text-text-primary-dark">
                <?php _e('booking_detail.page_title'); ?>
            </h1>
            <p class="mt-2 text-text-secondary-light dark:text-text-secondary-dark">
                <?php _e('booking_detail.booking_code'); ?>: <span class="font-mono text-accent"><?php echo htmlspecialchars($booking_code); ?></span>
            </p>
        </div>

        <?php if ($error): ?>
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
            <div class="flex">
                <span class="material-symbols-outlined text-red-400 mr-2">error</span>
                <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
        
        <!-- Booking Code Lookup Form -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-bold mb-4"><?php _e('booking_detail.lookup_title'); ?></h2>
            <form method="GET" class="flex gap-4">
                <input type="text" name="code" placeholder="<?php _e('booking_detail.lookup_placeholder'); ?>" 
                       value="<?php echo htmlspecialchars($booking_code); ?>"
                       class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                <button type="submit" class="px-6 py-3 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors">
                    <span class="material-symbols-outlined mr-2">search</span>
                    <?php _e('booking_detail.lookup_btn'); ?>
                </button>
            </form>
        </div>
        
        <?php elseif ($booking): ?>
        
        <!-- Booking Status -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold"><?php _e('booking_detail.booking_status'); ?></h2>
                <div class="flex gap-2">
                    <span class="px-4 py-2 text-sm font-medium rounded-full <?php echo $status_labels[$booking['status']]['color']; ?>">
                        <?php echo $status_labels[$booking['status']]['label']; ?>
                    </span>
                    <?php if ($booking['payment_status']): ?>
                    <span class="px-4 py-2 text-sm font-medium rounded-full <?php echo $payment_labels[$booking['payment_status']]['color']; ?>">
                        <?php echo $payment_labels[$booking['payment_status']]['label']; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.booked_date'); ?>:</span>
                    <p><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></p>
                </div>
                <?php if ($booking['checked_in_at']): ?>
                <div>
                    <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.checked_in_date'); ?>:</span>
                    <p><?php echo date('d/m/Y H:i', strtotime($booking['checked_in_at'])); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($booking['checked_out_at']): ?>
                <div>
                    <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.checked_out_date'); ?>:</span>
                    <p><?php echo date('d/m/Y H:i', strtotime($booking['checked_out_at'])); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($booking['cancelled_at']): ?>
                <div>
                    <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.cancelled_date'); ?>:</span>
                    <p><?php echo date('d/m/Y H:i', strtotime($booking['cancelled_at'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($booking['cancellation_reason']): ?>
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.cancel_reason'); ?>:</span>
                <p class="mt-1"><?php echo htmlspecialchars($booking['cancellation_reason']); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Booking Info -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Room Information -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">
                            <?php echo $booking['category'] === 'apartment' ? 'apartment' : 'hotel'; ?>
                        </span>
                        <?php _e('booking_detail.room_info'); ?>
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-lg font-semibold text-accent"><?php echo htmlspecialchars($booking['type_name']); ?></h4>
                            <?php if ($booking['description']): ?>
                            <p class="text-text-secondary-light dark:text-text-secondary-dark mt-1">
                                <?php echo htmlspecialchars($booking['description']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.check_in'); ?>:</span>
                                <p class="text-lg font-semibold"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.check_out'); ?>:</span>
                                <p class="text-lg font-semibold"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.num_guests'); ?>:</span>
                                <p><?php echo $booking['num_adults']; ?> <?php _e('booking_detail.adults'); ?><?php echo $booking['num_children'] ? ', ' . $booking['num_children'] . ' ' . __('booking_detail.children') : ''; ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.num_nights'); ?>:</span>
                                <p><?php echo $booking['total_nights']; ?> <?php _e('profile_bookings.nights'); ?></p>
                            </div>
                            <?php if ($booking['room_number']): ?>
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.room_number'); ?>:</span>
                                <p class="text-lg font-semibold text-accent"><?php echo $booking['room_number']; ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.floor'); ?>:</span>
                                <p><?php _e('booking_detail.floor'); ?> <?php echo $booking['floor']; ?>, <?php echo $booking['building']; ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($booking['amenities']): ?>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.amenities'); ?>:</span>
                            <p class="mt-1"><?php echo htmlspecialchars($booking['amenities']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['special_requests']): ?>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.special_requests'); ?>:</span>
                            <p class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <?php echo htmlspecialchars($booking['special_requests']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Guest Information -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">person</span>
                        <?php _e('booking_detail.guest_info'); ?>
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.full_name'); ?>:</span>
                            <p class="text-lg font-semibold"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                        </div>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.email'); ?>:</span>
                            <p><?php echo htmlspecialchars($booking['guest_email']); ?></p>
                        </div>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.phone'); ?>:</span>
                            <p><?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                        </div>
                        <?php if ($booking['guest_id_number']): ?>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.id_number'); ?>:</span>
                            <p><?php echo htmlspecialchars($booking['guest_id_number']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Price Breakdown -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">receipt</span>
                        <?php _e('booking_detail.price_detail'); ?>
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span><?php _e('booking_detail.room_price'); ?> (<?php echo $booking['total_nights']; ?> <?php _e('profile_bookings.nights'); ?>)</span>
                            <span><?php echo number_format($booking['room_price'] * $booking['total_nights']); ?> VNĐ</span>
                        </div>
                        
                        <?php if ($booking['service_charges'] > 0): ?>
                        <div class="flex justify-between">
                            <span><?php _e('booking_detail.service_charges'); ?></span>
                            <span><?php echo number_format($booking['service_charges']); ?> VNĐ</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span><?php _e('booking_detail.discount'); ?></span>
                            <span>-<?php echo number_format($booking['discount_amount']); ?> VNĐ</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['points_used'] > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span><?php _e('booking_detail.points_used'); ?></span>
                            <span>-<?php echo $booking['points_used']; ?> <?php _e('profile_loyalty.points'); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <hr class="border-gray-300 dark:border-gray-600">
                        
                        <div class="flex justify-between text-lg font-bold">
                            <span><?php _e('booking_detail.total'); ?></span>
                            <span class="text-accent"><?php echo number_format($booking['total_amount']); ?> VNĐ</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <?php if ($booking['payment_method']): ?>
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">payment</span>
                        <?php _e('booking_detail.payment_info'); ?>
                    </h3>
                    
                    <div class="space-y-3">
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.payment_method'); ?>:</span>
                            <p class="capitalize"><?php echo $booking['payment_method']; ?></p>
                        </div>
                        
                        <?php if ($booking['transaction_id']): ?>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.transaction_id'); ?>:</span>
                            <p class="font-mono text-sm"><?php echo htmlspecialchars($booking['transaction_id']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['paid_at']): ?>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark"><?php _e('booking_detail.paid_at'); ?>:</span>
                            <p><?php echo date('d/m/Y H:i', strtotime($booking['paid_at'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4"><?php _e('booking_detail.actions'); ?></h3>
                    
                    <div class="space-y-3">
                        <?php if ($booking['status'] === 'pending'): ?>
                        <a href="../booking/confirmation.php?booking_code=<?php echo urlencode($booking['booking_code']); ?>" 
                           class="w-full px-4 py-3 bg-gradient-to-r from-primary to-purple-600 text-white rounded-lg hover:opacity-90 transition-all flex items-center justify-center gap-2 font-semibold">
                            <span class="material-symbols-outlined">check_circle</span>
                            <?php _e('booking_detail.confirm_booking'); ?>
                        </a>
                        <?php endif; ?>
                        
                        <button onclick="window.print()" 
                                class="w-full px-4 py-3 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors">
                            <span class="material-symbols-outlined mr-2">print</span>
                            <?php _e('booking_detail.print'); ?>
                        </button>
                        
                        <button onclick="shareBooking()" 
                                class="w-full px-4 py-3 border-2 border-accent text-accent rounded-lg hover:bg-accent/5 transition-colors">
                            <span class="material-symbols-outlined mr-2">share</span>
                            <?php _e('booking_detail.share'); ?>
                        </button>
                        
                        <!-- QR Code Button -->
                        <a href="view-qrcode.php?id=<?php echo $booking['booking_id']; ?>" 
                           class="w-full px-4 py-3 border-2 border-green-500 text-green-600 rounded-lg hover:bg-green-50 transition-colors flex items-center justify-center">
                            <span class="material-symbols-outlined mr-2">qr_code</span>
                            <?php _e('booking_detail.view_qr'); ?>
                        </a>
                        
                        <!-- Cancellation Policy & Refund Info -->
                        <?php if ($can_cancel && $refund_info): ?>
                        <div class="space-y-3">
                            <!-- Refund Information -->
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <h4 class="font-bold text-sm mb-2 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">info</span>
                                    <?php _e('booking_detail.refund_info'); ?>
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span><?php _e('booking_detail.time_remaining'); ?>:</span>
                                        <span class="font-bold"><?php echo round($refund_info['days_until_checkin'], 1); ?> <?php _e('booking_detail.days'); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><?php _e('booking_detail.total_booking'); ?>:</span>
                                        <span class="font-bold"><?php echo number_format($refund_info['total_amount']); ?> VNĐ</span>
                                    </div>
                                    <div class="flex justify-between text-green-600 dark:text-green-400">
                                        <span><?php _e('booking_detail.refund_amount'); ?>:</span>
                                        <span class="font-bold text-lg"><?php echo number_format($refund_info['refund_amount']); ?> VNĐ</span>
                                    </div>
                                    <?php if ($refund_info['processing_fee'] > 0): ?>
                                    <div class="flex justify-between text-xs text-gray-600">
                                        <span><?php _e('booking_detail.processing_fee'); ?>:</span>
                                        <span>-<?php echo number_format($refund_info['processing_fee']); ?> VNĐ</span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="pt-2 border-t border-blue-200 dark:border-blue-800">
                                        <p class="text-xs text-blue-800 dark:text-blue-200">
                                            <?php echo $refund_info['policy_message']; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Cancel Button -->
                            <button onclick="showCancelModal()" 
                                    class="w-full px-4 py-3 border-2 border-red-500 text-red-600 rounded-lg hover:bg-red-50 transition-colors font-semibold">
                                <span class="material-symbols-outlined mr-2">cancel</span>
                                <?php _e('booking_detail.cancel_booking'); ?>
                            </button>
                        </div>
                        <?php elseif ($booking['status'] === 'confirmed' || $booking['status'] === 'pending'): ?>
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-sm text-yellow-800 dark:text-yellow-200">
                            <span class="material-symbols-outlined text-sm mr-1">info</span>
                            <?php _e('booking_detail.cannot_cancel'); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Booking History Timeline -->
                <?php if (!empty($booking_history)): ?>
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">history</span>
                        <?php _e('booking_detail.history'); ?>
                    </h3>
                    
                    <div class="space-y-4">
                        <?php foreach ($booking_history as $history): ?>
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-3 h-3 bg-accent rounded-full"></div>
                                <?php if ($history !== end($booking_history)): ?>
                                <div class="w-0.5 h-full bg-gray-300 dark:bg-gray-600 mt-1"></div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 pb-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-1 text-xs font-medium rounded <?php echo $status_labels[$history['new_status']]['color'] ?? 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $status_labels[$history['new_status']]['label'] ?? $history['new_status']; ?>
                                    </span>
                                    <span class="text-xs text-text-secondary-light dark:text-text-secondary-dark">
                                        <?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?>
                                    </span>
                                </div>
                                <?php if ($history['changed_by_name']): ?>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                    Bởi: <?php echo htmlspecialchars($history['changed_by_name']); ?>
                                </p>
                                <?php endif; ?>
                                <?php if ($history['notes']): ?>
                                <p class="text-sm mt-1"><?php echo htmlspecialchars($history['notes']); ?></p>
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
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-600">cancel</span>
                        Xác nhận hủy đặt phòng
                    </h3>
                    <button onclick="closeCancelModal()" class="text-gray-500 hover:text-gray-700">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <!-- Refund Summary -->
                <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg border border-blue-200 dark:border-blue-700">
                    <h4 class="font-bold mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">payments</span>
                        Thông tin hoàn tiền
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Tổng tiền đặt phòng:</span>
                            <span class="font-bold"><?php echo number_format($refund_info['total_amount']); ?> VNĐ</span>
                        </div>
                        <?php if ($refund_info['processing_fee'] > 0): ?>
                        <div class="flex justify-between text-red-600">
                            <span>Phí xử lý (5%):</span>
                            <span>-<?php echo number_format($refund_info['processing_fee']); ?> VNĐ</span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-lg font-bold text-green-600 pt-2 border-t border-blue-300">
                            <span>Số tiền hoàn lại:</span>
                            <span><?php echo number_format($refund_info['refund_amount']); ?> VNĐ</span>
                        </div>
                        <div class="pt-2 text-xs text-blue-800 dark:text-blue-200">
                            <p><strong>Chính sách:</strong> <?php echo $refund_info['policy_message']; ?></p>
                            <p class="mt-1"><strong>Thời gian hoàn tiền:</strong> 5-7 ngày làm việc</p>
                        </div>
                    </div>
                </div>
                
                <!-- Cancellation Policy -->
                <div class="mb-6">
                    <?php echo getRefundPolicyText(); ?>
                </div>
                
                <!-- Reason Input -->
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Lý do hủy phòng (không bắt buộc)</label>
                    <textarea id="cancelReason" rows="3" 
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent"
                              placeholder="VD: Thay đổi kế hoạch, có việc đột xuất..."></textarea>
                </div>
                
                <!-- Confirmation Checkbox -->
                <div class="mb-6">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" id="confirmCancel" class="mt-1">
                        <span class="text-sm">
                            Tôi đã đọc và đồng ý với chính sách hủy phòng. Tôi hiểu rằng số tiền hoàn lại sẽ là 
                            <strong class="text-green-600"><?php echo number_format($refund_info['refund_amount']); ?> VNĐ</strong>
                            và sẽ được xử lý trong vòng 5-7 ngày làm việc.
                        </span>
                    </label>
                </div>
                
                <!-- Actions -->
                <div class="flex gap-3">
                    <button onclick="closeCancelModal()" 
                            class="flex-1 px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Quay lại
                    </button>
                    <button onclick="confirmCancellation()" 
                            class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold">
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
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> Đang xử lý...';
    
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
            alert(' Lỗi: ' + data.message);
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

// Print styles
const printStyles = `
    <style>
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
`;
document.head.insertAdjacentHTML('beforeend', printStyles);
</script>
</body>
</html>