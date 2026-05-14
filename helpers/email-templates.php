<?php
/**
 * Email Templates Helper
 * Quản lý các template email cho Aurora Hotel Plaza
 * Style: Clean white background, Gold brand colors
 */

// Load environment helper for URL functions
require_once __DIR__ . '/../config/environment.php';

class EmailTemplates {
    
    /**
     * Welcome email template
     */
    public static function getWelcomeTemplate($userName, $userEmail, $userId) {
        $currentDate = date('d/m/Y H:i');
        $hotelUrl = self::getBaseUrl();
        
        // Load CSS
        $css = file_get_contents(__DIR__ . '/../includes/email-templates/email-styles.css');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chào mừng đến với Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Aurora Hotel Plaza</h1>
                <p>Chào mừng bạn đến với gia đình Aurora</p>
            </div>
            
            <div class="email-content">
                <p class="email-greeting">Xin chào <strong>{$userName}</strong>!</p>
                
                <p class="email-text">Chúng tôi rất vui khi bạn đã trở thành thành viên của <strong>Aurora Hotel Plaza</strong>.</p>
                
                <p class="email-text">Tài khoản của bạn đã được tạo thành công với thông tin sau:</p>
                
                <div class="info-box">
                    <div class="info-box-title">Thông tin tài khoản</div>
                    <div class="info-row">
                        <span class="info-label">Họ tên</span>
                        <span class="info-value">{$userName}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{$userEmail}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Mã thành viên</span>
                        <span class="info-value">#{$userId}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày đăng ký</span>
                        <span class="info-value">{$currentDate}</span>
                    </div>
                </div>
                
                <div class="alert-box" style="background-color: #d1fae5; border-left-color: #059669;">
                    <div class="alert-box-title" style="color: #065f46;">Quyền lợi thành viên</div>
                    <ul>
                        <li style="color: #065f46;">Tích điểm với mỗi lần đặt phòng (1 điểm = 10,000 VND)</li>
                        <li style="color: #065f46;">Ưu đãi đặc biệt dành riêng cho thành viên</li>
                        <li style="color: #065f46;">Nâng hạng thành viên VIP khi đạt đủ điểm</li>
                        <li style="color: #065f46;">Nhận thông báo về các chương trình khuyến mãi</li>
                        <li style="color: #065f46;">Hỗ trợ ưu tiên 24/7</li>
                    </ul>
                </div>
                
                <div class="button-wrapper">
                    <a href="{$hotelUrl}/rooms.php" class="email-button">Đặt phòng ngay</a>
                </div>
                
                <p class="email-text">Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi qua email hoặc hotline: <strong>(+84-251) 391 8888</strong></p>
            </div>
            
            <div class="email-footer">
                <p class="footer-text" style="font-weight: 600; color: #b8941f; font-size: 14px;">Aurora Hotel Plaza</p>
                <p class="footer-text">253 Phạm Văn Thuận, KP2, Tam Hiệp, TP.Đồng Nai</p>
                <p class="footer-text">(+84-251) 391 8888 | info@aurorahotelplaza.com</p>
                <p class="footer-text" style="margin-top: 12px; color: #94a3b8;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Password reset email template
     */
    public static function getPasswordResetTemplate($userName, $resetLink) {
        // Load CSS
        $css = file_get_contents(__DIR__ . '/../includes/email-templates/email-styles.css');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Đặt lại mật khẩu</h1>
                <p>Aurora Hotel Plaza</p>
            </div>
            
            <div class="email-content">
                <p class="email-greeting">Xin chào <strong>{$userName}</strong>,</p>
                
                <p class="email-text">Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Nhấn vào nút bên dưới để tạo mật khẩu mới:</p>
                
                <div class="button-wrapper">
                    <a href="{$resetLink}" class="email-button">Đặt lại mật khẩu</a>
                </div>
                
                <p class="email-text" style="font-size: 13px; color: #64748b;">
                    Hoặc copy link sau vào trình duyệt:<br>
                    <a href="{$resetLink}" style="color: #b8941f; word-break: break-all;">{$resetLink}</a>
                </p>
                
                <div class="alert-box" style="background-color: #fef3c7; border-left-color: #d97706;">
                    <div class="alert-box-title" style="color: #92400e;">Lưu ý quan trọng</div>
                    <ul>
                        <li style="color: #92400e;">Link này chỉ có hiệu lực trong <strong>1 giờ</strong>.</li>
                        <li style="color: #92400e;">Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</li>
                        <li style="color: #92400e;">Không chia sẻ link này với bất kỳ ai.</li>
                    </ul>
                </div>
            </div>
            
            <div class="email-footer">
                <p class="footer-text" style="color: #64748b; font-size: 12px; margin-bottom: 8px;">Email này được gửi tự động, vui lòng không trả lời trực tiếp.</p>
                <p class="footer-text" style="font-weight: 600; color: #b8941f; font-size: 14px;">Aurora Hotel Plaza</p>
                <p class="footer-text" style="margin-top: 12px; color: #94a3b8;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Temporary password email template
     */
    public static function getTemporaryPasswordTemplate($userName, $tempPassword) {
        $currentDate = date('d/m/Y H:i');
        $hotelUrl = self::getBaseUrl();
        
        // Load CSS with fallback
        $cssFile = __DIR__ . '/../includes/email-templates/email-styles.css';
        $css = '';
        if (file_exists($cssFile)) {
            $css = @file_get_contents($cssFile);
        }
        if (empty($css)) {
            $css = 'body{font-family:Arial,sans-serif;margin:0;padding:0;background:#f1f5f9}.email-wrapper{padding:20px}.email-container{max-width:620px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06)}.email-header{background:linear-gradient(135deg,#d4af37,#b8941f);color:#fff;padding:40px 30px;text-align:center}.email-header h1{margin:0;font-size:26px;font-weight:700}.email-content{padding:36px 32px}.email-greeting{font-size:16px;margin-bottom:18px;color:#1e293b}.email-text{color:#475569;line-height:1.7;font-size:15px}.info-box{background:#f8fafc;border-left:3px solid #d4af37;padding:20px;margin:24px 0;border-radius:0 8px 8px 0}.info-box-title{font-weight:600;margin-bottom:14px;color:#1e293b}.button-wrapper{text-align:center;margin:28px 0}.email-button{display:inline-block;background:linear-gradient(135deg,#d4af37,#b8941f);color:#fff;padding:13px 30px;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;box-shadow:0 4px 12px rgba(212,175,55,0.3)}.alert-box{background:#fef3c7;border-left:3px solid #d97706;padding:18px 20px;margin:24px 0;border-radius:0 8px 8px 0}.alert-box-title{font-weight:600;color:#92400e}.email-footer{background:#f8fafc;padding:28px 32px;text-align:center;border-top:1px solid #e2e8f0}.footer-text{margin:4px 0;color:#94a3b8;font-size:13px}';
        }
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mật khẩu tạm thời - Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Mật khẩu tạm thời</h1>
                <p>Aurora Hotel Plaza</p>
            </div>
            
            <div class="email-content">
                <p class="email-greeting">Xin chào <strong>{$userName}</strong>,</p>
                
                <p class="email-text">Dưới đây là mật khẩu tạm thời cho tài khoản của bạn:</p>
                
                <div class="info-box" style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.08) 0%, rgba(184, 148, 31, 0.05) 100%); border: 2px dashed #d4af37; text-align: center; border-radius: 10px;">
                    <div class="info-box-title" style="color: #78716c; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Mật khẩu tạm thời</div>
                    <div style="font-size: 24px; font-weight: 700; color: #b8941f; letter-spacing: 3px; padding: 8px 0; font-family: 'Courier New', monospace;">
                        {$tempPassword}
                    </div>
                </div>
                
                <p class="email-text">Vui lòng sử dụng mật khẩu này để đăng nhập và thay đổi mật khẩu mới ngay lập tức.</p>
                
                <div class="button-wrapper">
                    <a href="{$hotelUrl}/auth/login.php" class="email-button">Đăng nhập ngay</a>
                </div>
                
                <div class="alert-box" style="background-color: #fef3c7; border-left-color: #d97706;">
                    <div class="alert-box-title" style="color: #92400e;">Lưu ý quan trọng</div>
                    <ul>
                        <li style="color: #92400e;">Mật khẩu tạm thời này có hiệu lực trong <strong>30 phút</strong>.</li>
                        <li style="color: #92400e;">Vui lòng thay đổi mật khẩu ngay sau khi đăng nhập.</li>
                        <li style="color: #92400e;">Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</li>
                    </ul>
                </div>
                
                <p class="email-text">Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi qua hotline: <strong>(+84-251) 391 8888</strong></p>
            </div>
            
            <div class="email-footer">
                <p class="footer-text" style="color: #64748b; font-size: 12px; margin-bottom: 8px;">Email này được gửi tự động, vui lòng không trả lời trực tiếp.</p>
                <p class="footer-text" style="font-weight: 600; color: #b8941f; font-size: 14px;">Aurora Hotel Plaza</p>
                <p class="footer-text">(+84-251) 391 8888 | info@aurorahotelplaza.com</p>
                <p class="footer-text" style="margin-top: 12px; color: #94a3b8;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Booking confirmation email template (Simple version - for backward compatibility)
     * For detailed template, use getBookingConfirmationEmailHTML in includes/email-templates/booking-confirmation.php
     */
    public static function getBookingConfirmationTemplate($bookingData) {
        $checkIn = date('d/m/Y', strtotime($bookingData['check_in_date']));
        $checkOut = date('d/m/Y', strtotime($bookingData['check_out_date']));
        $hotelUrl = self::getBaseUrl();

        $full_code = $bookingData['booking_code'];
        $prefix = substr($full_code, 0, -6);
        $suffix = substr($full_code, -6);
        $highlighted_code = htmlspecialchars($prefix) . '<span style="background-color: #d4af37; color: #000; padding: 2px 6px; border-radius: 4px; font-weight: 700;">' . htmlspecialchars($suffix) . '</span>';
        
        $css = file_get_contents(__DIR__ . '/../includes/email-templates/email-styles.css');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đã gửi yêu cầu đặt phòng - Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Đã gửi yêu cầu đặt phòng</h1>
                <p>Aurora Hotel Plaza</p>
            </div>
            
            <div class="email-content">
                <p class="email-text">Yêu cầu đặt phòng của bạn đã được <strong style="color: #059669;">gửi thành công</strong> tới <strong>Aurora Hotel Plaza</strong>. Đội ngũ lễ tân sẽ xác nhận trong thời gian sớm nhất.</p>
                
                <div class="info-box">
                    <div class="info-box-title">Thông tin đặt phòng</div>
                    <div class="info-row">
                        <span class="info-label">Mã đặt phòng</span>
                        <span class="info-value">
                            {$highlighted_code}
                            <br>
                            <span style="font-size: 11px; color: #78716c; font-style: italic;">
                                * Mã rút gọn: <strong>{$suffix}</strong>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Loại phòng</span>
                        <span class="info-value">{$bookingData['room_type_name']}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày nhận phòng</span>
                        <span class="info-value">{$checkIn}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày trả phòng</span>
                        <span class="info-value">{$checkOut}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số đêm</span>
                        <span class="info-value">{$bookingData['num_nights']} đêm</span>
                    </div>
                </div>
                
                <div class="alert-box" style="background-color: #d1fae5; border-left-color: #059669;">
                    <div class="alert-box-title" style="color: #065f46;">Trạng thái: Đang chờ xác nhận</div>
                    <p style="font-size: 14px; color: #475569; margin: 8px 0;">Nhân viên khách sạn sẽ liên hệ xác nhận qua số điện thoại hoặc email của bạn. Chi tiết giá và thanh toán sẽ được thông báo sau khi xác nhận.</p>
                </div>
                
                <div class="button-wrapper">
                    <a href="{$hotelUrl}/booking/confirmation.php?booking_code={$bookingData['booking_code']}" class="email-button">Xem chi tiết đặt phòng</a>
                </div>
            </div>
            
            <div class="email-footer">
                <p class="footer-text" style="font-weight: 600; color: #b8941f; font-size: 14px;">Aurora Hotel Plaza</p>
                <p class="footer-text" style="margin-top: 12px; color: #94a3b8;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Get base URL - Sử dụng hàm từ environment.php
     */
    private static function getBaseUrl() {
        return getBaseUrl();
    }
}
