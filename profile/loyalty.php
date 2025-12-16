<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once '../config/database.php';
require_once '../helpers/language.php';
initLanguage();

try {
    $db = getDB();

    // Get user loyalty information
    $stmt = $db->prepare("
        SELECT ul.*, mt.tier_name, mt.tier_level, mt.discount_percentage, mt.benefits, mt.color_code, mt.min_points,
               (SELECT MIN(min_points) FROM membership_tiers WHERE min_points > ul.current_points) as next_tier_points
        FROM user_loyalty ul
        LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
        WHERE ul.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $loyalty = $stmt->fetch();

    // If no loyalty record exists, create one
    if (!$loyalty) {
        $stmt = $db->prepare("INSERT INTO user_loyalty (user_id, current_points, lifetime_points) VALUES (?, 0, 0)");
        $stmt->execute([$_SESSION['user_id']]);

        // Get the created record
        $stmt = $db->prepare("
            SELECT ul.*, mt.tier_name, mt.tier_level, mt.discount_percentage, mt.benefits, mt.color_code, mt.min_points,
                   (SELECT MIN(min_points) FROM membership_tiers WHERE min_points > ul.current_points) as next_tier_points
            FROM user_loyalty ul
            LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
            WHERE ul.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $loyalty = $stmt->fetch();
    }

    // Get recent points transactions
    $stmt = $db->prepare("
        SELECT * FROM points_transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $transactions = $stmt->fetchAll();

    // Get all membership tiers for progress display
    $stmt = $db->prepare("SELECT * FROM membership_tiers ORDER BY tier_level");
    $stmt->execute();
    $all_tiers = $stmt->fetchAll();

    // Get user's booking statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status IN ('confirmed', 'checked_out') THEN total_amount ELSE 0 END) as total_spent,
            COUNT(CASE WHEN status IN ('confirmed', 'checked_out') THEN 1 END) as completed_bookings
        FROM bookings 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $booking_stats = $stmt->fetch();

} catch (Exception $e) {
    error_log("Loyalty page error: " . $e->getMessage());
    $error = "Có lỗi xảy ra khi tải thông tin điểm thưởng.";
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('profile_loyalty.title'); ?></title>
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
                    <div class="mx-auto max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-8 pl-4 border-l-4 border-accent">
                            <h1 class="text-3xl font-bold text-white uppercase tracking-wider">
                                <?php _e('profile_loyalty.page_title'); ?>
                            </h1>
                            <p class="mt-1 text-white/60">
                                <?php _e('profile_loyalty.page_subtitle'); ?>
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

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- Left Column: Points & Tier Info -->
                            <div class="lg:col-span-2 space-y-6">

                                <!-- Current Points Card -->
                                <div
                                    class="glass-card bg-gradient-to-br from-accent/90 to-amber-600/90 rounded-2xl shadow-xl p-8 text-white relative overflow-hidden group">
                                    <div
                                        class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity">
                                    </div>
                                    <div class="relative z-10">
                                        <div class="flex items-center justify-between mb-6">
                                            <div>
                                                <h2 class="text-2xl font-bold mb-1">
                                                    <?php _e('profile_loyalty.current_points'); ?></h2>
                                                <p class="text-white/80 text-sm">
                                                    <?php _e('profile_loyalty.available_points'); ?></p>
                                            </div>
                                            <div class="p-3 bg-white/20 rounded-full backdrop-blur-md">
                                                <span class="material-symbols-outlined text-4xl text-white">stars</span>
                                            </div>
                                        </div>
                                        <div class="text-5xl font-bold mb-3 tracking-tight">
                                            <?php echo number_format($loyalty['current_points'] ?? 0); ?>
                                            <span
                                                class="text-lg font-normal opacity-80"><?php _e('profile_loyalty.points'); ?></span>
                                        </div>
                                        <div class="text-sm text-white/70 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-sm">history</span>
                                            <?php _e('profile_loyalty.total_lifetime'); ?>:
                                            <?php echo number_format($loyalty['lifetime_points'] ?? 0); ?>
                                            <?php _e('profile_loyalty.points'); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Membership Tier Card -->
                                <div class="glass-card p-6">
                                    <h3
                                        class="text-xl font-bold mb-6 flex items-center gap-3 text-white border-b border-white/10 pb-4">
                                        <span class="material-symbols-outlined text-accent">workspace_premium</span>
                                        <?php _e('profile_loyalty.membership_tier'); ?>
                                    </h3>

                                    <?php if ($loyalty['tier_name']): ?>
                                        <div class="mb-6">
                                            <div class="flex items-center gap-6 mb-6">
                                                <div class="w-20 h-20 rounded-full flex items-center justify-center text-white font-bold text-3xl shadow-lg ring-4 ring-white/10"
                                                    style="background: <?php echo $loyalty['color_code']; ?>">
                                                    <?php echo strtoupper(substr($loyalty['tier_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <h4 class="text-3xl font-bold mb-1"
                                                        style="color: <?php echo $loyalty['color_code']; ?>">
                                                        <?php echo $loyalty['tier_name']; ?>
                                                    </h4>
                                                    <p class="text-white/60">
                                                        <?php echo str_replace('{:percent}', $loyalty['discount_percentage'], __('profile_loyalty.discount_for_all')); ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <?php if ($loyalty['benefits']): ?>
                                                <div class="bg-white/5 border border-white/10 rounded-xl p-5 mb-6">
                                                    <h5 class="font-bold text-white mb-2 flex items-center gap-2">
                                                        <span
                                                            class="material-symbols-outlined text-accent text-sm">card_giftcard</span>
                                                        <?php _e('profile_loyalty.member_benefits'); ?>:
                                                    </h5>
                                                    <p class="text-sm text-white/70 leading-relaxed">
                                                        <?php echo htmlspecialchars($loyalty['benefits']); ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-8">
                                            <div
                                                class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <span
                                                    class="material-symbols-outlined text-5xl text-white/20">card_membership</span>
                                            </div>
                                            <p class="text-white/50">
                                                <?php _e('profile_loyalty.no_tier'); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Progress to Next Tier -->
                                    <?php if ($loyalty['next_tier_points']): ?>
                                        <div class="mt-6 pt-6 border-t border-white/10">
                                            <div class="flex justify-between text-sm mb-3">
                                                <span
                                                    class="text-white/70"><?php _e('profile_loyalty.progress_next_tier'); ?></span>
                                                <span
                                                    class="text-white font-mono"><?php echo number_format($loyalty['current_points']); ?>
                                                    / <?php echo number_format($loyalty['next_tier_points']); ?></span>
                                            </div>
                                            <div class="w-full bg-slate-700/50 rounded-full h-3 overflow-hidden">
                                                <div class="bg-accent h-3 rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(var(--accent-rgb),0.5)]"
                                                    style="width: <?php echo min(100, ($loyalty['current_points'] / $loyalty['next_tier_points']) * 100); ?>%">
                                                </div>
                                            </div>
                                            <p class="text-xs text-white/50 mt-3 text-right italic">
                                                <?php echo str_replace('{:points}', '<span class="text-accent font-bold">' . number_format($loyalty['next_tier_points'] - $loyalty['current_points']) . '</span>', __('profile_loyalty.points_to_next')); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Tier Progress -->
                                <div class="glass-card p-6">
                                    <h3
                                        class="text-xl font-bold mb-6 text-white flex items-center gap-3 border-b border-white/10 pb-4">
                                        <span class="material-symbols-outlined text-accent">leaderboard</span>
                                        <?php _e('profile_loyalty.all_tiers'); ?>
                                    </h3>
                                    <div class="space-y-4">
                                        <?php foreach ($all_tiers as $tier): ?>
                                            <div
                                                class="flex items-center gap-4 p-4 rounded-xl border transition-all duration-300 <?php echo ($loyalty['tier_id'] == $tier['tier_id']) ? 'border-accent bg-accent/10 shadow-lg shadow-accent/5' : 'border-white/5 bg-white/5 hover:bg-white/10'; ?>">
                                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-inner"
                                                    style="background: <?php echo $tier['color_code']; ?>">
                                                    <?php echo strtoupper(substr($tier['tier_name'], 0, 1)); ?>
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="font-bold text-lg"
                                                        style="color: <?php echo $tier['color_code']; ?>">
                                                        <?php echo $tier['tier_name']; ?>
                                                    </h4>
                                                    <p class="text-xs text-white/60 mt-1">
                                                        <?php echo str_replace('{:points}', '<span class="font-mono text-white/80">' . number_format($tier['min_points']) . '</span>', __('profile_loyalty.from_points')); ?>
                                                        • <span
                                                            class="text-accent"><?php echo str_replace('{:percent}', $tier['discount_percentage'], __('profile_loyalty.discount_for_all')); ?></span>
                                                    </p>
                                                </div>
                                                <?php if ($loyalty['tier_id'] == $tier['tier_id']): ?>
                                                    <span
                                                        class="px-3 py-1 bg-accent/90 text-white text-xs font-bold uppercase tracking-wider rounded-lg shadow-sm"><?php _e('profile_loyalty.current'); ?></span>
                                                <?php elseif ($loyalty['current_points'] >= $tier['min_points']): ?>
                                                    <span class="material-symbols-outlined text-green-400">check_circle</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Points History -->
                                <div class="glass-card p-6">
                                    <h3
                                        class="text-xl font-bold mb-6 text-white flex items-center gap-3 border-b border-white/10 pb-4">
                                        <span class="material-symbols-outlined text-accent">history_edu</span>
                                        <?php _e('profile_loyalty.points_history'); ?>
                                    </h3>

                                    <?php if (!empty($transactions)): ?>
                                        <div class="space-y-3">
                                            <?php foreach ($transactions as $transaction): ?>
                                                <div
                                                    class="flex items-center justify-between p-4 bg-white/5 hover:bg-white/10 border border-white/5 rounded-xl transition-all">
                                                    <div class="flex items-center gap-4">
                                                        <div
                                                            class="w-10 h-10 rounded-lg flex items-center justify-center <?php echo $transaction['transaction_type'] == 'earn' ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400'; ?>">
                                                            <span class="material-symbols-outlined text-lg">
                                                                <?php echo $transaction['transaction_type'] == 'earn' ? 'add' : 'remove'; ?>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <p class="font-medium text-white text-sm">
                                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                                            </p>
                                                            <p class="text-xs text-white/40 mt-1">
                                                                <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <p
                                                            class="font-bold font-mono <?php echo $transaction['transaction_type'] == 'earn' ? 'text-green-400' : 'text-red-400'; ?>">
                                                            <?php echo $transaction['transaction_type'] == 'earn' ? '+' : '-'; ?>        <?php echo number_format($transaction['points']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-8">
                                            <div
                                                class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <span
                                                    class="material-symbols-outlined text-4xl text-white/20">history</span>
                                            </div>
                                            <p class="text-white/40">
                                                <?php _e('profile_loyalty.no_transactions'); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Right Column: Stats & Actions -->
                            <div class="space-y-6">

                                <!-- Booking Statistics -->
                                <div class="glass-card p-6">
                                    <h3
                                        class="text-lg font-bold mb-4 text-white uppercase tracking-wider text-sm border-b border-white/10 pb-2">
                                        <?php _e('profile_loyalty.statistics'); ?></h3>
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center py-2 border-b border-white/5">
                                            <span
                                                class="text-white/60 text-sm"><?php _e('profile_loyalty.total_bookings'); ?></span>
                                            <span
                                                class="font-bold text-white"><?php echo $booking_stats['total_bookings']; ?></span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-white/5">
                                            <span
                                                class="text-white/60 text-sm"><?php _e('profile_loyalty.completed_bookings'); ?></span>
                                            <span
                                                class="font-bold text-white"><?php echo $booking_stats['completed_bookings']; ?></span>
                                        </div>
                                        <div class="flex justify-between items-center py-2">
                                            <span
                                                class="text-white/60 text-sm"><?php _e('profile_loyalty.total_spent'); ?></span>
                                            <span
                                                class="font-bold text-accent"><?php echo number_format($booking_stats['total_spent']); ?>
                                                VNĐ</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- How to Earn Points -->
                                <div class="glass-card p-6">
                                    <h3
                                        class="text-lg font-bold mb-4 text-white uppercase tracking-wider text-sm border-b border-white/10 pb-2">
                                        <?php _e('profile_loyalty.how_to_earn'); ?></h3>
                                    <div class="space-y-4">
                                        <div class="flex items-start gap-4">
                                            <div class="p-2 bg-accent/10 rounded-lg">
                                                <span class="material-symbols-outlined text-accent text-sm">hotel</span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-white text-sm mb-1">
                                                    <?php _e('profile_loyalty.earn_booking'); ?></p>
                                                <p class="text-xs text-white/50 leading-relaxed">
                                                    <?php _e('profile_loyalty.earn_booking_desc'); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-4">
                                            <div class="p-2 bg-accent/10 rounded-lg">
                                                <span
                                                    class="material-symbols-outlined text-accent text-sm">rate_review</span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-white text-sm mb-1">
                                                    <?php _e('profile_loyalty.earn_review'); ?></p>
                                                <p class="text-xs text-white/50 leading-relaxed">
                                                    <?php _e('profile_loyalty.earn_review_desc'); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-4">
                                            <div class="p-2 bg-accent/10 rounded-lg">
                                                <span class="material-symbols-outlined text-accent text-sm">share</span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-white text-sm mb-1">
                                                    <?php _e('profile_loyalty.earn_referral'); ?></p>
                                                <p class="text-xs text-white/50 leading-relaxed">
                                                    <?php _e('profile_loyalty.earn_referral_desc'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="glass-card p-6">
                                    <h3
                                        class="text-lg font-bold mb-4 text-white uppercase tracking-wider text-sm border-b border-white/10 pb-2">
                                        <?php _e('profile_loyalty.quick_actions'); ?></h3>
                                    <div class="space-y-3">
                                        <a href="../booking/index.php"
                                            class="block w-full px-4 py-3 bg-gradient-to-r from-accent to-yellow-600 text-white text-center rounded-xl hover:shadow-[0_0_20px_rgba(var(--accent-rgb),0.3)] transition-all font-bold uppercase tracking-wider text-sm">
                                            <div class="flex items-center justify-center gap-2">
                                                <span class="material-symbols-outlined">add</span>
                                                <?php _e('profile_loyalty.book_new'); ?>
                                            </div>
                                        </a>
                                        <a href="bookings.php"
                                            class="block w-full px-4 py-3 text-white text-center rounded-xl hover:bg-white/5 border border-white/10 transition-all font-bold uppercase tracking-wider text-sm">
                                            <div class="flex items-center justify-center gap-2">
                                                <span class="material-symbols-outlined">history</span>
                                                <?php _e('profile_loyalty.view_history'); ?>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include '../includes/footer.php'; ?>

    </div>

    <script src="../assets/js/main.js"></script>
</body>

</html>