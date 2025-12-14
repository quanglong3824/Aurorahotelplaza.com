<?php
/**
 * Contact Email Templates
 * Templates cho email liên hệ - Aurora Hotel Plaza
 * Style: Liquid Glass + Modern UI (No Icons)
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
            <div style="background: rgba(16, 185, 129, 0.12); color: #059669; padding: 10px 24px; border-radius: 30px; font-size: 13px; font-weight: 600; display: inline-block; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.25);">
                Thành viên Aurora Hotel
            </div>
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
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: linear-gradient(160deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 100vh;">
    <div style="width: 100%; padding: 50px 20px;">
        <div style="max-width: 600px; margin: 0 auto; background: rgba(255, 255, 255, 0.95); box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3); border-radius: 28px; overflow: hidden; border: 1px solid rgba(255,255,255,0.2);">
            
            <div style="background: linear-gradient(135deg, #d4af37 0%, #f5d77a 50%, #b8941f 100%); padding: 55px 35px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 30px; font-weight: 700; letter-spacing: -0.5px; text-shadow: 0 2px 15px rgba(0,0,0,0.2);">Aurora Hotel Plaza</h1>
                <p style="margin: 14px 0 0; color: rgba(255, 255, 255, 0.95); font-size: 15px; font-weight: 500;">Khách sạn 4 sao tại Đồng Nai</p>
            </div>
            
            <div style="text-align: center; margin-top: -32px; position: relative; z-index: 10;">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: inline-block; box-shadow: 0 8px 25px rgba(16, 185, 129, 0.45); border: 4px solid #ffffff;"></div>
            </div>
            
            <div style="padding: 40px 45px 50px;">
                <div style="text-align: center; margin-bottom: 35px;">
                    <h2 style="margin: 0 0 12px; color: #10b981; font-size: 26px; font-weight: 700;">Gửi liên hệ thành công</h2>
                    <p style="margin: 0; color: #64748b; font-size: 15px;">Cảm ơn bạn đã liên hệ với chúng tôi</p>
                </div>
                
                {$memberBadge}
                
                <p style="font-size: 15px; color: #475569; margin: 0 0 35px; line-height: 1.85; text-align: center;">
                    Xin chào <strong style="color: #1e293b;">{$name}</strong>,<br>
                    Chúng tôi sẽ phản hồi trong <span style="color: #d4af37; font-weight: 700;">24 giờ làm việc</span>.
                </p>
                
                <div style="background: rgba(212, 175, 55, 0.08); border: 1px solid rgba(212, 175, 55, 0.2); border-radius: 20px; padding: 35px; text-align: center; margin: 35px 0;">
                    <div style="font-size: 11px; color: #92400e; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 3px; font-weight: 700;">Mã liên hệ</div>
                    <div style="font-size: 36px; font-weight: 800; color: #b8941f; font-family: 'Courier New', monospace; letter-spacing: 5px;">{$submission_id}</div>
                    <p style="margin: 14px 0 0; font-size: 12px; color: #78350f;">Lưu mã này để theo dõi phản hồi</p>
                </div>

                <div style="background: rgba(248, 250, 252, 0.9); border: 1px solid rgba(226, 232, 240, 0.6); border-radius: 18px; padding: 28px 32px; margin: 35px 0;">
                    <div style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 22px; padding-bottom: 15px; border-bottom: 1px solid rgba(226, 232, 240, 0.8);">Thông tin liên hệ</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b; width: 38%;">Họ và tên</td>
                            <td style="padding: 14px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$name}</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b;">Email</td>
                            <td style="padding: 14px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$email}</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b;">Số điện thoại</td>
                            <td style="padding: 14px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$phone}</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b;">Chủ đề</td>
                            <td style="padding: 14px 0; font-size: 14px; text-align: right;">
                                <span style="background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: white; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600;">{$subject}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b;">Thời gian</td>
                            <td style="padding: 14px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$created_at}</td>
                        </tr>
                    </table>
                </div>
                
                <div style="background: rgba(239, 246, 255, 0.9); border: 1px solid rgba(191, 219, 254, 0.6); border-radius: 18px; padding: 28px 32px; margin: 35px 0;">
                    <div style="font-size: 15px; font-weight: 700; color: #1e40af; margin: 0 0 18px;">Nội dung tin nhắn</div>
                    <div style="background: rgba(255,255,255,0.95); padding: 22px; border-radius: 14px; border: 1px solid rgba(191, 219, 254, 0.4);">
                        <p style="margin: 0; font-size: 14px; color: #334155; line-height: 1.95;">{$message}</p>
                    </div>
                </div>

                <div style="background: rgba(254, 242, 242, 0.9); border: 1px solid rgba(254, 202, 202, 0.6); border-radius: 18px; padding: 25px 30px; margin: 35px 0;">
                    <div style="font-size: 15px; font-weight: 700; color: #991b1b; margin: 0 0 15px;">Cần hỗ trợ gấp?</div>
                    <p style="margin: 0 0 18px; font-size: 14px; color: #7f1d1d; line-height: 1.6;">Liên hệ trực tiếp với chúng tôi:</p>
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 8px 0;">
                                <a href="tel:+842513918888" style="display: inline-block; background: rgba(255,255,255,0.95); color: #dc2626; padding: 12px 22px; border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 600; border: 1px solid rgba(254, 202, 202, 0.6);">(+84-251) 391 8888</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0;">
                                <a href="mailto:info@aurorahotelplaza.com" style="display: inline-block; background: rgba(255,255,255,0.95); color: #dc2626; padding: 12px 22px; border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 600; border: 1px solid rgba(254, 202, 202, 0.6);">info@aurorahotelplaza.com</a>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div style="text-align: center; margin: 45px 0 25px;">
                    <a href="https://aurorahotelplaza.com" style="display: inline-block; padding: 18px 50px; background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 30px; box-shadow: 0 12px 35px rgba(212, 175, 55, 0.4);">
                        Khám phá Aurora Hotel
                    </a>
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 45px 35px; text-align: center;">
                <p style="margin: 0 0 10px; font-size: 20px; color: #d4af37; font-weight: 700;">Aurora Hotel Plaza</p>
                <p style="margin: 0 0 20px; font-size: 13px; color: #94a3b8; line-height: 1.7;">Số 253, Phạm Văn Thuận, KP2<br>Phường Tam Hiệp, Tỉnh Đồng Nai</p>
                <div style="margin: 20px 0; padding: 15px 0; border-top: 1px solid rgba(148, 163, 184, 0.2); border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
                    <a href="tel:+842513918888" style="color: #d4af37; text-decoration: none; font-size: 14px;">(+84-251) 391 8888</a>
                    <span style="color: #475569; margin: 0 15px;">•</span>
                    <a href="mailto:info@aurorahotelplaza.com" style="color: #d4af37; text-decoration: none; font-size: 14px;">info@aurorahotelplaza.com</a>
                </div>
                <p style="margin: 0; font-size: 12px; color: #64748b;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
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
            'Đặt phòng' => ['color' => '#059669', 'bg' => 'rgba(16, 185, 129, 0.1)', 'border' => 'rgba(16, 185, 129, 0.25)'],
            'Tổ chức sự kiện' => ['color' => '#7c3aed', 'bg' => 'rgba(139, 92, 246, 0.1)', 'border' => 'rgba(139, 92, 246, 0.25)'],
            'Dịch vụ khác' => ['color' => '#2563eb', 'bg' => 'rgba(59, 130, 246, 0.1)', 'border' => 'rgba(59, 130, 246, 0.25)'],
            'Góp ý' => ['color' => '#d97706', 'bg' => 'rgba(245, 158, 11, 0.1)', 'border' => 'rgba(245, 158, 11, 0.25)'],
            'Khiếu nại' => ['color' => '#dc2626', 'bg' => 'rgba(239, 68, 68, 0.1)', 'border' => 'rgba(239, 68, 68, 0.25)']
        ];
        $config = $subjectConfig[$data['subject']] ?? ['color' => '#6b7280', 'bg' => 'rgba(107, 114, 128, 0.1)', 'border' => 'rgba(107, 114, 128, 0.25)'];

        $memberInfo = $user_id 
            ? '<span style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 8px 18px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid rgba(16, 185, 129, 0.25);">Thành viên #' . $user_id . '</span>'
            : '<span style="background: rgba(107, 114, 128, 0.1); color: #6b7280; padding: 8px 18px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid rgba(107, 114, 128, 0.25);">Khách vãng lai</span>';

        $priorityBadge = ($data['subject'] === 'Khiếu nại') 
            ? '<span style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 6px 16px; border-radius: 15px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-left: 12px;">Ưu tiên cao</span>' 
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
    <title>Liên hệ mới - {$subject}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: linear-gradient(160deg, #7f1d1d 0%, #991b1b 50%, #dc2626 100%); min-height: 100vh;">
    <div style="width: 100%; padding: 40px 20px;">
        <div style="max-width: 650px; margin: 0 auto; background: rgba(255, 255, 255, 0.98); box-shadow: 0 30px 60px rgba(0, 0, 0, 0.25); border-radius: 28px; overflow: hidden; border: 1px solid rgba(255,255,255,0.2);">
            
            <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 45px 35px; text-align: center;">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.15); border-radius: 50%; display: inline-block; margin-bottom: 20px; border: 2px solid rgba(255,255,255,0.25);"></div>
                <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700;">Liên hệ mới từ Website</h1>
                <p style="margin: 12px 0 0; color: rgba(255, 255, 255, 0.9); font-size: 14px;">Có khách hàng vừa gửi tin nhắn</p>
            </div>
            
            <div style="padding: 40px;">
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 35px; padding-bottom: 25px; border-bottom: 2px solid rgba(241, 245, 249, 0.8);">
                    <span style="background: {$config['bg']}; color: {$config['color']}; padding: 12px 24px; border-radius: 25px; font-size: 14px; font-weight: 700; border: 1px solid {$config['border']};">{$subject}</span>
                    {$priorityBadge}
                    <span style="color: #64748b; font-size: 14px; margin-left: auto; font-weight: 600;">Mã: <span style="color: #1e293b; font-weight: 700;">{$submission_id}</span></span>
                </div>
                
                <div style="background: rgba(248, 250, 252, 0.9); border: 1px solid rgba(226, 232, 240, 0.6); border-radius: 18px; padding: 30px; margin-bottom: 30px;">
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 12px;">
                        <h3 style="margin: 0; font-size: 17px; color: #1e293b; font-weight: 700;">Thông tin khách hàng</h3>
                        {$memberInfo}
                    </div>
                    
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8); width: 35%;"><span style="font-size: 14px; color: #64748b;">Họ và tên</span></td>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8);"><span style="font-size: 16px; color: #dc2626; font-weight: 700;">{$name}</span></td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8);"><span style="font-size: 14px; color: #64748b;">Email</span></td>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8);"><a href="mailto:{$email}" style="font-size: 15px; color: #2563eb; text-decoration: none; font-weight: 600;">{$email}</a></td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8);"><span style="font-size: 14px; color: #64748b;">Số điện thoại</span></td>
                            <td style="padding: 16px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.8);"><a href="tel:{$phone}" style="font-size: 15px; color: #2563eb; text-decoration: none; font-weight: 600;">{$phone}</a></td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 0;"><span style="font-size: 14px; color: #64748b;">Thời gian</span></td>
                            <td style="padding: 16px 0;"><span style="font-size: 15px; color: #1e293b; font-weight: 600;">{$created_at}</span></td>
                        </tr>
                    </table>
                </div>

                <div style="background: rgba(254, 243, 199, 0.7); border: 1px solid rgba(253, 230, 138, 0.6); border-radius: 18px; padding: 30px; margin-bottom: 30px;">
                    <h3 style="margin: 0 0 20px; font-size: 16px; color: #92400e; font-weight: 700;">Nội dung tin nhắn</h3>
                    <div style="background: rgba(255,255,255,0.95); padding: 25px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border: 1px solid rgba(253, 230, 138, 0.4);">
                        <p style="margin: 0; font-size: 15px; color: #1e293b; line-height: 1.95;">{$message}</p>
                    </div>
                </div>
                
                <div style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin: 40px 0;">
                    <a href="mailto:{$email}?subject=Re: {$subject} - Aurora Hotel Plaza" style="display: inline-block; padding: 16px 35px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 30px; box-shadow: 0 10px 30px rgba(220, 38, 38, 0.35);">Phản hồi Email</a>
                    <a href="tel:{$phone}" style="display: inline-block; padding: 16px 35px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 30px; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.35);">Gọi điện ngay</a>
                </div>
                
                <div style="background: rgba(236, 253, 245, 0.9); border: 1px solid rgba(167, 243, 208, 0.6); border-radius: 18px; padding: 22px 28px; margin-top: 30px;">
                    <p style="margin: 0; font-size: 14px; color: #065f46; line-height: 1.7;">
                        <strong>Nhắc nhở:</strong> Vui lòng phản hồi khách hàng trong vòng <strong>24 giờ</strong> để đảm bảo chất lượng dịch vụ.
                    </p>
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 35px; text-align: center;">
                <p style="margin: 0 0 5px; font-size: 13px; color: #94a3b8;">Email tự động từ hệ thống</p>
                <p style="margin: 0; font-size: 16px; color: #d4af37; font-weight: 700;">Aurora Hotel Plaza</p>
                <p style="margin: 12px 0 0; font-size: 12px; color: #64748b;">© 2025 Aurora Hotel Plaza</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
