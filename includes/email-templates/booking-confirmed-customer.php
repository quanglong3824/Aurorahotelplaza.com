<?php
function getBookingConfirmedCustomerEmail($data) {
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
            .header { background: linear-gradient(135deg, #d4af37, #b8941f); color: #fff; padding: 32px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
            .header .subtitle { font-size: 14px; opacity: 0.9; margin-top: 8px; }
            .content { padding: 32px; }
            .greeting { font-size: 16px; margin-bottom: 20px; color: #333; }
            .booking-code { text-align: center; background: #fefce8; padding: 16px; border-radius: 8px; margin: 20px 0; border: 2px solid #d4af37; }
            .booking-code .label { font-size: 12px; color: #666; text-transform: uppercase; }
            .booking-code .code { font-size: 28px; font-weight: 700; color: #d4af37; letter-spacing: 3px; }
            .section { margin: 24px 0; }
            .section-title { font-size: 16px; font-weight: 700; color: #d4af37; text-transform: uppercase; border-bottom: 2px solid #f0e6d2; padding-bottom: 8px; margin-bottom: 12px; }
            .info-row { display: flex; padding: 8px 0; border-bottom: 1px dotted #eee; }
            .info-label { font-weight: 600; color: #666; min-width: 130px; font-size: 14px; }
            .info-value { color: #333; font-size: 14px; }
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
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Aurora Hotel Plaza</h1>
                <div class='subtitle'>Xác Nhận Đặt Phòng / Booking Confirmation</div>
            </div>
            <div class='content'>
                <div class='greeting'>
                    Kính gửi <strong>{$data['guest_name']}</strong>,
                </div>
                <p>
                    Chúng tôi xin xác nhận đơn đặt phòng của Quý khách tại Aurora Hotel Plaza đã được phê duyệt.
                    Dưới đây là thông tin chi tiết:
                </p>

                <div class='booking-code'>
                    <div class='label'>Mã đặt phòng</div>
                    <div class='code'>{$data['booking_code']}</div>
                </div>

                <div class='section'>
                    <div class='section-title'>Thông tin phòng</div>
                    <div class='info-row'>
                        <span class='info-label'>Loại phòng:</span>
                        <span class='info-value'>{$data['type_name']}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Loại giường:</span>
                        <span class='info-value'>{$data['bed_type']}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Số khách:</span>
                        <span class='info-value'>{$data['num_adults']} người lớn" . ($data['num_children'] > 0 ? ", {$data['num_children']} trẻ em" : "") . "</span>
                    </div>
                    " . ($data['room_number'] ? "
                    <div class='info-row'>
                        <span class='info-label'>Số phòng:</span>
                        <span class='info-value' style='color:#16a34a;font-weight:700;'>{$data['room_number']}</span>
                    </div>" : "
                    <div class='info-row'>
                        <span class='info-label'>Phòng:</span>
                        <span class='info-value'>Sẽ được phân khi check-in</span>
                    </div>") . "
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
                    <p style='text-align:center;font-size:14px;font-weight:600;'>Tổng thời gian: {$data['total_nights']} đêm</p>
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
                    <p style='background:#f8f9fa;padding:12px;border-radius:8px;'>" . nl2br(htmlspecialchars($data['special_requests'])) . "</p>
                </div>" : "") . "

                <p style='margin-top:24px;'>
                    Quý khách vui lòng xuất trình mã đặt phòng khi check-in tại quầy lễ tân.
                    Nếu có bất kỳ thắc mắc nào, xin vui lòng liên hệ với chúng tôi.
                </p>

                <p style='margin-top:20px;'>
                    Trân trọng,<br>
                    <strong>Aurora Hotel Plaza</strong>
                </p>
            </div>
            <div class='footer'>
                <div class='hotel-name'>Aurora Hotel Plaza</div>
                253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai<br>
                Hotline: 0251 3511 888 | info@aurorahotelplaza.com
            </div>
        </div>
    </body>
    </html>
    ";
}
