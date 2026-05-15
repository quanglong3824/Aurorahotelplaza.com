<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/security.php';

$booking_id = $_GET['id'] ?? null;

if (!$booking_id) {
    header('Location: bookings.php');
    exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT b.*, b.booking_type, b.inquiry_message, b.duration_type,
               u.full_name as user_name, u.email as user_email, u.phone as user_phone,
               rt.type_name, rt.category, rt.bed_type, rt.max_occupancy, rt.size_sqm,
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

    $nights = $booking['total_nights'];
    $per_night = $nights > 0 ? $booking['room_price'] / $nights : $booking['room_price'];

    $status_labels = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'checked_in' => 'Đã nhận phòng',
        'checked_out' => 'Đã trả phòng',
        'cancelled' => 'Đã hủy'
    ];

    $duration_labels = [
        '1_month' => '1 tháng',
        '3_months' => '3 tháng',
        '6_months' => '6 tháng',
        '12_months' => '12 tháng (1 năm)',
        'custom' => 'Khác'
    ];

} catch (Exception $e) {
    error_log("Booking report error: " . $e->getMessage());
    header('Location: bookings.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đặt phòng #<?php echo htmlspecialchars($booking['booking_code']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #000; font-size: 13px; line-height: 1.5; }
        .no-print { background: #222; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; }
        .no-print a, .no-print button { color: #fff; text-decoration: none; padding: 6px 14px; border-radius: 4px; font-size: 13px; cursor: pointer; border: 1px solid #555; background: #333; }
        .no-print button:hover { background: #555; }

        .page { max-width: 210mm; margin: 0 auto; padding: 12mm; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 14px; }
        .logo { width: 120px; }
        .logo img { width: 100%; height: auto; }
        .header-right { text-align: right; }
        .doc-title { font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .doc-subtitle { font-size: 11px; color: #555; margin-top: 1px; }
        .doc-date { font-size: 10px; color: #666; margin-top: 4px; }

        /* Booking code bar */
        .code-bar { display: flex; justify-content: space-between; align-items: center; border: 1px solid #000; padding: 8px 16px; margin-bottom: 14px; }
        .code-bar .label { font-size: 10px; text-transform: uppercase; color: #555; letter-spacing: 1px; }
        .code-bar .code { font-size: 18px; font-weight: 700; letter-spacing: 3px; }
        .code-bar .status { font-size: 11px; font-weight: 600; border: 1px solid #000; padding: 2px 8px; text-transform: uppercase; }

        /* Two column */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin-bottom: 14px; border-bottom: 1px solid #ccc; }
        .col { padding: 10px 0; }
        .col:first-child { padding-right: 16px; border-right: 1px solid #ccc; }
        .col:last-child { padding-left: 16px; }
        .col-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid #000; }
        .row { display: flex; justify-content: space-between; padding: 2px 0; font-size: 11px; }
        .row .label { color: #555; }
        .row .value { font-weight: 500; text-align: right; }

        /* Stay strip */
        .stay-strip { display: grid; grid-template-columns: 1fr auto 1fr; border: 1px solid #000; margin-bottom: 14px; }
        .stay-box { padding: 10px 16px; text-align: center; }
        .stay-box .label { font-size: 9px; text-transform: uppercase; color: #555; letter-spacing: 1px; }
        .stay-box .date { font-size: 15px; font-weight: 700; margin-top: 2px; }
        .stay-box .time { font-size: 9px; color: #666; margin-top: 1px; }
        .stay-divider { display: flex; align-items: center; justify-content: center; border-left: 1px solid #000; border-right: 1px solid #000; padding: 0 12px; }
        .stay-divider .nights { font-size: 13px; font-weight: 700; }
        .stay-divider .nights-label { font-size: 9px; color: #555; text-transform: uppercase; }

        /* Pricing */
        .pricing { border: 1px solid #000; margin-bottom: 14px; }
        .pricing-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 6px 16px; border-bottom: 1px solid #000; }
        .pricing-row { display: flex; justify-content: space-between; padding: 4px 16px; font-size: 11px; border-bottom: 1px dotted #ccc; }
        .pricing-row:last-child { border-bottom: none; }
        .pricing-row.total { border-top: 2px solid #000; padding-top: 6px; margin-top: 2px; }
        .pricing-row.total .label { font-weight: 700; font-size: 12px; text-transform: uppercase; }
        .pricing-row.total .value { font-weight: 700; font-size: 14px; }

        /* Notes */
        .notes { border: 1px solid #000; padding: 8px 16px; margin-bottom: 14px; }
        .notes-title { font-size: 10px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
        .notes-text { font-size: 11px; white-space: pre-wrap; color: #333; }

        /* Signatures */
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 24px; }
        .sig { text-align: center; }
        .sig .role { font-size: 10px; font-weight: 700; text-transform: uppercase; margin-bottom: 35px; }
        .sig .name { border-top: 1px solid #000; display: inline-block; padding-top: 4px; font-size: 11px; min-width: 140px; }

        /* Footer */
        .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #ccc; text-align: center; font-size: 9px; color: #555; }
        .footer .hotel { font-size: 11px; font-weight: 700; color: #000; margin-bottom: 2px; }

        @media print {
            .no-print { display: none !important; }
            .page { padding: 10mm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="booking-detail.php?id=<?php echo $booking_id; ?>">← Quay lại</a>
        <button onclick="window.print()">🖨️ In</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <img src="../assets/img/src/logo/logo-white-ui.png" alt="Aurora Hotel Plaza">
            </div>
            <div class="header-right">
                <div class="doc-title">Xác Nhận Đặt Phòng</div>
                <div class="doc-subtitle">Booking Confirmation</div>
                <div class="doc-date">Ngày in: <?php echo date('H:i d/m/Y'); ?></div>
            </div>
        </div>

        <!-- Code bar -->
        <div class="code-bar">
            <div>
                <div class="label">Mã đặt phòng / Booking Code</div>
                <div class="code"><?php echo htmlspecialchars($booking['booking_code']); ?></div>
            </div>
            <div class="status"><?php echo $status_labels[$booking['status']] ?? $booking['status']; ?></div>
        </div>

        <!-- Guest & Booking info -->
        <div class="two-col">
            <div class="col">
                <div class="col-title">Khách hàng / Guest</div>
                <div class="row"><span class="label">Họ tên:</span><span class="value"><?php echo htmlspecialchars($booking['guest_name']); ?></span></div>
                <div class="row"><span class="label">SĐT:</span><span class="value"><?php echo htmlspecialchars($booking['guest_phone']); ?></span></div>
                <div class="row"><span class="label">Email:</span><span class="value"><?php echo htmlspecialchars($booking['guest_email']); ?></span></div>
                <?php if ($booking['guest_id_number']): ?>
                <div class="row"><span class="label">CMND/CCCD:</span><span class="value"><?php echo htmlspecialchars($booking['guest_id_number']); ?></span></div>
                <?php endif; ?>
                <div class="row"><span class="label">Ngày đặt:</span><span class="value"><?php echo date('H:i d/m/Y', strtotime($booking['created_at'])); ?></span></div>
            </div>
            <div class="col">
                <div class="col-title">Phòng / Room</div>
                <div class="row"><span class="label">Loại phòng:</span><span class="value"><?php echo htmlspecialchars($booking['type_name']); ?></span></div>
                <div class="row"><span class="label">Giường:</span><span class="value"><?php echo htmlspecialchars($booking['bed_type']); ?></span></div>
                <?php if ($booking['room_number']): ?>
                <div class="row"><span class="label">Số phòng:</span><span class="value" style="font-weight:700;"><?php echo htmlspecialchars($booking['room_number']); ?></span></div>
                <div class="row"><span class="label">Tầng:</span><span class="value"><?php echo $booking['floor']; ?><?php if ($booking['building']) echo ' - ' . htmlspecialchars($booking['building']); ?></span></div>
                <?php else: ?>
                <div class="row"><span class="label">Phòng:</span><span class="value">Chưa phân</span></div>
                <?php endif; ?>
                <div class="row"><span class="label">Số khách:</span><span class="value"><?php echo $booking['num_adults']; ?> NL<?php if ($booking['num_children'] > 0) echo ', ' . $booking['num_children'] . ' TE'; ?></span></div>
            </div>
        </div>

        <!-- Stay strip -->
        <div class="stay-strip">
            <div class="stay-box">
                <div class="label">Nhận phòng / Check-in</div>
                <div class="date"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></div>
                <div class="time">Từ 14:00</div>
            </div>
            <div class="stay-divider">
                <div>
                    <div class="nights"><?php echo $nights; ?> đêm</div>
                    <div class="nights-label">nights</div>
                </div>
            </div>
            <div class="stay-box">
                <div class="label">Trả phòng / Check-out</div>
                <div class="date"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></div>
                <div class="time">Trước 12:00</div>
            </div>
        </div>

        <?php if ($booking['booking_type'] === 'inquiry'): ?>
        <div class="notes">
            <div class="notes-title">Yêu cầu căn hộ / Apartment Inquiry</div>
            <div class="notes-text">Thời gian: <?php echo $duration_labels[$booking['duration_type']] ?? $booking['duration_type']; ?> — Hình thức: Liên hệ báo giá<?php if (!empty($booking['inquiry_message'])) echo "\nTin nhắn: " . htmlspecialchars($booking['inquiry_message']); ?></div>
        </div>
        <?php endif; ?>

        <!-- Pricing -->
        <div class="pricing">
            <div class="pricing-title">Chi phí / Pricing</div>
            <div class="pricing-row"><span class="label">Đơn giá / đêm × <?php echo $nights; ?> đêm</span><span class="value"><?php echo number_format($per_night, 0, ',', '.'); ?> VND</span></div>
            <?php if (($booking['extra_beds'] ?? 0) > 0): ?>
            <div class="pricing-row"><span class="label">Giường phụ (<?php echo $booking['extra_beds']; ?>)</span><span class="value"><?php echo number_format($booking['extra_bed_fee'] ?? 0, 0, ',', '.'); ?> VND</span></div>
            <?php endif; ?>
            <?php if ($booking['service_fee'] > 0): ?>
            <div class="pricing-row"><span class="label">Phí dịch vụ</span><span class="value"><?php echo number_format($booking['service_fee'], 0, ',', '.'); ?> VND</span></div>
            <?php endif; ?>
            <?php if ($booking['discount_amount'] > 0): ?>
            <div class="pricing-row"><span class="label">Giảm giá</span><span class="value">-<?php echo number_format($booking['discount_amount'], 0, ',', '.'); ?> VND</span></div>
            <?php endif; ?>
            <div class="pricing-row total"><span class="label">Tổng cộng / Total</span><span class="value"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?> VND</span></div>
        </div>

        <?php if (!empty($booking['special_requests'])): ?>
        <div class="notes">
            <div class="notes-title">Yêu cầu đặc biệt / Special Requests</div>
            <div class="notes-text"><?php echo htmlspecialchars($booking['special_requests']); ?></div>
        </div>
        <?php endif; ?>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig">
                <div class="role">Khách hàng / Guest</div>
                <div class="name"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
            </div>
            <div class="sig">
                <div class="role">Quản lý / Management</div>
                <div class="name">Aurora Hotel Plaza</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="hotel">Aurora Hotel Plaza</div>
            253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai | Hotline: 0251 3511 888 | info@aurorahotelplaza.com
        </div>
    </div>
</body>
</html>
