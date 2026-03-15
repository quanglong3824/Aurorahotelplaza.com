<?php
// views/profile/booking-detail.php

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
<main class="flex h-full grow flex-col">
    <div class="glass-page-wrapper">
        <div class="w-full pt-[180px] pb-16 px-4">
            <div class="mx-auto max-w-5xl">
                <!-- Page Header -->
                <div class="mb-8 pl-4 border-l-4 border-accent">
                    <div class="flex items-center gap-4 mb-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="bookings.php" class="inline-flex items-center gap-2 text-white/70 hover:text-accent transition-colors text-sm">
                                <span class="material-symbols-outlined text-lg">arrow_back</span>
                                <?php _e('booking_detail.back_to_list'); ?>
                            </a>
                        <?php else: ?>
                            <a href="../index.php" class="inline-flex items-center gap-2 text-white/70 hover:text-accent transition-colors text-sm">
                                <span class="material-symbols-outlined text-lg">arrow_back</span>
                                <?php _e('booking_detail.back_to_home'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-3xl font-bold text-white uppercase tracking-wider"><?php _e('booking_detail.page_title'); ?></h1>
                    <p class="mt-1 text-white/60">
                        <?php _e('booking_detail.booking_code'); ?>: <span class="font-mono text-accent text-lg"><?php echo htmlspecialchars($booking_code); ?></span>
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-6 rounded-xl bg-red-500/10 border border-red-500/20 p-4 backdrop-blur-sm">
                        <div class="flex items-center">
                            <span class="material-symbols-outlined text-red-400 mr-2">error</span>
                            <p class="text-red-200"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                    <div class="glass-card p-6">
                        <h2 class="text-xl font-bold mb-4 text-white"><?php _e('booking_detail.lookup_title'); ?></h2>
                        <form method="GET" class="flex gap-4">
                            <input type="text" name="code" placeholder="<?php _e('booking_detail.lookup_placeholder'); ?>" value="<?php echo htmlspecialchars($booking_code); ?>"
                                class="flex-1 px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:outline-none focus:border-accent transition-all">
                            <button type="submit" class="px-6 py-3 bg-accent text-white font-bold rounded-xl hover:bg-accent/90 transition-all shadow-lg">
                                <span class="material-symbols-outlined mr-2 align-middle">search</span>
                                <?php _e('booking_detail.lookup_btn'); ?>
                            </button>
                        </form>
                    </div>
                <?php elseif ($booking): ?>
                    <!-- Booking Status -->
                    <div class="glass-card p-6 mb-8">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 border-b border-white/10 pb-6">
                            <h2 class="text-xl font-bold text-white uppercase tracking-wider"><?php _e('booking_detail.booking_status'); ?></h2>
                            <div class="flex gap-3">
                                <span class="px-4 py-1.5 text-sm font-bold rounded-full border <?php
                                    $status = $booking['status'];
                                    echo ($status == 'confirmed') ? 'bg-green-500/20 text-green-400 border-green-500/30' :
                                        (($status == 'pending') ? 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30' :
                                            (($status == 'cancelled') ? 'bg-red-500/20 text-red-400 border-red-500/30' : 'bg-gray-500/20 text-gray-400 border-gray-500/30'));
                                ?>">
                                    <?php echo $status_labels[$status]['label'] ?? $status; ?>
                                </span>
                                <?php if (!empty($booking['payment_status'])): ?>
                                    <span class="px-4 py-1.5 text-sm font-bold rounded-full border <?php
                                        $p_status = $booking['payment_status'];
                                        echo ($p_status == 'paid') ? 'bg-green-500/20 text-green-400 border-green-500/30' :
                                            (($p_status == 'unpaid') ? 'bg-red-500/20 text-red-400 border-red-500/30' : 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30');
                                    ?>">
                                        <?php echo $payment_labels[$p_status]['label'] ?? $p_status; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
                            <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                <span class="block text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.booked_date'); ?></span>
                                <p class="text-white font-mono"><?php echo date('m/d/Y H:i', strtotime($booking['created_at'])); ?></p>
                            </div>
                            <?php if ($booking['checked_in_at']): ?>
                                <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                    <span class="block text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.checked_in_date'); ?></span>
                                    <p class="text-white font-mono"><?php echo date('m/d/Y H:i', strtotime($booking['checked_in_at'])); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($booking['checked_out_at']): ?>
                                <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                    <span class="block text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.checked_out_date'); ?></span>
                                    <p class="text-white font-mono"><?php echo date('m/d/Y H:i', strtotime($booking['checked_out_at'])); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($booking['cancelled_at']): ?>
                                <div class="p-3 bg-red-500/10 rounded-lg border border-red-500/20">
                                    <span class="block text-red-300/70 text-xs uppercase tracking-wider mb-1"><?php _e('booking_detail.cancelled_date'); ?></span>
                                    <p class="text-red-300 font-mono"><?php echo date('m/d/Y H:i', strtotime($booking['cancelled_at'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Room Information -->
                            <div class="glass-card p-6">
                                <h3 class="text-xl font-bold mb-6 flex items-center gap-3 text-white border-b border-white/10 pb-4">
                                    <span class="material-symbols-outlined text-accent"><?php echo ($booking['category'] ?? '') === 'apartment' ? 'apartment' : 'hotel'; ?></span>
                                    <?php _e('booking_detail.room_info'); ?>
                                </h3>
                                <div class="space-y-6">
                                    <div>
                                        <h4 class="text-2xl font-bold text-accent"><?php echo htmlspecialchars($booking['type_name']); ?></h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white/5 p-4 rounded-xl border border-white/5">
                                        <div>
                                            <span class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.check_in'); ?></span>
                                            <p class="text-lg font-bold text-white"><?php echo date('m/d/Y', strtotime($booking['check_in_date'])); ?></p>
                                        </div>
                                        <div>
                                            <span class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.check_out'); ?></span>
                                            <p class="text-lg font-bold text-white"><?php echo date('m/d/Y', strtotime($booking['check_out_date'])); ?></p>
                                        </div>
                                        <div>
                                            <span class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.num_guests'); ?></span>
                                            <p class="text-white"><?php echo $booking['num_adults'] ?? 0; ?> <?php _e('booking_detail.adults'); ?> <?php echo ($booking['num_children'] ?? 0) ? ', ' . $booking['num_children'] . ' ' . __('booking_detail.children') : ''; ?></p>
                                        </div>
                                        <div>
                                            <span class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.num_nights'); ?></span>
                                            <p class="text-white"><?php echo $booking['total_nights'] ?? 0; ?> <?php _e('profile_bookings.nights'); ?></p>
                                        </div>
                                    </div>
                                    <?php if ($booking['special_requests']): ?>
                                        <div>
                                            <span class="font-bold text-white block mb-2"><?php _e('booking_detail.special_requests'); ?>:</span>
                                            <p class="p-4 bg-white/5 rounded-xl text-white/80 border border-white/10 italic text-sm">"<?php echo htmlspecialchars($booking['special_requests']); ?>"</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Guest Information -->
                            <div class="glass-card p-6">
                                <h3 class="text-xl font-bold mb-6 flex items-center gap-3 text-white border-b border-white/10 pb-4">
                                    <span class="material-symbols-outlined text-accent">person</span>
                                    <?php _e('booking_detail.guest_info'); ?>
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                        <span class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.full_name'); ?></span>
                                        <p class="text-lg font-bold text-white"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                                    </div>
                                    <div class="p-3 bg-white/5 rounded-lg border border-white/5">
                                        <span class="text-white/50 text-xs uppercase tracking-wider block mb-1"><?php _e('booking_detail.email'); ?></span>
                                        <p class="text-white font-mono text-sm"><?php echo htmlspecialchars($booking['guest_email'] ?? $booking['user_email'] ?? ''); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-8">
                            <!-- Price Breakdown -->
                            <div class="glass-card p-6">
                                <h3 class="text-xl font-bold mb-6 flex items-center gap-3 text-white border-b border-white/10 pb-4">
                                    <span class="material-symbols-outlined text-accent">receipt</span>
                                    <?php _e('booking_detail.price_detail'); ?>
                                </h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between text-white/80">
                                        <span><?php _e('booking_detail.room_price'); ?></span>
                                        <span class="font-mono"><?php echo number_format($booking['total_amount']); ?> VND</span>
                                    </div>
                                    <div class="border-t border-white/10 pt-4 mt-2">
                                        <div class="flex justify-between text-lg font-bold text-white">
                                            <span><?php _e('booking_detail.total'); ?></span>
                                            <span class="text-accent font-mono"><?php echo number_format($booking['total_amount']); ?> VND</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($can_cancel): ?>
                                <div class="glass-card p-6 border-red-500/20">
                                    <button onclick="openCancelModal()" class="w-full py-3 bg-red-500/20 hover:bg-red-500/40 text-red-400 font-bold rounded-xl transition-all border border-red-500/30">
                                        <?php _e('booking_detail.cancel_btn'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
