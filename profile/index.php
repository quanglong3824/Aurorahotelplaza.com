<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once '../config/database.php';
require_once '../helpers/language.php';
initLanguage();

$user_id = $_SESSION['user_id'];
$active_tab = $_GET['tab'] ?? 'info';

try {
    $db = getDB();
    
    // Get user + loyalty info
    $stmt = $db->prepare("
        SELECT u.*, ul.current_points, ul.lifetime_points, mt.tier_name, mt.discount_percentage, mt.color_code, mt.benefits
        FROM users u
        LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
        LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: ../auth/logout.php');
        exit;
    }
    
    // Get booking stats
    $stmt = $db->prepare("
        SELECT COUNT(*) as total, 
               SUM(CASE WHEN status IN ('confirmed','checked_in') THEN 1 ELSE 0 END) as active,
               SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as spent
        FROM bookings WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
    // Get recent bookings (limit 5)
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name FROM bookings b
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
    
    // Get points history (limit 5)
    $stmt = $db->prepare("SELECT * FROM points_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $points_history = $stmt->fetchAll();
    
    // Get contact history (limit 5)
    $stmt = $db->prepare("
        SELECT contact_code, subject, message, status, created_at, updated_at
        FROM contact_submissions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $contact_history = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    $error = "Có lỗi xảy ra.";
}

function getStatusBadge($status) {
    $map = [
        'pending' => [__('booking_status.pending'), 'bg-yellow-100 text-yellow-800'],
        'confirmed' => [__('booking_status.confirmed'), 'bg-blue-100 text-blue-800'],
        'checked_in' => [__('booking_status.checked_in'), 'bg-green-100 text-green-800'],
        'checked_out' => [__('booking_status.checked_out'), 'bg-gray-100 text-gray-800'],
        'cancelled' => [__('booking_status.cancelled'), 'bg-red-100 text-red-800'],
    ];
    $info = $map[$status] ?? [$status, 'bg-gray-100 text-gray-800'];
    return '<span class="px-2 py-1 text-xs font-medium rounded-full '.$info[1].'">'.$info[0].'</span>';
}

function getContactStatusBadge($status) {
    $map = [
        'new' => ['Mới', 'bg-blue-100 text-blue-800'],
        'in_progress' => ['Đang xử lý', 'bg-yellow-100 text-yellow-800'],
        'resolved' => ['Đã giải quyết', 'bg-green-100 text-green-800'],
        'closed' => ['Đã đóng', 'bg-gray-100 text-gray-800'],
    ];
    $info = $map[$status] ?? [$status, 'bg-gray-100 text-gray-800'];
    return '<span class="px-2 py-1 text-xs font-medium rounded-full '.$info[1].'">'.$info[0].'</span>';
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php _e('profile_page.title'); ?></title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <link href="../assets/css/fonts.css" rel="stylesheet"/>
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .tab-btn { padding: 0.75rem 1rem; border-bottom: 2px solid transparent; transition: all 0.2s; }
        .tab-btn.active { border-color: #d4af37; color: #d4af37; font-weight: 600; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-24 pb-16">
    <div class="mx-auto max-w-5xl w-full px-4 py-6">
        
        <!-- User Header -->
        <div class="bg-gradient-to-r from-primary-light to-accent p-6 rounded-xl text-white mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-accent text-2xl font-bold">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                        <p class="text-white/80 text-sm"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                <?php if ($user['tier_name']): ?>
                <div class="px-4 py-2 rounded-lg font-bold" style="background: <?php echo $user['color_code']; ?>20; border: 2px solid <?php echo $user['color_code']; ?>;">
                    <span class="material-symbols-outlined align-middle mr-1">workspace_premium</span>
                    <?php echo $user['tier_name']; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-surface-dark rounded-lg p-4 shadow-sm">
                <p class="text-gray-500 text-xs"><?php _e('profile_page.current_points'); ?></p>
                <p class="text-2xl font-bold text-accent"><?php echo number_format($user['current_points'] ?? 0); ?></p>
            </div>
            <div class="bg-white dark:bg-surface-dark rounded-lg p-4 shadow-sm">
                <p class="text-gray-500 text-xs"><?php _e('profile_page.total_points'); ?></p>
                <p class="text-2xl font-bold text-primary-light"><?php echo number_format($user['lifetime_points'] ?? 0); ?></p>
            </div>
            <div class="bg-white dark:bg-surface-dark rounded-lg p-4 shadow-sm">
                <p class="text-gray-500 text-xs"><?php _e('profile_page.bookings'); ?></p>
                <p class="text-2xl font-bold text-blue-600"><?php echo $stats['total'] ?? 0; ?></p>
            </div>
            <div class="bg-white dark:bg-surface-dark rounded-lg p-4 shadow-sm">
                <p class="text-gray-500 text-xs"><?php _e('profile_page.spent'); ?></p>
                <p class="text-xl font-bold text-green-600"><?php echo number_format($stats['spent'] ?? 0); ?>đ</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm">
            <div class="border-b flex overflow-x-auto">
                <button class="tab-btn <?php echo $active_tab == 'info' ? 'active' : ''; ?>" onclick="switchTab('info')">
                    <span class="material-symbols-outlined align-middle text-sm mr-1">person</span><?php _e('profile_page.tab_info'); ?>
                </button>
                <button class="tab-btn <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>" onclick="switchTab('bookings')">
                    <span class="material-symbols-outlined align-middle text-sm mr-1">hotel</span><?php _e('profile_page.tab_bookings'); ?>
                </button>
                <button class="tab-btn <?php echo $active_tab == 'points' ? 'active' : ''; ?>" onclick="switchTab('points')">
                    <span class="material-symbols-outlined align-middle text-sm mr-1">stars</span><?php _e('profile_page.tab_points'); ?>
                </button>
                <button class="tab-btn <?php echo $active_tab == 'contacts' ? 'active' : ''; ?>" onclick="switchTab('contacts')">
                    <span class="material-symbols-outlined align-middle text-sm mr-1">contact_support</span><?php _e('profile_page.tab_contacts'); ?>
                </button>
            </div>

            <!-- Tab: Thông tin -->
            <div id="tab-info" class="tab-content <?php echo $active_tab == 'info' ? 'active' : ''; ?> p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-lg"><?php _e('profile_page.personal_info'); ?></h3>
                    <a href="edit.php" class="text-accent hover:underline text-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">edit</span><?php _e('profile_page.edit'); ?>
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500"><?php _e('profile_page.email'); ?>:</span> <?php echo htmlspecialchars($user['email']); ?></div>
                    <div><span class="text-gray-500"><?php _e('profile_page.phone'); ?>:</span> <?php echo $user['phone'] ?: __('profile_page.not_updated'); ?></div>
                    <div><span class="text-gray-500"><?php _e('profile_page.dob'); ?>:</span> <?php echo $user['date_of_birth'] ? date('d/m/Y', strtotime($user['date_of_birth'])) : __('profile_page.not_updated'); ?></div>
                    <div><span class="text-gray-500"><?php _e('profile_page.joined'); ?>:</span> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
                    <div class="md:col-span-2"><span class="text-gray-500"><?php _e('profile_page.address'); ?>:</span> <?php echo $user['address'] ? htmlspecialchars($user['address']) : __('profile_page.not_updated'); ?></div>
                </div>
                
                <?php if ($user['tier_name'] && $user['benefits']): ?>
                <div class="mt-6 p-4 bg-accent/10 rounded-lg">
                    <h4 class="font-semibold mb-2">🎁 <?php _e('profile_page.benefits'); ?> <?php echo $user['tier_name']; ?> (<?php _e('profile_page.discount'); ?> <?php echo $user['discount_percentage']; ?>%)</h4>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['benefits']); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Đặt phòng -->
            <div id="tab-bookings" class="tab-content <?php echo $active_tab == 'bookings' ? 'active' : ''; ?> p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-lg"><?php _e('profile_page.recent_bookings'); ?></h3>
                    <a href="bookings.php" class="text-accent hover:underline text-sm"><?php _e('profile_page.view_all'); ?> →</a>
                </div>
                <?php if (empty($bookings)): ?>
                    <p class="text-center text-gray-500 py-8"><?php _e('profile_page.no_bookings'); ?></p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($bookings as $b): ?>
                    <div class="border rounded-lg p-4 hover:shadow-sm transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($b['type_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo $b['booking_code']; ?> • <?php echo date('d/m/Y', strtotime($b['check_in_date'])); ?> - <?php echo date('d/m/Y', strtotime($b['check_out_date'])); ?></p>
                            </div>
                            <div class="text-right">
                                <?php echo getStatusBadge($b['status']); ?>
                                <p class="text-accent font-bold mt-1"><?php echo number_format($b['total_amount']); ?>đ</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Điểm thưởng -->
            <div id="tab-points" class="tab-content <?php echo $active_tab == 'points' ? 'active' : ''; ?> p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-lg"><?php _e('profile_page.points_history'); ?></h3>
                    <a href="loyalty.php" class="text-accent hover:underline text-sm"><?php _e('profile_page.details'); ?> →</a>
                </div>
                <?php if (empty($points_history)): ?>
                    <p class="text-center text-gray-500 py-8"><?php _e('profile_page.no_points'); ?></p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($points_history as $p): ?>
                    <div class="flex justify-between items-center border-b pb-3">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined <?php echo $p['transaction_type'] == 'earn' ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $p['transaction_type'] == 'earn' ? 'add_circle' : 'remove_circle'; ?>
                            </span>
                            <div>
                                <p class="font-medium text-sm"><?php echo htmlspecialchars($p['description']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></p>
                            </div>
                        </div>
                        <span class="font-bold <?php echo $p['transaction_type'] == 'earn' ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $p['transaction_type'] == 'earn' ? '+' : '-'; ?><?php echo number_format($p['points']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Lịch sử liên hệ -->
            <div id="tab-contacts" class="tab-content <?php echo $active_tab == 'contacts' ? 'active' : ''; ?> p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-lg"><?php _e('profile_page.contact_history'); ?></h3>
                    <a href="#" class="text-accent hover:underline text-sm"><?php _e('profile_page.view_all'); ?> →</a>
                </div>
                <?php if (empty($contact_history)): ?>
                    <p class="text-center text-gray-500 py-8"><?php _e('profile_page.no_contacts'); ?></p>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($contact_history as $c): ?>
                    <div class="border rounded-lg p-4 hover:shadow-sm transition">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-accent text-sm">contact_support</span>
                                <span class="font-semibold text-sm"><?php echo htmlspecialchars($c['contact_code'] ?: '#' . $c['id']); ?></span>
                            </div>
                            <?php echo getContactStatusBadge($c['status']); ?>
                        </div>
                        <p class="font-medium text-sm mb-1"><?php echo htmlspecialchars($c['subject']); ?></p>
                        <p class="text-sm text-gray-600 line-clamp-2"><?php echo htmlspecialchars($c['message']); ?></p>
                        <p class="text-xs text-gray-500 mt-2">
                            <?php _e('profile_page.submitted'); ?>: <?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?>
                            <?php if ($c['updated_at'] !== $c['created_at']): ?>
                                • <?php _e('profile_page.updated'); ?>: <?php echo date('d/m/Y H:i', strtotime($c['updated_at'])); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
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
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.closest('.tab-btn').classList.add('active');
    history.replaceState(null, '', '?tab=' + tab);
}
</script>
</body>
</html>