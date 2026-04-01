<?php
/**
 * Email Template: Booking Confirmation
 * Style: Modern Luxury / Liquid Glass / Golden Accent
 */

function getBookingConfirmationEmailHTML($booking, $hotel_info = []) {
    $hotel_name = $hotel_info['name'] ?? 'Aurora Hotel Plaza';
    $hotel_address = $hotel_info['address'] ?? '253 Phạm Văn Thuận, KP2, Phường Tam Hiệp, Biên Hòa, Đồng Nai';
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
    <title>Xác nhận đặt phòng - Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>AURORA HOTEL PLAZA</h1>
                <p>LUXURY HOSPITALITY EXPERIENCE</p>
            </div>
            <div class="accent-bar"></div>
            
            <div class="email-content">
                <div class="greeting">Kính gửi {$booking['guest_name']},</div>
                <p class="main-text">Cảm ơn quý khách đã tin tưởng lựa chọn Aurora Hotel Plaza. Chúng tôi rất vui mừng thông báo rằng yêu cầu đặt phòng của quý khách đã được nhận thành công.</p>
                
                <div class="highlight-box">
                    <div class="highlight-label">Mã đặt phòng của bạn</div>
                    <div class="highlight-value">{$booking['booking_code']}</div>
                    <div style="margin-top: 15px;">
                        <span class="badge badge-gold">Chờ xác nhận</span>
                    </div>
                </div>
                
                <div class="data-card">
                    <div class="card-title">Chi tiết đặt phòng</div>
                    <div class="data-row">
                        <span class="data-label">Loại phòng</span>
                        <span class="data-value">{$booking['type_name']}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Nhận phòng</span>
                        <span class="data-value">{$check_in} (14:00)</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Trả phòng</span>
                        <span class="data-value">{$check_out} (12:00)</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Số đêm lưu trú</span>
                        <span class="data-value">{$booking['total_nights']} đêm</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Số khách</span>
                        <span class="data-value">{$booking['num_adults']} người lớn</span>
                    </div>
                </div>

                <div class="data-card">
                    <div class="card-title">Tổng chi phí dự kiến</div>
                    <div style="text-align: center; padding: 10px 0;">
                        <div style="font-size: 28px; font-weight: 800; color: #d4af37;">{$booking['total_amount_formatted']} VND</div>
                        <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">(Đã bao gồm thuế và phí dịch vụ)</p>
                    </div>
                </div>
                
                <div class="data-card" style="background-color: #f8fafc; border: 1px dashed #cbd5e1;">
                    <div class="card-title" style="color: #64748b;">Lưu ý quan trọng</div>
                    <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #64748b;">
                        <li style="margin-bottom: 8px;">Nhân viên của chúng tôi sẽ liên hệ để xác nhận đặt phòng trong vòng 24 giờ tới.</li>
                        <li style="margin-bottom: 8px;">Vui lòng xuất trình mã đặt phòng này khi làm thủ tục nhận phòng tại lễ tân.</li>
                        <li style="margin-bottom: 8px;">Quý khách có thể yêu cầu thay đổi hoặc hủy phòng ít nhất 24 giờ trước thời điểm nhận phòng.</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin-top: 40px;">
                    <a href="{$hotel_website}/profile.php" class="cta-button">Quản lý đặt phòng</a>
                </div>
            </div>
            
            <div class="email-footer">
                <div class="footer-brand">AURORA HOTEL PLAZA</div>
                <div class="footer-info">
                    {$hotel_address}<br>
                    T: {$hotel_phone} | E: {$hotel_email}
                </div>
                <div class="copyright">
                    &copy; 2026 AURORA HOTEL PLAZA. ALL RIGHTS RESERVED.
                </div>
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
    $hotel_phone = $hotel_info['phone'] ?? '(+84-251) 391 8888';
    
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    $text = <<<TEXT
AURORA HOTEL PLAZA
XÁC NHẬN ĐẶT PHÒNG

Kính gửi {$booking['guest_name']},

Cảm ơn quý khách đã chọn Aurora Hotel Plaza. Chúng tôi đã nhận được yêu cầu đặt phòng của quý khách.

MÃ ĐẶT PHÒNG: {$booking['booking_code']}
TRẠNG THÁI: Chờ xác nhận

THÔNG TIN ĐẶT PHÒNG:
- Loại phòng: {$booking['type_name']}
- Nhận phòng: {$check_in}
- Trả phòng: {$check_out}
- Tổng chi phí: {$booking['total_amount_formatted']} VND

Nhân viên chúng tôi sẽ liên hệ xác nhận trong vòng 24 giờ.

LIÊN HỆ:
Hotline: {$hotel_phone}
Trân trọng,
Aurora Hotel Plaza Team
TEXT;
    
    return $text;
}
?>
