<?php
function getBookingConfirmedStaffEmail($data) {
    $checkIn = date('d/m/Y', strtotime($data['check_in_date']));
    $checkOut = date('d/m/Y', strtotime($data['check_out_date']));
    $bookingDate = date('H:i d/m/Y', strtotime($data['created_at']));

    $isInquiry = ($data['booking_type'] ?? 'instant') === 'inquiry';

    return "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1e293b, #334155); color: #fff; padding: 32px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
            .header .subtitle { font-size: 14px; opacity: 0.9; margin-top: 8px; }
            .header .badge { display: inline-block; background: #22c55e; color: #fff; padding: 4px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; margin-top: 12px; }
            .content { padding: 32px; }
            .booking-code { text-align: center; background: #fefce8; padding: 16px; border-radius: 8px; margin: 20px 0; border: 2px solid #d4af37; }
            .booking-code .label { font-size: 12px; color: #666; text-transform: uppercase; }
            .booking-code .code { font-size: 28px; font-weight: 700; color: #d4af37; letter-spacing: 3px; }
            .section { margin: 24px 0; }
            .section-title { font-size: 16px; font-weight: 700; color: #1e293b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 12px; }
            .info-row { display: flex; padding: 8px 0; border-bottom: 1px dotted #eee; }
            .info-label { font-weight: 600; color: #64748b; min-width: 130px; font-size: 14px; }
            .info-value { color: #1e293b; font-size: 14px; }
            .dates { display: flex; gap: 16px; margin: 16px 0; }
            .date-box { flex: 1; text-align: center; padding: 16px; border-radius: 8px; }
            .date-box.checkin { background: #f0fdf4; border: 2px solid #22c55e; }
            .date-box.checkout { background: #fef2f2; border: 2px solid #ef4444; }
            .date-box .label { font-size: 12px; font-weight: 700; text-transform: uppercase; }
            .date-box.checkin .label { color: #16a34a; }
            .date-box.checkout .label { color: #dc2626; }
            .date-box .date { font-size: 18px; font-weight: 700; margin-top: 4px; }
            .total-box { background: #fefce8; padding: 16px; border-radius: 8px; text-align: right; border: 2px solid #d4af37; margin: 16px 0; }
            .total-box .label { font-size: 14px; color: #666; }
            .total-box .amount { font-size: 24px; font-weight: 700; color: #d4af37; }
            .footer { background: #f8f9fa; padding: 24px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
            .footer .hotel-name { font-size: 14px; font-weight: 700; color: #d4af37; margin-bottom: 4px; }
            .action-btn { display: inline-block; background: #d4af37; color: #000; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 16px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Aurora Hotel Plaza</h1>
                <div class='subtitle'>Thông Báo Xác Nhận Đặt Phòng</div>
                <div class='badge'>✓ ĐÃ XÁC NHẬN</div>
            </div>
            <div class='content'>
                <p>
                    Đơn đặt phòng sau đã được <strong>xác nhận</strong>. Vui lòng kiểm tra thông tin và chuẩn bị đón khách.
                </p>

                <div class='booking-code'>
                    <div class='label'>Mã đặt phòng</div>
                    <div class='code'>{$data['booking_code']}</div>
                </div>

                <div class='section'>
                    <div class='section-title'>Thông tin khách hàng</div>
                    <div class='info-row'>
                        <span class='info-label'>Họ tên:</span>
                        <span class='info-value'>{$data['guest_name']}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Số điện thoại:</span>
                        <span class='info-value'>{$data['guest_phone']}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Email:</span>
                        <span class='info-value'>{$data['guest_email']}</span>
                    </div>
                </div>

                <div class='section'>
                    <div class='section-title'>Thông tin phòng</div>
                    <div class='info-row'>
                        <span class='info-label'>Loại phòng:</span>
                        <span class='info-value'>{$data['type_name']}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Danh mục:</span>
                        <span class='info-value'>" . ucfirst($data['category']) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Loại giường:</span>
                        <span class='info-value'>{$data['bed_type']}</span>
                    </div>
                    " . ($data['room_number'] ? "
                    <div class='info-row'>
                        <span class='info-label'>Số phòng:</span>
                        <span class='info-value' style='color:#16a34a;font-weight:700;'>{$data['room_number']}</span>
                    </div>" : "
                    <div class='info-row'>
                        <span class='info-label'>Phòng:</span>
                        <span class='info-value' style='color:#b45309;'>⚠ Chưa phân phòng</span>
                    </div>") . "
                    <div class='info-row'>
                        <span class='info-label'>Số khách:</span>
                        <span class='info-value'>{$data['num_adults']} người lớn" . ($data['num_children'] > 0 ? ", {$data['num_children']} trẻ em" : "") . "</span>
                    </div>
                </div>

                <div class='section'>
                    <div class='section-title'>Thời gian lưu trú</div>
                    <div class='dates'>
                        <div class='date-box checkin'>
                            <div class='label'>Check-in</div>
                            <div class='date'>$checkIn</div>
                            <div style='font-size:11px;color:#666;margin-top:4px;'>Sau 14:00</div>
                        </div>
                        <div class='date-box checkout'>
                            <div class='label'>Check-out</div>
                            <div class='date'>$checkOut</div>
                            <div style='font-size:11px;color:#666;margin-top:4px;'>Trước 12:00</div>
                        </div>
                    </div>
                    <p style='text-align:center;font-size:14px;font-weight:600;'>Tổng: {$data['total_nights']} đêm</p>
                </div>

                <div class='section'>
                    <div class='section-title'>Chi phí</div>
                    <div class='info-row'>
                        <span class='info-label'>Đơn giá/đêm:</span>
                        <span class='info-value'>{$data['per_night']} VND</span>
                    </div>
                    <div class='total-box'>
                        <div class='label'>TỔNG CỘNG</div>
                        <div class='amount'>{$data['total_amount']} VND</div>
                    </div>
                </div>

                " . ($data['special_requests'] ? "
                <div class='section'>
                    <div class='section-title'>Yêu cầu đặc biệt</div>
                    <p style='background:#fffbeb;padding:12px;border-radius:8px;border:1px solid #fde68a;'>" . nl2br(htmlspecialchars($data['special_requests'])) . "</p>
                </div>" : "") . "

                <div style='text-align:center;margin-top:24px;'>
                    <a href='" . (defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com') . "/admin/booking-report.php?id={$data['booking_id']}' class='action-btn'>📄 Xem báo cáo đầy đủ</a>
                </div>
            </div>
            <div class='footer'>
                <div class='hotel-name'>Aurora Hotel Plaza - Hệ thống quản lý đặt phòng</div>
                Email tự động - Vui lòng không trả lời email này
            </div>
        </div>
    </body>
    </html>
    ";
}
