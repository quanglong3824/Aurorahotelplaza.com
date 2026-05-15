<?php
function getBookingUnconfirmedEmail($data) {
    $checkIn = date('d/m/Y', strtotime($data['check_in_date']));
    $checkOut = date('d/m/Y', strtotime($data['check_out_date']));

    return "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #6b7280, #4b5563); color: #fff; padding: 32px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
            .header .subtitle { font-size: 14px; opacity: 0.9; margin-top: 8px; }
            .content { padding: 32px; }
            .greeting { font-size: 16px; margin-bottom: 20px; color: #333; }
            .booking-code { text-align: center; background: #fefce8; padding: 16px; border-radius: 8px; margin: 20px 0; border: 2px solid #d4af37; }
            .booking-code .label { font-size: 12px; color: #666; text-transform: uppercase; }
            .booking-code .code { font-size: 28px; font-weight: 700; color: #d4af37; letter-spacing: 3px; }
            .section { margin: 24px 0; }
            .section-title { font-size: 16px; font-weight: 700; color: #d4af37; text-transform: uppercase; border-bottom: 2px solid #f0e6d2; padding-bottom: 8px; margin-bottom: 12px; }
            .info-row { display: flex; padding: 8px 0; border-bottom: 1px dotted #eee; }
            .info-label { font-weight: 600; color: #666; min-width: 130px; font-size: 14px; }
            .info-value { color: #333; font-size: 14px; }
            .apology-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin: 16px 0; }
            .apology-box h4 { margin: 0 0 10px; color: #dc2626; font-size: 14px; }
            .apology-box p { margin: 0; font-size: 14px; color: #7f1d1d; line-height: 1.6; }
            .info-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin: 16px 0; }
            .info-box h4 { margin: 0 0 10px; color: #16a34a; font-size: 14px; }
            .info-box p { margin: 0; font-size: 14px; color: #166534; line-height: 1.6; }
            .footer { background: #f8f9fa; padding: 24px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
            .footer .hotel-name { font-size: 14px; font-weight: 700; color: #d4af37; margin-bottom: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Aurora Hotel Plaza</h1>
                <div class='subtitle'>Important Notice Regarding Your Booking</div>
            </div>
            <div class='content'>
                <div class='greeting'>
                    Dear <strong>{$data['guest_name']}</strong>,
                </div>

                <div class='apology-box'>
                    <h4>⚠ We Sincerely Apologize</h4>
                    <p>
                        Due to a technical error on our system, you may have received a booking confirmation email prematurely. 
                        We sincerely apologize for any confusion this may have caused.
                    </p>
                </div>

                <p>
                    Please be assured that <strong>your booking information is still safely stored in our system</strong>. 
                    Our reception team is currently reviewing your request and will contact you shortly to confirm availability and finalize your reservation.
                </p>

                <div class='booking-code'>
                    <div class='label'>Your Booking Reference</div>
                    <div class='code'>{$data['booking_code']}</div>
                </div>

                <div class='section'>
                    <div class='section-title'>Booking Details</div>
                    <div class='info-row'>
                        <span class='info-label'>Room Type:</span>
                        <span class='info-value'>{$data['type_name']}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Check-in:</span>
                        <span class='info-value'>{$checkIn}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Check-out:</span>
                        <span class='info-value'>{$checkOut}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Total Nights:</span>
                        <span class='info-value'>{$data['total_nights']} nights</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Estimated Total:</span>
                        <span class='info-value'>{$data['total_amount']} VND</span>
                    </div>
                </div>

                <div class='info-box'>
                    <h4>✓ What Happens Next?</h4>
                    <p>
                        Our team will contact you again to confirm your booking details and provide you with an official confirmation. 
                        You do not need to take any action at this time.
                    </p>
                </div>

                <p style='margin-top:24px;'>
                    If you have any questions or need immediate assistance, please don't hesitate to contact us directly.
                </p>

                <p style='margin-top:20px;'>
                    Warm regards,<br>
                    <strong>Aurora Hotel Plaza Team</strong>
                </p>
            </div>
            <div class='footer'>
                <div class='hotel-name'>Aurora Hotel Plaza</div>
                253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai<br>
                Hotline: 0251 3511 888 | info@aurorahotelplaza.com
            </div>
        </div>
    </body>
    </html>
    ";
}
