<?php
/**
 * Email Templates Helper
 * Quản lý các template email cho Aurora Hotel Plaza
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
<body>
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
                        <span class="info-label">User ID</span>
                        <span class="info-value">#{$userId}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày đăng ký</span>
                        <span class="info-value">{$currentDate}</span>
                    </div>
                </div>
                
                <div class="alert-box">
                    <div class="alert-box-title">Quyền lợi thành viên</div>
                    <ul>
                        <li>Tích điểm với mỗi lần đặt phòng (1 điểm = 10,000 VNĐ)</li>
                        <li>Ưu đãi đặc biệt dành riêng cho thành viên</li>
                        <li>Nâng hạng thành viên VIP khi đạt đủ điểm</li>
                        <li>Nhận thông báo về các chương trình khuyến mãi</li>
                        <li>Hỗ trợ ưu tiên 24/7</li>
                    </ul>
                </div>
                
                <div class="button-wrapper">
                    <a href="{$hotelUrl}/rooms.php" class="email-button">Đặt phòng ngay</a>
                </div>
                
                <p class="email-text">Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi qua email hoặc hotline: <strong>(+84-251) 391 8888</strong></p>
            </div>
            
            <div class="email-footer">
                <p class="footer-text"><strong>Aurora Hotel Plaza</strong></p>
                <p class="footer-text">KP2, Phường Tân Hiệp, Thủ Đông Nai</p>
                <p class="footer-text">(+84-251) 391 8888 | info@aurorahotelplaza.com</p>
                <p class="footer-text">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
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
    <title>Đặt lại mật khẩu</title>
    <style>{$css}</style>
</head>
<body>
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
                
                <p class="email-text" style="font-size: 13px; color: #666;">
                    Hoặc copy link sau vào trình duyệt:<br>
                    <a href="{$resetLink}" style="color: #667eea; word-break: break-all;">{$resetLink}</a>
                </p>
                
                <div class="alert-box" style="background-color: #fff3cd; border-left-color: #f59e0b;">
                    <div class="alert-box-title" style="color: #856404;">Lưu ý quan trọng</div>
                    <ul>
                        <li style="color: #856404;">Link này chỉ có hiệu lực trong <strong>1 giờ</strong>.</li>
                        <li style="color: #856404;">Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</li>
                        <li style="color: #856404;">Không chia sẻ link này với bất kỳ ai.</li>
                    </ul>
                </div>
            </div>
            
            <div class="email-footer">
                <p class="footer-text">Email này được gửi tự động, vui lòng không trả lời trực tiếp.</p>
                <p class="footer-text">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
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
            // Fallback inline CSS
            $css = 'body{font-family:Arial,sans-serif;margin:0;padding:0;background:#f5f5f5}.email-wrapper{padding:20px}.email-container{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden}.email-header{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:30px;text-align:center}.email-header h1{margin:0;font-size:24px}.email-content{padding:30px}.email-greeting{font-size:16px;margin-bottom:20px}.email-text{color:#333;line-height:1.6}.info-box{background:#f8f9fa;border-left:4px solid #667eea;padding:15px;margin:20px 0}.info-box-title{font-weight:bold;margin-bottom:10px}.button-wrapper{text-align:center;margin:25px 0}.email-button{display:inline-block;background:#667eea;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px}.alert-box{background:#fff3cd;border-left:4px solid #f59e0b;padding:15px;margin:20px 0}.alert-box-title{font-weight:bold;color:#856404}.email-footer{background:#f8f9fa;padding:20px;text-align:center;font-size:12px;color:#666}.footer-text{margin:5px 0}';
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
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Mật khẩu tạm thời</h1>
                <p>Aurora Hotel Plaza</p>
            </div>
            
            <div class="email-content">
                <p class="email-greeting">Xin chào <strong>{$userName}</strong>,</p>
                
                <p class="email-text">Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Dưới đây là mật khẩu tạm thời:</p>
                
                <div class="info-box" style="background-color: #e3f2fd; border-left-color: #2196f3; text-align: center;">
                    <div class="info-box-title" style="color: #1976d2;">Mật khẩu tạm thời của bạn</div>
                    <div style="font-size: 24px; font-weight: bold; color: #1976d2; letter-spacing: 2px; padding: 10px;">
                        {$tempPassword}
                    </div>
                </div>
                
                <p class="email-text">Vui lòng sử dụng mật khẩu này để đăng nhập và thay đổi mật khẩu mới ngay lập tức.</p>
                
                <div class="button-wrapper">
                    <a href="{$hotelUrl}/auth/login.php" class="email-button">Đăng nhập ngay</a>
                </div>
                
                <div class="alert-box" style="background-color: #fff3cd; border-left-color: #f59e0b;">
                    <div class="alert-box-title" style="color: #856404;">Lưu ý quan trọng</div>
                    <ul>
                        <li style="color: #856404;">Mật khẩu tạm thời này có hiệu lực trong <strong>30 phút</strong>.</li>
                        <li style="color: #856404;">Vui lòng thay đổi mật khẩu ngay sau khi đăng nhập.</li>
                        <li style="color: #856404;">Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</li>
                    </ul>
                </div>
                
                <p class="email-text">Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi qua hotline: <strong>(+84-251) 391 8888</strong></p>
            </div>
            
            <div class="email-footer">
                <p class="footer-text"><strong>Aurora Hotel Plaza</strong></p>
                <p class="footer-text">KP2, Phường Tân Hiệp, Thủ Đông Nai</p>
                <p class="footer-text">(+84-251) 391 8888 | info@aurorahotelplaza.com</p>
                <p class="footer-text">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
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
        $totalAmount = number_format($bookingData['total_amount']);
        $hotelUrl = self::getBaseUrl();
        
        // Load CSS
        $css = file_get_contents(__DIR__ . '/../includes/email-templates/email-styles.css');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt phòng thành công</title>
    <style>{$css}</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Đặt phòng thành công</h1>
                <p>Aurora Hotel Plaza</p>
            </div>
            
            <div class="email-content">
                <p class="email-text">Cảm ơn bạn đã đặt phòng tại <strong>Aurora Hotel Plaza</strong>!</p>
                
                <div class="info-box">
                    <div class="info-box-title">Thông tin đặt phòng</div>
                    <div class="info-row">
                        <span class="info-label">Mã đặt phòng</span>
                        <span class="info-value">{$bookingData['booking_code']}</span>
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
                
                <div class="total-amount-box">
                    <div class="total-label">Tổng chi phí</div>
                    <div class="total-amount">{$totalAmount} VNĐ</div>
                </div>
                
                <div class="button-wrapper">
                    <a href="{$hotelUrl}/booking/confirmation.php?booking_code={$bookingData['booking_code']}" class="email-button">Xem chi tiết đặt phòng</a>
                </div>
            </div>
            
            <div class="email-footer">
                <p class="footer-text">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
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
