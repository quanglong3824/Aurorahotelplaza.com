<?php
// views/profile/bookings.php

$status_labels = [
    'pending' => ['label' => __('booking_status.pending'), 'color' => 'bg-amber-500/10 text-amber-500 border border-amber-500/20'],
    'confirmed' => ['label' => __('booking_status.confirmed'), 'color' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20'],
    'checked_in' => ['label' => __('booking_status.checked_in'), 'color' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'],
    'checked_out' => ['label' => __('booking_status.checked_out'), 'color' => 'bg-slate-500/10 text-slate-400 border border-slate-500/20'],
    'cancelled' => ['label' => __('booking_status.cancelled'), 'color' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20'],
    'no_show' => ['label' => __('booking_status.no_show'), 'color' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20']
];

$payment_labels = [
    'unpaid' => ['label' => __('payment_status.unpaid'), 'color' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20'],
    'partial' => ['label' => __('payment_status.partial'), 'color' => 'bg-amber-500/10 text-amber-500 border border-amber-500/20'],
    'paid' => ['label' => __('payment_status.paid'), 'color' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'],
    'refunded' => ['label' => __('payment_status.refunded'), 'color' => 'bg-slate-500/10 text-slate-400 border border-slate-500/20']
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
                            <div class="glass-card overflow-hidden group mb-6">
                                <div class="flex flex-col lg:flex-row min-h-[200px]">
                                    <!-- Room Image -->
                                    <div class="w-full lg:w-64 relative overflow-hidden">
                                        <img src="../<?php echo $booking['thumbnail'] ?: 'assets/img/room-placeholder.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($booking['type_name']); ?>"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                        <div class="absolute top-4 left-4">
                                            <span class="booking-code-tag shadow-lg">#<?php echo $booking['booking_code']; ?></span>
                                        </div>
                                    </div>

                                    <!-- Content Info -->
                                    <div class="flex-1 p-6 flex flex-col justify-between">
                                        <div>
                                            <div class="flex justify-between items-start mb-4">
                                                <h3 class="text-2xl font-bold text-white group-hover:text-accent transition-colors">
                                                    <?php echo htmlspecialchars($booking['type_name']); ?>
                                                </h3>
                                                <div class="flex gap-2">
                                                    <span class="status-badge <?php echo $status_labels[$booking['status']]['color'] ?? 'bg-gray-100'; ?>">
                                                        <?php echo $status_labels[$booking['status']]['label'] ?? $booking['status']; ?>
                                                    </span>
                                                    <span class="status-badge <?php echo $payment_labels[$booking['payment_status']]['color'] ?? 'bg-gray-100'; ?>">
                                                        <?php echo $payment_labels[$booking['payment_status']]['label'] ?? $booking['payment_status']; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                                <!-- Dates -->
                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-3 text-white/50">
                                                        <span class="material-symbols-outlined text-xl">calendar_month</span>
                                                        <div class="text-sm">
                                                            <p class="uppercase text-[10px] tracking-widest"><?php _e('common.check_in'); ?></p>
                                                            <p class="text-white font-bold"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-3 text-white/50">
                                                        <span class="material-symbols-outlined text-xl">logout</span>
                                                        <div class="text-sm">
                                                            <p class="uppercase text-[10px] tracking-widest"><?php _e('common.check_out'); ?></p>
                                                            <p class="text-white font-bold"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Guest & Price -->
                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-3 text-white/50">
                                                        <span class="material-symbols-outlined text-xl">account_circle</span>
                                                        <div class="text-sm">
                                                            <p class="uppercase text-[10px] tracking-widest"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                                                            <p class="text-accent font-black text-lg"><?php echo number_format($booking['total_amount']); ?> <span class="text-xs">VND</span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-6 flex justify-end border-t border-white/5 pt-4 gap-3">
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <a href="booking-detail.php?code=<?php echo $booking['booking_code']; ?>" 
                                                   class="bg-accent/10 hover:bg-accent/20 text-accent border border-accent/30 px-4 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all animate-pulse">
                                                    <span class="material-symbols-outlined text-sm">verified</span>
                                                    <span>XÁC NHẬN NGAY</span>
                                                </a>
                                            <?php endif; ?>
                                            <a href="booking-detail.php?code=<?php echo $booking['booking_code']; ?>" 
                                               class="btn-details group/btn flex items-center gap-2 px-8 py-2.5">
                                                <span><?php _e('common.details'); ?></span>
                                                <span class="material-symbols-outlined text-sm group-hover/btn:translate-x-1 transition-transform">arrow_forward</span>
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
