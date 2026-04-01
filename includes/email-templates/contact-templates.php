<?php
/**
 * Contact Email Templates
 * Style: Modern Luxury / Liquid Glass / Golden Accent
 */

class ContactEmailTemplates
{
    /**
     * Template email xác nhận gửi cho khách hàng
     */
    public static function getCustomerConfirmationTemplate($data)
    {
        $name = htmlspecialchars($data['name']);
        $email = htmlspecialchars($data['email']);
        $phone = htmlspecialchars($data['phone']);
        $subject = htmlspecialchars($data['subject']);
        $message = nl2br(htmlspecialchars($data['message']));
        $submission_id = $data['submission_id'];
        $created_at = $data['created_at'];
        $user_id = isset($data['user_id']) ? $data['user_id'] : null;

        $css = file_get_contents(__DIR__ . '/email-styles.css');
        $member_status = $user_id ? '<span class="badge badge-gold">Thành viên Aurora</span>' : '';

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
                <h1>AURORA HOTEL PLAZA</h1>
                <p>CUSTOMER SUPPORT SERVICE</p>
            </div>
            <div class="accent-bar"></div>
            
            <div class="email-content">
                <div class="greeting">Xin chào {$name},</div>
                <p class="main-text">Cảm ơn bạn đã quan tâm và liên hệ với Aurora Hotel Plaza. Chúng tôi đã nhận được thông tin của bạn và sẽ phản hồi trong thời gian sớm nhất.</p>
                
                <div class="highlight-box">
                    <div class="highlight-label">Mã liên hệ của bạn</div>
                    <div class="highlight-value">{$submission_id}</div>
                    <div style="margin-top: 10px;">{$member_status}</div>
                </div>
                
                <div class="data-card">
                    <div class="card-title">Thông tin đã gửi</div>
                    <div class="data-row">
                        <span class="data-label">Họ và tên</span>
                        <span class="data-value">{$name}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Email</span>
                        <span class="data-value">{$email}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Chủ đề</span>
                        <span class="data-value">{$subject}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Thời gian</span>
                        <span class="data-value">{$created_at}</span>
                    </div>
                </div>

                <div class="data-card">
                    <div class="card-title">Nội dung tin nhắn</div>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; font-size: 14px; color: #475569; line-height: 1.8;">
                        {$message}
                    </div>
                </div>
                
                <p class="main-text" style="text-align: center; font-style: italic;">Đội ngũ hỗ trợ của chúng tôi sẽ liên hệ với bạn qua Email hoặc Số điện thoại trong vòng 24 giờ làm việc.</p>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="https://aurorahotelplaza.com" class="cta-button">Khám phá Aurora Hotel</a>
                </div>
            </div>
            
            <div class="email-footer">
                <div class="footer-brand">AURORA HOTEL PLAZA</div>
                <div class="footer-info">
                    253 Phạm Văn Thuận, Biên Hòa, Đồng Nai<br>
                    Hotline: (+84-251) 391 8888
                </div>
                <div class="copyright">&copy; 2026 AURORA HOTEL PLAZA. ALL RIGHTS RESERVED.</div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Template email thông báo gửi cho khách sạn (Admin)
     */
    public static function getHotelNotificationTemplate($data)
    {
        $name = htmlspecialchars($data['name']);
        $email = htmlspecialchars($data['email']);
        $phone = htmlspecialchars($data['phone']);
        $subject = htmlspecialchars($data['subject']);
        $message = nl2br(htmlspecialchars($data['message']));
        $submission_id = $data['submission_id'];
        $created_at = $data['created_at'];
        $user_id = isset($data['user_id']) ? $data['user_id'] : null;

        $css = file_get_contents(__DIR__ . '/email-styles.css');
        $member_info = $user_id ? "Thành viên #$user_id" : "Khách vãng lai";

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ mới - Admin Notification</title>
    <style>
        {$css}
        .email-header { background: linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%); }
        .accent-bar { background: #ef4444; }
        .highlight-value { color: #dc2626; }
        .cta-button { background: #dc2626; box-shadow: 0 10px 25px rgba(220, 38, 38, 0.2); }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>ADMIN NOTIFICATION</h1>
                <p>NEW WEBSITE INQUIRY RECEIVED</p>
            </div>
            <div class="accent-bar"></div>
            
            <div class="email-content">
                <div class="greeting">Thông báo hệ thống,</div>
                <p class="main-text">Có một yêu cầu liên hệ mới vừa được gửi từ website chính thức.</p>
                
                <div class="highlight-box">
                    <div class="highlight-label">Mã yêu cầu</div>
                    <div class="highlight-value">{$submission_id}</div>
                </div>
                
                <div class="data-card">
                    <div class="card-title">Chi tiết khách hàng</div>
                    <div class="data-row"><span class="data-label">Họ tên</span><span class="data-value">{$name}</span></div>
                    <div class="data-row"><span class="data-label">Email</span><span class="data-value">{$email}</span></div>
                    <div class="data-row"><span class="data-label">Điện thoại</span><span class="data-value">{$phone}</span></div>
                    <div class="data-row"><span class="data-label">Phân loại</span><span class="data-value">{$member_info}</span></div>
                    <div class="data-row"><span class="data-label">Chủ đề</span><span class="data-value">{$subject}</span></div>
                    <div class="data-row"><span class="data-label">Thời gian</span><span class="data-value">{$created_at}</span></div>
                </div>

                <div class="data-card">
                    <div class="card-title">Nội dung tin nhắn</div>
                    <div style="background: #fff; border-left: 4px solid #dc2626; padding: 15px; font-size: 14px; color: #1e293b; line-height: 1.6;">
                        {$message}
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="mailto:{$email}" class="cta-button">Phản hồi khách hàng ngay</a>
                </div>
            </div>
            
            <div class="email-footer">
                <div class="footer-brand">SYSTEM AUTOMATION</div>
                <div class="copyright">&copy; 2026 AURORA HOTEL PLAZA. MANAGEMENT SYSTEM.</div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
?>
