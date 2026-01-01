<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/language.php';
initLanguage();

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;

// For testing, allow viewing any user by ID
if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
}

if (!$user_id) {
    // Redirect to login or show guest message
    header('Location: index.php');
    exit;
}

try {
    $db = getDB();

    // Get user information
    $stmt = $db->prepare("
        SELECT 
            u.*,
            ul.current_points,
            ul.lifetime_points,
            ul.tier_id,
            mt.tier_name,
            mt.tier_level,
            mt.discount_percentage,
            mt.benefits,
            mt.color_code
        FROM users u
        LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
        LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        die(__('profile_full.user_not_found'));
    }

    // Get booking statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in_bookings,
            SUM(CASE WHEN status = 'checked_out' THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_spent
        FROM bookings
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();

    // Get all bookings
    $stmt = $db->prepare("
        SELECT 
            b.*,
            rt.type_name,
            rt.category,
            r.room_number,
            r.floor
        FROM bookings b
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();

    // Get points transactions
    $stmt = $db->prepare("
        SELECT *
        FROM points_transactions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $points_history = $stmt->fetchAll();

    // Get payments
    $stmt = $db->prepare("
        SELECT 
            p.*,
            b.booking_code
        FROM payments p
        LEFT JOIN bookings b ON p.booking_id = b.booking_id
        WHERE b.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $payments = $stmt->fetchAll();

    // Get contact history
    $contacts = [];
    try {
        $stmt = $db->prepare("
            SELECT 
                c.*,
                COALESCE(c.contact_code, LPAD(c.id, 8, '0')) as display_code
            FROM contact_submissions c
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$user_id]);
        $contacts = $stmt->fetchAll();
    } catch (Exception $e) {
        // Nếu bảng chưa có cột id, thử query khác
        try {
            $stmt = $db->prepare("
                SELECT * FROM contact_submissions 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$user_id]);
            $contacts = $stmt->fetchAll();
            // Thêm display_code
            foreach ($contacts as &$c) {
                $c['display_code'] = str_pad($c['submission_id'] ?? rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            }
        } catch (Exception $e2) {
            error_log("Contact history error: " . $e2->getMessage());
        }
    }

} catch (Exception $e) {
    die(__('profile_full.error') . ': ' . $e->getMessage());
}

// Helper functions
function getStatusBadge($status)
{
    $badges = [
        'pending' => '<span class="badge badge-warning">Chờ xác nhận</span>',
        'confirmed' => '<span class="badge badge-success">Đã xác nhận</span>',
        'checked_in' => '<span class="badge badge-info">Đang ở</span>',
        'checked_out' => '<span class="badge badge-secondary">Đã trả phòng</span>',
        'cancelled' => '<span class="badge badge-danger">Đã hủy</span>',
    ];
    return $badges[$status] ?? $status;
}

function getPaymentStatusBadge($status)
{
    $badges = [
        'unpaid' => '<span class="badge badge-warning">Chưa thanh toán</span>',
        'paid' => '<span class="badge badge-success">Đã thanh toán</span>',
        'refunded' => '<span class="badge badge-info">Đã hoàn tiền</span>',
    ];
    return $badges[$status] ?? $status;
}

function getContactStatusBadge($status)
{
    $badges = [
        'new' => '<span class="badge badge-info">Chờ phản hồi</span>',
        'in_progress' => '<span class="badge badge-warning">Đang xử lý</span>',
        'resolved' => '<span class="badge badge-success">Đã phản hồi</span>',
        'closed' => '<span class="badge badge-secondary">Đã đóng</span>',
    ];
    return $badges[$status] ?? $status;
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('profile_full.title'); ?> - <?php echo htmlspecialchars($user['full_name']); ?></title>

    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet" />
    <script src="assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/liquid-glass.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/pages-glass.css?v=<?php echo time(); ?>">
    <style>
        /* Premium Dark Glass Enhancements */
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
        }

        .profile-header-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .profile-header-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(212, 175, 55, 0.15), transparent 60%);
            pointer-events: none;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(212, 175, 55, 0.3);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .tab-button {
            position: relative;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
            font-weight: 500;
            border-radius: 0.5rem;
        }

        .tab-button:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .tab-button.active {
            background: rgba(212, 175, 55, 0.15);
            color: #d4af37;
            border: 1px solid rgba(212, 175, 55, 0.2);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Badge overrides */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.2);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.2);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border-color: rgba(59, 130, 246, 0.2);
        }

        .badge-secondary {
            background: rgba(107, 114, 128, 0.15);
            color: #9ca3af;
            border-color: rgba(107, 114, 128, 0.2);
        }
    </style>
