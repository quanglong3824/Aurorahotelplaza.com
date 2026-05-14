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
    <title>Báo cáo xác nhận đặt phòng #<?php echo htmlspecialchars($booking['booking_code']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        .no-print {
            background: #1e293b;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .no-print a, .no-print button {
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }
        .no-print .btn-back { background: #475569; }
        .no-print .btn-print { background: #d4af37; color: #000; font-weight: 600; }
        .no-print .btn-email { background: #2563eb; }

        .page {
            max-width: 800px;
            margin: 24px auto;
            background: #fff;
            padding: 48px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #d4af37;
            padding-bottom: 24px;
            margin-bottom: 32px;
        }
        .header h1 {
            font-size: 28px;
            color: #d4af37;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .header .address {
            font-size: 13px;
            color: #666;
            margin-bottom: 16px;
        }
        .header .title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            margin-top: 12px;
        }
        .header .subtitle {
            font-size: 14px;
            color: #64748b;
            margin-top: 4px;
        }
        .booking-code-display {
            font-size: 24px;
            font-weight: 700;
            color: #d4af37;
            margin-top: 12px;
            letter-spacing: 3px;
        }

        .section {
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 24px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px dotted #e2e8f0;
        }
        .info-label {
            font-weight: 600;
            color: #64748b;
            min-width: 140px;
            font-size: 14px;
        }
        .info-value {
            color: #1e293b;
            font-size: 14px;
        }

        .dates-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin: 16px 0;
        }
        .date-box {
            text-align: center;
            padding: 16px;
            border-radius: 8px;
            border: 2px solid;
        }
        .date-box.checkin {
            border-color: #22c55e;
            background: #f0fdf4;
        }
        .date-box.checkout {
            border-color: #ef4444;
            background: #fef2f2;
        }
        .date-box .label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .date-box.checkin .label { color: #16a34a; }
        .date-box.checkout .label { color: #dc2626; }
        .date-box .date {
            font-size: 20px;
            font-weight: 700;
        }
        .date-box.checkin .date { color: #16a34a; }
        .date-box.checkout .date { color: #dc2626; }
        .date-box .time {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        .price-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .price-table th, .price-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .price-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
        }
        .price-table .total-row {
            background: #fefce8;
            font-weight: 700;
        }
        .price-table .total-row td {
            font-size: 18px;
            color: #d4af37;
            border-bottom: none;
        }

        .footer {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
        }
        .footer .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin: 40px 0 20px;
        }
        .footer .signature {
            text-align: center;
        }
        .footer .signature .role {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 14px;
            margin-bottom: 60px;
        }
        .footer .signature .name {
            border-top: 1px solid #333;
            display: inline-block;
            padding-top: 4px;
            font-size: 14px;
        }
        .footer .hotel-info {
            font-size: 12px;
            color: #64748b;
            margin-top: 20px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-confirmed { background: #dbeafe; color: #1d4ed8; }
        .badge-pending { background: #fef3c7; color: #b45309; }
        .badge-cancelled { background: #fee2e2; color: #dc2626; }

        .inquiry-box {
            background: #faf5ff;
            border: 1px solid #e9d5ff;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        .inquiry-box h4 {
            color: #7c3aed;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .notes-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .page {
                margin: 0;
                box-shadow: none;
                padding: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="booking-detail.php?id=<?php echo $booking_id; ?>" class="btn-back">← Quay lại</a>
        <div style="display:flex;gap:8px;">
            <button onclick="window.print()" class="btn-print">🖨️ In báo cáo</button>
        </div>
    </div>

    <div class="page">
        <div class="header">
            <h1>Aurora Hotel Plaza</h1>
            <div class="address">253 Phạm Văn Thuận, KP2, Tam Hiệp, TP.Đồng Nai</div>
            <div class="title">Xác Nhận Đặt Phòng</div>
            <div class="subtitle">Booking Confirmation</div>
            <div class="booking-code-display"><?php echo htmlspecialchars($booking['booking_code']); ?></div>
            <div style="margin-top:8px;">
                <span class="badge badge-<?php echo $booking['status']; ?>">
                    <?php echo $status_labels[$booking['status']] ?? $booking['status']; ?>
                </span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Thông tin khách hàng / Guest Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Họ tên:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số điện thoại:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['guest_phone']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['guest_email']); ?></span>
                </div>
                <?php if ($booking['guest_id_number']): ?>
                <div class="info-row">
                    <span class="info-label">Số CMND/CCCD:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['guest_id_number']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Thông tin đặt phòng / Booking Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Mã đơn:</span>
                    <span class="info-value" style="font-weight:700;color:#d4af37;"><?php echo htmlspecialchars($booking['booking_code']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ngày đặt:</span>
                    <span class="info-value"><?php echo date('H:i d/m/Y', strtotime($booking['created_at'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Loại phòng:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['type_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Loại giường:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['bed_type']); ?></span>
                </div>
                <?php if ($booking['category']): ?>
                <div class="info-row">
                    <span class="info-label">Danh mục:</span>
                    <span class="info-value"><?php echo ucfirst(htmlspecialchars($booking['category'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($booking['size_sqm']): ?>
                <div class="info-row">
                    <span class="info-label">Diện tích:</span>
                    <span class="info-value"><?php echo $booking['size_sqm']; ?> m²</span>
                </div>
                <?php endif; ?>
                <?php if ($booking['room_number']): ?>
                <div class="info-row">
                    <span class="info-label">Số phòng:</span>
                    <span class="info-value" style="font-weight:700;color:#16a34a;"><?php echo htmlspecialchars($booking['room_number']); ?></span>
                </div>
                <?php if ($booking['floor']): ?>
                <div class="info-row">
                    <span class="info-label">Tầng:</span>
                    <span class="info-value"><?php echo $booking['floor']; ?><?php if ($booking['building']) echo ' - ' . htmlspecialchars($booking['building']); ?></span>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="info-row">
                    <span class="info-label">Phòng:</span>
                    <span class="info-value" style="color:#b45309;">Sẽ được phân khi check-in</span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Số khách:</span>
                    <span class="info-value"><?php echo $booking['num_adults']; ?> người lớn<?php if ($booking['num_children'] > 0) echo ', ' . $booking['num_children'] . ' trẻ em'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số phòng:</span>
                    <span class="info-value"><?php echo $booking['num_rooms']; ?></span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Thời gian lưu trú / Stay Duration</div>
            <div class="dates-grid">
                <div class="date-box checkin">
                    <div class="label">Check-in</div>
                    <div class="date"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></div>
                    <div class="time">Sau 14:00</div>
                </div>
                <div class="date-box checkout">
                    <div class="label">Check-out</div>
                    <div class="date"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></div>
                    <div class="time">Trước 12:00</div>
                </div>
            </div>
            <div style="text-align:center;margin-top:8px;">
                <span style="font-size:16px;font-weight:700;color:#1e293b;">
                    Tổng thời gian: <?php echo $nights; ?> đêm
                </span>
            </div>
        </div>

        <?php if ($booking['booking_type'] === 'inquiry'): ?>
        <div class="inquiry-box">
            <h4>📋 Yêu cầu căn hộ / Apartment Inquiry</h4>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Thời gian:</span>
                    <span class="info-value"><?php echo $duration_labels[$booking['duration_type']] ?? $booking['duration_type']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Hình thức:</span>
                    <span class="info-value">Liên hệ báo giá</span>
                </div>
            </div>
            <?php if (!empty($booking['inquiry_message'])): ?>
            <div style="margin-top:12px;">
                <strong>Tin nhắn:</strong>
                <p style="margin-top:4px;white-space:pre-wrap;"><?php echo htmlspecialchars($booking['inquiry_message']); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="section">
            <div class="section-title">Chi phí / Pricing</div>
            <table class="price-table">
                <thead>
                    <tr>
                        <th>Mô tả</th>
                        <th style="text-align:right;">Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Đơn giá phòng / đêm × <?php echo $nights; ?> đêm</td>
                        <td style="text-align:right;"><?php echo number_format($per_night, 0, ',', '.'); ?> VND</td>
                    </tr>
                    <?php if (($booking['extra_beds'] ?? 0) > 0): ?>
                    <tr>
                        <td>Giường phụ (<?php echo $booking['extra_beds']; ?> giường)</td>
                        <td style="text-align:right;"><?php echo number_format($booking['extra_bed_fee'] ?? 0, 0, ',', '.'); ?> VND</td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($booking['service_fee'] > 0): ?>
                    <tr>
                        <td>Phí dịch vụ</td>
                        <td style="text-align:right;"><?php echo number_format($booking['service_fee'], 0, ',', '.'); ?> VND</td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($booking['discount_amount'] > 0): ?>
                    <tr>
                        <td>Giảm giá</td>
                        <td style="text-align:right;color:#dc2626;">-<?php echo number_format($booking['discount_amount'], 0, ',', '.'); ?> VND</td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td>TỔNG CỘNG / TOTAL</td>
                        <td style="text-align:right;"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?> VND</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if (!empty($booking['special_requests'])): ?>
        <div class="notes-box">
            <strong>📝 Yêu cầu đặc biệt / Special Requests:</strong>
            <p style="margin-top:8px;white-space:pre-wrap;"><?php echo htmlspecialchars($booking['special_requests']); ?></p>
        </div>
        <?php endif; ?>

        <div class="footer">
            <div class="signatures">
                <div class="signature">
                    <div class="role">Khách hàng / Guest</div>
                    <div class="name"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                </div>
                <div class="signature">
                    <div class="role">Quản lý / Management</div>
                    <div class="name">Aurora Hotel Plaza</div>
                </div>
            </div>
            <p style="font-size:13px;color:#64748b;margin-bottom:4px;">
                Báo cáo được tạo tự động từ hệ thống quản lý đặt phòng Aurora Hotel Plaza
            </p>
            <p style="font-size:11px;color:#94a3b8;">
                Ngày in: <?php echo date('H:i d/m/Y'); ?> | Mã đơn: <?php echo htmlspecialchars($booking['booking_code']); ?>
            </p>
            <div class="hotel-info">
                <strong>Aurora Hotel Plaza</strong> - 253 Phạm Văn Thuận, KP2, Tam Hiệp, TP.Đồng Nai<br>
                Hotline: 0251 3511 888 | Email: info@aurorahotelplaza.com | Website: aurorahotelplaza.com
            </div>
        </div>
    </div>
</body>
</html>
