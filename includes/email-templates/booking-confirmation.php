<?php
/**
 * Email Template: Booking Confirmation (Enhanced Responsive)
 * Style: Modern Luxury / Golden Accent
 */

function getBookingConfirmationEmailHTML($booking, $hotel_info = []) {
    $hotel_name = $hotel_info['name'] ?? 'AURORA HOTEL PLAZA';
    $hotel_address = $hotel_info['address'] ?? '253 Phạm Văn Thuận, Biên Hòa, Đồng Nai';
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
    <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-wrapper">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container">
                    <!-- Header -->
                    <tr>
                        <td class="email-header">
                            <h1>{$hotel_name}</h1>
                            <p>LUXURY HOSPITALITY EXPERIENCE</p>
                        </td>
                    </tr>
                    <tr><td class="accent-bar"></td></tr>
                    
                    <!-- Body -->
                    <tr>
                        <td class="email-body">
                            <div class="greeting">Kính gửi {$booking['guest_name']},</div>
                            <p class="main-text">Cảm ơn quý khách đã tin tưởng lựa chọn Aurora Hotel Plaza. Chúng tôi rất vui mừng thông báo rằng yêu cầu đặt phòng của quý khách đã được nhận thành công.</p>
                            
                            <!-- Highlight Box -->
                            <div class="highlight-box">
                                <div class="highlight-label">Mã đặt phòng của bạn</div>
                                <div class="highlight-value">{$booking['booking_code']}</div>
                                <div style="margin-top: 15px;">
                                    <span style="background-color: #d4af37; color: #ffffff; padding: 5px 15px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">Chờ xác nhận</span>
                                </div>
                            </div>
                            
                            <!-- Booking Details Card -->
                            <div class="info-card">
                                <div class="card-title">Chi tiết đặt phòng</div>
                                <table class="data-table">
                                    <tr>
                                        <td class="label">Loại phòng</td>
                                        <td class="value">{$booking['type_name']}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Nhận phòng</td>
                                        <td class="value">{$check_in} (14:00)</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Trả phòng</td>
                                        <td class="value">{$check_out} (12:00)</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Số đêm</td>
                                        <td class="value">{$booking['total_nights']} đêm</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Payment Card -->
                            <div class="info-card">
                                <div class="card-title">Tổng chi phí dự kiến</div>
                                <div style="text-align: center; padding: 10px 0;">
                                    <div style="font-size: 26px; font-weight: 800; color: #d4af37;">{$booking['total_amount_formatted']} VND</div>
                                    <p style="font-size: 11px; color: #94a3b8; margin-top: 5px;">(Đã bao gồm thuế và phí dịch vụ)</p>
                                </div>
                            </div>
                            
                            <!-- CTA Button -->
                            <div class="button-container">
                                <a href="{$hotel_website}/profile.php" class="btn-cta">Quản lý đặt phòng</a>
                            </div>

                            <p class="main-text" style="font-size: 13px; color: #94a3b8; text-align: center;">
                                * Nhân viên của chúng tôi sẽ liên hệ xác nhận trong vòng 24 giờ.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td class="email-footer">
                            <div class="footer-brand">AURORA HOTEL PLAZA</div>
                            <div class="footer-text">
                                {$hotel_address}<br>
                                Hotline: {$hotel_phone} | Email: {$hotel_email}
                            </div>
                            <div class="copyright">&copy; 2026 AURORA HOTEL PLAZA. ALL RIGHTS RESERVED.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    
    return $html;
}

function getBookingConfirmationEmailText($booking, $hotel_info = []) {
    // Giữ nguyên bản text không thay đổi
    $hotel_name = $hotel_info['name'] ?? 'Aurora Hotel Plaza';
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    return "AURORA HOTEL PLAZA\nXác nhận đặt phòng\n\nKính gửi {$booking['guest_name']},\nMã đặt phòng: {$booking['booking_code']}\nLoại phòng: {$booking['type_name']}\nNhận phòng: {$check_in}\nTrả phòng: {$check_out}\nTổng cộng: {$booking['total_amount_formatted']} VND\n\nChúng tôi sẽ liên hệ xác nhận sớm nhất.";
}
?>
