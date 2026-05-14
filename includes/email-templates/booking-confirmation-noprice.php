<?php
/**
 * Email Template: Booking Confirmation (No Price)
 * Sent to customers - no pricing information
 * Style: Clean white background, Gold brand colors
 */

function getBookingConfirmationNoPriceEmailHTML($booking, $hotel_info = []) {
    $hotel_name = $hotel_info['name'] ?? 'Aurora Hotel Plaza';
    $hotel_address = $hotel_info['address'] ?? 'KP2, Phường Tân Hiệp, Thủ Đông Nai';
    $hotel_phone = $hotel_info['phone'] ?? '(+84-251) 391 8888';
    $hotel_email = $hotel_info['email'] ?? 'info@aurorahotelplaza.com';
    $hotel_website = $hotel_info['website'] ?? 'https://aurorahotelplaza.com';
    $hotel_phone_clean = preg_replace('/[^0-9+]/', '', $hotel_phone);
    
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    $full_code = $booking['booking_code'];
    $suffix = substr($full_code, -6);

    return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu đặt phòng đã được gửi - {$hotel_name}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div style="width: 100%; padding: 40px 20px; background-color: #f1f5f9;">
        <div style="max-width: 620px; margin: 0 auto; background: #ffffff; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06); border-radius: 12px; overflow: hidden;">
            
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); padding: 40px 30px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: 0.5px; text-shadow: 0 1px 3px rgba(0,0,0,0.15);">{$hotel_name}</h1>
                <p style="margin: 10px 0 0; color: rgba(255, 255, 255, 0.92); font-size: 14px; font-weight: 500;">Yêu cầu đặt phòng đã được gửi</p>
            </div>
            
            <!-- Content -->
            <div style="padding: 36px 32px;">
                <!-- Greeting -->
                <p style="font-size: 16px; color: #1e293b; margin: 0 0 18px;">Kính gửi <strong>{$booking['guest_name']}</strong>,</p>
                
                <p style="font-size: 15px; color: #475569; margin: 0 0 24px; line-height: 1.7;">
                    Yêu cầu đặt phòng của quý khách đã được <span style="color: #b8941f; font-weight: 600;">gửi thành công</span> tới {$hotel_name}.<br>
                    Đội ngũ lễ tân sẽ <span style="color: #059669; font-weight: 600;">xác nhận trong thời gian sớm nhất</span>.
                </p>
                
                <!-- Booking Code -->
                <div style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.08) 0%, rgba(184, 148, 31, 0.05) 100%); border: 2px dashed #d4af37; border-radius: 10px; padding: 24px 20px; text-align: center; margin: 24px 0;">
                    <div style="font-size: 12px; color: #78716c; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Mã đặt phòng</div>
                    <div style="font-size: 22px; font-weight: 700; color: #b8941f; font-family: 'Courier New', monospace; letter-spacing: 2px;">{$full_code}</div>
                    <p style="margin: 12px 0 0; font-size: 12px; color: #78716c;">Mã rút gọn: <strong>{$suffix}</strong> — Dùng để tra cứu nhanh hoặc báo lễ tân</p>
                </div>
                
                <!-- Status Badge -->
                <div style="text-align: center; margin: 20px 0;">
                    <span style="display: inline-block; padding: 7px 18px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; background-color: #fef3c7; color: #92400e; border-radius: 20px;">Chờ xác nhận</span>
                </div>
                
                <!-- Booking Info -->
                <div style="background-color: #f8fafc; border-left: 3px solid #d4af37; padding: 20px 22px; margin: 24px 0; border-radius: 0 8px 8px 0;">
                    <div style="font-size: 15px; font-weight: 600; color: #1e293b; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">Thông tin đặt phòng</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 10px 0; font-size: 14px; color: #64748b; width: 40%;">Loại phòng</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$booking['type_name']}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9;">Ngày nhận phòng</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid #f1f5f9;">{$check_in}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9;">Ngày trả phòng</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid #f1f5f9;">{$check_out}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9;">Số đêm</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid #f1f5f9;">{$booking['total_nights']} đêm</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9;">Số khách</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid #f1f5f9;">{$booking['num_adults']} người</td>
                        </tr>
                    </table>
                </div>

                <!-- Status Info -->
                <div style="background-color: #eff6ff; border-left: 3px solid #3b82f6; padding: 18px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
                    <div style="font-size: 14px; font-weight: 600; color: #1e40af; margin: 0 0 10px;">Trạng thái yêu cầu</div>
                    <p style="margin: 0; font-size: 14px; color: #475569; line-height: 1.6;">
                        Nhân viên khách sạn sẽ liên hệ xác nhận qua <strong>số điện thoại</strong> hoặc <strong>email</strong> của quý khách.<br>
                        Chi tiết giá và thanh toán sẽ được thông báo sau khi xác nhận.
                    </p>
                </div>

                <!-- Important Notes -->
                <div style="background-color: #fef2f2; border-left: 3px solid #ef4444; padding: 18px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
                    <div style="font-size: 14px; font-weight: 600; color: #991b1b; margin: 0 0 10px;">Lưu ý quan trọng</div>
                    <ul style="margin: 0; padding-left: 18px; font-size: 14px; color: #7f1d1d; line-height: 1.8;">
                        <li>Quý khách sẽ nhận được email xác nhận khi khách sạn duyệt yêu cầu.</li>
                        <li>Vui lòng mang theo CMND/CCCD khi nhận phòng.</li>
                        <li>Nhận phòng từ 14:00 — Trả phòng trước 12:00.</li>
                        <li>Hủy miễn phí trước 24 giờ so với thời gian nhận phòng.</li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div style="background-color: #f8fafc; padding: 20px 22px; margin: 24px 0; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 14px; font-weight: 600; color: #1e293b; margin: 0 0 12px;">Liên hệ khách sạn</div>
                    <div style="font-size: 14px; color: #475569; margin: 6px 0;">
                        Điện thoại: <strong><a href="tel:{$hotel_phone_clean}" style="color: #b8941f; text-decoration: none;">{$hotel_phone}</a></strong>
                    </div>
                    <div style="font-size: 14px; color: #475569; margin: 6px 0;">
                        Email: <strong><a href="mailto:{$hotel_email}" style="color: #b8941f; text-decoration: none;">{$hotel_email}</a></strong>
                    </div>
                    <div style="font-size: 14px; color: #475569; margin: 6px 0;">
                        Website: <strong><a href="{$hotel_website}" style="color: #b8941f; text-decoration: none;">{$hotel_website}</a></strong>
                    </div>
                </div>
                
                <!-- CTA Button -->
                <div style="text-align: center; margin: 32px 0 24px;">
                    <a href="{$hotel_website}" style="display: inline-block; padding: 13px 30px; background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; border-radius: 8px; box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);">
                        Khám phá {$hotel_name}
                    </a>
                </div>
                
                <p style="font-size: 15px; color: #475569; margin: 0; line-height: 1.7;">
                    Trân trọng,<br><strong>Đội ngũ {$hotel_name}</strong>
                </p>
            </div>
            
            <!-- Footer -->
            <div style="background-color: #f8fafc; padding: 28px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
                <p style="margin: 0 0 8px; font-size: 12px; color: #64748b;">Email này được gửi tự động, vui lòng không trả lời trực tiếp.</p>
                <p style="margin: 0; font-size: 14px; font-weight: 600; color: #b8941f;">{$hotel_name}</p>
                <p style="margin: 4px 0 0; font-size: 13px; color: #94a3b8;">{$hotel_address}</p>
                <p style="margin: 4px 0 0; font-size: 13px; color: #94a3b8;">{$hotel_phone} | {$hotel_email}</p>
                <p style="margin: 12px 0 0; font-size: 12px; color: #94a3b8;">© 2025 {$hotel_name}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}

