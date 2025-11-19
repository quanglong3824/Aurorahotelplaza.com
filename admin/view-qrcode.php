<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/auth-middleware.php';

AuthMiddleware::requireStaff();

$booking_id = $_GET['id'] ?? 0;

if (!$booking_id) {
    header('Location: bookings.php');
    exit;
}

try {
    $db = getDB();
    
    // Get booking details
    $stmt = $db->prepare("
        SELECT b.*, u.full_name, u.email, rt.type_name, r.room_number
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_id = :booking_id
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header('Location: bookings.php');
        exit;
    }
    
    // QR code URL using local library
    $qr_url = '../profile/api/get-qrcode.php?booking_id=' . $booking_id;
    
} catch (Exception $e) {
    error_log("View QR error: " . $e->getMessage());
    header('Location: bookings.php');
    exit;
}

$page_title = 'QR Code - ' . $booking['booking_code'];
$page_subtitle = 'Mã QR cho đặt phòng';

include 'includes/admin-header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="booking-detail.php?id=<?php echo $booking_id; ?>" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Quay lại
        </a>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- QR Code Display -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-lg">Mã QR</h3>
            </div>
            <div class="card-body">
                <div class="bg-white p-8 rounded-xl flex items-center justify-center">
                    <img id="qrImage" src="<?php echo $qr_url; ?>" alt="QR Code" class="w-full max-w-sm" onerror="handleQRError()">
                </div>
                
                <div class="mt-6 space-y-3">
                    <button onclick="downloadQR()" class="btn btn-primary w-full">
                        <span class="material-symbols-outlined text-sm">download</span>
                        Tải xuống QR Code
                    </button>
                    
                    <button onclick="printQR()" class="btn btn-secondary w-full">
                        <span class="material-symbols-outlined text-sm">print</span>
                        In QR Code
                    </button>
                    
                    <button onclick="shareQR()" class="btn btn-secondary w-full">
                        <span class="material-symbols-outlined text-sm">share</span>
                        Chia sẻ
                    </button>
                </div>
                
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-blue-600">info</span>
                        <div class="flex-1 text-sm text-blue-800 dark:text-blue-200">
                            <p class="font-semibold mb-1">Hướng dẫn sử dụng:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Khách có thể chụp màn hình để lưu QR</li>
                                <li>Quét QR tại quầy lễ tân khi check-in</li>
                                <li>QR chứa thông tin booking đầy đủ</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Booking Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-lg">Thông tin đặt phòng</h3>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Mã đơn</p>
                    <p class="font-bold text-2xl" style="color: #d4af37;"><?php echo htmlspecialchars($booking['booking_code']); ?></p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Khách hàng</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Email</p>
                        <p class="font-semibold text-sm"><?php echo htmlspecialchars($booking['guest_email']); ?></p>
                    </div>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Loại phòng</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($booking['type_name']); ?></p>
                </div>
                
                <?php if ($booking['room_number']): ?>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Phòng</p>
                    <p class="font-semibold text-lg">Phòng <?php echo htmlspecialchars($booking['room_number']); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Check-in</p>
                        <p class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Check-out</p>
                        <p class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></p>
                    </div>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Tổng tiền</p>
                    <p class="font-bold text-xl" style="color: #d4af37;">
                        <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>đ
                    </p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Trạng thái</p>
                    <?php
                    $status_classes = [
                        'pending' => 'badge-warning',
                        'confirmed' => 'badge-info',
                        'checked_in' => 'badge-success',
                        'checked_out' => 'badge-secondary',
                        'cancelled' => 'badge-danger'
                    ];
                    $status_labels = [
                        'pending' => 'Chờ xác nhận',
                        'confirmed' => 'Đã xác nhận',
                        'checked_in' => 'Đã nhận phòng',
                        'checked_out' => 'Đã trả phòng',
                        'cancelled' => 'Đã hủy'
                    ];
                    ?>
                    <span class="badge <?php echo $status_classes[$booking['status']] ?? 'badge-secondary'; ?>">
                        <?php echo $status_labels[$booking['status']] ?? $booking['status']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function downloadQR() {
    window.location.href = '../profile/api/download-qrcode.php?booking_id=<?php echo $booking_id; ?>';
}

function printQR() {
    const printWindow = window.open('', '_blank');
    const qrImage = document.getElementById('qrImage').src;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QR Code - <?php echo $booking['booking_code']; ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    padding: 40px;
                }
                .qr-container {
                    max-width: 400px;
                    margin: 0 auto;
                }
                img {
                    width: 100%;
                    max-width: 400px;
                }
                .info {
                    margin-top: 30px;
                    text-align: left;
                    border-top: 2px solid #d4af37;
                    padding-top: 20px;
                }
                .info-row {
                    margin: 10px 0;
                    display: flex;
                    justify-content: space-between;
                }
                .label {
                    color: #666;
                    font-size: 14px;
                }
                .value {
                    font-weight: bold;
                    font-size: 14px;
                }
                .booking-code {
                    font-size: 24px;
                    font-weight: bold;
                    color: #d4af37;
                    margin: 20px 0;
                }
                @media print {
                    body {
                        padding: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="qr-container">
                <h1>Aurora Hotel Plaza</h1>
                <div class="booking-code"><?php echo $booking['booking_code']; ?></div>
                <img src="${qrImage}" alt="QR Code">
                <div class="info">
                    <div class="info-row">
                        <span class="label">Khách hàng:</span>
                        <span class="value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Loại phòng:</span>
                        <span class="value"><?php echo htmlspecialchars($booking['type_name']); ?></span>
                    </div>
                    <?php if ($booking['room_number']): ?>
                    <div class="info-row">
                        <span class="label">Phòng:</span>
                        <span class="value">Phòng <?php echo htmlspecialchars($booking['room_number']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="label">Check-in:</span>
                        <span class="value"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Check-out:</span>
                        <span class="value"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></span>
                    </div>
                </div>
            </div>
            <script>
                window.onload = function() {
                    window.print();
                }
            </script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
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
        navigator.clipboard.writeText(window.location.href);
        showToast('Đã sao chép link', 'success');
    }
}

function handleQRError() {
    const qrImage = document.getElementById('qrImage');
    qrImage.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect width="400" height="400" fill="%23f0f0f0"/><text x="50%" y="50%" text-anchor="middle" fill="%23666" font-size="16">Không thể tải QR Code</text></svg>';
}
</script>

<?php include 'includes/admin-footer.php'; ?>
