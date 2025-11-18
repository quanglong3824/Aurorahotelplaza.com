<?php
/**
 * Email Template: Booking Confirmation
 * Sent when a new booking is created
 */

function getBookingConfirmationEmailHTML($booking, $hotel_info = []) {
    $hotel_name = $hotel_info['name'] ?? 'Aurora Hotel Plaza';
    $hotel_address = $hotel_info['address'] ?? 'KP2, Phường Tân Hiệp, Thủ Đông Nai';
    $hotel_phone = $hotel_info['phone'] ?? '(+84-251) 391 8888';
    $hotel_email = $hotel_info['email'] ?? 'info@aurorahotelplaza.com';
    $hotel_website = $hotel_info['website'] ?? 'https://aurorahotelplaza.com';
    
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    // Load CSS
    $css = file_get_contents(__DIR__ . '/email-styles.css');
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đặt phòng</title>
    <style>{$css}</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>{$hotel_name}</h1>
                <p>Xác nhận đặt phòng thành công</p>
            </div>
            
            <div class="email-content">
                <p class="email-greeting">Kính gửi <strong>{$booking['guest_name']}</strong>,</p>
                
                <p class="email-text">Cảm ơn quý khách đã chọn {$hotel_name}. Chúng tôi đã nhận được yêu cầu đặt phòng của quý khách.</p>
                
                <div class="booking-code-box">
                    <div class="booking-code-label">Mã đặt phòng</div>
                    <div class="booking-code">{$booking['booking_code']}</div>
                </div>
                
                <div style="text-align: center;">
                    <span class="status-badge status-pending">Chờ xác nhận</span>
                </div>
                
                <div class="info-box">
                    <div class="info-box-title">Thông tin đặt phòng</div>
                    <div class="info-row">
                        <span class="info-label">Loại phòng</span>
                        <span class="info-value">{$booking['type_name']}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày nhận phòng</span>
                        <span class="info-value">{$check_in}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày trả phòng</span>
                        <span class="info-value">{$check_out}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số đêm</span>
                        <span class="info-value">{$booking['total_nights']} đêm</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số khách</span>
                        <span class="info-value">{$booking['num_adults']} người</span>
                    </div>
                </div>
                
                <div class="info-box">
                    <div class="info-box-title">Thông tin khách hàng</div>
                    <div class="info-row">
                        <span class="info-label">Họ tên</span>
                        <span class="info-value">{$booking['guest_name']}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{$booking['guest_email']}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Điện thoại</span>
                        <span class="info-value">{$booking['guest_phone']}</span>
                    </div>
                </div>
                
                <div class="total-amount-box">
                    <div class="total-label">Tổng chi phí</div>
                    <div class="total-amount">{$booking['total_amount_formatted']} VNĐ</div>
                </div>
                
                <div class="alert-box">
                    <div class="alert-box-title">Lưu ý quan trọng</div>
                    <ul>
                        <li>Đặt phòng của quý khách đang ở trạng thái "Chờ xác nhận". Nhân viên sẽ kiểm tra và xác nhận trong thời gian sớm nhất.</li>
                        <li>Quý khách sẽ nhận được email xác nhận trong vòng 24 giờ.</li>
                        <li>Sau khi xác nhận, quý khách có thể tải mã QR để check-in nhanh chóng.</li>
                        <li>Có thể thanh toán trực tuyến hoặc tại khách sạn khi nhận phòng.</li>
                        <li>Hủy miễn phí trước 24 giờ so với thời gian nhận phòng.</li>
                    </ul>
                </div>
                
                <div class="divider"></div>
                
                <div class="contact-info">
                    <div class="contact-info-title">Liên hệ với chúng tôi</div>
                    <div class="contact-item">Điện thoại: <strong>{$hotel_phone}</strong></div>
                    <div class="contact-item">Email: <strong>{$hotel_email}</strong></div>
                    <div class="contact-item">Website: <strong>{$hotel_website}</strong></div>
                    <div class="contact-item">Địa chỉ: <strong>{$hotel_address}</strong></div>
                </div>
                
                <p class="email-text">Chúng tôi rất mong được phục vụ quý khách!</p>
                
                <p class="email-text">Trân trọng,<br><strong>Đội ngũ {$hotel_name}</strong></p>
            </div>
            
            <div class="email-footer">
                <p class="footer-text">Email này được gửi tự động, vui lòng không trả lời trực tiếp.</p>
                <p class="footer-text">© 2025 {$hotel_name}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    
    return $html;
}

function getBookingConfirmationEmailText($booking, $hotel_info = []) {
    $hotel_name = $hotel_info['name'] ?? 'Aurora Hotel Plaza';
    $hotel_phone = $hotel_info['phone'] ?? '(028) 1234 5678';
    $hotel_email = $hotel_info['email'] ?? 'info@aurorahotelplaza.com';
    
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    $text = <<<TEXT
{$hotel_name}
XÁC NHẬN ĐẶT PHÒNG

Kính gửi {$booking['guest_name']},

Cảm ơn quý khách đã chọn {$hotel_name}. Chúng tôi đã nhận được yêu cầu đặt phòng của quý khách.

MÃ ĐẶT PHÒNG: {$booking['booking_code']}
TRẠNG THÁI: Chờ xác nhận

THÔNG TIN ĐẶT PHÒNG:
- Loại phòng: {$booking['type_name']}
- Ngày nhận phòng: {$check_in}
- Ngày trả phòng: {$check_out}
- Số đêm: {$booking['total_nights']} đêm
- Số khách: {$booking['num_adults']} người lớn

THÔNG TIN KHÁCH HÀNG:
- Họ tên: {$booking['guest_name']}
- Email: {$booking['guest_email']}
- Điện thoại: {$booking['guest_phone']}

TỔNG CHI PHÍ: {$booking['total_amount_formatted']} VNĐ

LƯU Ý QUAN TRỌNG:
- Đặt phòng của quý khách đang ở trạng thái "Chờ xác nhận"
- Nhân viên sẽ xác nhận trong vòng 24 giờ
- Sau khi xác nhận, quý khách có thể tải mã QR để check-in
- Có thể hủy miễn phí trước 24 giờ so với thời gian nhận phòng

LIÊN HỆ:
Điện thoại: {$hotel_phone}
Email: {$hotel_email}

Chúng tôi rất mong được phục vụ quý khách!

Trân trọng,
Đội ngũ {$hotel_name}
TEXT;
    
    return $text;
}
?>
