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
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Chờ xác nhận</span>',
        'confirmed' => '<span class="badge badge-success">Đã xác nhận</span>',
        'checked_in' => '<span class="badge badge-info">Đang ở</span>',
        'checked_out' => '<span class="badge badge-secondary">Đã trả phòng</span>',
        'cancelled' => '<span class="badge badge-danger">Đã hủy</span>',
    ];
    return $badges[$status] ?? $status;
}

function getPaymentStatusBadge($status) {
    $badges = [
        'unpaid' => '<span class="badge badge-warning">Chưa thanh toán</span>',
        'paid' => '<span class="badge badge-success">Đã thanh toán</span>',
        'refunded' => '<span class="badge badge-info">Đã hoàn tiền</span>',
    ];
    return $badges[$status] ?? $status;
}

function getContactStatusBadge($status) {
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
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php _e('profile_full.title'); ?> - <?php echo htmlspecialchars($user['full_name']); ?></title>

<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">

<style>
.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 0.375rem;
}
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-info { background: #dbeafe; color: #1e40af; }
.badge-secondary { background: #e5e7eb; color: #374151; }

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.tier-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-weight: bold;
    font-size: 1.125rem;
}

.tab-button {
    padding: 0.75rem 1.5rem;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
}

.tab-button.active {
    border-bottom-color: #d4af37;
    color: #d4af37;
    font-weight: 600;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-body">
<div class="relative flex min-h-screen w-full flex-col">

<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-20 pb-10">
    <div class="mx-auto w-full max-w-7xl px-4 py-8">
        
        <!-- User Header -->
        <div class="bg-gradient-to-r from-primary-light to-accent p-8 rounded-xl text-white mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center text-primary-light text-3xl font-bold">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                        <p class="text-white/80"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-white/80"><?php echo htmlspecialchars($user['phone'] ?? __('profile_full.not_updated')); ?></p>
                    </div>
                </div>
                
                <?php if ($user['tier_name']): ?>
                <div class="tier-badge" style="background: <?php echo $user['color_code']; ?>20; color: <?php echo $user['color_code']; ?>; border: 2px solid <?php echo $user['color_code']; ?>;">
                    <span class="material-symbols-outlined">workspace_premium</span>
                    <?php echo $user['tier_name']; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm"><?php _e('profile_full.current_points'); ?></p>
                        <p class="text-3xl font-bold text-accent"><?php echo number_format($user['current_points'] ?? 0); ?></p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-accent">stars</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm"><?php _e('profile_full.lifetime_points'); ?></p>
                        <p class="text-3xl font-bold text-primary-light"><?php echo number_format($user['lifetime_points'] ?? 0); ?></p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-primary-light">emoji_events</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm"><?php _e('profile_full.total_bookings'); ?></p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_bookings']; ?></p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-blue-600">hotel</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm"><?php _e('profile_full.total_spent'); ?></p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($stats['total_spent']); ?> đ</p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-green-600">payments</span>
                </div>
            </div>
        </div>

        <!-- Membership Benefits -->
        <?php if ($user['tier_name']): ?>
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <h2 class="text-xl font-bold mb-4">🎁 <?php _e('profile_full.tier_benefits'); ?> <?php echo $user['tier_name']; ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-accent">discount</span>
                    <span><?php _e('profile_full.discount'); ?>: <strong><?php echo $user['discount_percentage']; ?>%</strong></span>
                </div>
                <?php 
                $benefits = explode(',', $user['benefits']);
                foreach ($benefits as $benefit): 
                ?>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-accent">check_circle</span>
                    <span><?php echo trim($benefit); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="bg-white rounded-xl shadow">
            <div class="border-b flex gap-4 px-6">
                <button class="tab-button active" onclick="switchTab('bookings')">
                    <span class="material-symbols-outlined" style="vertical-align: middle;">hotel</span>
                    <?php _e('profile_full.booking_history'); ?> (<?php echo $stats['total_bookings']; ?>)
                </button>
                <button class="tab-button" onclick="switchTab('points')">
                    <span class="material-symbols-outlined" style="vertical-align: middle;">stars</span>
                    <?php _e('profile_full.points_history'); ?> (<?php echo count($points_history); ?>)
                </button>
                <button class="tab-button" onclick="switchTab('payments')">
                    <span class="material-symbols-outlined" style="vertical-align: middle;">receipt</span>
                    <?php _e('profile_full.payments'); ?> (<?php echo count($payments); ?>)
                </button>
                <button class="tab-button" onclick="switchTab('contacts')">
                    <span class="material-symbols-outlined" style="vertical-align: middle;">mail</span>
                    <?php _e('profile_full.contacts'); ?> (<?php echo count($contacts); ?>)
                </button>
            </div>

            <!-- Bookings Tab -->
            <div id="tab-bookings" class="tab-content active p-6">
                <?php if (empty($bookings)): ?>
                    <p class="text-center text-gray-500 py-8"><?php _e('profile_full.no_bookings'); ?></p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($bookings as $booking): ?>
                        <div class="border rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-bold text-lg"><?php echo htmlspecialchars($booking['type_name']); ?></h3>
                                    <p class="text-gray-600"><?php _e('profile_full.booking_code'); ?>: <?php echo $booking['booking_code']; ?></p>
                                    <?php if ($booking['room_number']): ?>
                                    <p class="text-gray-600"><?php _e('profile_full.room'); ?>: <?php echo $booking['room_number']; ?> - <?php _e('profile_full.floor'); ?> <?php echo $booking['floor']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <?php echo getStatusBadge($booking['status']); ?>
                                    <?php echo getPaymentStatusBadge($booking['payment_status']); ?>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600"><?php _e('profile_full.check_in'); ?></p>
                                    <p class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600"><?php _e('profile_full.check_out'); ?></p>
                                    <p class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600"><?php _e('profile_full.num_nights'); ?></p>
                                    <p class="font-semibold"><?php echo $booking['total_nights']; ?> đêm</p>
                                </div>
                                <div>
                                    <p class="text-gray-600"><?php _e('profile_full.total_amount'); ?></p>
                                    <p class="font-semibold text-accent"><?php echo number_format($booking['total_amount']); ?> đ</p>
                                </div>
                            </div>
                            
                            <?php if ($booking['special_requests']): ?>
                            <div class="mt-3 p-3 bg-gray-50 rounded">
                                <p class="text-sm text-gray-600"><?php _e('profile_full.special_requests'); ?>:</p>
                                <p class="text-sm"><?php echo htmlspecialchars($booking['special_requests']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-3 text-xs text-gray-500">
                                <?php _e('profile_full.booked_at'); ?>: <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Points Tab -->
            <div id="tab-points" class="tab-content p-6">
                <?php if (empty($points_history)): ?>
                    <p class="text-center text-gray-500 py-8"><?php _e('profile_full.no_points'); ?></p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($points_history as $trans): ?>
                        <div class="flex justify-between items-center border-b pb-3">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-2xl <?php echo $trans['transaction_type'] == 'earn' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $trans['transaction_type'] == 'earn' ? 'add_circle' : 'remove_circle'; ?>
                                </span>
                                <div>
                                    <p class="font-semibold"><?php echo htmlspecialchars($trans['description']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-lg <?php echo $trans['transaction_type'] == 'earn' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $trans['transaction_type'] == 'earn' ? '+' : '-'; ?><?php echo number_format($trans['points']); ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payments Tab -->
            <div id="tab-payments" class="tab-content p-6">
                <?php if (empty($payments)): ?>
                    <p class="text-center text-gray-500 py-8"><?php _e('profile_full.no_payments'); ?></p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($payments as $payment): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-bold">Booking: <?php echo $payment['booking_code']; ?></p>
                                    <p class="text-sm text-gray-600"><?php _e('profile_full.method'); ?>: <?php echo strtoupper($payment['payment_method']); ?></p>
                                    <?php if ($payment['transaction_id']): ?>
                                    <p class="text-sm text-gray-600"><?php _e('profile_full.transaction_id'); ?>: <?php echo $payment['transaction_id']; ?></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-xl text-green-600"><?php echo number_format($payment['amount']); ?> đ</p>
                                    <span class="badge badge-success"><?php echo $payment['status']; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contacts Tab -->
            <div id="tab-contacts" class="tab-content p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-lg"><?php _e('profile_full.contact_history'); ?></h3>
                    <a href="contact.php" class="bg-accent text-white px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">add</span>
                        <?php _e('profile_full.new_contact'); ?>
                    </a>
                </div>
                <?php if (empty($contacts)): ?>
                    <div class="text-center py-12">
                        <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">mail</span>
                        <p class="text-gray-500 mb-4"><?php _e('profile_full.no_contacts'); ?></p>
                        <a href="contact.php" class="inline-flex items-center gap-2 bg-accent text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 transition">
                            <span class="material-symbols-outlined">send</span>
                            <?php _e('profile_full.first_contact'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($contacts as $contact): ?>
                        <div class="border rounded-xl p-5 hover:shadow-md transition bg-white">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-accent/10 rounded-full flex items-center justify-center">
                                        <span class="material-symbols-outlined text-accent">mail</span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900"><?php echo htmlspecialchars($contact['subject'] ?? __('profile_full.general_contact')); ?></p>
                                        <p class="text-sm text-gray-500 font-mono">#<?php echo htmlspecialchars($contact['display_code']); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <?php echo getContactStatusBadge($contact['status']); ?>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4 mb-3">
                                <p class="text-gray-700 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars(mb_substr($contact['message'], 0, 200))); ?><?php echo mb_strlen($contact['message']) > 200 ? '...' : ''; ?></p>
                            </div>
                            
                            <div class="flex justify-between items-center text-sm">
                                <div class="flex items-center gap-4 text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">schedule</span>
                                        <?php echo date('d/m/Y H:i', strtotime($contact['created_at'])); ?>
                                    </span>
                                </div>
                                <button onclick="viewContactDetail(<?php echo $contact['id']; ?>)" class="text-accent hover:underline font-medium flex items-center gap-1">
                                    <?php _e('profile_full.view_detail'); ?>
                                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php include 'includes/footer.php'; ?>

</div>

<!-- Contact Detail Modal -->
<div id="contactModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800">
            <h3 class="text-lg font-bold"><?php _e('profile_full.contact_detail'); ?></h3>
            <button onclick="closeContactModal()" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="contactModalContent" class="p-6">
            <div class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-accent"></div>
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
    document.getElementById('tab-' + tabName).classList.add('active');
    
    // Add active to clicked button
    event.target.closest('.tab-button').classList.add('active');
}

function viewContactDetail(id) {
    const modal = document.getElementById('contactModal');
    const content = document.getElementById('contactModalContent');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    content.innerHTML = '<div class="flex justify-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-accent"></div></div>';
    
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
                                <p class="text-sm text-gray-500">Mã liên hệ</p>
                                <p class="text-2xl font-bold font-mono text-accent">#${c.display_code}</p>
                            </div>
                            ${statusBadges[c.status] || c.status}
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500 mb-1">Chủ đề</p>
                                <p class="font-semibold">${escapeHtml(c.subject || 'Liên hệ chung')}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500 mb-1">Thời gian gửi</p>
                                <p class="font-semibold">${c.created_at}</p>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-500 mb-2">Nội dung tin nhắn</p>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">${escapeHtml(c.message)}</p>
                            </div>
                        </div>
                        
                        ${c.admin_note ? `
                        <div>
                            <p class="text-sm text-gray-500 mb-2">Phản hồi từ khách sạn</p>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <p class="text-green-800 whitespace-pre-wrap">${escapeHtml(c.admin_note)}</p>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="pt-4 border-t flex justify-end gap-3">
                            <button onclick="closeContactModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-100">Đóng</button>
                            <a href="contact.php" class="px-4 py-2 bg-accent text-white rounded-lg hover:opacity-90">Gửi liên hệ mới</a>
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
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auto switch to contacts tab if hash or localStorage
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.replace('#', '');
    const savedTab = localStorage.getItem('profileTab');
    
    if (hash === 'contacts' || savedTab === 'contacts') {
        switchTabByName('contacts');
        localStorage.removeItem('profileTab');
    }
});

function switchTabByName(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    const tabContent = document.getElementById('tab-' + tabName);
    if (tabContent) {
        tabContent.classList.add('active');
    }
    
    // Find and activate the correct button
    document.querySelectorAll('.tab-button').forEach(btn => {
        if (btn.textContent.toLowerCase().includes(tabName === 'contacts' ? 'liên hệ' : tabName)) {
            btn.classList.add('active');
        }
    });
}
</script>

</body>
</html>
