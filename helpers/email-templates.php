<?php
/**
 * Email Templates Helper
 * Quản lý các template email cho Aurora Hotel Plaza
 */

class EmailTemplates {
    
    /**
     * Welcome email template
     */
    public static function getWelcomeTemplate($userName, $userEmail, $userId) {
        $currentDate = date('d/m/Y H:i');
        $hotelUrl = self::getBaseUrl();
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chào mừng đến với Aurora Hotel Plaza</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: bold;">
                                ✨ Aurora Hotel Plaza
                            </h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">
                                Luxury & Comfort
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333333; margin: 0 0 20px 0; font-size: 24px;">
                                Xin chào {$userName}! 👋
                            </h2>
                            
                            <p style="color: #666666; line-height: 1.6; margin: 0 0 15px 0; font-size: 16px;">
                                Chào mừng bạn đến với <strong>Aurora Hotel Plaza</strong>! Chúng tôi rất vui khi bạn đã trở thành thành viên của gia đình Aurora.
                            </p>
                            
                            <p style="color: #666666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Tài khoản của bạn đã được tạo thành công với thông tin sau:
                            </p>
                            
                            <!-- Info Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin: 0 0 25px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table width="100%" cellpadding="8" cellspacing="0">
                                            <tr>
                                                <td style="color: #666666; font-size: 14px; width: 40%;">
                                                    <strong>👤 Họ tên:</strong>
                                                </td>
                                                <td style="color: #333333; font-size: 14px;">
                                                    {$userName}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-size: 14px;">
                                                    <strong>📧 Email:</strong>
                                                </td>
                                                <td style="color: #333333; font-size: 14px;">
                                                    {$userEmail}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-size: 14px;">
                                                    <strong>🆔 User ID:</strong>
                                                </td>
                                                <td style="color: #333333; font-size: 14px;">
                                                    #{$userId}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-size: 14px;">
                                                    <strong>📅 Ngày đăng ký:</strong>
                                                </td>
                                                <td style="color: #333333; font-size: 14px;">
                                                    {$currentDate}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Benefits -->
                            <div style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border-left: 4px solid #667eea; padding: 20px; margin: 0 0 25px 0; border-radius: 5px;">
                                <h3 style="color: #667eea; margin: 0 0 15px 0; font-size: 18px;">
                                    🎁 Quyền lợi thành viên
                                </h3>
                                <ul style="color: #666666; line-height: 1.8; margin: 0; padding-left: 20px; font-size: 14px;">
                                    <li>Tích điểm với mỗi lần đặt phòng (1 điểm = 10,000 VNĐ)</li>
                                    <li>Ưu đãi đặc biệt dành riêng cho thành viên</li>
                                    <li>Nâng hạng thành viên VIP khi đạt đủ điểm</li>
                                    <li>Nhận thông báo về các chương trình khuyến mãi</li>
                                    <li>Hỗ trợ ưu tiên 24/7</li>
                                </ul>
                            </div>
                            
                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 10px 0 20px 0;">
                                        <a href="{$hotelUrl}/booking/index.php" 
                                           style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);">
                                            🏨 Đặt phòng ngay
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #666666; line-height: 1.6; margin: 0; font-size: 14px;">
                                Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi qua email hoặc hotline: <strong>1900-xxxx</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="color: #999999; margin: 0 0 10px 0; font-size: 14px;">
                                <strong>Aurora Hotel Plaza</strong>
                            </p>
                            <p style="color: #999999; margin: 0 0 10px 0; font-size: 12px;">
                                123 Đường ABC, Quận XYZ, TP. Hồ Chí Minh
                            </p>
                            <p style="color: #999999; margin: 0 0 15px 0; font-size: 12px;">
                                📞 1900-xxxx | 📧 info@aurorahotelplaza.com
                            </p>
                            <p style="color: #cccccc; margin: 0; font-size: 11px;">
                                © 2024 Aurora Hotel Plaza. All rights reserved.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
    
    /**
     * Password reset email template
     */
    public static function getPasswordResetTemplate($userName, $resetLink) {
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    
                    <tr>
                        <td style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 40px 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">
                                🔐 Đặt lại mật khẩu
                            </h1>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333333; margin: 0 0 20px 0; font-size: 20px;">
                                Xin chào {$userName},
                            </h2>
                            
                            <p style="color: #666666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Nhấn vào nút bên dưới để tạo mật khẩu mới:
                            </p>
                            
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{$resetLink}" 
                                           style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                                            Đặt lại mật khẩu
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #666666; line-height: 1.6; margin: 20px 0; font-size: 14px;">
                                Hoặc copy link sau vào trình duyệt:<br>
                                <a href="{$resetLink}" style="color: #667eea; word-break: break-all;">{$resetLink}</a>
                            </p>
                            
                            <div style="background-color: #fff3cd; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 5px;">
                                <p style="color: #856404; margin: 0; font-size: 14px;">
                                    ⚠️ Link này chỉ có hiệu lực trong <strong>1 giờ</strong>. Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="color: #999999; margin: 0; font-size: 12px;">
                                © 2024 Aurora Hotel Plaza. All rights reserved.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
    
    /**
     * Booking confirmation email template
     */
    public static function getBookingConfirmationTemplate($bookingData) {
        $checkIn = date('d/m/Y', strtotime($bookingData['check_in_date']));
        $checkOut = date('d/m/Y', strtotime($bookingData['check_out_date']));
        $totalAmount = number_format($bookingData['total_amount']);
        $hotelUrl = self::getBaseUrl();
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    
                    <tr>
                        <td style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">
                                ✅ Đặt phòng thành công!
                            </h1>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #666666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Cảm ơn bạn đã đặt phòng tại <strong>Aurora Hotel Plaza</strong>!
                            </p>
                            
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin: 0 0 25px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="color: #333333; margin: 0 0 15px 0; font-size: 18px;">
                                            📋 Thông tin đặt phòng
                                        </h3>
                                        <table width="100%" cellpadding="8" cellspacing="0">
                                            <tr>
                                                <td style="color: #666666; font-size: 14px; width: 40%;"><strong>Mã đặt phòng:</strong></td>
                                                <td style="color: #333333; font-size: 14px; font-weight: bold;">{$bookingData['booking_code']}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-size: 14px;"><strong>Loại phòng:</strong></td>
                                                <td style="color: #333333; font-size: 14px;">{$bookingData['room_type_name']}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-size: 14px;"><strong>Ngày nhận phòng:</strong></td>
                                                <td style="color: #333333; font-size: 14px;">{$checkIn}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-size: 14px;"><strong>Ngày trả phòng:</strong></td>
                                                <td style="color: #333333; font-size: 14px;">{$checkOut}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-size: 14px;"><strong>Số đêm:</strong></td>
                                                <td style="color: #333333; font-size: 14px;">{$bookingData['num_nights']} đêm</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-size: 14px;"><strong>Tổng tiền:</strong></td>
                                                <td style="color: #10b981; font-size: 18px; font-weight: bold;">{$totalAmount} VNĐ</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 10px 0;">
                                        <a href="{$hotelUrl}/booking/confirmation.php?booking_code={$bookingData['booking_code']}" 
                                           style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                                            Xem chi tiết đặt phòng
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="color: #999999; margin: 0; font-size: 12px;">
                                © 2024 Aurora Hotel Plaza. All rights reserved.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
    
    /**
     * Get base URL
     */
    private static function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
