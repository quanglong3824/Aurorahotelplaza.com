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

function getStatusBadge($status)
{
    $map = [
        'pending' => [__('booking_status.pending'), 'bg-yellow-100 text-yellow-800'],
        'confirmed' => [__('booking_status.confirmed'), 'bg-blue-100 text-blue-800'],
        'checked_in' => [__('booking_status.checked_in'), 'bg-green-100 text-green-800'],
        'checked_out' => [__('booking_status.checked_out'), 'bg-gray-100 text-gray-800'],
        'cancelled' => [__('booking_status.cancelled'), 'bg-red-100 text-red-800'],
    ];
    $info = $map[$status] ?? [$status, 'bg-gray-100 text-gray-800'];
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $info[1] . '">' . $info[0] . '</span>';
}

function getContactStatusBadge($status)
{
    $map = [
        'new' => ['Mới', 'bg-blue-100 text-blue-800'],
        'in_progress' => ['Đang xử lý', 'bg-yellow-100 text-yellow-800'],
        'resolved' => ['Đã giải quyết', 'bg-green-100 text-green-800'],
        'closed' => ['Đã đóng', 'bg-gray-100 text-gray-800'],
    ];
    $info = $map[$status] ?? [$status, 'bg-gray-100 text-gray-800'];
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $info[1] . '">' . $info[0] . '</span>';
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('profile_page.title'); ?></title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <link href="../assets/css/fonts.css" rel="stylesheet" />
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="../assets/css/pages-glass.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
</head>

<body class="glass-page font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">

        <?php include '../includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Glass Page Wrapper -->
            <div class="glass-page-wrapper">

                <div class="w-full pt-[180px] pb-16 px-4">
                    <div class="mx-auto max-w-6xl">
                        <!-- User Header - Liquid Glass Style -->
                        <div class="glass-card mb-8">
                            <div class="relative overflow-hidden p-6 md:p-8 rounded-2xl">
                                <div
                                    class="relative z-10 flex flex-col md:flex-row items-center md:items-start justify-between gap-6">
                                    <div class="flex flex-col md:flex-row items-center gap-6">
                                        <div
                                            class="w-24 h-24 bg-white/10 backdrop-blur-md border border-white/20 rounded-full flex items-center justify-center text-accent text-4xl font-bold shadow-lg">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                        </div>
                                        <div class="text-center md:text-left">
                                            <h1 class="text-3xl font-bold text-white mb-2">
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </h1>
                                            <div class="flex flex-col md:flex-row gap-4 text-white/70 text-sm">
                                                <span class="flex items-center gap-2">
                                                    <span class="material-symbols-outlined text-lg">mail</span>
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </span>
                                                <span class="flex items-center gap-2">
                                                    <span class="material-symbols-outlined text-lg">phone</span>
                                                    <?php echo $user['phone'] ?: __('profile_page.not_updated'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($user['tier_name']): ?>
                                        <div class="glass-badge px-6 py-3 rounded-xl font-bold flex items-center gap-2"
                                            style="background: <?php echo $user['color_code']; ?>30; border: 1px solid <?php echo $user['color_code']; ?>; color: <?php echo $user['color_code']; ?>;">
                                            <span class="material-symbols-outlined text-2xl">workspace_premium</span>
                                            <span class="text-lg"><?php echo $user['tier_name']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                            <div class="glass-card p-6 text-center hover:-translate-y-1 transition-all">
                                <div
                                    class="w-12 h-12 mx-auto rounded-xl bg-accent/20 flex items-center justify-center mb-3">
                                    <span class="material-symbols-outlined text-accent text-2xl">stars</span>
                                </div>
                                <p class="text-white/60 text-xs uppercase tracking-wider mb-1">
                                    <?php _e('profile_page.current_points'); ?>
                                </p>
                                <p class="text-2xl font-bold text-white">
                                    <?php echo number_format($user['current_points'] ?? 0); ?>
                                </p>
                            </div>
                            <div class="glass-card p-6 text-center hover:-translate-y-1 transition-all">
                                <div
                                    class="w-12 h-12 mx-auto rounded-xl bg-purple-500/20 flex items-center justify-center mb-3">
                                    <span
                                        class="material-symbols-outlined text-purple-400 text-2xl">military_tech</span>
                                </div>
                                <p class="text-white/60 text-xs uppercase tracking-wider mb-1">
                                    <?php _e('profile_page.total_points'); ?>
                                </p>
                                <p class="text-2xl font-bold text-white">
                                    <?php echo number_format($user['lifetime_points'] ?? 0); ?>
                                </p>
                            </div>
                            <div class="glass-card p-6 text-center hover:-translate-y-1 transition-all">
                                <div
                                    class="w-12 h-12 mx-auto rounded-xl bg-blue-500/20 flex items-center justify-center mb-3">
                                    <span class="material-symbols-outlined text-blue-400 text-2xl">hotel</span>
                                </div>
                                <p class="text-white/60 text-xs uppercase tracking-wider mb-1">
                                    <?php _e('profile_page.bookings'); ?>
                                </p>
                                <p class="text-2xl font-bold text-white"><?php echo $stats['total'] ?? 0; ?></p>
                            </div>
                            <div class="glass-card p-6 text-center hover:-translate-y-1 transition-all">
                                <div
                                    class="w-12 h-12 mx-auto rounded-xl bg-green-500/20 flex items-center justify-center mb-3">
                                    <span class="material-symbols-outlined text-green-400 text-2xl">payments</span>
                                </div>
                                <p class="text-white/60 text-xs uppercase tracking-wider mb-1">
                                    <?php _e('profile_page.spent'); ?>
                                </p>
                                <p class="text-xl font-bold text-white">
                                    <?php echo number_format($stats['spent'] ?? 0); ?>đ
                                </p>
                            </div>
                        </div>

                        <!-- Main Content Tabs -->
                        <div class="glass-card overflow-hidden min-h-[500px]">
                            <!-- Tab Navigation -->
                            <div class="flex overflow-x-auto border-b border-white/10 scrollbar-hide">
                                <button class="profile-tab-btn <?php echo $active_tab == 'info' ? 'active' : ''; ?>"
                                    onclick="switchTab('info')">
                                    <span class="material-symbols-outlined align-middle text-lg mr-2">person</span>
                                    <?php _e('profile_page.tab_info'); ?>
                                </button>
                                <button class="profile-tab-btn <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>"
                                    onclick="switchTab('bookings')">
                                    <span class="material-symbols-outlined align-middle text-lg mr-2">hotel</span>
                                    <?php _e('profile_page.tab_bookings'); ?>
                                </button>
                                <button class="profile-tab-btn <?php echo $active_tab == 'points' ? 'active' : ''; ?>"
                                    onclick="switchTab('points')">
                                    <span class="material-symbols-outlined align-middle text-lg mr-2">stars</span>
                                    <?php _e('profile_page.tab_points'); ?>
                                </button>
                                <button class="profile-tab-btn <?php echo $active_tab == 'contacts' ? 'active' : ''; ?>"
                                    onclick="switchTab('contacts')">
                                    <span
                                        class="material-symbols-outlined align-middle text-lg mr-2">contact_support</span>
                                    <?php _e('profile_page.tab_contacts'); ?>
                                </button>
                            </div>

                            <!-- Tab: Thông tin -->
                            <div id="tab-info"
                                class="tab-content <?php echo $active_tab == 'info' ? 'active' : ''; ?> p-8">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="font-bold text-xl text-white"><?php _e('profile_page.personal_info'); ?>
                                    </h3>
                                    <a href="edit.php"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-accent hover:text-white rounded-lg transition-all text-sm text-white/80">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                        <?php _e('profile_page.edit'); ?>
                                    </a>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-white/80">
                                    <div class="space-y-4">
                                        <div class="group">
                                            <p class="text-xs text-white/50 uppercase tracking-wider mb-1">
                                                <?php _e('profile_page.email'); ?>
                                            </p>
                                            <p class="text-lg"><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                        <div class="group">
                                            <p class="text-xs text-white/50 uppercase tracking-wider mb-1">
                                                <?php _e('profile_page.phone'); ?>
                                            </p>
                                            <p class="text-lg">
                                                <?php echo $user['phone'] ?: __('profile_page.not_updated'); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="group">
                                            <p class="text-xs text-white/50 uppercase tracking-wider mb-1">
                                                <?php _e('profile_page.dob'); ?>
                                            </p>
                                            <p class="text-lg">
                                                <?php echo $user['date_of_birth'] ? date('d/m/Y', strtotime($user['date_of_birth'])) : __('profile_page.not_updated'); ?>
                                            </p>
                                        </div>
                                        <div class="group">
                                            <p class="text-xs text-white/50 uppercase tracking-wider mb-1">
                                                <?php _e('profile_page.joined'); ?>
                                            </p>
                                            <p class="text-lg">
                                                <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="md:col-span-2 group">
                                        <p class="text-xs text-white/50 uppercase tracking-wider mb-1">
                                            <?php _e('profile_page.address'); ?>
                                        </p>
                                        <p class="text-lg">
                                            <?php echo $user['address'] ? htmlspecialchars($user['address']) : __('profile_page.not_updated'); ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($user['tier_name'] && $user['benefits']): ?>
                                    <div
                                        class="mt-8 p-6 bg-gradient-to-br from-accent/10 to-transparent border border-accent/20 rounded-xl">
                                        <h4 class="font-bold text-accent mb-3 flex items-center gap-2">
                                            <span class="material-symbols-outlined">workspace_premium</span>
                                            <?php _e('profile_page.benefits'); ?>     <?php echo $user['tier_name']; ?>
                                            (<?php _e('profile_page.discount'); ?>
                                            <?php echo $user['discount_percentage']; ?>%)
                                        </h4>
                                        <p class="text-white/80 leading-relaxed">
                                            <?php echo htmlspecialchars($user['benefits']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab: Đặt phòng -->
                            <div id="tab-bookings"
                                class="tab-content <?php echo $active_tab == 'bookings' ? 'active' : ''; ?> p-8">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="font-bold text-xl text-white">
                                        <?php _e('profile_page.recent_bookings'); ?>
                                    </h3>
                                    <a href="bookings.php"
                                        class="text-accent hover:text-white transition-colors text-sm font-medium flex items-center gap-1">
                                        <?php _e('profile_page.view_all'); ?> <span
                                            class="material-symbols-outlined text-sm">arrow_forward</span>
                                    </a>
                                </div>

                                <?php if (empty($bookings)): ?>
                                    <div class="flex flex-col items-center justify-center py-12 text-white/50">
                                        <span
                                            class="material-symbols-outlined text-6xl mb-4 opacity-50">calendar_today</span>
                                        <p><?php _e('profile_page.no_bookings'); ?></p>
                                        <a href="../rooms.php" class="mt-4 btn-glass-gold px-6 py-2">
                                            <?php _e('nav.book_now'); ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($bookings as $b): ?>
                                            <div
                                                class="bg-white/5 border border-white/10 rounded-xl p-5 hover:bg-white/10 transition-all group">
                                                <div
                                                    class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                                    <div>
                                                        <h4
                                                            class="font-bold text-lg text-white mb-1 group-hover:text-accent transition-colors">
                                                            <?php echo htmlspecialchars($b['type_name']); ?>
                                                        </h4>
                                                        <div class="flex flex-wrap gap-2 text-sm text-white/60">
                                                            <span class="px-2 py-0.5 bg-white/10 rounded text-xs font-mono">
                                                                <?php echo $b['booking_code']; ?>
                                                            </span>
                                                            <span class="flex items-center gap-1">
                                                                <span
                                                                    class="material-symbols-outlined text-sm">calendar_month</span>
                                                                <?php echo date('d/m/Y', strtotime($b['check_in_date'])); ?> -
                                                                <?php echo date('d/m/Y', strtotime($b['check_out_date'])); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center justify-between w-full md:w-auto gap-4">
                                                        <?php echo getStatusBadge($b['status']); ?>
                                                        <div class="text-right">
                                                            <p class="text-accent font-bold text-lg">
                                                                <?php echo number_format($b['total_amount']); ?>đ
                                                            </p>
                                                            <p class="text-xs text-white/40"><?php echo $b['payment_status']; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab: Điểm thưởng -->
                            <div id="tab-points"
                                class="tab-content <?php echo $active_tab == 'points' ? 'active' : ''; ?> p-8">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="font-bold text-xl text-white"><?php _e('profile_page.points_history'); ?>
                                    </h3>
                                    <a href="loyalty.php"
                                        class="text-accent hover:text-white transition-colors text-sm font-medium flex items-center gap-1">
                                        <?php _e('profile_page.details'); ?> <span
                                            class="material-symbols-outlined text-sm">arrow_forward</span>
                                    </a>
                                </div>

                                <?php if (empty($points_history)): ?>
                                    <div class="text-center py-12 text-white/50">
                                        <p><?php _e('profile_page.no_points'); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-0 text-white/90">
                                        <?php foreach ($points_history as $p): ?>
                                            <div
                                                class="flex justify-between items-center py-4 border-b border-white/5 last:border-0 hover:bg-white/5 p-4 rounded-lg transition-colors">
                                                <div class="flex items-center gap-4">
                                                    <div
                                                        class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $p['transaction_type'] == 'earn' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'; ?>">
                                                        <span class="material-symbols-outlined">
                                                            <?php echo $p['transaction_type'] == 'earn' ? 'add' : 'remove'; ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium">
                                                            <?php echo htmlspecialchars($p['description']); ?>
                                                        </p>
                                                        <p class="text-xs text-white/50">
                                                            <?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <span
                                                    class="font-bold text-lg <?php echo $p['transaction_type'] == 'earn' ? 'text-green-400' : 'text-red-400'; ?>">
                                                    <?php echo $p['transaction_type'] == 'earn' ? '+' : '-'; ?>
                                                    <?php echo number_format($p['points']); ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab: Lịch sử liên hệ -->
                            <div id="tab-contacts"
                                class="tab-content <?php echo $active_tab == 'contacts' ? 'active' : ''; ?> p-8">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="font-bold text-xl text-white">
                                        <?php _e('profile_page.contact_history'); ?>
                                    </h3>
                                    <a href="../contact.php"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-accent hover:bg-accent-hover text-white rounded-lg transition-all text-sm font-bold">
                                        <span class="material-symbols-outlined text-sm">add</span>
                                        <?php _e('profile_full.new_contact'); ?>
                                    </a>
                                </div>

                                <?php if (empty($contact_history)): ?>
                                    <div class="text-center py-12 text-white/50">
                                        <p><?php _e('profile_page.no_contacts'); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($contact_history as $c): ?>
                                            <div
                                                class="bg-white/5 border border-white/10 rounded-xl p-5 hover:bg-white/10 transition-all">
                                                <div class="flex justify-between items-start mb-3">
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="px-2 py-0.5 bg-white/10 rounded text-xs font-mono text-white/70">
                                                            <?php echo htmlspecialchars($c['contact_code'] ?: '#' . (isset($c['id']) ? $c['id'] : '')); ?>
                                                        </span>
                                                    </div>
                                                    <?php echo getContactStatusBadge($c['status']); ?>
                                                </div>
                                                <h4 class="font-bold text-white mb-2">
                                                    <?php echo htmlspecialchars($c['subject']); ?>
                                                </h4>
                                                <p class="text-white/60 text-sm line-clamp-2 mb-3">
                                                    <?php echo htmlspecialchars($c['message']); ?>
                                                </p>
                                                <p class="text-xs text-white/40 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">schedule</span>
                                                    <?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
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
            document.querySelectorAll('.profile-tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');

            // Find button with onclick="switchTab('tab')"
            const btn = document.querySelector(`.profile-tab-btn[onclick="switchTab('${tab}')"]`);
            if (btn) btn.classList.add('active');

            history.replaceState(null, '', '?tab=' + tab);
        }
    </script>
</body>

</html>