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
    
    // Get booking details with full information - only for current user
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name, rt.type_name_en, rt.category, rt.bed_type, rt.size_sqm,
               r.room_number, r.floor,
               p.payment_method, p.transaction_id, p.paid_at
        FROM bookings b
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id AND p.status = 'completed'
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
    
    // Calculate nights
    $check_in = new DateTime($booking['check_in_date']);
    $check_out = new DateTime($booking['check_out_date']);
    $nights = $check_in->diff($check_out)->days;
    
    // Status labels and classes
    $status_config = [
        'pending' => ['label' => __('booking_status.pending'), 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', 'icon' => 'schedule'],
        'confirmed' => ['label' => __('booking_status.confirmed'), 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', 'icon' => 'check_circle'],
        'checked_in' => ['label' => __('booking_status.checked_in'), 'class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', 'icon' => 'door_open'],
        'checked_out' => ['label' => __('booking_status.checked_out'), 'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', 'icon' => 'door_front'],
        'cancelled' => ['label' => __('booking_status.cancelled'), 'class' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', 'icon' => 'cancel'],
        'no_show' => ['label' => __('booking_status.no_show') ?? 'Không đến', 'class' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300', 'icon' => 'person_off']
    ];
    
    $payment_status_config = [
        'unpaid' => ['label' => __('payment_status.unpaid') ?? 'Chưa thanh toán', 'class' => 'bg-red-100 text-red-800'],
        'partial' => ['label' => __('payment_status.partial') ?? 'Thanh toán một phần', 'class' => 'bg-yellow-100 text-yellow-800'],
        'paid' => ['label' => __('payment_status.paid') ?? 'Đã thanh toán', 'class' => 'bg-green-100 text-green-800'],
        'refunded' => ['label' => __('payment_status.refunded') ?? 'Đã hoàn tiền', 'class' => 'bg-purple-100 text-purple-800']
    ];
    
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
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
    <title><?php _e('profile_qrcode.title'); ?> - <?php echo $booking['booking_code']; ?></title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <link href="../assets/css/fonts.css" rel="stylesheet"/>
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
    <link rel="stylesheet" href="./assets/css/qr-popup.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-24 pb-16">
<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- Back Button -->
    <div class="mb-6 no-print">
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
            
            <div class="space-y-3 no-print">
                <button onclick="openQRPopup()" class="w-full bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">qr_code_2</span>
                    <?php _e('profile_qrcode.download_qr'); ?>
                </button>
                
                <button onclick="printBookingQR()" class="w-full bg-blue-600 text-white py-3 px-6 rounded-xl font-semibold hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">print</span>
                    In QR Code
                </button>
                
                <button onclick="shareQR()" class="w-full bg-gray-600 text-white py-3 px-6 rounded-xl font-semibold hover:bg-gray-700 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">share</span>
                    <?php _e('profile_qrcode.share'); ?>
                </button>
            </div>
            
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl no-print">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">info</span>
                    <div class="flex-1 text-sm text-blue-800 dark:text-blue-200">
                        <p class="font-semibold mb-2">Hướng dẫn sử dụng:</p>
                        <ul class="space-y-1 list-disc list-inside">
                            <li>Xuất trình mã QR này khi check-in tại quầy lễ tân</li>
                            <li>Bạn có thể tải xuống hoặc in mã QR để sử dụng</li>
                            <li>Mã QR chứa đầy đủ thông tin đặt phòng của bạn</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Booking Info - Full Details -->
        <div id="printArea" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
            <div class="text-center mb-6 print:mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AURORA HOTEL PLAZA</h1>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Xác nhận đặt phòng / Booking Confirmation</p>
            </div>
            
            <div class="space-y-5">
                <!-- Booking Code -->
                <div class="text-center border-b border-gray-200 dark:border-gray-700 pb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Mã đặt phòng / Booking Code</p>
                    <p class="font-bold text-3xl" style="color: #d4af37;"><?php echo htmlspecialchars($booking['booking_code']); ?></p>
                </div>
                
                <!-- Status Badges -->
                <div class="flex flex-wrap gap-2 justify-center">
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold <?php echo $status_config[$booking['status']]['class'] ?? 'bg-gray-100 text-gray-800'; ?>">
                        <span class="material-symbols-outlined text-base"><?php echo $status_config[$booking['status']]['icon'] ?? 'info'; ?></span>
                        <?php echo $status_config[$booking['status']]['label'] ?? $booking['status']; ?>
                    </span>
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold <?php echo $payment_status_config[$booking['payment_status']]['class'] ?? 'bg-gray-100 text-gray-800'; ?>">
                        <?php echo $payment_status_config[$booking['payment_status']]['label'] ?? $booking['payment_status']; ?>
                    </span>
                </div>
                
                <!-- Guest Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">person</span>
                        Thông tin khách hàng
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Họ tên</p>
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Số điện thoại</p>
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-gray-500 dark:text-gray-400">Email</p>
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($booking['guest_email']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Room Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">hotel</span>
                        Thông tin phòng
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Loại phòng</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($booking['type_name']); ?></span>
                        </div>
                        <?php if ($booking['room_number']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Số phòng</span>
                            <span class="font-bold text-green-600 dark:text-green-400 text-lg"><?php echo htmlspecialchars($booking['room_number']); ?></span>
                        </div>
                        <?php if ($booking['floor']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Tầng</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?php echo $booking['floor']; ?></span>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-2 rounded-lg text-center">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">Phòng sẽ được phân khi check-in</p>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Số khách</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                <?php echo $booking['num_adults']; ?> người lớn
                                <?php if ($booking['num_children'] > 0): ?>
                                , <?php echo $booking['num_children']; ?> trẻ em
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Số phòng</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?php echo $booking['num_rooms']; ?> phòng</span>
                        </div>
                    </div>
                </div>
                
                <!-- Check-in/Check-out -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl text-center">
                        <p class="text-xs text-green-600 dark:text-green-400 mb-1">CHECK-IN</p>
                        <p class="font-bold text-xl text-green-700 dark:text-green-300"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">Sau 14:00</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-xl text-center">
                        <p class="text-xs text-red-600 dark:text-red-400 mb-1">CHECK-OUT</p>
                        <p class="font-bold text-xl text-red-700 dark:text-red-300"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></p>
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">Trước 12:00</p>
                    </div>
                </div>
                
                <!-- Duration -->
                <div class="text-center py-2">
                    <span class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-400">
                        <span class="material-symbols-outlined text-sm">dark_mode</span>
                        <?php echo $nights; ?> đêm
                    </span>
                </div>
                
                <!-- Payment Details -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">payments</span>
                        Chi tiết thanh toán
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Giá phòng/đêm</span>
                            <span class="text-gray-900 dark:text-white"><?php echo number_format($booking['room_price'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Số đêm x Số phòng</span>
                            <span class="text-gray-900 dark:text-white"><?php echo $nights; ?> x <?php echo $booking['num_rooms']; ?></span>
                        </div>
                        <?php if ($booking['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Giảm giá</span>
                            <span>-<?php echo number_format($booking['discount_amount'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($booking['service_charges'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Phí dịch vụ</span>
                            <span class="text-gray-900 dark:text-white"><?php echo number_format($booking['service_charges'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php endif; ?>
                        <div class="border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-900 dark:text-white">Tổng cộng</span>
                                <span class="font-bold text-2xl" style="color: #d4af37;">
                                    <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>đ
                                </span>
                            </div>
                        </div>
                        <?php if ($booking['payment_method']): ?>
                        <div class="flex justify-between text-sm mt-2">
                            <span class="text-gray-500 dark:text-gray-400">Phương thức</span>
                            <span class="text-gray-900 dark:text-white">
                                <?php 
                                $payment_methods = [
                                    'vnpay' => 'VNPay',
                                    'cash' => 'Tiền mặt',
                                    'bank_transfer' => 'Chuyển khoản',
                                    'credit_card' => 'Thẻ tín dụng'
                                ];
                                echo $payment_methods[$booking['payment_method']] ?? $booking['payment_method'];
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if ($booking['transaction_id']): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Mã giao dịch</span>
                            <span class="text-gray-900 dark:text-white font-mono"><?php echo $booking['transaction_id']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Special Requests -->
                <?php if (!empty($booking['special_requests'])): ?>
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">note</span>
                        Yêu cầu đặc biệt
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Booking Time -->
                <div class="text-center text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <p>Đặt phòng lúc: <?php echo date('H:i d/m/Y', strtotime($booking['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openQRPopup() {
    const overlay = document.getElementById('qrPopupOverlay');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeQRPopup(event) {
    if (event && event.target !== event.currentTarget) return;
    const overlay = document.getElementById('qrPopupOverlay');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function downloadQRFromPopup() {
    const qrImage = document.getElementById('popupQrImage');
    const bookingCode = '<?php echo $booking['booking_code']; ?>';
    
    // Create canvas to draw QR with booking info
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const img = new Image();
    img.crossOrigin = 'anonymous';
    
    img.onload = function() {
        // Set canvas size with extra space for text
        const padding = 40;
        const headerHeight = 60;
        const footerHeight = 80;
        canvas.width = img.width + (padding * 2);
        canvas.height = img.height + headerHeight + footerHeight + (padding * 2);
        
        // White background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Header - Hotel name
        ctx.fillStyle = '#d4af37';
        ctx.font = 'bold 20px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('AURORA HOTEL PLAZA', canvas.width / 2, padding + 25);
        
        // Draw QR code
        ctx.drawImage(img, padding, headerHeight + padding, img.width, img.height);
        
        // Footer - Booking code
        const footerY = headerHeight + padding + img.height + 20;
        ctx.fillStyle = '#666666';
        ctx.font = '14px Arial';
        ctx.fillText('Mã đặt phòng / Booking Code', canvas.width / 2, footerY);
        
        ctx.fillStyle = '#d4af37';
        ctx.font = 'bold 24px Arial';
        ctx.fillText(bookingCode, canvas.width / 2, footerY + 30);
        
        // Check-in info
        ctx.fillStyle = '#888888';
        ctx.font = '12px Arial';
        ctx.fillText('Check-in: <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?> | Check-out: <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>', canvas.width / 2, footerY + 55);
        
        // Download
        const link = document.createElement('a');
        link.download = 'QRCode-' + bookingCode + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        
        showToast('Đã tải xuống QR Code!', 'success');
    };
    
    img.onerror = function() {
        // Fallback to direct download
        window.location.href = 'api/download-qrcode.php?booking_id=<?php echo $booking_id; ?>';
    };
    
    img.src = qrImage.src;
}

function screenshotQR() {
    showToast('Hãy sử dụng tổ hợp phím để chụp màn hình:\n• Windows: Win + Shift + S\n• Mac: Cmd + Shift + 4', 'info');
}

function downloadQR() {
    openQRPopup();
}

// Close popup with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQRPopup();
    }
});

function printBookingQR() {
    const printWindow = window.open('', '_blank', 'width=800,height=900');
    const qrImage = document.getElementById('qrImage').src;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QR Code - <?php echo $booking['booking_code']; ?></title>
            <meta charset="utf-8">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Arial, sans-serif;
                    padding: 30px;
                    max-width: 800px;
                    margin: 0 auto;
                    color: #333;
                }
                .header {
                    text-align: center;
                    border-bottom: 3px solid #d4af37;
                    padding-bottom: 20px;
                    margin-bottom: 25px;
                }
                .hotel-name {
                    font-size: 28px;
                    font-weight: bold;
                    color: #1a1a1a;
                    margin-bottom: 5px;
                }
                .hotel-subtitle {
                    font-size: 14px;
                    color: #666;
                }
                .booking-code {
                    font-size: 32px;
                    font-weight: bold;
                    color: #d4af37;
                    margin: 15px 0;
                    letter-spacing: 2px;
                }
                .content {
                    display: flex;
                    gap: 30px;
                }
                .qr-section {
                    flex: 0 0 250px;
                    text-align: center;
                }
                .qr-section img {
                    width: 220px;
                    height: 220px;
                    border: 2px solid #e0e0e0;
                    border-radius: 10px;
                    padding: 10px;
                    background: white;
                }
                .qr-note {
                    font-size: 11px;
                    color: #666;
                    margin-top: 10px;
                }
                .info-section {
                    flex: 1;
                }
                .info-group {
                    background: #f9f9f9;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 15px;
                }
                .info-group-title {
                    font-size: 14px;
                    font-weight: bold;
                    color: #d4af37;
                    margin-bottom: 10px;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 6px 0;
                    border-bottom: 1px dashed #e0e0e0;
                    font-size: 13px;
                }
                .info-row:last-child {
                    border-bottom: none;
                }
                .info-label {
                    color: #666;
                }
                .info-value {
                    font-weight: 600;
                    color: #333;
                }
                .dates-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    margin-bottom: 15px;
                }
                .date-box {
                    text-align: center;
                    padding: 15px;
                    border-radius: 8px;
                }
                .date-box.checkin {
                    background: #e8f5e9;
                    border: 1px solid #a5d6a7;
                }
                .date-box.checkout {
                    background: #ffebee;
                    border: 1px solid #ef9a9a;
                }
                .date-label {
                    font-size: 11px;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-bottom: 5px;
                }
                .date-box.checkin .date-label { color: #2e7d32; }
                .date-box.checkout .date-label { color: #c62828; }
                .date-value {
                    font-size: 18px;
                    font-weight: bold;
                }
                .date-box.checkin .date-value { color: #1b5e20; }
                .date-box.checkout .date-value { color: #b71c1c; }
                .date-time {
                    font-size: 11px;
                    color: #666;
                    margin-top: 3px;
                }
                .total-amount {
                    background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
                    color: white;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                }
                .total-label {
                    font-size: 12px;
                    opacity: 0.9;
                }
                .total-value {
                    font-size: 28px;
                    font-weight: bold;
                }
                .status-badges {
                    display: flex;
                    justify-content: center;
                    gap: 10px;
                    margin: 15px 0;
                }
                .badge {
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                }
                .badge-confirmed { background: #e3f2fd; color: #1565c0; }
                .badge-paid { background: #e8f5e9; color: #2e7d32; }
                .badge-pending { background: #fff3e0; color: #e65100; }
                .badge-unpaid { background: #ffebee; color: #c62828; }
                .footer {
                    text-align: center;
                    margin-top: 25px;
                    padding-top: 15px;
                    border-top: 1px solid #e0e0e0;
                    font-size: 11px;
                    color: #999;
                }
                @media print {
                    body { padding: 15px; }
                    .content { gap: 20px; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="hotel-name">AURORA HOTEL PLAZA</div>
                <div class="hotel-subtitle">Xác nhận đặt phòng / Booking Confirmation</div>
                <div class="booking-code"><?php echo $booking['booking_code']; ?></div>
                <div class="status-badges">
                    <span class="badge badge-<?php echo $booking['status'] === 'confirmed' ? 'confirmed' : 'pending'; ?>">
                        <?php echo $status_config[$booking['status']]['label'] ?? $booking['status']; ?>
                    </span>
                    <span class="badge badge-<?php echo $booking['payment_status'] === 'paid' ? 'paid' : 'unpaid'; ?>">
                        <?php echo $payment_status_config[$booking['payment_status']]['label'] ?? $booking['payment_status']; ?>
                    </span>
                </div>
            </div>
            
            <div class="content">
                <div class="qr-section">
                    <img src="${qrImage}" alt="QR Code">
                    <p class="qr-note">Quét mã QR này khi check-in<br>tại quầy lễ tân</p>
                </div>
                
                <div class="info-section">
                    <div class="info-group">
                        <div class="info-group-title">👤 Thông tin khách hàng</div>
                        <div class="info-row">
                            <span class="info-label">Họ tên:</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Điện thoại:</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['guest_phone']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['guest_email']); ?></span>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-group-title">🏨 Thông tin phòng</div>
                        <div class="info-row">
                            <span class="info-label">Loại phòng:</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['type_name']); ?></span>
                        </div>
                        <?php if ($booking['room_number']): ?>
                        <div class="info-row">
                            <span class="info-label">Số phòng:</span>
                            <span class="info-value" style="color: #2e7d32; font-size: 16px;"><?php echo htmlspecialchars($booking['room_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Số khách:</span>
                            <span class="info-value"><?php echo $booking['num_adults']; ?> người lớn<?php if ($booking['num_children'] > 0) echo ', ' . $booking['num_children'] . ' trẻ em'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Số phòng:</span>
                            <span class="info-value"><?php echo $booking['num_rooms']; ?> phòng</span>
                        </div>
                    </div>
                    
                    <div class="dates-grid">
                        <div class="date-box checkin">
                            <div class="date-label">Check-in</div>
                            <div class="date-value"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></div>
                            <div class="date-time">Sau 14:00</div>
                        </div>
                        <div class="date-box checkout">
                            <div class="date-label">Check-out</div>
                            <div class="date-value"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></div>
                            <div class="date-time">Trước 12:00</div>
                        </div>
                    </div>
                    
                    <div class="total-amount">
                        <div class="total-label"><?php echo $nights; ?> đêm x <?php echo $booking['num_rooms']; ?> phòng</div>
                        <div class="total-value"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>đ</div>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p>Aurora Hotel Plaza - Biên Hòa, Đồng Nai</p>
                <p>Hotline: 0251 3511 888 | Email: info@aurorahotelplaza.com</p>
                <p style="margin-top: 10px;">In lúc: ${new Date().toLocaleString('vi-VN')}</p>
            </div>
            
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                }
            <\/script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
}

function shareQR() {
    if (navigator.share) {
        navigator.share({
            title: 'QR Code - <?php echo $booking['booking_code']; ?>',
            text: 'Mã QR cho đặt phòng tại Aurora Hotel Plaza\\nCheck-in: <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>\\nCheck-out: <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>',
            url: window.location.href
        }).catch(err => console.log('Error sharing:', err));
    } else {
        // Fallback: copy link
        navigator.clipboard.writeText(window.location.href).then(() => {
            showToast('Đã sao chép link vào clipboard!', 'success');
        });
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg text-white font-medium shadow-lg z-50 transition-all transform translate-y-0 ${type === 'success' ? 'bg-green-500' : 'bg-blue-500'}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function handleQRError() {
    const qrImage = document.getElementById('qrImage');
    qrImage.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect width="400" height="400" fill="%23f0f0f0"/><text x="50%" y="50%" text-anchor="middle" fill="%23666" font-size="16">Không thể tải QR Code</text></svg>';
}
</script>

<!-- QR Code Popup with Liquid Glass Effect -->
<div id="qrPopupOverlay" class="qr-popup-overlay" onclick="closeQRPopup(event)">
    <div class="qr-popup-container" onclick="event.stopPropagation()">
        <div class="qr-popup-glass">
            <button class="qr-popup-close" onclick="closeQRPopup()">
                <span class="material-symbols-outlined">close</span>
            </button>
            
            <div class="qr-popup-header">
                <h3>Mã QR Check-in</h3>
                <p>Xuất trình mã này tại quầy lễ tân</p>
            </div>
            
            <div class="qr-popup-code">
                <img id="popupQrImage" src="<?php echo $qr_url; ?>" alt="QR Code">
                <div class="qr-popup-booking-code">
                    <span>Mã đặt phòng</span>
                    <strong><?php echo htmlspecialchars($booking['booking_code']); ?></strong>
                </div>
            </div>
            
            <div class="qr-popup-actions">
                <button onclick="downloadQRFromPopup()" class="qr-popup-btn qr-popup-btn-primary">
                    <span class="material-symbols-outlined">download</span>
                    Tải xuống QR Code
                </button>
                <button onclick="screenshotQR()" class="qr-popup-btn qr-popup-btn-secondary">
                    <span class="material-symbols-outlined">screenshot</span>
                    Chụp màn hình
                </button>
            </div>
            
            <div class="qr-popup-info">
                <p>💡 Bạn có thể chụp màn hình hoặc tải xuống để lưu mã QR này</p>
            </div>
        </div>
    </div>
</div>

</main>
<?php include '../includes/footer.php'; ?>
</div>
</body>
</html>
