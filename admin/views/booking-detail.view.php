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

        <a href="view-qrcode.php?id=<?php echo $booking_id; ?>" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">qr_code</span>
            Xem QR
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
                        $is_inquiry = ($booking['booking_type'] ?? 'instant') === 'inquiry';
                        $status_classes = [
                            'pending' => 'badge-warning',
                            'contacted' => 'badge-info',
                            'confirmed' => 'badge-success',
                            'checked_in' => 'badge-success',
                            'checked_out' => 'badge-secondary',
                            'cancelled' => 'badge-danger'
                        ];
                        $status_labels = [
                            'pending' => $is_inquiry ? 'Chờ liên hệ' : 'Chờ xác nhận',
                            'contacted' => 'Đã liên hệ',
                            'confirmed' => 'Đã xác nhận',
                            'checked_in' => 'Đã nhận phòng',
                            'checked_out' => 'Đã trả phòng',
                            'cancelled' => 'Đã hủy'
                        ];
                        ?>
                        <div class="flex items-center gap-2">
                            <?php if ($is_inquiry): ?>
                                <span class="badge bg-purple-500/20 text-purple-400 border border-purple-500/30">
                                    <span class="material-symbols-outlined text-xs mr-1">apartment</span>
                                    Yêu cầu căn hộ
                                </span>
                            <?php endif; ?>
                            <span class="badge <?php echo $status_classes[$booking['status']] ?? 'badge-secondary'; ?>">
                                <?php echo $status_labels[$booking['status']] ?? $booking['status']; ?>
                            </span>
                        </div>
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
                        <p class="font-medium"><?php echo date('m/d/Y', strtotime($booking['check_in_date'])); ?></p>
                        <?php if ($booking['checked_in_at']): ?>
                            <p class="text-sm text-green-600">
                                Đã check-in: <?php echo date('m/d/Y H:i', strtotime($booking['checked_in_at'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Check-out</p>
                        <p class="font-medium"><?php echo date('m/d/Y', strtotime($booking['check_out_date'])); ?></p>
                        <?php if ($booking['checked_out_at']): ?>
                            <p class="text-sm text-green-600">
                                Đã check-out: <?php echo date('m/d/Y H:i', strtotime($booking['checked_out_at'])); ?>
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
                        <p class="font-medium"><?php echo date('m/d/Y H:i', strtotime($booking['created_at'])); ?></p>
                    </div>
                </div>

                <!-- Booking Type & Pricing Details (NEW) -->
                <?php
                $is_short_stay = ($booking['booking_type'] ?? 'standard') === 'short_stay';
                $price_type_labels = [
                    'single' => 'Giá 1 người',
                    'double' => 'Giá 2 người',
                    'short_stay' => 'Giá nghỉ ngắn hạn',
                    'weekly' => 'Giá tuần',
                    'daily' => 'Giá ngày'
                ];
                $price_type = $booking['price_type_used'] ?? 'double';
                ?>
                <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="material-symbols-outlined text-amber-500">receipt_long</span>
                        <h4 class="font-semibold">Chi tiết giá áp dụng</h4>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Loại hình</p>
                            <span
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium
                                <?php echo $is_short_stay ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'; ?>">
                                <span
                                    class="material-symbols-outlined text-xs"><?php echo $is_short_stay ? 'schedule' : 'hotel'; ?></span>
                                <?php echo $is_short_stay ? 'Nghỉ ngắn hạn' : 'Nghỉ qua đêm'; ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Loại giá</p>
                            <span
                                class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">
                                <?php echo $price_type_labels[$price_type] ?? $price_type; ?>
                            </span>
                        </div>
                        <?php if (($booking['extra_beds'] ?? 0) > 0): ?>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Giường phụ</p>
                                <p class="font-medium text-orange-600"><?php echo $booking['extra_beds']; ?> giường</p>
                            </div>
                        <?php endif; ?>
                        <?php
                        $extra_guests = json_decode($booking['extra_guests_data'] ?? '[]', true);
                        if (!empty($extra_guests)):
                            ?>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Khách thêm</p>
                                <p class="font-medium text-blue-600"><?php echo count($extra_guests); ?> khách</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($booking['special_requests']): ?>
                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Yêu cầu đặc biệt</p>
                        <p class="text-sm"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($is_inquiry): ?>
                    <!-- Apartment Inquiry Information -->
                    <div class="mt-4 pt-4 border-t border-purple-200 dark:border-purple-700">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-purple-500">apartment</span>
                            <h4 class="font-semibold text-purple-600 dark:text-purple-400">Thông tin yêu cầu căn hộ</h4>
                        </div>
                        <div class="grid grid-cols-2 gap-4 bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Thời gian cư trú mong muốn</p>
                                <?php
                                $duration_labels = [
                                    '1_month' => '1 tháng',
                                    '3_months' => '3 tháng',
                                    '6_months' => '6 tháng',
                                    '12_months' => '12 tháng (1 năm)',
                                    'custom' => 'Khác'
                                ];
                                ?>
                                <p class="font-medium">
                                    <?php echo $duration_labels[$booking['duration_type']] ?? $booking['duration_type'] ?? 'N/A'; ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Hình thức</p>
                                <p class="font-medium text-purple-600">Liên hệ báo giá</p>
                            </div>
                        </div>
                        <?php if (!empty($booking['inquiry_message'])): ?>
                            <div class="mt-3">
                                <p class="text-sm text-gray-500 mb-1">Tin nhắn / Yêu cầu cụ thể</p>
                                <p class="text-sm p-3 bg-gray-50 dark:bg-gray-700 rounded-lg whitespace-pre-wrap">
                                    <?php echo nl2br(htmlspecialchars($booking['inquiry_message'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        <div class="mt-4 flex gap-3">
                            <a href="tel:<?php echo htmlspecialchars($booking['guest_phone']); ?>"
                                class="flex-1 btn btn-success text-center">
                                <span class="material-symbols-outlined text-sm mr-1">call</span> Gọi điện
                            </a>
                            <a href="mailto:<?php echo htmlspecialchars($booking['guest_email']); ?>"
                                class="flex-1 btn btn-primary text-center">
                                <span class="material-symbols-outlined text-sm mr-1">mail</span> Gửi email
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($booking['status'] === 'cancelled' && $booking['cancellation_reason']): ?>
                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Lý do hủy</p>
                        <p class="text-sm text-red-600">
                            <?php echo nl2br(htmlspecialchars($booking['cancellation_reason'])); ?>
                        </p>
                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                            Hủy lúc: <?php echo date('m/d/Y H:i', strtotime($booking['cancelled_at'])); ?>
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
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-1">Số điện thoại
                        </p>
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
                        <span>Giá phòng (<?php echo $booking['total_nights']; ?> đêm ×
                            <?php echo $booking['num_rooms']; ?> phòng)</span>
                        <span
                            class="font-medium"><?php echo number_format($booking['room_price'], 0, ',', '.'); ?>VND</span>
                    </div>
                    <?php if (($booking['extra_guest_fee'] ?? 0) > 0): ?>
                        <div class="flex justify-between text-blue-600">
                            <span>Phụ thu khách thêm</span>
                            <span
                                class="font-medium"><?php echo number_format($booking['extra_guest_fee'], 0, ',', '.'); ?>VND</span>
                        </div>
                    <?php endif; ?>
                    <?php if (($booking['extra_bed_fee'] ?? 0) > 0): ?>
                        <div class="flex justify-between text-orange-600">
                            <span>Phí giường phụ (<?php echo $booking['extra_beds'] ?? 0; ?> giường)</span>
                            <span
                                class="font-medium"><?php echo number_format($booking['extra_bed_fee'], 0, ',', '.'); ?>VND</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($booking['service_fee'] > 0): ?>
                        <div class="flex justify-between">
                            <span>Phí dịch vụ</span>
                            <span
                                class="font-medium"><?php echo number_format($booking['service_fee'], 0, ',', '.'); ?>VND</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($booking['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Giảm giá</span>
                            <span
                                class="font-medium">-<?php echo number_format($booking['discount_amount'], 0, ',', '.'); ?>VND</span>
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
                        <span
                            class="font-bold text-accent"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>VND</span>
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
                        <span
                            class="badge <?php echo $payment_classes[$booking['payment_status']] ?? 'badge-secondary'; ?>">
                            <?php echo $payment_labels[$booking['payment_status']] ?? $booking['payment_status']; ?>
                        </span>
                    </div>
                </div>

                <!-- Confirm Payment Button -->
                <?php if ($booking['payment_status'] === 'unpaid' && !in_array($booking['status'], ['cancelled', 'checked_out'])): ?>
                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                        <button onclick="showConfirmPaymentModal()" class="btn btn-success w-full">
                            <span class="material-symbols-outlined text-sm">payments</span>
                            Xác nhận thanh toán & Cộng điểm thưởng
                        </button>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-2 text-center">
                            Khách hàng sẽ nhận được
                            <strong><?php echo number_format(floor($booking['total_amount'] / 100)); ?> điểm</strong> thưởng
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($payments)): ?>
                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                        <p class="font-medium mb-3">Lịch sử thanh toán</p>
                        <div class="space-y-2">
                            <?php foreach ($payments as $payment): ?>
                                <div
                                    class="flex items-center justify-between p-3 bg-background-light dark:bg-background-dark rounded-lg">
                                    <div>
                                        <p class="font-medium"><?php echo number_format($payment['amount'], 0, ',', '.'); ?>VND
                                        </p>
                                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                            <?php
                                            $methods = [
                                                'cash' => 'Tiền mặt',
                                                'bank_transfer' => 'Chuyển khoản',
                                                'credit_card' => 'Thẻ tín dụng'
                                            ];
                                            echo $methods[$payment['payment_method']] ?? $payment['payment_method'];
                                            ?>
                                            - <?php echo date('m/d/Y H:i', strtotime($payment['created_at'])); ?>
                                        </p>
                                        <?php if ($payment['notes']): ?>
                                            <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                                                Ghi chú: <?php echo htmlspecialchars($payment['notes']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <span
                                        class="badge <?php echo $payment['status'] === 'completed' ? 'badge-success' : 'badge-warning'; ?>">
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
                            <div
                                class="flex items-center justify-between p-3 bg-background-light dark:bg-background-dark rounded-lg">
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($service['service_name']); ?></p>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                        Số lượng: <?php echo $service['quantity']; ?> ×
                                        <?php echo number_format($service['unit_price'], 0, ',', '.'); ?>VND
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium"><?php echo number_format($service['total_price'], 0, ',', '.'); ?>VND
                                    </p>
                                    <span
                                        class="badge badge-<?php echo $service['status'] === 'completed' ? 'success' : 'warning'; ?>">
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
                    <img src="<?php echo htmlspecialchars($booking['qr_code']); ?>" alt="QR Code" class="w-48 h-48 mx-auto">
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
                                    - <?php echo date('m/d/Y H:i', strtotime($item['created_at'])); ?>
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
        // Load booking data and available rooms for check-in
        fetch(`api/get-available-rooms.php?booking_id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.booking) {
                    // Check if room is already assigned
                    if (data.booking.room_id) {
                        // Room already assigned, just do check-in
                        if (confirm('Xác nhận khách đã check-in?')) {
                            updateBookingStatus(id, 'checked_in');
                        }
                    } else {
                        // No room assigned, show room selection modal
                        loadAvailableRoomsForCheckin(id, data.booking);
                    }
                } else {
                    showToast('Không thể tải thông tin đơn đặt phòng', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra', 'error');
            });
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
        // Load available rooms
        fetch(`api/get-available-rooms.php?booking_id=${bookingId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAssignRoomModal(bookingId, data.booking, data.rooms);
                } else {
                    showToast(data.message || 'Không thể tải danh sách phòng', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra', 'error');
            });
    }

    function showAssignRoomModal(bookingId, booking, rooms) {
        const modal = document.getElementById('assignRoomModal');

        // Update booking info
        document.getElementById('assign_booking_code').textContent = booking.booking_code;
        document.getElementById('assign_room_type').textContent = booking.type_name;
        document.getElementById('assign_check_in').textContent = new Date(booking.check_in_date).toLocaleDateString('vi-VN');
        document.getElementById('assign_check_out').textContent = new Date(booking.check_out_date).toLocaleDateString('vi-VN');

        // Populate rooms list
        const roomsList = document.getElementById('rooms_list');
        roomsList.innerHTML = '';

        if (rooms.length === 0) {
            roomsList.innerHTML = '<p class="text-center text-gray-500 py-4">Không có phòng khả dụng</p>';
        } else {
            rooms.forEach(room => {
                const isAvailable = room.is_available == 1;
                const roomCard = document.createElement('div');
                roomCard.className = `p-4 border rounded-lg cursor-pointer transition-all ${isAvailable
                    ? 'border-green-300 hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/20'
                    : 'border-gray-300 bg-gray-100 dark:bg-gray-700 opacity-60 cursor-not-allowed'
                    }`;

                if (isAvailable) {
                    roomCard.onclick = () => selectRoom(bookingId, room.room_id, room.room_number);
                }

                roomCard.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center ${isAvailable ? 'bg-green-100 text-green-600' : 'bg-gray-200 text-gray-500'
                    }">
                            <span class="material-symbols-outlined">meeting_room</span>
                        </div>
                        <div>
                            <p class="font-bold text-lg">Phòng ${room.room_number}</p>
                            <p class="text-sm text-gray-600">
                                ${room.floor ? `Tầng ${room.floor}` : ''}
                                ${room.building ? ` - ${room.building}` : ''}
                            </p>
                            <p class="text-xs ${isAvailable ? 'text-green-600' : 'text-red-600'}">
                                ${isAvailable ? '✓ Khả dụng' : '✗ Đã được đặt'}
                            </p>
                        </div>
                    </div>
                    ${isAvailable ? `
                        <span class="material-symbols-outlined text-green-600">arrow_forward</span>
                    ` : ''}
                </div>
            `;

                roomsList.appendChild(roomCard);
            });
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeAssignRoomModal() {
        const modal = document.getElementById('assignRoomModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function selectRoom(bookingId, roomId, roomNumber) {
        if (!confirm(`Xác nhận phân phòng ${roomNumber} cho đơn này?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('room_id', roomId);

        fetch('api/assign-room.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeAssignRoomModal();
                    showToast('Phân phòng thành công!', 'success');
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

    // Confirm Payment Modal
    function showConfirmPaymentModal() {
        const modal = document.getElementById('confirmPaymentModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeConfirmPaymentModal() {
        const modal = document.getElementById('confirmPaymentModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function confirmPayment() {
        const paymentMethod = document.getElementById('paymentMethod').value;
        const notes = document.getElementById('paymentNotes').value;
        const bookingId = <?php echo $booking_id; ?>;

        if (!paymentMethod) {
            showToast('Vui lòng chọn phương thức thanh toán', 'error');
            return;
        }

        // Disable button to prevent double submission
        const submitBtn = document.getElementById('confirmPaymentBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">progress_activity</span> Đang xử lý...';

        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('payment_method', paymentMethod);
        formData.append('notes', notes);
        formData.append('csrf_token', '<?php echo Security::generateCSRFToken(); ?>');

        fetch('api/confirm-payment.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeConfirmPaymentModal();

                    // Show success message with points info
                    const message = `Xác nhận thanh toán thành công!\n\n` +
                        `Khách hàng: ${data.data.customer_name}\n` +
                        `Số tiền: ${new Intl.NumberFormat('vi-VN').format(data.data.amount)} VND\n` +
                        `Điểm thưởng: +${new Intl.NumberFormat('vi-VN').format(data.data.points_earned)} điểm\n` +
                        (data.data.tier_upgraded ? `\n🎉 Đã lên hạng: ${data.data.new_tier}` : '');

                    alert(message);

                    // Reload page to show updated info
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span> Xác nhận thanh toán';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra khi xử lý thanh toán', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span> Xác nhận thanh toán';
            });
    }
</script>

<!-- Assign Room Modal -->
<div id="assignRoomModal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <span class="material-symbols-outlined text-2xl align-middle mr-2">meeting_room</span>
                    Phân phòng
                </h3>
                <button onclick="closeAssignRoomModal()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>

        <div class="p-6 space-y-4 overflow-y-auto flex-1">
            <!-- Booking Info -->
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Thông tin đơn hàng:</p>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Mã đơn:</span>
                        <span class="font-semibold ml-2" id="assign_booking_code"></span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Loại phòng:</span>
                        <span class="font-semibold ml-2" id="assign_room_type"></span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Check-in:</span>
                        <span class="font-semibold ml-2" id="assign_check_in"></span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Check-out:</span>
                        <span class="font-semibold ml-2" id="assign_check_out"></span>
                    </div>
                </div>
            </div>

            <!-- Rooms List -->
            <div>
                <p class="font-semibold mb-3 text-gray-900 dark:text-white">Chọn phòng:</p>
                <div id="rooms_list" class="space-y-2">
                    <!-- Rooms will be populated here -->
                </div>
            </div>
        </div>

        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
            <button onclick="closeAssignRoomModal()"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium">
                Đóng
            </button>
        </div>
    </div>
</div>

<!-- Confirm Payment Modal -->
<div id="confirmPaymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <span class="material-symbols-outlined text-2xl align-middle mr-2">payments</span>
                    Xác nhận thanh toán
                </h3>
                <button onclick="closeConfirmPaymentModal()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>

        <div class="p-6 space-y-4">
            <!-- Booking Info -->
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Thông tin đơn hàng:</p>
                <p class="font-semibold text-gray-900 dark:text-white">
                    <?php echo htmlspecialchars($booking['booking_code']); ?>
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Khách hàng: <?php echo htmlspecialchars($booking['guest_name']); ?>
                </p>
                <p class="text-lg font-bold text-accent mt-2">
                    <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>VND
                </p>
            </div>

            <!-- Points Info -->
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-green-600 dark:text-green-400">stars</span>
                    <p class="font-semibold text-gray-900 dark:text-white">Điểm thưởng</p>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Khách hàng sẽ nhận được:
                </p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                    +<?php echo number_format(floor($booking['total_amount'] / 100)); ?> điểm
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    (1% giá trị đơn hàng<?php echo $booking['status'] === 'confirmed' ? ' + 10% bonus' : ''; ?>)
                </p>
            </div>

            <!-- Payment Method -->
            <div>
                <label for="paymentMethod" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Phương thức thanh toán <span class="text-red-500">*</span>
                </label>
                <select id="paymentMethod"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">-- Chọn phương thức --</option>
                    <option value="cash">Tiền mặt</option>
                    <option value="bank_transfer">Chuyển khoản</option>
                    <option value="credit_card">Thẻ tín dụng</option>
                </select>
            </div>

            <!-- Notes -->
            <div>
                <label for="paymentNotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Ghi chú (tùy chọn)
                </label>
                <textarea id="paymentNotes" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white"
                    placeholder="Nhập ghi chú về thanh toán..."></textarea>
            </div>
        </div>

        <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex gap-3">
            <button onclick="closeConfirmPaymentModal()"
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium">
                Hủy
            </button>
            <button id="confirmPaymentBtn" onclick="confirmPayment()"
                class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                Xác nhận thanh toán
            </button>
        </div>
    </div>
</div>
