<?php
/**
 * Contact Email Templates
 * Templates cho email liên hệ - Aurora Hotel Plaza
 * Style: Clean white background, Gold brand colors
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

        $memberBadge = $user_id ? '
            <span style="display: inline-block; background: #d1fae5; color: #065f46; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 16px;">Thành viên Aurora Hotel</span>
        ' : '';

        return self::getCustomerHTML($name, $email, $phone, $subject, $message, $submission_id, $created_at, $memberBadge);
    }

    private static function getCustomerHTML($name, $email, $phone, $subject, $message, $submission_id, $created_at, $memberBadge)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận liên hệ - Aurora Hotel Plaza</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div style="width: 100%; padding: 40px 20px; background-color: #f1f5f9;">
        <div style="max-width: 620px; margin: 0 auto; background: #ffffff; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06); border-radius: 12px; overflow: hidden;">
            
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); padding: 40px 30px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: 0.5px; text-shadow: 0 1px 3px rgba(0,0,0,0.15);">Aurora Hotel Plaza</h1>
                <p style="margin: 10px 0 0; color: rgba(255, 255, 255, 0.92); font-size: 14px; font-weight: 500;">Xác nhận liên hệ thành công</p>
            </div>
            
            <!-- Content -->
            <div style="padding: 36px 32px;">
                {$memberBadge}
                
                <p style="font-size: 16px; color: #1e293b; margin: 0 0 18px;">Xin chào <strong>{$name}</strong>,</p>
                
                <p style="font-size: 15px; color: #475569; margin: 0 0 24px; line-height: 1.7;">
                    Cảm ơn bạn đã liên hệ với chúng tôi. Tin nhắn của bạn đã được gửi thành công và chúng tôi sẽ phản hồi trong <span style="color: #b8941f; font-weight: 600;">24 giờ làm việc</span>.
                </p>
                
                <!-- Submission Code -->
                <div style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.08) 0%, rgba(184, 148, 31, 0.05) 100%); border: 2px dashed #d4af37; border-radius: 10px; padding: 24px 20px; text-align: center; margin: 24px 0;">
                    <div style="font-size: 12px; color: #78716c; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Mã liên hệ</div>
                    <div style="font-size: 22px; font-weight: 700; color: #b8941f; font-family: 'Courier New', monospace; letter-spacing: 2px;">{$submission_id}</div>
                    <p style="margin: 12px 0 0; font-size: 12px; color: #78716c;">Lưu mã này để theo dõi phản hồi</p>
                </div>

                <!-- Contact Info -->
                <div style="background-color: #f8fafc; border-left: 3px solid #d4af37; padding: 20px 22px; margin: 24px 0; border-radius: 0 8px 8px 0;">
                    <div style="font-size: 15px; font-weight: 600; color: #1e293b; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">Thông tin liên hệ</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 10px 0; font-size: 14px; color: #64748b; width: 38%;">Họ và tên</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$name}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9;">Email</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid #f1f5f9;">{$email}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9;">Số điện thoại</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid #f1f5f9;">{$phone}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9;">Chủ đề</td>
                            <td style="padding: 10px 0; font-size: 14px; text-align: right; border-top: 1px solid #f1f5f9;">
                                <span style="background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: white; padding: 5px 14px; border-radius: 16px; font-size: 12px; font-weight: 600;">{$subject}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9;">Thời gian</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid #f1f5f9;">{$created_at}</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Message Content -->
                <div style="background-color: #eff6ff; border-left: 3px solid #3b82f6; padding: 18px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
                    <div style="font-size: 14px; font-weight: 600; color: #1e40af; margin: 0 0 12px;">Nội dung tin nhắn</div>
                    <div style="background: #ffffff; padding: 18px; border-radius: 8px; border: 1px solid #dbeafe;">
                        <p style="margin: 0; font-size: 14px; color: #334155; line-height: 1.8;">{$message}</p>
                    </div>
                </div>

                <!-- Support Info -->
                <div style="background-color: #f8fafc; padding: 20px 22px; margin: 24px 0; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 14px; font-weight: 600; color: #1e293b; margin: 0 0 12px;">Cần hỗ trợ gấp?</div>
                    <p style="margin: 0 0 12px; font-size: 14px; color: #475569; line-height: 1.6;">Liên hệ trực tiếp với chúng tôi:</p>
                    <div style="font-size: 14px; color: #475569; margin: 6px 0;">
                        Điện thoại: <strong><a href="tel:+842513918888" style="color: #b8941f; text-decoration: none;">(+84-251) 391 8888</a></strong>
                    </div>
                    <div style="font-size: 14px; color: #475569; margin: 6px 0;">
                        Email: <strong><a href="mailto:info@aurorahotelplaza.com" style="color: #b8941f; text-decoration: none;">info@aurorahotelplaza.com</a></strong>
                    </div>
                </div>
                
                <!-- CTA Button -->
                <div style="text-align: center; margin: 32px 0 24px;">
                    <a href="https://aurorahotelplaza.com" style="display: inline-block; padding: 13px 30px; background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; border-radius: 8px; box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);">
                        Khám phá Aurora Hotel
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="background-color: #f8fafc; padding: 28px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
                <p style="margin: 0; font-size: 14px; font-weight: 600; color: #b8941f;">Aurora Hotel Plaza</p>
                <p style="margin: 4px 0 0; font-size: 13px; color: #94a3b8;">253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai</p>
                <p style="margin: 4px 0 0; font-size: 13px; color: #94a3b8;">(+84-251) 391 8888 | info@aurorahotelplaza.com</p>
                <p style="margin: 12px 0 0; font-size: 12px; color: #94a3b8;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
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

        $subjectConfig = [
            'Đặt phòng' => ['color' => '#065f46', 'bg' => '#d1fae5', 'border' => '#a7f3d0'],
            'Tổ chức sự kiện' => ['color' => '#5b21b6', 'bg' => '#ede9fe', 'border' => '#ddd6fe'],
            'Dịch vụ khác' => ['color' => '#1e40af', 'bg' => '#dbeafe', 'border' => '#bfdbfe'],
            'Góp ý' => ['color' => '#92400e', 'bg' => '#fef3c7', 'border' => '#fde68a'],
            'Khiếu nại' => ['color' => '#991b1b', 'bg' => '#fee2e2', 'border' => '#fecaca']
        ];
        $config = $subjectConfig[$data['subject']] ?? ['color' => '#475569', 'bg' => '#f1f5f9', 'border' => '#e2e8f0'];

        $memberInfo = $user_id 
            ? '<span style="background: #d1fae5; color: #065f46; padding: 6px 14px; border-radius: 16px; font-size: 12px; font-weight: 600;">Thành viên #' . $user_id . '</span>'
            : '<span style="background: #f1f5f9; color: #64748b; padding: 6px 14px; border-radius: 16px; font-size: 12px; font-weight: 600;">Khách vãng lai</span>';

        $priorityBadge = ($data['subject'] === 'Khiếu nại') 
            ? '<span style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 5px 14px; border-radius: 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-left: 10px;">Ưu tiên cao</span>' 
            : '';

        return self::getAdminHTML($name, $email, $phone, $subject, $message, $submission_id, $created_at, $config, $memberInfo, $priorityBadge);
    }

    private static function getAdminHTML($name, $email, $phone, $subject, $message, $submission_id, $created_at, $config, $memberInfo, $priorityBadge)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ mới - {$subject} - Aurora Hotel Plaza</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div style="width: 100%; padding: 40px 20px; background-color: #f1f5f9;">
        <div style="max-width: 620px; margin: 0 auto; background: #ffffff; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06); border-radius: 12px; overflow: hidden;">
            
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); padding: 40px 30px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: 0.5px; text-shadow: 0 1px 3px rgba(0,0,0,0.15);">Liên hệ mới từ Website</h1>
                <p style="margin: 10px 0 0; color: rgba(255, 255, 255, 0.92); font-size: 14px; font-weight: 500;">Aurora Hotel Plaza</p>
            </div>
            
            <!-- Content -->
            <div style="padding: 36px 32px;">
                <!-- Subject & Meta -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
                    <span style="background: {$config['bg']}; color: {$config['color']}; padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid {$config['border']};">{$subject}</span>
                    {$priorityBadge}
                    <span style="color: #64748b; font-size: 13px; margin-left: auto; font-weight: 500;">Mã: <span style="color: #1e293b; font-weight: 600;">{$submission_id}</span></span>
                </div>
                
                <!-- Customer Info -->
                <div style="background-color: #f8fafc; border-left: 3px solid #d4af37; padding: 20px 22px; margin-bottom: 24px; border-radius: 0 8px 8px 0;">
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 18px; gap: 10px;">
                        <h3 style="margin: 0; font-size: 15px; color: #1e293b; font-weight: 600;">Thông tin khách hàng</h3>
                        {$memberInfo}
                    </div>
                    
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; width: 35%;"><span style="font-size: 14px; color: #64748b;">Họ và tên</span></td>
                            <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;"><span style="font-size: 15px; color: #1e293b; font-weight: 600;">{$name}</span></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;"><span style="font-size: 14px; color: #64748b;">Email</span></td>
                            <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;"><a href="mailto:{$email}" style="font-size: 14px; color: #b8941f; text-decoration: none; font-weight: 600;">{$email}</a></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;"><span style="font-size: 14px; color: #64748b;">Số điện thoại</span></td>
                            <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;"><a href="tel:{$phone}" style="font-size: 14px; color: #b8941f; text-decoration: none; font-weight: 600;">{$phone}</a></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px 0;"><span style="font-size: 14px; color: #64748b;">Thời gian</span></td>
                            <td style="padding: 12px 0;"><span style="font-size: 14px; color: #1e293b; font-weight: 600;">{$created_at}</span></td>
                        </tr>
                    </table>
                </div>

                <!-- Message Content -->
                <div style="background-color: #eff6ff; border-left: 3px solid #3b82f6; padding: 18px 20px; margin-bottom: 24px; border-radius: 0 8px 8px 0;">
                    <h3 style="margin: 0 0 14px; font-size: 14px; color: #1e40af; font-weight: 600;">Nội dung tin nhắn</h3>
                    <div style="background: #ffffff; padding: 18px; border-radius: 8px; border: 1px solid #dbeafe;">
                        <p style="margin: 0; font-size: 14px; color: #334155; line-height: 1.8;">{$message}</p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin: 28px 0;">
                    <a href="mailto:{$email}?subject=Re: {$subject} - Aurora Hotel Plaza" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; border-radius: 8px; box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);">Phản hồi Email</a>
                    <a href="tel:{$phone}" style="display: inline-block; padding: 12px 24px; background: #1e293b; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; border-radius: 8px; box-shadow: 0 4px 12px rgba(30, 41, 59, 0.2);">Gọi điện ngay</a>
                </div>
                
                <!-- Reminder -->
                <div style="background-color: #d1fae5; border-left: 3px solid #059669; padding: 16px 20px; margin-top: 24px; border-radius: 0 8px 8px 0;">
                    <p style="margin: 0; font-size: 14px; color: #065f46; line-height: 1.6;">
                        <strong>Nhắc nhở:</strong> Vui lòng phản hồi khách hàng trong vòng <strong>24 giờ</strong> để đảm bảo chất lượng dịch vụ.
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="background-color: #f8fafc; padding: 28px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
                <p style="margin: 0 0 5px; font-size: 12px; color: #64748b;">Email tự động từ hệ thống</p>
                <p style="margin: 0; font-size: 14px; font-weight: 600; color: #b8941f;">Aurora Hotel Plaza</p>
                <p style="margin: 4px 0 0; font-size: 12px; color: #94a3b8;">© 2025 Aurora Hotel Plaza</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
