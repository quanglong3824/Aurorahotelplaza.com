<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once '../config/database.php';

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
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Điểm thưởng - Aurora Hotel Plaza</title>
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
                Điểm thưởng & Hạng thành viên
            </h1>
            <p class="mt-2 text-text-secondary-light dark:text-text-secondary-dark">
                Theo dõi điểm tích lũy và tận hưởng các ưu đãi đặc biệt
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
                            <h2 class="text-2xl font-bold">Điểm hiện tại</h2>
                            <p class="text-white/80">Điểm có thể sử dụng</p>
                        </div>
                        <span class="material-symbols-outlined text-4xl">stars</span>
                    </div>
                    <div class="text-4xl font-bold mb-2">
                        <?php echo number_format($loyalty['current_points'] ?? 0); ?> điểm
                    </div>
                    <div class="text-sm text-white/80">
                        Tổng điểm tích lũy: <?php echo number_format($loyalty['lifetime_points'] ?? 0); ?> điểm
                    </div>
                </div>

                <!-- Membership Tier Card -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">workspace_premium</span>
                        Hạng thành viên
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
                                    Giảm <?php echo $loyalty['discount_percentage']; ?>% cho mọi đặt phòng
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($loyalty['benefits']): ?>
                        <div class="bg-primary-light/10 dark:bg-gray-700 rounded-lg p-4">
                            <h5 class="font-semibold mb-2">Quyền lợi thành viên:</h5>
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
                            Bạn chưa có hạng thành viên. Tích lũy điểm để nâng hạng!
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Progress to Next Tier -->
                    <?php if ($loyalty['next_tier_points']): ?>
                    <div class="mt-6">
                        <div class="flex justify-between text-sm mb-2">
                            <span>Tiến độ lên hạng tiếp theo</span>
                            <span><?php echo number_format($loyalty['current_points']); ?> / <?php echo number_format($loyalty['next_tier_points']); ?> điểm</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-accent h-3 rounded-full transition-all duration-300" 
                                 style="width: <?php echo min(100, ($loyalty['current_points'] / $loyalty['next_tier_points']) * 100); ?>%"></div>
                        </div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-2">
                            Còn <?php echo number_format($loyalty['next_tier_points'] - $loyalty['current_points']); ?> điểm để lên hạng tiếp theo
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tier Progress -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4">Các hạng thành viên</h3>
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
                                    Từ <?php echo number_format($tier['min_points']); ?> điểm • Giảm <?php echo $tier['discount_percentage']; ?>%
                                </p>
                            </div>
                            <?php if ($loyalty['tier_id'] == $tier['tier_id']): ?>
                            <span class="px-3 py-1 bg-accent text-white text-sm rounded-full">Hiện tại</span>
                            <?php elseif ($loyalty['current_points'] >= $tier['min_points']): ?>
                            <span class="material-symbols-outlined text-green-500">check_circle</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Points History -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4">Lịch sử điểm thưởng</h3>
                    
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
                                    <?php echo $transaction['transaction_type'] == 'earn' ? '+' : '-'; ?><?php echo number_format($transaction['points']); ?> điểm
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-gray-400 mb-4 block">history</span>
                        <p class="text-text-secondary-light dark:text-text-secondary-dark">
                            Chưa có giao dịch điểm nào
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Stats & Actions -->
            <div class="space-y-6">
                
                <!-- Booking Statistics -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold mb-4">Thống kê</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-text-secondary-light dark:text-text-secondary-dark">Tổng đặt phòng</span>
                            <span class="font-bold"><?php echo $booking_stats['total_bookings']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-text-secondary-light dark:text-text-secondary-dark">Đặt phòng thành công</span>
                            <span class="font-bold"><?php echo $booking_stats['completed_bookings']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-text-secondary-light dark:text-text-secondary-dark">Tổng chi tiêu</span>
                            <span class="font-bold text-accent"><?php echo number_format($booking_stats['total_spent']); ?> VNĐ</span>
                        </div>
                    </div>
                </div>

                <!-- How to Earn Points -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold mb-4">Cách tích điểm</h3>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-accent mt-1">hotel</span>
                            <div>
                                <p class="font-medium">Đặt phòng</p>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">1 điểm cho mỗi 10,000 VNĐ</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-accent mt-1">rate_review</span>
                            <div>
                                <p class="font-medium">Đánh giá dịch vụ</p>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">50 điểm cho mỗi đánh giá</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-accent mt-1">share</span>
                            <div>
                                <p class="font-medium">Giới thiệu bạn bè</p>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">200 điểm cho mỗi giới thiệu</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold mb-4">Hành động nhanh</h3>
                    <div class="space-y-3">
                        <a href="../booking/index.php" class="block w-full px-4 py-3 bg-accent text-white text-center rounded-lg hover:bg-accent/90 transition-colors">
                            <span class="material-symbols-outlined mr-2">add</span>
                            Đặt phòng mới
                        </a>
                        <a href="bookings.php" class="block w-full px-4 py-3 border-2 border-accent text-accent text-center rounded-lg hover:bg-accent/5 transition-colors">
                            <span class="material-symbols-outlined mr-2">history</span>
                            Xem lịch sử đặt phòng
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