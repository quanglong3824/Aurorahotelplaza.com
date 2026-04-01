<?php
/**
 * Contact Email Templates (Advanced Responsive & Bilingual)
 * Style: Modern Luxury / Golden Accent
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
        $member_status = $user_id ? '<span style="background-color: #d4af37; color: #ffffff; padding: 4px 12px; border-radius: 15px; font-size: 10px; font-weight: 700;">' . strtoupper(__('profile.member_since', [], 'profile')) . '</span>' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{__('contact.send_success', [], 'contact')}</title>
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
                            <h1>AURORA HOTEL PLAZA</h1>
                            <p>CUSTOMER SUPPORT SERVICE</p>
                        </td>
                    </tr>
                    <tr><td class="accent-bar"></td></tr>
                    
                    <!-- Body -->
                    <tr>
                        <td class="email-body">
                            <div class="greeting">{__('email.dear')} {$name},</div>
                            <p class="main-text">{__('email_inquiry.customer_promise')}</p>
                            
                            <!-- Highlight Box -->
                            <div class="highlight-box">
                                <div class="highlight-label">{__('contact_track.code', [], 'contact_track')}</div>
                                <div class="highlight-value">{$submission_id}</div>
                                <div style="margin-top: 10px;">{$member_status}</div>
                            </div>
                            
                            <!-- Contact Info Card -->
                            <div class="info-card">
                                <div class="card-title">{__('inquiry.your_info', [], 'inquiry')}</div>
                                <table class="data-table">
                                    <tr><td class="label">{__('contact.full_name', [], 'contact')}</td><td class="value">{$name}</td></tr>
                                    <tr><td class="label">{__('contact.email', [], 'contact')}</td><td class="value">{$email}</td></tr>
                                    <tr><td class="label">{__('contact.subject', [], 'contact')}</td><td class="value">{$subject}</td></tr>
                                    <tr><td class="label">{__('contact_track.date', [], 'contact_track')}</td><td class="value">{$created_at}</td></tr>
                                </table>
                            </div>

                            <!-- Message Content -->
                            <div class="info-card">
                                <div class="card-title">{__('contact.message', [], 'contact')}</div>
                                <div style="background-color: #f8fafc; padding: 15px; border-radius: 12px; font-size: 14px; color: #475569; line-height: 1.6;">
                                    {$message}
                                </div>
                            </div>
                            
                            <!-- CTA Button -->
                            <div class="button-container">
                                <a href="https://aurorahotelplaza.com" class="btn-cta">{__('home.explore_more', [], 'home')}</a>
                            </div>

                            <p class="main-text" style="font-size: 13px; color: #94a3b8; text-align: center; font-style: italic;">
                                * {__('email.contact_note')}
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td class="email-footer">
                            <div class="footer-brand">AURORA HOTEL PLAZA</div>
                            <div class="footer-text">
                                253 Phạm Văn Thuận, Biên Hòa, Đồng Nai<br>
                                Hotline: (+84-251) 391 8888
                            </div>
                            <div class="copyright">{__('email.copyright', ['year' => date('Y')])}</div>
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
     * Template email thông báo gửi cho khách sạn (Admin)
     * Giữ nguyên tiếng Việt cho thông báo nội bộ
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
        .email-header { background: linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%) !important; }
        .accent-bar { background: #dc2626 !important; }
        .highlight-value { color: #dc2626 !important; }
        .btn-cta { background: #dc2626 !important; box-shadow: 0 10px 20px rgba(220, 38, 38, 0.2) !important; }
        .card-title { border-bottom: 1px solid #fee2e2 !important; }
    </style>
</head>
<body>
    <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-wrapper">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container">
                    <!-- Header -->
                    <tr>
                        <td class="email-header">
                            <h1>ADMIN NOTIFICATION</h1>
                            <p>NEW WEBSITE INQUIRY RECEIVED</p>
                        </td>
                    </tr>
                    <tr><td class="accent-bar"></td></tr>
                    
                    <!-- Body -->
                    <tr>
                        <td class="email-body">
                            <div class="greeting">Thông báo hệ thống,</div>
                            <p class="main-text">Có một yêu cầu liên hệ mới vừa được gửi từ website chính thức.</p>
                            
                            <!-- Highlight Box -->
                            <div class="highlight-box" style="background-color: #fef2f2; border: 1px solid #fee2e2;">
                                <div class="highlight-label" style="color: #991b1b;">Mã yêu cầu</div>
                                <div class="highlight-value">{$submission_id}</div>
                            </div>
                            
                            <!-- Contact Info Card -->
                            <div class="info-card">
                                <div class="card-title">Chi tiết khách hàng</div>
                                <table class="data-table">
                                    <tr><td class="label">Họ tên</td><td class="value">{$name}</td></tr>
                                    <tr><td class="label">Email</td><td class="value">{$email}</td></tr>
                                    <tr><td class="label">Điện thoại</td><td class="value">{$phone}</td></tr>
                                    <tr><td class="label">Phân loại</td><td class="value">{$member_info}</td></tr>
                                    <tr><td class="label">Chủ đề</td><td class="value">{$subject}</td></tr>
                                </table>
                            </div>

                            <!-- Message Content -->
                            <div class="info-card">
                                <div class="card-title">Nội dung tin nhắn</div>
                                <div style="background-color: #ffffff; border-left: 4px solid #dc2626; padding: 15px; font-size: 14px; color: #1e293b; line-height: 1.6;">
                                    {$message}
                                </div>
                            </div>
                            
                            <!-- CTA Button -->
                            <div class="button-container">
                                <a href="mailto:{$email}" class="btn-cta">Phản hồi Email ngay</a>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td class="email-footer">
                            <div class="footer-brand">SYSTEM AUTOMATION</div>
                            <div class="copyright">&copy; 2026 AURORA HOTEL PLAZA. MANAGEMENT SYSTEM.</div>
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
}
?>
