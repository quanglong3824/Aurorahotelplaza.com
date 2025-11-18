<?php
session_start();
require_once '../config/database.php';

$booking_code = $_GET['code'] ?? '';
$error = '';
$booking = null;

if (!$booking_code) {
    $error = 'Mã đặt phòng không hợp lệ';
} else {
    try {
        $db = getDB();
        
        // Get booking details
        $stmt = $db->prepare("
            SELECT b.*, rt.type_name, rt.category, rt.description, rt.amenities,
                   r.room_number, r.floor, r.building,
                   p.payment_method, p.transaction_id, p.paid_at, p.status as payment_status
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            WHERE b.booking_code = ?
        ");
        $stmt->execute([$booking_code]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            $error = 'Không tìm thấy đặt phòng với mã này';
        } elseif (isset($_SESSION['user_id']) && $booking['user_id'] != $_SESSION['user_id']) {
            // If user is logged in but not the owner, don't show
            $error = 'Bạn không có quyền xem đặt phòng này';
        }
        
    } catch (Exception $e) {
        error_log("Booking detail error: " . $e->getMessage());
        $error = 'Có lỗi xảy ra khi tải thông tin đặt phòng';
    }
}

// Status labels and colors
$status_labels = [
    'pending' => ['label' => 'Chờ xác nhận', 'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
    'confirmed' => ['label' => 'Đã xác nhận', 'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
    'checked_in' => ['label' => 'Đã nhận phòng', 'color' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
    'checked_out' => ['label' => 'Đã trả phòng', 'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'],
    'cancelled' => ['label' => 'Đã hủy', 'color' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
    'no_show' => ['label' => 'Không đến', 'color' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200']
];

$payment_labels = [
    'unpaid' => ['label' => 'Chưa thanh toán', 'color' => 'bg-red-100 text-red-800'],
    'partial' => ['label' => 'Thanh toán một phần', 'color' => 'bg-yellow-100 text-yellow-800'],
    'paid' => ['label' => 'Đã thanh toán', 'color' => 'bg-green-100 text-green-800'],
    'refunded' => ['label' => 'Đã hoàn tiền', 'color' => 'bg-gray-100 text-gray-800']
];
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Chi tiết đặt phòng <?php echo htmlspecialchars($booking_code); ?> - Aurora Hotel Plaza</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="bookings.php" class="inline-flex items-center gap-2 text-text-secondary-light dark:text-text-secondary-dark hover:text-accent transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Quay lại danh sách
                </a>
                <?php else: ?>
                <a href="../index.php" class="inline-flex items-center gap-2 text-text-secondary-light dark:text-text-secondary-dark hover:text-accent transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Về trang chủ
                </a>
                <?php endif; ?>
            </div>
            <h1 class="text-3xl font-bold text-text-primary-light dark:text-text-primary-dark">
                Chi tiết đặt phòng
            </h1>
            <p class="mt-2 text-text-secondary-light dark:text-text-secondary-dark">
                Mã đặt phòng: <span class="font-mono text-accent"><?php echo htmlspecialchars($booking_code); ?></span>
            </p>
        </div>

        <?php if ($error): ?>
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
            <div class="flex">
                <span class="material-symbols-outlined text-red-400 mr-2">error</span>
                <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
        
        <!-- Booking Code Lookup Form -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-bold mb-4">Tra cứu đặt phòng</h2>
            <form method="GET" class="flex gap-4">
                <input type="text" name="code" placeholder="Nhập mã đặt phòng..." 
                       value="<?php echo htmlspecialchars($booking_code); ?>"
                       class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-accent focus:border-accent">
                <button type="submit" class="px-6 py-3 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors">
                    <span class="material-symbols-outlined mr-2">search</span>
                    Tra cứu
                </button>
            </form>
        </div>
        
        <?php elseif ($booking): ?>
        
        <!-- Booking Status -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">Trạng thái đặt phòng</h2>
                <div class="flex gap-2">
                    <span class="px-4 py-2 text-sm font-medium rounded-full <?php echo $status_labels[$booking['status']]['color']; ?>">
                        <?php echo $status_labels[$booking['status']]['label']; ?>
                    </span>
                    <?php if ($booking['payment_status']): ?>
                    <span class="px-4 py-2 text-sm font-medium rounded-full <?php echo $payment_labels[$booking['payment_status']]['color']; ?>">
                        <?php echo $payment_labels[$booking['payment_status']]['label']; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Ngày đặt:</span>
                    <p><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></p>
                </div>
                <?php if ($booking['checked_in_at']): ?>
                <div>
                    <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Nhận phòng:</span>
                    <p><?php echo date('d/m/Y H:i', strtotime($booking['checked_in_at'])); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($booking['checked_out_at']): ?>
                <div>
                    <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Trả phòng:</span>
                    <p><?php echo date('d/m/Y H:i', strtotime($booking['checked_out_at'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Booking Info -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Room Information -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">
                            <?php echo $booking['category'] === 'apartment' ? 'apartment' : 'hotel'; ?>
                        </span>
                        Thông tin phòng
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-lg font-semibold text-accent"><?php echo htmlspecialchars($booking['type_name']); ?></h4>
                            <?php if ($booking['description']): ?>
                            <p class="text-text-secondary-light dark:text-text-secondary-dark mt-1">
                                <?php echo htmlspecialchars($booking['description']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Nhận phòng:</span>
                                <p class="text-lg font-semibold"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Trả phòng:</span>
                                <p class="text-lg font-semibold"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Số khách:</span>
                                <p><?php echo $booking['num_adults']; ?> người lớn<?php echo $booking['num_children'] ? ', ' . $booking['num_children'] . ' trẻ em' : ''; ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Số đêm:</span>
                                <p><?php echo $booking['total_nights']; ?> đêm</p>
                            </div>
                            <?php if ($booking['room_number']): ?>
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Số phòng:</span>
                                <p class="text-lg font-semibold text-accent"><?php echo $booking['room_number']; ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Tầng:</span>
                                <p>Tầng <?php echo $booking['floor']; ?>, <?php echo $booking['building']; ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($booking['amenities']): ?>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Tiện nghi:</span>
                            <p class="mt-1"><?php echo htmlspecialchars($booking['amenities']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['special_requests']): ?>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Yêu cầu đặc biệt:</span>
                            <p class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <?php echo htmlspecialchars($booking['special_requests']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Guest Information -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">person</span>
                        Thông tin khách hàng
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Họ và tên:</span>
                            <p class="text-lg font-semibold"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                        </div>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Email:</span>
                            <p><?php echo htmlspecialchars($booking['guest_email']); ?></p>
                        </div>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Số điện thoại:</span>
                            <p><?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                        </div>
                        <?php if ($booking['guest_id_number']): ?>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">CMND/CCCD:</span>
                            <p><?php echo htmlspecialchars($booking['guest_id_number']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Price Breakdown -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">receipt</span>
                        Chi tiết giá
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span>Giá phòng (<?php echo $booking['total_nights']; ?> đêm)</span>
                            <span><?php echo number_format($booking['room_price'] * $booking['total_nights']); ?> VNĐ</span>
                        </div>
                        
                        <?php if ($booking['service_charges'] > 0): ?>
                        <div class="flex justify-between">
                            <span>Phí dịch vụ</span>
                            <span><?php echo number_format($booking['service_charges']); ?> VNĐ</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Giảm giá</span>
                            <span>-<?php echo number_format($booking['discount_amount']); ?> VNĐ</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['points_used'] > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Điểm thưởng sử dụng</span>
                            <span>-<?php echo $booking['points_used']; ?> điểm</span>
                        </div>
                        <?php endif; ?>
                        
                        <hr class="border-gray-300 dark:border-gray-600">
                        
                        <div class="flex justify-between text-lg font-bold">
                            <span>Tổng cộng</span>
                            <span class="text-accent"><?php echo number_format($booking['total_amount']); ?> VNĐ</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <?php if ($booking['payment_method']): ?>
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">payment</span>
                        Thông tin thanh toán
                    </h3>
                    
                    <div class="space-y-3">
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Phương thức:</span>
                            <p class="capitalize"><?php echo $booking['payment_method']; ?></p>
                        </div>
                        
                        <?php if ($booking['transaction_id']): ?>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Mã giao dịch:</span>
                            <p class="font-mono text-sm"><?php echo htmlspecialchars($booking['transaction_id']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['paid_at']): ?>
                        <div>
                            <span class="font-medium text-text-secondary-light dark:text-text-secondary-dark">Thời gian thanh toán:</span>
                            <p><?php echo date('d/m/Y H:i', strtotime($booking['paid_at'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold mb-4">Hành động</h3>
                    
                    <div class="space-y-3">
                        <button onclick="window.print()" 
                                class="w-full px-4 py-3 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors">
                            <span class="material-symbols-outlined mr-2">print</span>
                            In thông tin đặt phòng
                        </button>
                        
                        <button onclick="shareBooking()" 
                                class="w-full px-4 py-3 border-2 border-accent text-accent rounded-lg hover:bg-accent/5 transition-colors">
                            <span class="material-symbols-outlined mr-2">share</span>
                            Chia sẻ
                        </button>
                        
                        <!-- QR Code Button (Inactive) -->
                        <button disabled 
                                class="w-full px-4 py-3 border border-gray-300 text-gray-400 rounded-lg cursor-not-allowed opacity-50">
                            <span class="material-symbols-outlined mr-2">qr_code</span>
                            Tạo QR Code (Sắp có)
                        </button>
                        
                        <?php if ($booking['status'] === 'confirmed' && strtotime($booking['check_in_date']) > time()): ?>
                        <button onclick="cancelBooking()" 
                                class="w-full px-4 py-3 border-2 border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                            <span class="material-symbols-outlined mr-2">cancel</span>
                            Hủy đặt phòng
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</div>

<script src="../assets/js/main.js"></script>
<script>
function shareBooking() {
    if (navigator.share) {
        navigator.share({
            title: 'Thông tin đặt phòng - Aurora Hotel Plaza',
            text: 'Mã đặt phòng: <?php echo $booking_code; ?>',
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Đã sao chép link vào clipboard!');
        });
    }
}

function cancelBooking() {
    if (confirm('Bạn có chắc chắn muốn hủy đặt phòng này?\n\nLưu ý: Việc hủy đặt phòng có thể phát sinh phí theo chính sách của khách sạn.')) {
        // TODO: Implement cancel booking functionality
        alert('Tính năng hủy đặt phòng sẽ được triển khai sau.');
    }
}

// Print styles
const printStyles = `
    <style>
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
`;
document.head.insertAdjacentHTML('beforeend', printStyles);
</script>
</body>
</html>