</head>

<body class="bg-gray-900 font-body text-white min-h-screen flex flex-col relative overflow-x-hidden">

    <!-- Fixed Background -->
    <div class="fixed inset-0 z-0 pointer-events-none">
        <img src="assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg"
            class="w-full h-full object-cover opacity-20 filter blur-sm">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-900/80 via-gray-900/90 to-gray-900"></div>
    </div>

    <div class="relative z-10 flex flex-col min-h-screen">
        <?php include 'includes/header.php'; ?>

        <main class="flex-grow pt-24 pb-16 px-4">
            <div class="max-w-7xl mx-auto space-y-8">

                <!-- User Header -->
                <div class="profile-header-card rounded-2xl p-8 shadow-2xl">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="flex items-center gap-6">
                            <div class="relative group">
                                <div
                                    class="w-24 h-24 rounded-full bg-gradient-to-br from-accent to-yellow-600 p-1 shadow-lg shadow-accent/20">
                                    <div
                                        class="w-full h-full rounded-full bg-slate-800 flex items-center justify-center text-3xl font-bold text-accent">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div
                                    class="absolute bottom-0 right-0 w-6 h-6 bg-green-500 rounded-full border-2 border-slate-800">
                                </div>
                            </div>

                            <div class="text-center md:text-left">
                                <h1 class="text-3xl font-heading font-bold text-white mb-1">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </h1>
                                <div
                                    class="flex flex-col md:flex-row items-center gap-2 md:gap-4 text-slate-400 text-sm">
                                    <span class="flex items-center gap-1"><span
                                            class="material-symbols-outlined text-base">mail</span>
                                        <?php echo htmlspecialchars($user['email']); ?></span>
                                    <?php if ($user['phone']): ?>
                                        <span class="hidden md:inline">•</span>
                                        <span class="flex items-center gap-1"><span
                                                class="material-symbols-outlined text-base">phone</span>
                                            <?php echo htmlspecialchars($user['phone']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-center md:items-end gap-3">
                            <?php if ($user['tier_name']): ?>
                                <div class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-accent">workspace_premium</span>
                                    <span class="font-bold text-accent"><?php echo $user['tier_name']; ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center gap-3">
                                <a href="edit-profile.php"
                                    class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors flex items-center gap-2 text-sm font-medium">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                    <?php _e('profile_full.edit'); ?>
                                </a>
                                <a href="logout.php"
                                    class="px-4 py-2 rounded-lg border border-red-500/30 text-red-400 hover:bg-red-500/10 transition-colors flex items-center gap-2 text-sm font-medium">
                                    <span class="material-symbols-outlined text-sm">logout</span>
                                    <?php _e('profile_full.logout'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Current Points -->
                    <div class="stat-card group">
                        <div class="flex justify-between items-start mb-2">
                            <span
                                class="text-slate-400 text-sm font-medium uppercase tracking-wider"><?php _e('profile_full.current_points'); ?></span>
                            <span
                                class="material-symbols-outlined text-accent p-2 bg-accent/10 rounded-lg group-hover:bg-accent group-hover:text-white transition-colors">stars</span>
                        </div>
                        <p class="text-3xl font-bold text-white tracking-tight">
                            <?php echo number_format($user['current_points'] ?? 0); ?>
                        </p>
                        <p class="text-xs text-slate-500 mt-1">Available to use</p>
                    </div>

                    <!-- Lifetime Points -->
                    <div class="stat-card group">
                        <div class="flex justify-between items-start mb-2">
                            <span
                                class="text-slate-400 text-sm font-medium uppercase tracking-wider"><?php _e('profile_full.lifetime_points'); ?></span>
                            <span
                                class="material-symbols-outlined text-purple-400 p-2 bg-purple-400/10 rounded-lg group-hover:bg-purple-500 group-hover:text-white transition-colors">emoji_events</span>
                        </div>
                        <p class="text-3xl font-bold text-white tracking-tight">
                            <?php echo number_format($user['lifetime_points'] ?? 0);
                            ; ?>
                        </p>
                        <p class="text-xs text-slate-500 mt-1">Total earned</p>
                    </div>

                    <!-- Bookings -->
                    <div class="stat-card group">
                        <div class="flex justify-between items-start mb-2">
                            <span
                                class="text-slate-400 text-sm font-medium uppercase tracking-wider"><?php _e('profile_full.total_bookings'); ?></span>
                            <span
                                class="material-symbols-outlined text-blue-400 p-2 bg-blue-400/10 rounded-lg group-hover:bg-blue-500 group-hover:text-white transition-colors">hotel</span>
                        </div>
                        <p class="text-3xl font-bold text-white tracking-tight"><?php echo $stats['total_bookings']; ?>
                        </p>
                        <div class="flex gap-2 text-xs mt-1">
                            <span class="text-green-400"><?php echo $stats['completed_bookings']; ?> done</span>
                            <span class="text-slate-600">|</span>
                            <span class="text-amber-400"><?php echo $stats['confirmed_bookings']; ?> upcoming</span>
                        </div>
                    </div>

                    <!-- Spent -->
                    <div class="stat-card group">
                        <div class="flex justify-between items-start mb-2">
                            <span
                                class="text-slate-400 text-sm font-medium uppercase tracking-wider"><?php _e('profile_full.total_spent'); ?></span>
                            <span
                                class="material-symbols-outlined text-green-400 p-2 bg-green-400/10 rounded-lg group-hover:bg-green-500 group-hover:text-white transition-colors">payments</span>
                        </div>
                        <p class="text-2xl font-bold text-white tracking-tight truncate"
                            title="<?php echo number_format($stats['total_spent']); ?>">
                            <?php echo number_format($stats['total_spent']); ?> đ
                        </p>
                        <p class="text-xs text-slate-500 mt-1">Lifetime value</p>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="flex flex-col lg:flex-row gap-8">

                    <!-- Sidebar / Benefits (Mobile Only or Small Desktop) -->
                    <?php if ($user['tier_name']): ?>
                        <div class="lg:w-1/3 space-y-6">
                            <div class="glass-panel p-6">
                                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-accent">card_membership</span>
                                    Membership Benefits
                                </h3>

                                <div
                                    class="p-4 rounded-xl bg-gradient-to-br from-slate-800 to-slate-900 border border-slate-700 mb-6">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-accent font-bold"><?php echo $user['tier_name']; ?></span>
                                        <span
                                            class="text-xs px-2 py-1 rounded bg-accent/20 text-accent border border-accent/20"><?php echo $user['discount_percentage']; ?>%
                                            OFF</span>
                                    </div>
                                    <div class="w-full bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                        <!-- Simple progress visual -->
                                        <div class="bg-accent h-full rounded-full" style="width: 70%"></div>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-2">Earn points on every booking to upgrade.</p>
                                </div>

                                <ul class="space-y-3">
                                    <?php $benefits = explode(',', $user['benefits']);
                                    foreach ($benefits as $benefit): ?>
                                        <li class="flex items-start gap-3 text-sm text-slate-300">
                                            <span
                                                class="material-symbols-outlined text-green-400 text-lg mt-0.5">check_circle</span>
                                            <?php echo trim($benefit); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Quick Actions -->
                            <div class="glass-panel p-6">
                                <h3 class="text-lg font-bold text-white mb-4">Quick Actions</h3>
                                <div class="grid grid-cols-2 gap-3">
                                    <a href="booking/index.php"
                                        class="p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 transition flex flex-col items-center justify-center text-center gap-2">
                                        <span class="material-symbols-outlined text-accent">calendar_add_on</span>
                                        <span class="text-xs font-semibold">New Booking</span>
                                    </a>
                                    <a href="contact.php"
                                        class="p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 transition flex flex-col items-center justify-center text-center gap-2">
                                        <span class="material-symbols-outlined text-blue-400">support_agent</span>
                                        <span class="text-xs font-semibold">Support</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tabs & Content -->
                    <div class="lg:w-2/3 flex-grow">
                        <div class="glass-panel min-h-[500px]">
                            <!-- Tab Navigation -->
                            <div class="flex flex-wrap border-b border-white/10 p-2 gap-2">
                                <button
                                    class="tab-button w-full sm:w-auto active flex items-center justify-center gap-2"
                                    onclick="switchTab('bookings')">
                                    <span class="material-symbols-outlined">hotel</span>
                                    <span><?php _e('profile_full.booking_history'); ?></span>
                                    <span
                                        class="ml-1 text-xs px-1.5 py-0.5 rounded-full bg-slate-700"><?php echo $stats['total_bookings']; ?></span>
                                </button>
                                <button class="tab-button w-full sm:w-auto flex items-center justify-center gap-2"
                                    onclick="switchTab('points')">
                                    <span class="material-symbols-outlined">stars</span>
                                    <span><?php _e('profile_full.points_history');
                                    ; ?></span>
                                </button>
                                <button class="tab-button w-full sm:w-auto flex items-center justify-center gap-2"
                                    onclick="switchTab('payments')">
                                    <span class="material-symbols-outlined">receipt</span>
                                    <span><?php _e('profile_full.payments'); ?></span>
                                </button>
                                <button class="tab-button w-full sm:w-auto flex items-center justify-center gap-2"
                                    onclick="switchTab('contacts')">
                                    <span class="material-symbols-outlined">mail</span>
                                    <span><?php _e('profile_full.contacts'); ?></span>
                                </button>
                            </div>

                            <!-- Content Area -->
                            <div class="p-6">
                                <!-- Bookings Tab -->
                                <div id="tab-bookings" class="tab-content active">
                                    <?php if (empty($bookings)): ?>
                                        <div class="text-center py-12">
                                            <div
                                                class="w-16 h-16 mx-auto mb-4 bg-slate-800 rounded-full flex items-center justify-center">
                                                <span
                                                    class="material-symbols-outlined text-3xl text-slate-500">calendar_today</span>
                                            </div>
                                            <p class="text-slate-400 mb-4"><?php _e('profile_full.no_bookings'); ?></p>
                                            <a href="booking/index.php"
                                                class="px-6 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition text-sm font-medium">Book
                                                Now</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="space-y-4">
                                            <?php foreach ($bookings as $booking): ?>
                                                <div
                                                    class="group relative bg-slate-800/50 hover:bg-slate-800 border border-white/5 rounded-xl p-5 transition-all duration-300 card-hover-effect">
                                                    <div
                                                        class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-4">
                                                        <div class="flex items-start gap-4">
                                                            <div
                                                                class="w-12 h-12 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                                                                <span
                                                                    class="material-symbols-outlined text-blue-400 text-2xl">bed</span>
                                                            </div>
                                                            <div>
                                                                <h3
                                                                    class="font-bold text-lg text-white group-hover:text-accent transition-colors">
                                                                    <?php echo htmlspecialchars($booking['type_name']); ?>
                                                                </h3>
                                                                <div class="text-sm text-slate-400 font-mono mt-0.5">
                                                                    #<?php echo $booking['booking_code']; ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-col items-end gap-2">
                                                            <?php echo getStatusBadge($booking['status']); ?>
                                                            <?php echo getPaymentStatusBadge($booking['payment_status']); ?>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="grid grid-cols-2 md:grid-cols-4 gap-y-4 gap-x-2 text-sm pt-4 border-t border-white/5">
                                                        <div>
                                                            <div class="text-slate-500 text-xs mb-1">
                                                                <?php _e('profile_full.check_in'); ?>
                                                            </div>
                                                            <div class="font-semibold text-white">
                                                                <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="text-slate-500 text-xs mb-1">
                                                                <?php _e('profile_full.check_out'); ?>
                                                            </div>
                                                            <div class="font-semibold text-white">
                                                                <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="text-slate-500 text-xs mb-1">Duration</div>
                                                            <div class="font-semibold text-white">
                                                                <?php echo $booking['total_nights']; ?> nights
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="text-slate-500 text-xs mb-1">Total</div>
                                                            <div class="font-bold text-accent">
                                                                <?php echo number_format($booking['total_amount']); ?> đ
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Points Tab -->
                                <div id="tab-points" class="tab-content">
                                    <?php if (empty($points_history)): ?>
                                        <div class="text-center py-12 text-slate-400"><?php _e('profile_full.no_points'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div
                                            class="relative overflow-hidden rounded-xl bg-slate-800/40 border border-white/5">
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-sm">
                                                    <thead class="bg-slate-800/80 text-xs uppercase text-slate-400">
                                                        <tr>
                                                            <th class="px-6 py-4 font-semibold">Date</th>
                                                            <th class="px-6 py-4 font-semibold">Description</th>
                                                            <th class="px-6 py-4 font-semibold text-right">Points</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-white/5">
                                                        <?php foreach ($points_history as $trans): ?>
                                                            <tr class="hover:bg-white/5 transition-colors">
                                                                <td class="px-6 py-4 text-slate-300 whitespace-nowrap">
                                                                    <?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?>
                                                                </td>
                                                                <td class="px-6 py-4 text-white font-medium">
                                                                    <div class="flex items-center gap-2">
                                                                        <span
                                                                            class="material-symbols-outlined text-lg <?php echo $trans['transaction_type'] == 'earn' ? 'text-green-400' : 'text-red-400'; ?>">
                                                                            <?php echo $trans['transaction_type'] == 'earn' ? 'arrow_upward' : 'arrow_downward'; ?>
                                                                        </span>
                                                                        <?php echo htmlspecialchars($trans['description']); ?>
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4 text-right">
                                                                    <span
                                                                        class="font-bold <?php echo $trans['transaction_type'] == 'earn' ? 'text-green-400' : 'text-red-400'; ?>">
                                                                        <?php echo $trans['transaction_type'] == 'earn' ? '+' : '-'; ?>
                                                                        <?php echo number_format($trans['points']); ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Payments Tab -->
                                <div id="tab-payments" class="tab-content">
                                    <?php if (empty($payments)): ?>
                                        <div class="text-center py-12 text-slate-400">
                                            <?php _e('profile_full.no_payments'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="space-y-3">
                                            <?php foreach ($payments as $payment): ?>
                                                <div
                                                    class="bg-slate-800/40 border border-white/5 rounded-xl p-5 flex items-center justify-between hover:bg-slate-800/60 transition-colors">
                                                    <div class="flex items-center gap-4">
                                                        <div
                                                            class="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center">
                                                            <span
                                                                class="material-symbols-outlined text-green-400">payments</span>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold text-white">
                                                                <?php echo number_format($payment['amount']); ?> đ
                                                            </div>
                                                            <div class="text-xs text-slate-500">Ref:
                                                                <?php echo $payment['booking_code']; ?> •
                                                                <?php echo strtoupper($payment['payment_method']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="mb-1"><span
                                                                class="badge badge-success"><?php echo $payment['status']; ?></span>
                                                        </div>
                                                        <div class="text-xs text-slate-500">
                                                            <?php echo date('d/m/Y', strtotime($payment['created_at'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif;
                                    ; ?>
                                </div>

                                <!-- Contacts Tab -->
                                <div id="tab-contacts" class="tab-content">
                                    <div class="flex justify-between items-center mb-6">
                                        <h3 class="text-lg font-bold text-white">Message History</h3>
                                        <a href="contact.php"
                                            class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm transition flex items-center gap-2">
                                            <span class="material-symbols-outlined text-sm">edit_square</span>
                                            New Message
                                        </a>
                                    </div>

                                    <?php if (empty($contacts)): ?>
                                        <div class="text-center py-12">
                                            <div
                                                class="w-16 h-16 mx-auto mb-4 bg-slate-800 rounded-full flex items-center justify-center">
                                                <span class="material-symbols-outlined text-3xl text-slate-500">inbox</span>
                                            </div>
                                            <p class="text-slate-400 mb-4"><?php _e('profile_full.no_contacts'); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="space-y-4">
                                            <?php foreach ($contacts as $contact): ?>
                                                <div
                                                    class="bg-slate-800/40 hover:bg-slate-800/60 border border-white/5 rounded-xl p-5 transition-all">
                                                    <div class="flex justify-between items-start mb-3">
                                                        <div class="flex items-center gap-3">
                                                            <div
                                                                class="w-10 h-10 bg-accent/10 rounded-full flex items-center justify-center shrink-0">
                                                                <span class="material-symbols-outlined text-accent">mail</span>
                                                            </div>
                                                            <div>
                                                                <h4 class="font-bold text-white text-base">
                                                                    <?php echo htmlspecialchars($contact['subject'] ?? __('profile_full.general_contact')); ?>
                                                                </h4>
                                                                <span
                                                                    class="text-xs font-mono text-slate-500">#<?php echo htmlspecialchars($contact['display_code']); ?></span>
                                                            </div>
                                                        </div>
                                                        <?php echo getContactStatusBadge($contact['status']); ?>
                                                    </div>

                                                    <p
                                                        class="text-slate-300 text-sm bg-black/20 p-3 rounded-lg mb-3 leading-relaxed">
                                                        <?php echo nl2br(htmlspecialchars(mb_substr($contact['message'], 0, 150))); ?>
                                                        <?php echo mb_strlen($contact['message']) > 150 ? '...' : ''; ?>
                                                    </p>

                                                    <div class="flex justify-between items-center pt-2 border-t border-white/5">
                                                        <span class="text-xs text-slate-500 flex items-center gap-1">
                                                            <span class="material-symbols-outlined text-xs">schedule</span>
                                                            <?php echo date('d/m/Y H:i', strtotime($contact['created_at'])); ?>
                                                        </span>
                                                        <button onclick="viewContactDetail(<?php echo $contact['id']; ?>)"
                                                            class="text-accent text-sm hover:text-white transition-colors flex items-center gap-1 font-medium">
                                                            View Full Details <span
                                                                class="material-symbols-outlined text-sm">arrow_forward</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <?php include 'includes/footer.php'; ?>

    </div>

    <!-- Contact Detail Modal -->
    <div id="contactModal"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-opacity duration-300">
        <div class="glass-panel w-full max-w-2xl max-h-[90vh] overflow-y-auto transform scale-95 opacity-0 transition-all duration-300"
            id="contactModalContainer">
            <div
                class="p-6 border-b border-white/10 flex justify-between items-center sticky top-0 bg-slate-900/95 backdrop-blur-xl z-20">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-accent">mail_lock</span>
                    <?php _e('profile_full.contact_detail'); ?>
                </h3>
                <button onclick="closeContactModal()"
                    class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors text-slate-400 hover:text-white">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>
            <div id="contactModalContent" class="p-6">
                <div class="flex flex-col items-center py-12">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-accent mb-4"></div>
                    <p class="text-slate-400">Loading details...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            const tab = document.getElementById('tab-' + tabName);
            if (tab) tab.classList.add('active');

            // Add active to the corresponding button
            // Using attribute selector to find the button that calls this function
            const btn = document.querySelector(`button[onclick="switchTab('${tabName}')"]`);
            if (btn) btn.classList.add('active');
        }

        function viewContactDetail(id) {
            const modal = document.getElementById('contactModal');
            const container = document.getElementById('contactModalContainer');
            const content = document.getElementById('contactModalContent');

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Animation
            setTimeout(() => {
                if (container) {
                    container.classList.remove('scale-95', 'opacity-0');
                    container.classList.add('scale-100', 'opacity-100');
                }
            }, 10);

            content.innerHTML = '<div class="flex flex-col items-center py-12"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-accent mb-4"></div><p class="text-slate-400">Loading details...</p></div>';

            fetch('profile/api/contact-detail.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const c = data.contact;
                        const statusBadges = {
                            'new': '<span class="badge badge-info">Chờ phản hồi</span>',
                            'in_progress': '<span class="badge badge-warning">Đang xử lý</span>',
                            'resolved': '<span class="badge badge-success">Đã phản hồi</span>',
                            'closed': '<span class="badge badge-secondary">Đã đóng</span>'
                        };

                        content.innerHTML = `
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-slate-400 uppercase tracking-wider">Contact Code</p>
                                <p class="text-2xl font-bold font-mono text-accent">#${c.display_code}</p>
                            </div>
                            ${statusBadges[c.status] || c.status}
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/5 rounded-lg p-4 border border-white/5">
                                <p class="text-xs text-slate-400 mb-1 uppercase tracking-wider">Subject</p>
                                <p class="font-semibold text-white">${escapeHtml(c.subject || 'General Contact')}</p>
                            </div>
                            <div class="bg-white/5 rounded-lg p-4 border border-white/5">
                                <p class="text-xs text-slate-400 mb-1 uppercase tracking-wider">Date Sent</p>
                                <p class="font-semibold text-white">${c.created_at}</p>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-sm text-slate-400 mb-2">Message Content</p>
                            <div class="bg-slate-900/50 border border-white/5 rounded-lg p-4">
                                <p class="text-slate-300 whitespace-pre-wrap leading-relaxed">${escapeHtml(c.message)}</p>
                            </div>
                        </div>
                        
                        ${c.admin_note ? `
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-green-400 text-sm">reply</span>
                                <p class="text-sm text-green-400">Response from Hotel</p>
                            </div>
                            <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4">
                                <p class="text-green-300 whitespace-pre-wrap">${escapeHtml(c.admin_note)}</p>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="pt-6 border-t border-white/10 flex justify-end gap-3">
                            <button onclick="closeContactModal()" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 text-white rounded-lg transition-colors text-sm font-medium">Close</button>
                            <a href="contact.php" class="px-5 py-2.5 bg-accent hover:bg-accent/80 text-white rounded-lg transition-colors flex items-center gap-2 text-sm font-medium">
                                <span class="material-symbols-outlined text-sm">send</span>
                                New Message
                            </a>
                        </div>
                    </div>
                `;
                    } else {
                        content.innerHTML = '<p class="text-center text-red-500 py-8">Không thể tải thông tin liên hệ</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = '<p class="text-center text-red-500 py-8">Có lỗi xảy ra</p>';
                });
        }

        function closeContactModal() {
            const modal = document.getElementById('contactModal');
            const container = document.getElementById('contactModalContainer');

            if (container) {
                container.classList.remove('scale-100', 'opacity-100');
                container.classList.add('scale-95', 'opacity-0');
            }

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Auto switch to contacts tab if hash or localStorage
        document.addEventListener('DOMContentLoaded', function () {
            const hash = window.location.hash.replace('#', '');
            const savedTab = localStorage.getItem('profileTab');

            if (hash === 'contacts' || savedTab === 'contacts') {
                switchTabByName('contacts');
                localStorage.removeItem('profileTab');
            }
        });

        function switchTabByName(tabName) {
            switchTab(tabName);
        }
    </script>

</body>

</html>