<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/language.php';
initLanguage();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$booking_id = $_GET['id'] ?? 0;

if (!$booking_id) {
    header('Location: bookings.php');
    exit;
}

try {
    $db = getDB();
    
    // Get booking details - only for current user
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name, r.room_number
        FROM bookings b
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_id = :booking_id AND b.user_id = :user_id
    ");
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':user_id' => $_SESSION['user_id']
    ]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header('Location: bookings.php');
        exit;
    }
    
    // QR code URL using local library
    $qr_url = 'api/get-qrcode.php?booking_id=' . $booking_id;
    
} catch (Exception $e) {
    error_log("View QR error: " . $e->getMessage());
    header('Location: bookings.php');
    exit;
}

?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php _e('profile_qrcode.title'); ?></title>
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
<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="booking-detail.php?id=<?php echo $booking_id; ?>" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <span class="material-symbols-outlined">arrow_back</span>
            <span><?php _e('profile_qrcode.back'); ?></span>
        </a>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- QR Code Display -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white"><?php _e('profile_qrcode.your_qr'); ?></h2>
            
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 p-8 rounded-xl flex items-center justify-center mb-6">
                <img id="qrImage" src="<?php echo $qr_url; ?>" alt="QR Code" class="w-full max-w-sm" onerror="handleQRError()">
            </div>
            
            <div class="space-y-3">
                <button onclick="downloadQR()" class="w-full bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">download</span>
                    <?php _e('profile_qrcode.download_qr'); ?>
                </button>
                
                <button onclick="takeScreenshot()" class="w-full bg-blue-600 text-white py-3 px-6 rounded-xl font-semibold hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">screenshot</span>
                    <?php _e('profile_qrcode.screenshot'); ?>
                </button>
                
                <button onclick="shareQR()" class="w-full bg-gray-600 text-white py-3 px-6 rounded-xl font-semibold hover:bg-gray-700 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">share</span>
                    <?php _e('profile_qrcode.share'); ?>
                </button>
            </div>
            
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">info</span>
                    <div class="flex-1 text-sm text-blue-800 dark:text-blue-200">
                        <p class="font-semibold mb-2" <?php _e('profile_qrcode.tips_title'); ?></p>
                        <ul class="space-y-1">
                            <li><?php _e('profile_qrcode.tip_2'); ?></li>
                            <li> <?php _e('profile_qrcode.tip_3'); ?></li>
                            <li><?php _e('profile_qrcode.tip_4'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Booking Info -->
        <!-- Booking Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white"><?php _e('profile_qrcode.booking_info'); ?></h2>
            
            <div class="space-y-6">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1"><?php _e('profile_qrcode.booking_code'); ?></p>
                    <p class="font-bold text-3xl" style="color: #d4af37;"><?php echo htmlspecialchars($booking['booking_code']); ?></p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1"><?php _e('profile_qrcode.customer'); ?></p>
                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1"><?php _e('profile_qrcode.phone'); ?></p>
                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                    </div>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1"><?php _e('profile_qrcode.room_type'); ?></p>
                    <p class="font-semibold text-lg text-gray-900 dark:text-white"><?php echo htmlspecialchars($booking['type_name']); ?></p>
                </div>
                
                <?php if ($booking['room_number']): ?>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl">
                    <p class="text-sm text-green-600 dark:text-green-400 mb-1"><?php _e('profile_qrcode.room_assigned'); ?></p>
                    <p class="font-bold text-2xl text-green-700 dark:text-green-300"><?php _e('profile_qrcode.room'); ?> <?php echo htmlspecialchars($booking['room_number']); ?></p>
                </div>
                <?php else: ?>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-xl">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300"><?php _e('profile_qrcode.room_pending'); ?></p>
                <?php endif; ?>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-xl">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1"><?php _e('profile_qrcode.check_in'); ?></p>
                        <p class="font-bold text-lg text-gray-900 dark:text-white"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?php _e('profile_qrcode.after_time'); ?></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-xl">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1"><?php _e('profile_qrcode.check_out'); ?></p>
                        <p class="font-bold text-lg text-gray-900 dark:text-white"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?php _e('profile_qrcode.before_time'); ?></p>
                    </div>
                </div>
                
                <div class="border-t border-gray-00 darrk:border-gray-700 pt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1"><?php _e('profile_qrcodtotal'); ?></p>
                    <p class="font-bold text-3xl" style="color: #d4af37;">
                        <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>đ
                    </p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2"><?php _e('profile_qrcode.status'); ?></p>
                    <?php
                    $status_classes = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'confirmed' => 'bg-blue-100 text-blue-800',
                        'checked_in' => 'bg-green-100 text-green-800',
                        'checked_out' => 'bg-gray-100 text-gray-800',
                        'cancelled' => 'bg-red-100 text-red-800'
                    ];
                    $status_labels = [
                        'pending' => __('booking_status.pending'),
                        'confirmed' => __('booking_status.confirmed'),
                        'checked_in' => __('booking_status.checked_in'),
                        'checked_out' => __('booking_status.checked_out'),
                        'cancelled' => __('booking_status.cancelled')
                    ];
                    ?>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold <?php echo $status_classes[$booking['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                        <?php echo $status_labels[$booking['status']] ?? $booking['status']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function downloadQR() {
    window.location.href = 'api/download-qrcode.php?booking_id=<?php echo $booking_id; ?>';
}

function takeScreenshot() {
    alert('📱 Hướng dẫn chụp màn hình:\n\n' +
          'iPhone: Nhấn nút Nguồn + Tăng âm lượng\n' +
          'Android: Nhấn nút Nguồn + Giảm âm lượng\n' +
          'Máy tính: Sử dụng công cụ Snipping Tool hoặc Screenshot\n\n' +
          'Sau khi chụp, ảnh sẽ được lưu vào thư viện của bạn.');
}

function shareQR() {
    if (navigator.share) {
        navigator.share({
            title: 'QR Code - <?php echo $booking['booking_code']; ?>',
            text: 'Mã QR cho đặt phòng tại Aurora Hotel Plaza',
            url: window.location.href
        }).catch(err => console.log('Error sharing:', err));
    } else {
        // Fallback: copy link
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Đã sao chép link vào clipboard!');
        });
    }
}

function handleQRError() {
    const qrImage = document.getElementById('qrImage');
    qrImage.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect width="400" height="400" fill="%23f0f0f0"/><text x="50%" y="50%" text-anchor="middle" fill="%23666" font-size="16">Không thể tải QR Code</text></svg>';
}
</script>

</main>
<?php include '../includes/footer.php'; ?>
</div>
</body>
</html>
