<?php
// views/profile/bookings.php

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
            <div class="mx-auto max-w-7xl">
                <!-- Page Header -->
                <div class="mb-8 pl-4 border-l-4 border-accent">
                    <h1 class="text-3xl font-bold text-white mb-2"><?php _e('profile_bookings.title'); ?></h1>
                    <p class="text-white/60"><?php _e('profile_bookings.subtitle'); ?></p>
                </div>

                <!-- Stats Summary -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <div class="glass-card p-4 text-center">
                        <p class="text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('profile_bookings.stat_total'); ?></p>
                        <p class="text-2xl font-bold text-white"><?php echo $stats['total_bookings'] ?? 0; ?></p>
                    </div>
                    <div class="glass-card p-4 text-center">
                        <p class="text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('profile_bookings.stat_pending'); ?></p>
                        <p class="text-2xl font-bold text-yellow-400"><?php echo $stats['pending_bookings'] ?? 0; ?></p>
                    </div>
                    <div class="glass-card p-4 text-center">
                        <p class="text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('profile_bookings.stat_completed'); ?></p>
                        <p class="text-2xl font-bold text-green-400"><?php echo $stats['completed_bookings'] ?? 0; ?></p>
                    </div>
                    <div class="glass-card p-4 text-center">
                        <p class="text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('profile_bookings.stat_cancelled'); ?></p>
                        <p class="text-2xl font-bold text-red-400"><?php echo $stats['cancelled_bookings'] ?? 0; ?></p>
                    </div>
                    <div class="glass-card p-4 text-center col-span-2 md:col-span-1">
                        <p class="text-white/50 text-xs uppercase tracking-wider mb-1"><?php _e('profile_bookings.stat_spent'); ?></p>
                        <p class="text-xl font-bold text-accent"><?php echo number_format($stats['total_spent'] ?? 0); ?> VND</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="glass-card p-6 mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div class="space-y-2">
                            <label class="text-xs text-white/50 uppercase ml-1"><?php _e('profile_bookings.filter_status'); ?></label>
                            <select name="status" class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-accent transition-all">
                                <option value="" class="bg-[#1a1a1a]"><?php _e('common.all'); ?></option>
                                <?php foreach($status_labels as $val => $info): ?>
                                    <option value="<?php echo $val; ?>" <?php echo $filters['status'] == $val ? 'selected' : ''; ?> class="bg-[#1a1a1a]"><?php echo $info['label']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs text-white/50 uppercase ml-1"><?php _e('profile_bookings.filter_payment'); ?></label>
                            <select name="payment_status" class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-accent transition-all">
                                <option value="" class="bg-[#1a1a1a]"><?php _e('common.all'); ?></option>
                                <?php foreach($payment_labels as $val => $info): ?>
                                    <option value="<?php echo $val; ?>" <?php echo $filters['payment_status'] == $val ? 'selected' : ''; ?> class="bg-[#1a1a1a]"><?php echo $info['label']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs text-white/50 uppercase ml-1"><?php _e('profile_bookings.filter_search'); ?></label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="<?php _e('profile_bookings.search_placeholder'); ?>"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-accent transition-all">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-accent hover:bg-accent-hover text-white py-2 rounded-xl transition-all font-bold">
                                <?php _e('common.filter'); ?>
                            </button>
                            <a href="bookings.php" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl transition-all flex items-center justify-center">
                                <span class="material-symbols-outlined">refresh</span>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Bookings Table/List -->
                <?php if (empty($bookings)): ?>
                    <div class="glass-card p-12 text-center">
                        <span class="material-symbols-outlined text-6xl text-white/20 mb-4">history</span>
                        <p class="text-white/60 mb-6"><?php _e('profile_bookings.no_results'); ?></p>
                        <a href="../rooms.php" class="btn-glass-gold px-8 py-3"><?php _e('nav.book_now'); ?></a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach($bookings as $booking): ?>
                            <div class="glass-card overflow-hidden hover:bg-white/5 transition-all group">
                                <div class="flex flex-col md:flex-row">
                                    <div class="w-full md:w-48 h-48 md:h-auto overflow-hidden">
                                        <img src="../<?php echo $booking['thumbnail'] ?: 'assets/img/room-placeholder.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($booking['type_name']); ?>"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    </div>
                                    <div class="flex-1 p-6 flex flex-col md:flex-row justify-between gap-6">
                                        <div class="space-y-3">
                                            <div class="flex items-center gap-3">
                                                <span class="booking-code-tag">#<?php echo $booking['booking_code']; ?></span>
                                                <h3 class="text-xl font-bold text-white group-hover:text-accent transition-colors">
                                                    <?php echo htmlspecialchars($booking['type_name']); ?>
                                                </h3>
                                            </div>
                                            <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm text-white/60">
                                                <div class="flex items-center gap-2">
                                                    <span class="material-symbols-outlined text-lg">calendar_today</span>
                                                    <span><?php _e('common.check_in'); ?>: <b><?php echo date('m/d/Y', strtotime($booking['check_in_date'])); ?></b></span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="material-symbols-outlined text-lg">logout</span>
                                                    <span><?php _e('common.check_out'); ?>: <b><?php echo date('m/d/Y', strtotime($booking['check_out_date'])); ?></b></span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="material-symbols-outlined text-lg">person</span>
                                                    <span><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="material-symbols-outlined text-lg">payments</span>
                                                    <span><?php echo number_format($booking['total_amount']); ?> VND</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-row md:flex-col justify-between items-end md:items-end gap-4">
                                            <div class="flex flex-col items-end gap-2">
                                                <span class="status-badge <?php echo $status_labels[$booking['status']]['color'] ?? 'bg-gray-100'; ?>">
                                                    <?php echo $status_labels[$booking['status']]['label'] ?? $booking['status']; ?>
                                                </span>
                                                <span class="status-badge <?php echo $payment_labels[$booking['payment_status']]['color'] ?? 'bg-gray-100'; ?>">
                                                    <?php echo $payment_labels[$booking['payment_status']]['label'] ?? $booking['payment_status']; ?>
                                                </span>
                                            </div>
                                            <a href="booking-detail.php?code=<?php echo $booking['booking_code']; ?>" 
                                               class="btn-details flex items-center gap-2">
                                                <?php _e('common.details'); ?>
                                                <span class="material-symbols-outlined text-sm">chevron_right</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-12 flex justify-center gap-2">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="w-10 h-10 flex items-center justify-center rounded-xl transition-all <?php echo $page == $i ? 'bg-accent text-white font-bold' : 'bg-white/5 text-white/60 hover:bg-white/10'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
