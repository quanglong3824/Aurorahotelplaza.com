<?php
/**
 * Contact Email Templates
 * Templates cho email liên hệ - Aurora Hotel Plaza
 */

class ContactEmailTemplates {
    
    /**
     * Template email xác nhận gửi cho khách hàng
     * UI hiện đại với icon xác nhận thành công
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
        $user_id = isset($data['user_id']) ? $data['user_id'] : null;
        
        // Badge cho user đã đăng nhập
        $memberBadge = $user_id ? '
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; margin-bottom: 15px;">
                ✓ Thành viên Aurora
            </div>
        ' : '';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận liên hệ - Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <div class="email-wrapper" style="width: 100%; background-color: #f5f5f5; padding: 40px 20px;">
        <div class="email-container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); border-radius: 16px; overflow: hidden;">
            
            <!-- Header với gradient gold -->
            <div style="background: linear-gradient(135deg, #d4af37 0%, #b8941f 50%, #cc9a2c 100%); padding: 50px 30px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Aurora Hotel Plaza</h1>
                <p style="margin: 10px 0 0; color: rgba(255, 255, 255, 0.95); font-size: 15px;">Khách sạn 4 sao tại Đồng Nai</p>
            </div>
            
            <!-- Success Icon -->
            <div style="text-align: center; margin-top: -35px; position: relative; z-index: 10;">
                <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); border: 4px solid #ffffff;">
                    <svg width="35" height="35" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            
            <div style="padding: 30px 35px 40px;">
                <!-- Success Message -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="margin: 0 0 10px; color: #10b981; font-size: 22px; font-weight: 700;">Gửi liên hệ thành công!</h2>
                    <p style="margin: 0; color: #666; font-size: 15px; line-height: 1.6;">Cảm ơn bạn đã liên hệ với Aurora Hotel Plaza</p>
                </div>
                
                {$memberBadge}
                
                <p style="font-size: 15px; color: #444; margin: 0 0 25px; line-height: 1.7;">
                    Xin chào <strong style="color: #1f2937;">{$name}</strong>!<br>
                    Chúng tôi đã nhận được tin nhắn của bạn và sẽ phản hồi trong thời gian sớm nhất <span style="color: #d4af37; font-weight: 600;">(thường trong vòng 24 giờ làm việc)</span>.
                </p>
                
                <!-- Mã liên hệ -->
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px dashed #d4af37; border-radius: 12px; padding: 25px; text-align: center; margin: 25px 0;">
                    <div style="font-size: 12px; color: #92400e; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Mã liên hệ của bạn</div>
                    <div style="font-size: 28px; font-weight: 800; color: #b8941f; font-family: 'Courier New', monospace; letter-spacing: 3px;">CT{$submission_id}</div>
                    <p style="margin: 10px 0 0; font-size: 12px; color: #78350f;">Vui lòng lưu mã này để theo dõi phản hồi</p>
                </div>
                
                <!-- Thông tin liên hệ -->
                <div style="background-color: #f8fafc; border-left: 4px solid #d4af37; border-radius: 0 12px 12px 0; padding: 20px 25px; margin: 25px 0;">
                    <div style="font-size: 15px; font-weight: 700; color: #1f2937; margin: 0 0 18px; display: flex; align-items: center;">
                        <span style="margin-right: 8px;">📋</span> Thông tin liên hệ của bạn
                    </div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #64748b; width: 40%;">Họ và tên</td>
                            <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #1f2937; font-weight: 600; text-align: right;">{$name}</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #64748b;">Email</td>
                            <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #1f2937; font-weight: 600; text-align: right;">{$email}</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #64748b;">Số điện thoại</td>
                            <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #1f2937; font-weight: 600; text-align: right;">{$phone}</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #64748b;">Chủ đề</td>
                            <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; text-align: right;">
                                <span style="background-color: #d4af37; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">{$subject}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 12px 0; font-size: 14px; color: #64748b;">Thời gian gửi</td>
                            <td style="padding: 12px 0; font-size: 14px; color: #1f2937; font-weight: 600; text-align: right;">{$created_at}</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Nội dung tin nhắn -->
                <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6; border-radius: 0 12px 12px 0; padding: 20px 25px; margin: 25px 0;">
                    <div style="font-size: 15px; font-weight: 700; color: #1e40af; margin: 0 0 15px; display: flex; align-items: center;">
                        <span style="margin-right: 8px;">💬</span> Nội dung tin nhắn
                    </div>
                    <p style="margin: 0; font-size: 14px; color: #1e3a5f; line-height: 1.8; white-space: pre-wrap; background: white; padding: 15px; border-radius: 8px;">{$message}</p>
                </div>
                
                <!-- Liên hệ khẩn cấp -->
                <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 20px; margin: 25px 0;">
                    <div style="font-size: 14px; font-weight: 700; color: #991b1b; margin: 0 0 12px; display: flex; align-items: center;">
                        <span style="margin-right: 8px;">🔔</span> Cần hỗ trợ gấp?
                    </div>
                    <p style="margin: 0 0 15px; font-size: 13px; color: #7f1d1d; line-height: 1.6;">Nếu bạn cần hỗ trợ ngay, vui lòng liên hệ trực tiếp:</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <a href="tel:+842513918888" style="display: inline-flex; align-items: center; background: white; color: #dc2626; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; border: 1px solid #fecaca;">
                            📞 (+84-251) 391 8888
                        </a>
                        <a href="mailto:info@aurorahotelplaza.com" style="display: inline-flex; align-items: center; background: white; color: #dc2626; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; border: 1px solid #fecaca;">
                            ✉️ info@aurorahotelplaza.com
                        </a>
                    </div>
                </div>
                
                <!-- CTA Button -->
                <div style="text-align: center; margin: 35px 0 20px;">
                    <a href="https://aurorahotelplaza.com" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 30px; box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);">
                        🏨 Khám phá Aurora Hotel Plaza
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="background: linear-gradient(135deg, #1f2937 0%, #111827 100%); padding: 35px 30px; text-align: center;">
                <p style="margin: 0 0 5px; font-size: 16px; color: #d4af37; font-weight: 700;">Aurora Hotel Plaza</p>
                <p style="margin: 0 0 15px; font-size: 13px; color: #9ca3af;">Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai</p>
                <div style="margin: 15px 0;">
                    <a href="tel:+842513918888" style="color: #d4af37; text-decoration: none; font-size: 13px; margin: 0 10px;">(+84-251) 391 8888</a>
                    <span style="color: #4b5563;">|</span>
                    <a href="mailto:info@aurorahotelplaza.com" style="color: #d4af37; text-decoration: none; font-size: 13px; margin: 0 10px;">info@aurorahotelplaza.com</a>
                </div>
                <p style="margin: 15px 0 0; font-size: 12px; color: #6b7280;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Template email thông báo gửi cho khách sạn (Admin)
     * UI hiện đại với thông tin chi tiết về khách hàng
     */
    public static function getHotelNotificationTemplate($data) {
        $name = htmlspecialchars($data['name']);
        $email = htmlspecialchars($data['email']);
        $phone = htmlspecialchars($data['phone']);
        $subject = htmlspecialchars($data['subject']);
        $message = nl2br(htmlspecialchars($data['message']));
        $submission_id = $data['submission_id'];
        $created_at = $data['created_at'];
        $user_id = isset($data['user_id']) ? $data['user_id'] : null;
        
        // Xác định màu và icon badge theo chủ đề
        $subjectConfig = [
            'Đặt phòng' => ['color' => '#10b981', 'bg' => '#d1fae5', 'icon' => '🏨'],
            'Tổ chức sự kiện' => ['color' => '#8b5cf6', 'bg' => '#ede9fe', 'icon' => '🎉'],
            'Dịch vụ khác' => ['color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => '🛎️'],
            'Góp ý' => ['color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => '💡'],
            'Khiếu nại' => ['color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => '⚠️']
        ];
        $config = $subjectConfig[$data['subject']] ?? ['color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => '📩'];
        
        // Badge cho user đã đăng nhập
        $memberInfo = $user_id ? '
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 6px 14px; border-radius: 15px; font-size: 11px; font-weight: 600; display: inline-block;">
                ✓ Thành viên (ID: ' . $user_id . ')
            </div>
        ' : '
            <div style="background: #f3f4f6; color: #6b7280; padding: 6px 14px; border-radius: 15px; font-size: 11px; font-weight: 600; display: inline-block;">
                Khách vãng lai
            </div>
        ';
        
        // Priority badge dựa trên chủ đề
        $priorityBadge = '';
        if ($data['subject'] === 'Khiếu nại') {
            $priorityBadge = '<span style="background: #ef4444; color: white; padding: 4px 10px; border-radius: 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; margin-left: 8px;">Ưu tiên cao</span>';
        }
        
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ mới #{$submission_id} - {$subject}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f1f5f9;">
    <div style="width: 100%; background-color: #f1f5f9; padding: 30px 20px;">
        <div style="max-width: 650px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1); border-radius: 16px; overflow: hidden;">
            
            <!-- Header Alert -->
            <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 30px; text-align: center;">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                    <span style="font-size: 30px;">📩</span>
                </div>
                <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;">Liên hệ mới từ Website</h1>
                <p style="margin: 8px 0 0; color: rgba(255, 255, 255, 0.9); font-size: 14px;">Có khách hàng vừa gửi tin nhắn qua form liên hệ</p>
            </div>
            
            <div style="padding: 30px;">
                <!-- Quick Info Bar -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid #f1f5f9;">
                    <span style="background-color: {$config['bg']}; color: {$config['color']}; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                        {$config['icon']} {$subject}
                    </span>
                    {$priorityBadge}
                    <span style="color: #64748b; font-size: 13px; margin-left: auto;">
                        <strong>Mã:</strong> CT{$submission_id}
                    </span>
                </div>
                
                <!-- Customer Info Card -->
                <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 700;">
                            👤 Thông tin khách hàng
                        </h3>
                        {$memberInfo}
                    </div>
                    
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 14px 0; border-bottom: 1px solid #e2e8f0; width: 35%;">
                                <span style="font-size: 13px; color: #64748b;">Họ và tên</span>
                            </td>
                            <td style="padding: 14px 0; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-size: 15px; color: #dc2626; font-weight: 700;">{$name}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-size: 13px; color: #64748b;">Email</span>
                            </td>
                            <td style="padding: 14px 0; border-bottom: 1px solid #e2e8f0;">
                                <a href="mailto:{$email}" style="font-size: 14px; color: #3b82f6; text-decoration: none; font-weight: 600;">{$email}</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-size: 13px; color: #64748b;">Số điện thoại</span>
                            </td>
                            <td style="padding: 14px 0; border-bottom: 1px solid #e2e8f0;">
                                <a href="tel:{$phone}" style="font-size: 14px; color: #3b82f6; text-decoration: none; font-weight: 600;">{$phone}</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0;">
                                <span style="font-size: 13px; color: #64748b;">Thời gian gửi</span>
                            </td>
                            <td style="padding: 14px 0;">
                                <span style="font-size: 14px; color: #1e293b; font-weight: 600;">{$created_at}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Message Content -->
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 5px solid #f59e0b; border-radius: 0 12px 12px 0; padding: 25px; margin-bottom: 25px;">
                    <h3 style="margin: 0 0 15px; font-size: 15px; color: #92400e; font-weight: 700;">
                        💬 Nội dung tin nhắn
                    </h3>
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <p style="margin: 0; font-size: 15px; color: #1e293b; line-height: 1.9; white-space: pre-wrap;">{$message}</p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin: 30px 0;">
                    <a href="mailto:{$email}?subject=Re: {$subject} - Aurora Hotel Plaza" style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; border-radius: 25px; box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);">
                        📧 Phản hồi qua Email
                    </a>
                    <a href="tel:{$phone}" style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; border-radius: 25px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                        📞 Gọi điện ngay
                    </a>
                </div>
                
                <!-- Reminder -->
                <div style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 1px solid #a7f3d0; border-radius: 12px; padding: 18px 20px; margin-top: 20px;">
                    <p style="margin: 0; font-size: 13px; color: #065f46; line-height: 1.6;">
                        <strong>💡 Nhắc nhở:</strong> Vui lòng phản hồi khách hàng trong vòng <strong>24 giờ</strong> để đảm bảo chất lượng dịch vụ và sự hài lòng của khách hàng.
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="background: #1e293b; padding: 25px; text-align: center;">
                <p style="margin: 0 0 5px; font-size: 13px; color: #94a3b8;">Email này được gửi tự động từ hệ thống</p>
                <p style="margin: 0; font-size: 14px; color: #d4af37; font-weight: 600;">Aurora Hotel Plaza</p>
                <p style="margin: 8px 0 0; font-size: 12px; color: #64748b;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
