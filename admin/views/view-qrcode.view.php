<?php
/**
 * Aurora Hotel Plaza - View QR Code View
 */
?>

<div class="max-w-6xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="booking-detail.php?id=<?php echo $booking_id; ?>" class="btn btn-secondary">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Quay lại (Back)
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- QR Code Display -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-lg">Mã QR (QR Code)</h3>
            </div>
            <div class="card-body">
                <div class="bg-white p-8 rounded-xl flex items-center justify-center">
                    <img id="qrImage" src="<?php echo $qr_url; ?>" alt="QR Code" class="w-full max-w-sm"
                        onerror="handleQRError()">
                </div>

                <div class="mt-6 space-y-3">
                    <button onclick="downloadQR()" class="btn btn-primary w-full">
                        <span class="material-symbols-outlined text-sm">download</span>
                        Tải xuống QR Code (Download QR)
                    </button>

                    <button onclick="printBookingQR()" class="btn btn-secondary w-full">
                        <span class="material-symbols-outlined text-sm">print</span>
                        In QR Code (Print)
                    </button>

                    <button onclick="shareQR()" class="btn btn-secondary w-full">
                        <span class="material-symbols-outlined text-sm">share</span>
                        Chia sẻ (Share)
                    </button>
                </div>

                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-blue-600">info</span>
                        <div class="flex-1 text-sm text-blue-800 dark:text-blue-200">
                            <p class="font-semibold mb-1">Hướng dẫn sử dụng (Usage Instructions):</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Khách có thể chụp màn hình để lưu QR (Guests can take a screenshot)</li>
                                <li>Quét QR tại quầy lễ tân khi check-in (Scan QR at reception)</li>
                                <li>QR chứa thông tin booking đầy đủ (QR contains full booking info)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Info - Full Details -->
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h3 class="font-bold text-lg">Thông tin đặt phòng (Booking Info)</h3>
                <div class="flex gap-2">
                    <span class="badge <?php echo $status_config[$booking['status']]['class'] ?? 'badge-secondary'; ?>">
                        <span
                            class="material-symbols-outlined text-sm mr-1"><?php echo $status_config[$booking['status']]['icon'] ?? 'info'; ?></span>
                        <?php echo $status_config[$booking['status']]['label'] ?? $booking['status']; ?>
                    </span>
                    <span
                        class="badge <?php echo $payment_status_config[$booking['payment_status']]['class'] ?? 'badge-secondary'; ?>">
                        <?php echo $payment_status_config[$booking['payment_status']]['label'] ?? $booking['payment_status']; ?>
                    </span>
                </div>
            </div>
            <div class="card-body space-y-5">
                <!-- Booking Code -->
                <div class="text-center border-b border-gray-200 dark:border-gray-700 pb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Mã đặt phòng (Booking Code)</p>
                    <p class="font-bold text-3xl" style="color: #d4af37;">
                        <?php echo htmlspecialchars($booking['booking_code']); ?>
                    </p>
                </div>

                <!-- Guest Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">person</span>
                        Thông tin khách hàng (Guest Information)
                    </h4>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Họ tên (Full Name)</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Số điện thoại (Phone)</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-gray-500 dark:text-gray-400">Email</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($booking['guest_email']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Room Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">hotel</span>
                        Thông tin phòng (Room Info)
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Loại phòng (Room Type)</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($booking['type_name']); ?></span>
                        </div>
                        <?php if ($booking['room_number']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Số phòng (No. of Rooms)</span>
                                <span
                                    class="font-bold text-green-600 text-lg"><?php echo htmlspecialchars($booking['room_number']); ?></span>
                            </div>
                            <?php if ($booking['floor']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Tầng (Floor)</span>
                                    <span class="font-semibold"><?php echo $booking['floor']; ?></span>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-2 rounded text-center">
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">Chưa phân phòng (Not assigned)</p>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Số khách (No. of Guests)</span>
                            <span class="font-semibold">
                                <?php echo $booking['num_adults']; ?> người lớn (adults)
                                <?php if ($booking['num_children'] > 0): ?>, <?php echo $booking['num_children']; ?> trẻ
                                    em (children)<?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Số phòng (No. of Rooms)</span>
                            <span class="font-semibold"><?php echo $booking['num_rooms']; ?> phòng (rooms)</span>
                        </div>
                    </div>
                </div>

                <!-- Check-in/Check-out -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg text-center">
                        <p class="text-xs text-green-600 dark:text-green-400 mb-1">CHECK-IN</p>
                        <p class="font-bold text-lg text-green-700 dark:text-green-300">
                            <?php echo date('m/d/Y', strtotime($booking['check_in_date'])); ?>
                        </p>
                        <p class="text-xs text-green-600 dark:text-green-400">Sau (After) 14:00</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg text-center">
                        <p class="text-xs text-red-600 dark:text-red-400 mb-1">CHECK-OUT</p>
                        <p class="font-bold text-lg text-red-700 dark:text-red-300">
                            <?php echo date('m/d/Y', strtotime($booking['check_out_date'])); ?>
                        </p>
                        <p class="text-xs text-red-600 dark:text-red-400">Trước (Before) 12:00</p>
                    </div>
                </div>

                <div class="text-center">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">
                        <span class="material-symbols-outlined text-sm align-middle">dark_mode</span>
                        <?php echo $nights; ?> đêm (nights)
                    </span>
                </div>

                <!-- Payment Details -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">payments</span>
                        Chi tiết thanh toán (Payment Details)
                    </h4>
                    <div class="space-y-2 text-sm">
                        <?php
                        // room_price in DB stores total (per_night × nights)
                        $room_total_admin = (float) $booking['room_price'];
                        $nights_admin = max(1, (int) $nights);
                        $room_per_night_admin = $room_total_admin / $nights_admin;
                        ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Giá phòng/đêm (Room Price/Night)</span>
                            <span><?php echo number_format($room_per_night_admin, 0, ',', '.'); ?>VND</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Tiền phòng (<?php echo $nights; ?> đêm ×
                                <?php echo $booking['num_rooms']; ?> phòng)</span>
                            <span><?php echo number_format($room_total_admin, 0, ',', '.'); ?>VND</span>
                        </div>
                        <?php if ($booking['discount_amount'] > 0): ?>
                            <div class="flex justify-between text-green-600">
                                <span>Giảm giá (Discount)</span>
                                <span>-<?php echo number_format($booking['discount_amount'], 0, ',', '.'); ?>VND</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($booking['service_fee'] > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Phí dịch vụ (Service Fee)</span>
                                <span><?php echo number_format($booking['service_fee'], 0, ',', '.'); ?>VND</span>
                            </div>
                        <?php endif; ?>
                        <div class="border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold">Tổng cộng (Total)</span>
                                <span class="font-bold text-2xl" style="color: #d4af37;">
                                    <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>VND
                                </span>
                            </div>
                        </div>
                        <?php if ($booking['payment_method']): ?>
                            <div class="flex justify-between mt-2">
                                <span class="text-gray-500 dark:text-gray-400">Phương thức (Method)</span>
                                <span>
                                    <?php
                                    $payment_methods = [
                                        'cash' => 'Tiền mặt (Cash)',
                                        'bank_transfer' => 'Chuyển khoản (Bank Transfer)',
                                        'credit_card' => 'Thẻ tín dụng (Credit Card)'
                                    ];
                                    echo $payment_methods[$booking['payment_method']] ?? $booking['payment_method'];
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if ($booking['transaction_id']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Mã giao dịch</span>
                                <span class="font-mono text-xs"><?php echo $booking['transaction_id']; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Special Requests -->
                <?php if (!empty($booking['special_requests'])): ?>
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#d4af37]">note</span>
                            Yêu cầu đặc biệt
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Booking Time -->
                <div
                    class="text-center text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <p>Đặt phòng lúc: <?php echo date('H:i m/d/Y', strtotime($booking['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function downloadQR() {
        window.location.href = '../profile/api/download-qrcode.php?booking_id=<?php echo $booking_id; ?>';
    }

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
                .badge-checked_in { background: #e8f5e9; color: #2e7d32; }
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
                    <span class="badge badge-<?php echo $booking['status']; ?>">
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
                            <span class="info-value"><?php echo $booking['num_adults']; ?> người lớn<?php if ($booking['num_children'] > 0)
                                    echo ', ' . $booking['num_children'] . ' trẻ em'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Số phòng:</span>
                            <span class="info-value"><?php echo $booking['num_rooms']; ?> phòng</span>
                        </div>
                    </div>
                    
                    <div class="dates-grid">
                        <div class="date-box checkin">
                            <div class="date-label">Check-in</div>
                            <div class="date-value"><?php echo date('m/d/Y', strtotime($booking['check_in_date'])); ?></div>
                            <div class="date-time">Sau 14:00</div>
                        </div>
                        <div class="date-box checkout">
                            <div class="date-label">Check-out</div>
                            <div class="date-value"><?php echo date('m/d/Y', strtotime($booking['check_out_date'])); ?></div>
                            <div class="date-time">Trước 12:00</div>
                        </div>
                    </div>
                    
                    <div class="total-amount">
                        <div class="total-label"><?php echo $nights; ?> đêm x <?php echo $booking['num_rooms']; ?> phòng</div>
                        <div class="total-value"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>VND</div>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p>Aurora Hotel Plaza - Biên Hòa, Đồng Nai</p>
                <p>Hotline: 0251 3511 888 | Email: info@aurorahotelplaza.com</p>
                <p style="margin-top: 10px;">In lúc: ${new Date().toLocaleString('vi-VN')} | Nhân viên: <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
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
                text: 'Mã QR cho đặt phòng tại Aurora Hotel Plaza\nCheck-in: <?php echo date('m/d/Y', strtotime($booking['check_in_date'])); ?>\nCheck-out: <?php echo date('m/d/Y', strtotime($booking['check_out_date'])); ?>',
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
