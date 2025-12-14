<?php
/**
 * Contact Email Templates
 * Templates cho email liên hệ - Aurora Hotel Plaza
 * Style: Liquid Glass + Modern UI
 */

class ContactEmailTemplates
{

    /**
     * Template email xác nhận gửi cho khách hàng
     * UI hiện đại với liquid glass style
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

        // Badge cho user đã đăng nhập
        $memberBadge = $user_id ? '
            <div style="background: rgba(16, 185, 129, 0.15); backdrop-filter: blur(10px); color: #059669; padding: 10px 20px; border-radius: 30px; font-size: 13px; font-weight: 600; display: inline-block; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.3);">
                Thành viên Aurora Hotel
            </div>
        ' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận liên hệ - Aurora Hotel Plaza</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); min-height: 100vh;">
    <div style="width: 100%; padding: 40px 20px;">
        <div style="max-width: 600px; margin: 0 auto; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.5); border-radius: 24px; overflow: hidden;">
            
            <!-- Header với gradient gold -->
            <div style="background: linear-gradient(135deg, #d4af37 0%, #f5d77a 50%, #b8941f 100%); padding: 50px 30px; text-align: center; position: relative;">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.1); backdrop-filter: blur(5px);"></div>
                <div style="position: relative; z-index: 1;">
                    <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">Aurora Hotel Plaza</h1>
                    <p style="margin: 12px 0 0; color: rgba(255, 255, 255, 0.95); font-size: 15px; font-weight: 500;">Khách sạn 4 sao tại Đồng Nai</p>
                </div>
            </div>
            
            <!-- Success Icon - Liquid Glass -->
            <div style="text-align: center; margin-top: -40px; position: relative; z-index: 10;">
                <div style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.9); backdrop-filter: blur(10px); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4), 0 0 0 4px rgba(255,255,255,0.9); border: 2px solid rgba(255,255,255,0.5);">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            
            <div style="padding: 35px 40px 45px;">
                <!-- Success Message -->
                <div style="text-align: center; margin-bottom: 35px;">
                    <h2 style="margin: 0 0 12px; color: #10b981; font-size: 24px; font-weight: 700;">Gửi liên hệ thành công</h2>
                    <p style="margin: 0; color: #64748b; font-size: 15px; line-height: 1.6;">Cảm ơn bạn đã liên hệ với Aurora Hotel Plaza</p>
                </div>
                
                {$memberBadge}
                
                <p style="font-size: 15px; color: #475569; margin: 0 0 30px; line-height: 1.8;">
                    Xin chào <strong style="color: #1e293b;">{$name}</strong>,<br>
                    Chúng tôi đã nhận được tin nhắn của bạn và sẽ phản hồi trong thời gian sớm nhất <span style="color: #d4af37; font-weight: 600;">(thường trong vòng 24 giờ làm việc)</span>.
                </p>
                
                <!-- Mã liên hệ - Liquid Glass -->
                <div style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.1) 0%, rgba(212, 175, 55, 0.05) 100%); backdrop-filter: blur(10px); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 16px; padding: 30px; text-align: center; margin: 30px 0;">
                    <div style="font-size: 12px; color: #92400e; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;">Mã liên hệ của bạn</div>
                    <div style="font-size: 32px; font-weight: 800; color: #b8941f; font-family: 'Courier New', monospace; letter-spacing: 4px;">{$submission_id}</div>
                    <p style="margin: 12px 0 0; font-size: 13px; color: #78350f;">Vui lòng lưu mã này để theo dõi phản hồi</p>
                </div>
                
                <!-- Thông tin liên hệ - Liquid Glass Card -->
                <div style="background: rgba(248, 250, 252, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 16px; padding: 25px 30px; margin: 30px 0;">
                    <div style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 20px;">Thông tin liên hệ của bạn</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 14px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8); font-size: 14px; color: #64748b; width: 40%;">Họ và tên</td>
                            <td style="padding: 14px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8); font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$name}</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8); font-size: 14px; color: #64748b;">Email</td>
                            <td style="padding: 14px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8); font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$email}</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8); font-size: 14px; color: #64748b;">Số điện thoại</td>
                            <td style="padding: 14px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8); font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$phone}</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8); font-size: 14px; color: #64748b;">Chủ đề</td>
                            <td style="padding: 14px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8); font-size: 14px; text-align: right;">
                                <span style="background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: white; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">{$subject}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b;">Thời gian gửi</td>
                            <td style="padding: 14px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$created_at}</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Nội dung tin nhắn -->
                <div style="background: rgba(239, 246, 255, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(191, 219, 254, 0.8); border-radius: 16px; padding: 25px 30px; margin: 30px 0;">
                    <div style="font-size: 16px; font-weight: 700; color: #1e40af; margin: 0 0 18px;">Nội dung tin nhắn</div>
                    <div style="background: rgba(255,255,255,0.9); padding: 20px; border-radius: 12px; border: 1px solid rgba(191, 219, 254, 0.5);">
                        <p style="margin: 0; font-size: 14px; color: #334155; line-height: 1.9; white-space: pre-wrap;">{$message}</p>
                    </div>
                </div>
                
                <!-- Liên hệ khẩn cấp -->
                <div style="background: rgba(254, 242, 242, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(254, 202, 202, 0.8); border-radius: 16px; padding: 25px; margin: 30px 0;">
                    <div style="font-size: 15px; font-weight: 700; color: #991b1b; margin: 0 0 15px;">Cần hỗ trợ gấp?</div>
                    <p style="margin: 0 0 18px; font-size: 14px; color: #7f1d1d; line-height: 1.6;">Nếu bạn cần hỗ trợ ngay, vui lòng liên hệ trực tiếp:</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                        <a href="tel:+842513918888" style="display: inline-block; background: rgba(255,255,255,0.9); color: #dc2626; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 600; border: 1px solid rgba(254, 202, 202, 0.8);">
                            (+84-251) 391 8888
                        </a>
                        <a href="mailto:info@aurorahotelplaza.com" style="display: inline-block; background: rgba(255,255,255,0.9); color: #dc2626; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 600; border: 1px solid rgba(254, 202, 202, 0.8);">
                            info@aurorahotelplaza.com
                        </a>
                    </div>
                </div>
                
                <!-- CTA Button -->
                <div style="text-align: center; margin: 40px 0 20px;">
                    <a href="https://aurorahotelplaza.com" style="display: inline-block; padding: 18px 45px; background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 30px; box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);">
                        Khám phá Aurora Hotel Plaza
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 40px 30px; text-align: center;">
                <p style="margin: 0 0 8px; font-size: 18px; color: #d4af37; font-weight: 700;">Aurora Hotel Plaza</p>
                <p style="margin: 0 0 20px; font-size: 13px; color: #94a3b8; line-height: 1.6;">Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai</p>
                <div style="margin: 20px 0;">
                    <a href="tel:+842513918888" style="color: #d4af37; text-decoration: none; font-size: 14px; margin: 0 15px;">(+84-251) 391 8888</a>
                    <span style="color: #475569;">|</span>
                    <a href="mailto:info@aurorahotelplaza.com" style="color: #d4af37; text-decoration: none; font-size: 14px; margin: 0 15px;">info@aurorahotelplaza.com</a>
                </div>
                <p style="margin: 20px 0 0; font-size: 12px; color: #64748b;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }


    /**
     * Template email thông báo gửi cho khách sạn (Admin)
     * UI hiện đại với liquid glass style
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

        // Xác định màu badge theo chủ đề
        $subjectConfig = [
            'Đặt phòng' => ['color' => '#059669', 'bg' => 'rgba(16, 185, 129, 0.15)', 'border' => 'rgba(16, 185, 129, 0.3)'],
            'Tổ chức sự kiện' => ['color' => '#7c3aed', 'bg' => 'rgba(139, 92, 246, 0.15)', 'border' => 'rgba(139, 92, 246, 0.3)'],
            'Dịch vụ khác' => ['color' => '#2563eb', 'bg' => 'rgba(59, 130, 246, 0.15)', 'border' => 'rgba(59, 130, 246, 0.3)'],
            'Góp ý' => ['color' => '#d97706', 'bg' => 'rgba(245, 158, 11, 0.15)', 'border' => 'rgba(245, 158, 11, 0.3)'],
            'Khiếu nại' => ['color' => '#dc2626', 'bg' => 'rgba(239, 68, 68, 0.15)', 'border' => 'rgba(239, 68, 68, 0.3)']
        ];
        $config = $subjectConfig[$data['subject']] ?? ['color' => '#6b7280', 'bg' => 'rgba(107, 114, 128, 0.15)', 'border' => 'rgba(107, 114, 128, 0.3)'];

        // Badge cho user đã đăng nhập
        $memberInfo = $user_id ? '
            <span style="background: rgba(16, 185, 129, 0.15); backdrop-filter: blur(10px); color: #059669; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid rgba(16, 185, 129, 0.3);">
                Thành viên (ID: ' . $user_id . ')
            </span>
        ' : '
            <span style="background: rgba(107, 114, 128, 0.15); color: #6b7280; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid rgba(107, 114, 128, 0.3);">
                Khách vãng lai
            </span>
        ';

        // Priority badge dựa trên chủ đề
        $priorityBadge = '';
        if ($data['subject'] === 'Khiếu nại') {
            $priorityBadge = '<span style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 6px 14px; border-radius: 15px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-left: 10px;">Ưu tiên cao</span>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ mới - {$subject}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #7f1d1d 100%); min-height: 100vh;">
    <div style="width: 100%; padding: 30px 20px;">
        <div style="max-width: 650px; margin: 0 auto; background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px); box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.5); border-radius: 24px; overflow: hidden;">
            
            <!-- Header Alert -->
            <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 35px 30px; text-align: center; position: relative;">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.05); backdrop-filter: blur(5px);"></div>
                <div style="position: relative; z-index: 1;">
                    <div style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 18px; border: 2px solid rgba(255,255,255,0.3);">
                        <svg width="35" height="35" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;">Liên hệ mới từ Website</h1>
                    <p style="margin: 10px 0 0; color: rgba(255, 255, 255, 0.9); font-size: 14px;">Có khách hàng vừa gửi tin nhắn qua form liên hệ</p>
                </div>
            </div>
            
            <div style="padding: 35px;">
                <!-- Quick Info Bar -->
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 30px; padding-bottom: 25px; border-bottom: 2px solid rgba(241, 245, 249, 0.8);">
                    <span style="background: {$config['bg']}; backdrop-filter: blur(10px); color: {$config['color']}; padding: 10px 20px; border-radius: 25px; font-size: 14px; font-weight: 700; border: 1px solid {$config['border']};">
                        {$subject}
                    </span>
                    {$priorityBadge}
                    <span style="color: #64748b; font-size: 14px; margin-left: auto; font-weight: 600;">
                        Mã: <span style="color: #1e293b;">{$submission_id}</span>
                    </span>
                </div>
                
                <!-- Customer Info Card - Liquid Glass -->
                <div style="background: rgba(248, 250, 252, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 16px; padding: 28px; margin-bottom: 28px;">
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 22px; gap: 12px;">
                        <h3 style="margin: 0; font-size: 17px; color: #1e293b; font-weight: 700;">
                            Thông tin khách hàng
                        </h3>
                        {$memberInfo}
                    </div>
                    
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8); width: 35%;">
                                <span style="font-size: 14px; color: #64748b;">Họ và tên</span>
                            </td>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8);">
                                <span style="font-size: 16px; color: #dc2626; font-weight: 700;">{$name}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8);">
                                <span style="font-size: 14px; color: #64748b;">Email</span>
                            </td>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8);">
                                <a href="mailto:{$email}" style="font-size: 15px; color: #2563eb; text-decoration: none; font-weight: 600;">{$email}</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8);">
                                <span style="font-size: 14px; color: #64748b;">Số điện thoại</span>
                            </td>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8);">
                                <a href="tel:{$phone}" style="font-size: 15px; color: #2563eb; text-decoration: none; font-weight: 600;">{$phone}</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 0;">
                                <span style="font-size: 14px; color: #64748b;">Thời gian gửi</span>
                            </td>
                            <td style="padding: 16px 0;">
                                <span style="font-size: 15px; color: #1e293b; font-weight: 600;">{$created_at}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Message Content - Liquid Glass -->
                <div style="background: rgba(254, 243, 199, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(253, 230, 138, 0.8); border-radius: 16px; padding: 28px; margin-bottom: 28px;">
                    <h3 style="margin: 0 0 18px; font-size: 16px; color: #92400e; font-weight: 700;">
                        Nội dung tin nhắn
                    </h3>
                    <div style="background: rgba(255,255,255,0.95); padding: 22px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(253, 230, 138, 0.5);">
                        <p style="margin: 0; font-size: 15px; color: #1e293b; line-height: 1.9; white-space: pre-wrap;">{$message}</p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin: 35px 0;">
                    <a href="mailto:{$email}?subject=Re: {$subject} - Aurora Hotel Plaza" style="display: inline-block; padding: 16px 32px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 30px; box-shadow: 0 10px 25px rgba(220, 38, 38, 0.35);">
                        Phản hồi qua Email
                    </a>
                    <a href="tel:{$phone}" style="display: inline-block; padding: 16px 32px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 30px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.35);">
                        Gọi điện ngay
                    </a>
                </div>
                
                <!-- Reminder - Liquid Glass -->
                <div style="background: rgba(236, 253, 245, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(167, 243, 208, 0.8); border-radius: 16px; padding: 20px 25px; margin-top: 25px;">
                    <p style="margin: 0; font-size: 14px; color: #065f46; line-height: 1.7;">
                        <strong>Nhắc nhở:</strong> Vui lòng phản hồi khách hàng trong vòng <strong>24 giờ</strong> để đảm bảo chất lượng dịch vụ và sự hài lòng của khách hàng.
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 30px; text-align: center;">
                <p style="margin: 0 0 5px; font-size: 13px; color: #94a3b8;">Email này được gửi tự động từ hệ thống</p>
                <p style="margin: 0; font-size: 15px; color: #d4af37; font-weight: 700;">Aurora Hotel Plaza</p>
                <p style="margin: 10px 0 0; font-size: 12px; color: #64748b;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
