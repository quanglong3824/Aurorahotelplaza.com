<?php
session_start();
require_once '../config/database.php';

$booking_id = $_GET['id'] ?? null;

if (!$booking_id) {
    header('Location: bookings.php');
    exit;
}

try {
    $db = getDB();
    
    // Get booking details
    $stmt = $db->prepare("
        SELECT b.*, u.full_name as user_name, u.email as user_email, u.phone as user_phone,
               rt.type_name, rt.category, rt.bed_type, rt.max_occupancy,
               r.room_number, r.floor, r.building
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
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
    
    // Get booking history
    $stmt = $db->prepare("
        SELECT bh.*, u.full_name as changed_by_name
        FROM booking_history bh
        LEFT JOIN users u ON bh.changed_by = u.user_id
        WHERE bh.booking_id = :booking_id
        ORDER BY bh.created_at DESC
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payments
    $stmt = $db->prepare("
        SELECT * FROM payments
        WHERE booking_id = :booking_id
        ORDER BY created_at DESC
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get service bookings
    $stmt = $db->prepare("
        SELECT sb.*, s.service_name
        FROM service_bookings sb
        JOIN services s ON sb.service_id = s.service_id
        WHERE sb.booking_id = :booking_id
        ORDER BY sb.created_at DESC
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Booking detail error: " . $e->getMessage());
    header('Location: bookings.php');
    exit;
}

$page_title = 'Chi tiết đặt phòng #' . $booking['booking_code'];
$page_subtitle = 'Thông tin chi tiết và quản lý đơn đặt phòng';

include 'includes/admin-header.php';
?>

<!-- Action Bar -->
<div class="flex items-center justify-between mb-6 no-print">
    <a href="bookings.php" class="btn btn-secondary">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Quay lại
    </a>
    
    <div class="flex gap-2">
        <?php if ($booking['status'] === 'pending'): ?>
            <button onclick="confirmBooking(<?php echo $booking_id; ?>)" class="btn btn-success">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                Xác nhận đơn
            </button>
        <?php endif; ?>
        
        <?php if ($booking['status'] === 'confirmed'): ?>
            <button onclick="assignRoom(<?php echo $booking_id; ?>)" class="btn btn-primary">
                <span class="material-symbols-outlined text-sm">meeting_room</span>
                Phân phòng
            </button>
            <button onclick="checkinBooking(<?php echo $booking_id; ?>)" class="btn btn-success">
                <span class="material-symbols-outlined text-sm">login</span>
                Check-in
            </button>
        <?php endif; ?>
        
        <?php if ($booking['status'] === 'checked_in'): ?>
            <button onclick="checkoutBooking(<?php echo $booking_id; ?>)" class="btn btn-warning">
                <span class="material-symbols-outlined text-sm">logout</span>
                Check-out
            </button>
        <?php endif; ?>
        
        <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
            <button onclick="cancelBooking(<?php echo $booking_id; ?>)" class="btn btn-danger">
                <span class="material-symbols-outlined text-sm">cancel</span>
                Hủy đơn
            </button>
        <?php endif; ?>
        
        <button onclick="window.print()" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">print</span>
            In
        </button>
        
        <a href="../profile/api/download-qrcode.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">qr_code</span>
            Tải QR
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Booking Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Thông tin đặt phòng</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Mã đơn</p>
                        <p class="font-semibold text-lg"><?php echo htmlspecialchars($booking['booking_code']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Trạng thái</p>
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
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Loại phòng</p>
                        <p class="font-medium"><?php echo htmlspecialchars($booking['type_name']); ?></p>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                            <?php echo ucfirst($booking['category']); ?> - <?php echo $booking['bed_type']; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Phòng</p>
                        <?php if ($booking['room_number']): ?>
                            <p class="font-medium">Phòng <?php echo htmlspecialchars($booking['room_number']); ?></p>
                            <?php if ($booking['floor']): ?>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                    Tầng <?php echo $booking['floor']; ?>
                                    <?php if ($booking['building']): ?>
                                        - <?php echo htmlspecialchars($booking['building']); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-yellow-600">Chưa phân phòng</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Check-in</p>
                        <p class="font-medium"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></p>
                        <?php if ($booking['checked_in_at']): ?>
                            <p class="text-sm text-green-600">
                                Đã check-in: <?php echo date('d/m/Y H:i', strtotime($booking['checked_in_at'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Check-out</p>
                        <p class="font-medium"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></p>
                        <?php if ($booking['checked_out_at']): ?>
                            <p class="text-sm text-green-600">
                                Đã check-out: <?php echo date('d/m/Y H:i', strtotime($booking['checked_out_at'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Số đêm</p>
                        <p class="font-medium"><?php echo $booking['total_nights']; ?> đêm</p>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Số phòng</p>
                        <p class="font-medium"><?php echo $booking['num_rooms']; ?> phòng</p>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Số khách</p>
                        <p class="font-medium">
                            <?php echo $booking['num_adults']; ?> người lớn
                            <?php if ($booking['num_children'] > 0): ?>
                                + <?php echo $booking['num_children']; ?> trẻ em
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Ngày đặt</p>
                        <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></p>
                    </div>
                </div>
                
                <?php if ($booking['special_requests']): ?>
                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Yêu cầu đặc biệt</p>
                        <p class="text-sm"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($booking['status'] === 'cancelled' && $booking['cancellation_reason']): ?>
                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Lý do hủy</p>
                        <p class="text-sm text-red-600"><?php echo nl2br(htmlspecialchars($booking['cancellation_reason'])); ?></p>
                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                            Hủy lúc: <?php echo date('d/m/Y H:i', strtotime($booking['cancelled_at'])); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Guest Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Thông tin khách hàng</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Họ tên</p>
                        <p class="font-medium"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Email</p>
                        <p class="font-medium"><?php echo htmlspecialchars($booking['guest_email']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Số điện thoại</p>
                        <p class="font-medium"><?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                    </div>
                    <?php if ($booking['guest_id_number']): ?>
                        <div>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">CMND/CCCD</p>
                            <p class="font-medium"><?php echo htmlspecialchars($booking['guest_id_number']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($booking['user_id']): ?>
                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                        <a href="customers.php?id=<?php echo $booking['user_id']; ?>" class="text-accent hover:underline">
                            Xem hồ sơ khách hàng →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Thông tin thanh toán</h3>
            </div>
            <div class="card-body">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span>Giá phòng (<?php echo $booking['total_nights']; ?> đêm × <?php echo $booking['num_rooms']; ?> phòng)</span>
                        <span class="font-medium"><?php echo number_format($booking['room_price'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <?php if ($booking['service_charges'] > 0): ?>
                        <div class="flex justify-between">
                            <span>Phí dịch vụ</span>
                            <span class="font-medium"><?php echo number_format($booking['service_charges'], 0, ',', '.'); ?>đ</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($booking['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Giảm giá</span>
                            <span class="font-medium">-<?php echo number_format($booking['discount_amount'], 0, ',', '.'); ?>đ</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($booking['points_used'] > 0): ?>
                        <div class="flex justify-between text-blue-600">
                            <span>Điểm tích lũy sử dụng</span>
                            <span class="font-medium"><?php echo number_format($booking['points_used']); ?> điểm</span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between pt-3 border-t border-border-light dark:border-border-dark text-lg">
                        <span class="font-semibold">Tổng cộng</span>
                        <span class="font-bold text-accent"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Trạng thái thanh toán</span>
                        <?php
                        $payment_classes = [
                            'unpaid' => 'badge-danger',
                            'partial' => 'badge-warning',
                            'paid' => 'badge-success',
                            'refunded' => 'badge-secondary'
                        ];
                        $payment_labels = [
                            'unpaid' => 'Chưa thanh toán',
                            'partial' => 'Thanh toán 1 phần',
                            'paid' => 'Đã thanh toán',
                            'refunded' => 'Đã hoàn tiền'
                        ];
                        ?>
                        <span class="badge <?php echo $payment_classes[$booking['payment_status']] ?? 'badge-secondary'; ?>">
                            <?php echo $payment_labels[$booking['payment_status']] ?? $booking['payment_status']; ?>
                        </span>
                    </div>
                </div>
                
                <?php if (!empty($payments)): ?>
                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                        <p class="font-medium mb-3">Lịch sử thanh toán</p>
                        <div class="space-y-2">
                            <?php foreach ($payments as $payment): ?>
                                <div class="flex items-center justify-between p-3 bg-background-light dark:bg-background-dark rounded-lg">
                                    <div>
                                        <p class="font-medium"><?php echo number_format($payment['amount'], 0, ',', '.'); ?>đ</p>
                                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                            <?php 
                                            $methods = [
                                                'vnpay' => 'VNPay',
                                                'cash' => 'Tiền mặt',
                                                'bank_transfer' => 'Chuyển khoản',
                                                'credit_card' => 'Thẻ tín dụng'
                                            ];
                                            echo $methods[$payment['payment_method']] ?? $payment['payment_method'];
                                            ?>
                                            - <?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="badge <?php echo $payment['status'] === 'completed' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $payment['status'] === 'completed' ? 'Thành công' : ucfirst($payment['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Services -->
        <?php if (!empty($services)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold">Dịch vụ đã đặt</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-2">
                        <?php foreach ($services as $service): ?>
                            <div class="flex items-center justify-between p-3 bg-background-light dark:bg-background-dark rounded-lg">
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($service['service_name']); ?></p>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                        Số lượng: <?php echo $service['quantity']; ?> × <?php echo number_format($service['unit_price'], 0, ',', '.'); ?>đ
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium"><?php echo number_format($service['total_price'], 0, ',', '.'); ?>đ</p>
                                    <span class="badge badge-<?php echo $service['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($service['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- QR Code -->
        <?php if ($booking['qr_code']): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold">Mã QR</h3>
                </div>
                <div class="card-body text-center">
                    <img src="<?php echo htmlspecialchars($booking['qr_code']); ?>" 
                         alt="QR Code" class="w-48 h-48 mx-auto">
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-2">
                        Quét mã để xem thông tin đặt phòng
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- History -->
        <?php if (!empty($history)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold">Lịch sử thay đổi</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        <?php foreach ($history as $item): ?>
                            <div class="text-sm">
                                <p class="font-medium">
                                    <?php echo htmlspecialchars($item['old_status']); ?> 
                                    → <?php echo htmlspecialchars($item['new_status']); ?>
                                </p>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark text-xs">
                                    <?php echo $item['changed_by_name'] ? htmlspecialchars($item['changed_by_name']) : 'System'; ?>
                                    - <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                                </p>
                                <?php if ($item['notes']): ?>
                                    <p class="text-xs mt-1"><?php echo htmlspecialchars($item['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmBooking(id) {
    if (confirm('Xác nhận đơn đặt phòng này?')) {
        updateBookingStatus(id, 'confirmed');
    }
}

function checkinBooking(id) {
    if (confirm('Xác nhận khách đã check-in?')) {
        updateBookingStatus(id, 'checked_in');
    }
}

function checkoutBooking(id) {
    if (confirm('Xác nhận khách đã check-out?')) {
        updateBookingStatus(id, 'checked_out');
    }
}

function cancelBooking(id) {
    const reason = prompt('Lý do hủy đơn:');
    if (reason !== null) {
        updateBookingStatus(id, 'cancelled', reason);
    }
}

function updateBookingStatus(id, status, reason = '') {
    const formData = new FormData();
    formData.append('booking_id', id);
    formData.append('status', status);
    if (reason) formData.append('reason', reason);
    
    fetch('api/update-booking-status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Cập nhật thành công!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra', 'error');
    });
}

function assignRoom(bookingId) {
    // TODO: Implement room assignment modal
    alert('Chức năng phân phòng đang được phát triển');
}
</script>

<?php include 'includes/admin-footer.php'; ?>
