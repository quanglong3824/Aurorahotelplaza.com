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
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php _e('profile_loyalty.title'); ?></title>
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
    <div class="mx-auto max-w-6xl px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-text-primary-light dark:text-text-primary-dark">
                <?php _e('profile_loyalty.page_title'); ?>
            </h1>
            <p class="mt-2 text-text-secondary-light dark:text-text-secondary-dark">
                <?php _e('profile_loyalty.page_subtitle'); ?>
            </p>
        </div>

        <?php if (isset($error)): ?>
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
            <div class="flex">
                <span class="material-symbols-outlined text-red-400 mr-2">error</span>
                <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Points & Tier Info -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Current Points Card -->
                <div class="bg-gradient-to-br from-accent to-accent/80 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-2xl font-bold"><?php _e('profile_loyalty.current_points'); ?></h2>
                            <p class="text-white/80"><?php _e('profile_loyalty.available_points'); ?></p>
                        </div>
                        <span class="material-symbols-outlined text-4xl">stars</span>
                    </div>
                    <div class="text-4xl font-bold mb-2">
                        <?php echo number_format($loyalty['current_points'] ?? 0); ?> <?php _e('profile_loyalty.points'); ?>
                    </div>
                    <div class="text-sm text-white/80">
                        <?php _e('profile_loyalty.total_lifetime'); ?>: <?php echo number_format($loyalty['lifetime_points'] ?? 0); ?> <?php _e('profile_loyalty.points'); ?>
                    </div>
                </div>

                <!-- Membership Tier Card -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">workspace_premium</span>
                        <?php _e('profile_loyalty.membership_tier'); ?>
                    </h3>
                    
                    <?php if ($loyalty['tier_name']): ?>
                    <div class="mb-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-xl"
                                 style="background: <?php echo $loyalty['color_code']; ?>">
                                <?php echo strtoupper(substr($loyalty['tier_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h4 class="text-2xl font-bold" style="color: <?php echo $loyalty['color_code']; ?>">
                                    <?php echo $loyalty['tier_name']; ?>
                                </h4>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark">
                                    <?php echo str_replace('{:percent}', $loyalty['discount_percentage'], __('profile_loyalty.discount_for_all')); ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($loyalty['benefits']): ?>
                        <div class="bg-primary-light/10 dark:bg-gray-700 rounded-lg p-4">
                            <h5 class="font-semibold mb-2"><?php _e('profile_loyalty.member_benefits'); ?>:</h5>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                <?php echo htmlspecialchars($loyalty['benefits']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-gray-400 mb-4 block">card_membership</span>
                        <p class="text-text-secondary-light dark:text-text-secondary-dark">
                            <?php _e('profile_loyalty.no_tier'); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Progress to Next Tier -->
                    <?php if ($loyalty['next_tier_points']): ?>
                    <div class="mt-6">
                        <div class="flex justify-between text-sm mb-2">
                            <span><?php _e('profile_loyalty.progress_next_tier'); ?></span>
                            <span><?php echo number_format($loyalty['current_points']); ?> / <?php echo number_format($loyalty['next_tier_points']); ?> <?php _e('profile_loyalty.points'); ?></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-accent h-3 rounded-full transition-all duration-300" 
                                 style="width: <?php echo min(100, ($loyalty['current_points'] / $loyalty['next_tier_points']) * 100); ?>%"></div>
                        </div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-2">
                            <?php echo str_replace('{:points}', number_format($loyalty['next_tier_points'] - $loyalty['current_points']), __('profile_loyalty.points_to_next')); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tier Progress -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4"><?php _e('profile_loyalty.all_tiers'); ?></h3>
                    <div class="space-y-4">
                        <?php foreach ($all_tiers as $tier): ?>
                        <div class="flex items-center gap-4 p-4 rounded-lg border-2 <?php echo ($loyalty['tier_id'] == $tier['tier_id']) ? 'border-accent bg-accent/5' : 'border-gray-200 dark:border-gray-700'; ?>">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold"
                                 style="background: <?php echo $tier['color_code']; ?>">
                                <?php echo strtoupper(substr($tier['tier_name'], 0, 1)); ?>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold" style="color: <?php echo $tier['color_code']; ?>">
                                    <?php echo $tier['tier_name']; ?>
                                </h4>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                    <?php echo str_replace('{:points}', number_format($tier['min_points']), __('profile_loyalty.from_points')); ?> • <?php echo str_replace('{:percent}', $tier['discount_percentage'], __('profile_loyalty.discount_for_all')); ?>
                                </p>
                            </div>
                            <?php if ($loyalty['tier_id'] == $tier['tier_id']): ?>
                            <span class="px-3 py-1 bg-accent text-white text-sm rounded-full"><?php _e('profile_loyalty.current'); ?></span>
                            <?php elseif ($loyalty['current_points'] >= $tier['min_points']): ?>
                            <span class="material-symbols-outlined text-green-500">check_circle</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Points History -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4"><?php _e('profile_loyalty.points_history'); ?></h3>
                    
                    <?php if (!empty($transactions)): ?>
                    <div class="space-y-3">
                        <?php foreach ($transactions as $transaction): ?>
                        <div class="flex items-center justify-between p-4 bg-background-light dark:bg-background-dark rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $transaction['transaction_type'] == 'earn' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                    <span class="material-symbols-outlined text-sm">
                                        <?php echo $transaction['transaction_type'] == 'earn' ? 'add' : 'remove'; ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium">
                                        <?php echo htmlspecialchars($transaction['description']); ?>
                                    </p>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                        <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold <?php echo $transaction['transaction_type'] == 'earn' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $transaction['transaction_type'] == 'earn' ? '+' : '-'; ?><?php echo number_format($transaction['points']); ?> <?php _e('profile_loyalty.points'); ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-gray-400 mb-4 block">history</span>
                        <p class="text-text-secondary-light dark:text-text-secondary-dark">
                            <?php _e('profile_loyalty.no_transactions'); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Stats & Actions -->
            <div class="space-y-6">
                
                <!-- Booking Statistics -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold mb-4"><?php _e('profile_loyalty.statistics'); ?></h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_loyalty.total_bookings'); ?></span>
                            <span class="font-bold"><?php echo $booking_stats['total_bookings']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_loyalty.completed_bookings'); ?></span>
                            <span class="font-bold"><?php echo $booking_stats['completed_bookings']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_loyalty.total_spent'); ?></span>
                            <span class="font-bold text-accent"><?php echo number_format($booking_stats['total_spent']); ?> VNĐ</span>
                        </div>
                    </div>
                </div>

                <!-- How to Earn Points -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold mb-4"><?php _e('profile_loyalty.how_to_earn'); ?></h3>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-accent mt-1">hotel</span>
                            <div>
                                <p class="font-medium"><?php _e('profile_loyalty.earn_booking'); ?></p>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_loyalty.earn_booking_desc'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-accent mt-1">rate_review</span>
                            <div>
                                <p class="font-medium"><?php _e('profile_loyalty.earn_review'); ?></p>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_loyalty.earn_review_desc'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-accent mt-1">share</span>
                            <div>
                                <p class="font-medium"><?php _e('profile_loyalty.earn_referral'); ?></p>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php _e('profile_loyalty.earn_referral_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold mb-4"><?php _e('profile_loyalty.quick_actions'); ?></h3>
                    <div class="space-y-3">
                        <a href="../booking/index.php" class="block w-full px-4 py-3 bg-accent text-white text-center rounded-lg hover:bg-accent/90 transition-colors">
                            <span class="material-symbols-outlined mr-2">add</span>
                            <?php _e('profile_loyalty.book_new'); ?>
                        </a>
                        <a href="bookings.php" class="block w-full px-4 py-3 border-2 border-accent text-accent text-center rounded-lg hover:bg-accent/5 transition-colors">
                            <span class="material-symbols-outlined mr-2">history</span>
                            <?php _e('profile_loyalty.view_history'); ?>
                        </a>
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