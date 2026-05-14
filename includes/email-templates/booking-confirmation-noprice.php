<?php
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
    $prefix = substr($full_code, 0, -6);
    $suffix = substr($full_code, -6);

    return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu đặt phòng đã được gửi</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: linear-gradient(160deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 100vh;">
    <div style="width: 100%; padding: 50px 20px;">
        <div style="max-width: 600px; margin: 0 auto; background: rgba(255, 255, 255, 0.97); box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3); border-radius: 28px; overflow: hidden; border: 1px solid rgba(255,255,255,0.2);">
            
            <div style="background: linear-gradient(135deg, #d4af37 0%, #f5d77a 50%, #b8941f 100%); padding: 55px 35px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 30px; font-weight: 700; letter-spacing: -0.5px; text-shadow: 0 2px 15px rgba(0,0,0,0.2);">Aurora Hotel Plaza</h1>
                <p style="margin: 14px 0 0; color: rgba(255, 255, 255, 0.95); font-size: 15px; font-weight: 500;">Yêu cầu đặt phòng đã được gửi</p>
            </div>
            
            <div style="text-align: center; margin-top: -32px; position: relative; z-index: 10;">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: inline-block; box-shadow: 0 8px 25px rgba(16, 185, 129, 0.45); border: 4px solid #ffffff;"></div>
            </div>
            
            <div style="padding: 40px 45px 50px;">
                <div style="text-align: center; margin-bottom: 35px;">
                    <h2 style="margin: 0 0 12px; color: #10b981; font-size: 26px; font-weight: 700;">Đã gửi yêu cầu đặt phòng</h2>
                    <p style="margin: 0; color: #64748b; font-size: 15px;">Yêu cầu của quý khách đã được chuyển tới khách sạn</p>
                </div>
                
                <p style="font-size: 15px; color: #475569; margin: 0 0 30px; line-height: 1.85; text-align: center;">
                    Kính gửi <strong style="color: #1e293b;">{$booking['guest_name']}</strong>,<br>
                    Yêu cầu đặt phòng của quý khách đã được <span style="color: #d4af37; font-weight: 700;">gửi thành công</span> tới {$hotel_name}.<br>
                    Đội ngũ lễ tân sẽ <span style="color: #059669; font-weight: 700;">xác nhận trong thời gian sớm nhất</span>.
                </p>
                
                <div style="background: rgba(212, 175, 55, 0.08); border: 1px solid rgba(212, 175, 55, 0.2); border-radius: 20px; padding: 35px; text-align: center; margin: 35px 0;">
                    <div style="font-size: 11px; color: #92400e; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 3px; font-weight: 700;">Mã đặt phòng</div>
                    <div style="font-size: 32px; font-weight: 800; color: #b8941f; font-family: 'Courier New', monospace; letter-spacing: 3px;">{$full_code}</div>
                    <p style="margin: 14px 0 0; font-size: 12px; color: #78350f;">Mã rút gọn: <strong>{$suffix}</strong> — Dùng để tra cứu nhanh hoặc báo lễ tân</p>
                </div>
                
                <div style="background: rgba(248, 250, 252, 0.9); border: 1px solid rgba(226, 232, 240, 0.6); border-radius: 18px; padding: 28px 32px; margin: 35px 0;">
                    <div style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 22px; padding-bottom: 15px; border-bottom: 1px solid rgba(226, 232, 240, 0.8);">Thông tin đặt phòng</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b; width: 40%;">Loại phòng</td>
                            <td style="padding: 14px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right;">{$booking['type_name']}</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b; border-top: 1px solid rgba(226,232,240,0.6);">Ngày nhận phòng</td>
                            <td style="padding: 14px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid rgba(226,232,240,0.6);">{$check_in}</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b; border-top: 1px solid rgba(226,232,240,0.6);">Ngày trả phòng</td>
                            <td style="padding: 14px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid rgba(226,232,240,0.6);">{$check_out}</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b; border-top: 1px solid rgba(226,232,240,0.6);">Số đêm</td>
                            <td style="padding: 14px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid rgba(226,232,240,0.6);">{$booking['total_nights']} đêm</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 0; font-size: 14px; color: #64748b; border-top: 1px solid rgba(226,232,240,0.6);">Số khách</td>
                            <td style="padding: 14px 0; font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; border-top: 1px solid rgba(226,232,240,0.6);">{$booking['num_adults']} người</td>
                        </tr>
                    </table>
                </div>

                <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 18px; padding: 28px 32px; margin: 35px 0; text-align: center;">
                    <div style="font-size: 15px; font-weight: 700; color: #059669; margin: 0 0 12px;">Trạng thái yêu cầu</div>
                    <div style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 10px 28px; border-radius: 30px; font-size: 14px; font-weight: 700; letter-spacing: 0.5px;">Đang chờ xác nhận</div>
                    <p style="margin: 18px 0 0; font-size: 13px; color: #64748b; line-height: 1.7;">
                        Nhân viên khách sạn sẽ liên hệ xác nhận qua<br>
                        <strong style="color: #1e293b;">số điện thoại</strong> hoặc <strong style="color: #1e293b;">email</strong> của quý khách.
                    </p>
                </div>
                
                <div style="background: rgba(254, 242, 242, 0.9); border: 1px solid rgba(254, 202, 202, 0.6); border-radius: 18px; padding: 25px 30px; margin: 35px 0;">
                    <div style="font-size: 15px; font-weight: 700; color: #991b1b; margin: 0 0 15px;">Lưu ý quan trọng</div>
                    <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #7f1d1d; line-height: 2;">
                        <li>Quý khách sẽ nhận được email xác nhận khi khách sạn duyệt yêu cầu.</li>
                        <li>Vui lòng mang theo CMND/CCCD khi nhận phòng.</li>
                        <li>Nhận phòng từ 14:00 — Trả phòng trước 12:00.</li>
                        <li>Hủy miễn phí trước 24 giờ so với thời gian nhận phòng.</li>
                    </ul>
                </div>

                <div style="background: rgba(239, 246, 255, 0.9); border: 1px solid rgba(191, 219, 254, 0.6); border-radius: 18px; padding: 25px 30px; margin: 35px 0;">
                    <div style="font-size: 15px; font-weight: 700; color: #1e40af; margin: 0 0 15px;">Liên hệ khách sạn</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 10px 0;">
                                <a href="tel:{$hotel_phone_clean}" style="display: inline-block; background: rgba(255,255,255,0.95); color: #2563eb; padding: 12px 22px; border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 600; border: 1px solid rgba(191, 219, 254, 0.6);">{$hotel_phone}</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0;">
                                <a href="mailto:{$hotel_email}" style="display: inline-block; background: rgba(255,255,255,0.95); color: #2563eb; padding: 12px 22px; border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 600; border: 1px solid rgba(191, 219, 254, 0.6);">{$hotel_email}</a>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div style="text-align: center; margin: 45px 0 25px;">
                    <a href="{$hotel_website}" style="display: inline-block; padding: 18px 50px; background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 30px; box-shadow: 0 12px 35px rgba(212, 175, 55, 0.4);">
                        Khám phá Aurora Hotel
                    </a>
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 45px 35px; text-align: center;">
                <p style="margin: 0 0 10px; font-size: 20px; color: #d4af37; font-weight: 700;">Aurora Hotel Plaza</p>
                <p style="margin: 0 0 20px; font-size: 13px; color: #94a3b8; line-height: 1.7;">{$hotel_address}</p>
                <div style="margin: 20px 0; padding: 15px 0; border-top: 1px solid rgba(148, 163, 184, 0.2); border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
                    <a href="tel:{$hotel_phone_clean}" style="color: #d4af37; text-decoration: none; font-size: 14px;">{$hotel_phone}</a>
                    <span style="color: #475569; margin: 0 15px;">•</span>
                    <a href="mailto:{$hotel_email}" style="color: #d4af37; text-decoration: none; font-size: 14px;">{$hotel_email}</a>
                </div>
                <p style="margin: 0; font-size: 12px; color: #64748b;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
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
