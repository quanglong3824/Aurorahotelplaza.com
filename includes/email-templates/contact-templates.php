<?php
/**
 * Contact Email Templates
 * Templates cho email liên hệ - Aurora Hotel Plaza
 */

class ContactEmailTemplates {
    
    /**
     * Template email xác nhận gửi cho khách hàng
     */
    public static function getCustomerConfirmationTemplate($data) {
        $css = file_get_contents(__DIR__ . '/email-styles.css');
        
        $name = htmlspecialchars($data['name']);
        $email = htmlspecialchars($data['email']);
        $phone = htmlspecialchars($data['phone']);
        $subject = htmlspecialchars($data['subject']);
        $message = nl2br(htmlspecialchars($data['message']));
        $submission_id = $data['submission_id'];
        $created_at = $data['created_at'];
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận liên hệ - Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Aurora Hotel Plaza</h1>
                <p>Cảm ơn bạn đã liên hệ với chúng tôi</p>
            </div>
            
            <div class="email-content">
                <p class="email-greeting">Xin chào <strong>{$name}</strong>!</p>
                
                <p class="email-text">Chúng tôi đã nhận được tin nhắn của bạn và sẽ phản hồi trong thời gian sớm nhất (thường trong vòng 24 giờ làm việc).</p>
                
                <div class="booking-code-box">
                    <div class="booking-code-label">Mã liên hệ</div>
                    <div class="booking-code">CT{$submission_id}</div>
                </div>
                
                <div class="info-box">
                    <div class="info-box-title">Thông tin liên hệ của bạn</div>
                    <div class="info-row">
                        <span class="info-label">Họ và tên</span>
                        <span class="info-value">{$name}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{$email}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số điện thoại</span>
                        <span class="info-value">{$phone}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Chủ đề</span>
                        <span class="info-value">{$subject}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Thời gian gửi</span>
                        <span class="info-value">{$created_at}</span>
                    </div>
                </div>
                
                <div class="alert-box">
                    <div class="alert-box-title">Nội dung tin nhắn</div>
                    <p style="margin: 0; font-size: 14px; color: #555; line-height: 1.7;">{$message}</p>
                </div>
                
                <p class="email-text">Nếu bạn cần hỗ trợ gấp, vui lòng liên hệ trực tiếp qua:</p>
                
                <div class="contact-info">
                    <div class="contact-item"><strong>Hotline:</strong> (+84-251) 391 8888</div>
                    <div class="contact-item"><strong>Email:</strong> info@aurorahotelplaza.com</div>
                    <div class="contact-item"><strong>Địa chỉ:</strong> Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai</div>
                </div>
            </div>
            
            <div class="email-footer">
                <p class="footer-text"><strong>Aurora Hotel Plaza</strong></p>
                <p class="footer-text">Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai</p>
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
     * Template email thông báo gửi cho khách sạn
     */
    public static function getHotelNotificationTemplate($data) {
        $css = file_get_contents(__DIR__ . '/email-styles.css');
        
        $name = htmlspecialchars($data['name']);
        $email = htmlspecialchars($data['email']);
        $phone = htmlspecialchars($data['phone']);
        $subject = htmlspecialchars($data['subject']);
        $message = nl2br(htmlspecialchars($data['message']));
        $submission_id = $data['submission_id'];
        $created_at = $data['created_at'];
        
        // Xác định màu badge theo chủ đề
        $subjectColors = [
            'Đặt phòng' => '#10b981',
            'Tổ chức sự kiện' => '#8b5cf6',
            'Dịch vụ khác' => '#3b82f6',
            'Góp ý' => '#f59e0b',
            'Khiếu nại' => '#ef4444'
        ];
        $badgeColor = $subjectColors[$data['subject']] ?? '#6b7280';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ mới #{$submission_id}</title>
    <style>{$css}</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);">
                <h1>📩 Liên hệ mới</h1>
                <p>Có khách hàng vừa gửi tin nhắn</p>
            </div>
            
            <div class="email-content">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <span style="background-color: {$badgeColor}; color: white; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;">{$subject}</span>
                    <span style="color: #666; font-size: 14px;">#{$submission_id}</span>
                </div>
                
                <div class="info-box" style="border-left-color: #dc2626;">
                    <div class="info-box-title">👤 Thông tin khách hàng</div>
                    <div class="info-row">
                        <span class="info-label">Họ và tên</span>
                        <span class="info-value" style="color: #dc2626; font-weight: 700;">{$name}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><a href="mailto:{$email}" style="color: #667eea; text-decoration: none;">{$email}</a></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số điện thoại</span>
                        <span class="info-value"><a href="tel:{$phone}" style="color: #667eea; text-decoration: none;">{$phone}</a></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Thời gian gửi</span>
                        <span class="info-value">{$created_at}</span>
                    </div>
                </div>
                
                <div class="alert-box" style="background-color: #fef3c7; border-left-color: #f59e0b;">
                    <div class="alert-box-title" style="color: #92400e;">💬 Nội dung tin nhắn</div>
                    <p style="margin: 0; font-size: 15px; color: #78350f; line-height: 1.8; white-space: pre-wrap;">{$message}</p>
                </div>
                
                <div class="button-wrapper">
                    <a href="mailto:{$email}?subject=Re: {$subject} - Aurora Hotel Plaza" class="email-button" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);">
                        📧 Phản hồi ngay
                    </a>
                </div>
                
                <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 15px; margin-top: 20px;">
                    <p style="margin: 0; font-size: 13px; color: #166534;">
                        <strong>💡 Lưu ý:</strong> Vui lòng phản hồi khách hàng trong vòng 24 giờ để đảm bảo chất lượng dịch vụ.
                    </p>
                </div>
            </div>
            
            <div class="email-footer">
                <p class="footer-text">Email này được gửi tự động từ hệ thống Aurora Hotel Plaza</p>
                <p class="footer-text">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