function getBookingConfirmationNoPriceEmailText($booking, $hotel_info = []) {
    $hotel_name = $hotel_info['name'] ?? 'Aurora Hotel Plaza';
    $hotel_phone = $hotel_info['phone'] ?? '(+84-251) 391 8888';
    $hotel_email = $hotel_info['email'] ?? 'info@aurorahotelplaza.com';
    
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    return <<<TEXT
{$hotel_name}
YÊU CẦU ĐẶT PHÒNG ĐÃ ĐƯỢC GỬI

Kính gửi {$booking['guest_name']},

Yêu cầu đặt phòng của quý khách đã được gửi thành công tới {$hotel_name}.
Đội ngũ lễ tân sẽ xác nhận trong thời gian sớm nhất.

MÃ ĐẶT PHÒNG: {$booking['booking_code']}
TRẠNG THÁI: Đang chờ xác nhận

THÔNG TIN ĐẶT PHÒNG:
- Loại phòng: {$booking['type_name']}
- Ngày nhận phòng: {$check_in}
- Ngày trả phòng: {$check_out}
- Số đêm: {$booking['total_nights']} đêm
- Số khách: {$booking['num_adults']} người lớn

Quý khách sẽ nhận được email xác nhận khi khách sạn duyệt yêu cầu.

LIÊN HỆ:
Điện thoại: {$hotel_phone}
Email: {$hotel_email}

Chúng tôi rất mong được phục vụ quý khách!

Trân trọng,
Đội ngũ {$hotel_name}
TEXT;
}
?>
