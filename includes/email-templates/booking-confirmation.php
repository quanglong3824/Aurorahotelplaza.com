<?php
/**
 * Email Template: Booking Confirmation (Advanced Responsive & Bilingual)
 * Style: Modern Luxury / Golden Accent
 */

function getBookingConfirmationEmailHTML($booking, $hotel_info = []) {
    $hotel_name = $hotel_info['name'] ?? 'AURORA HOTEL PLAZA';
    $hotel_address = $hotel_info['address'] ?? (getLang() === 'vi' ? '253 Phạm Văn Thuận, Biên Hòa, Đồng Nai' : '253 Pham Van Thuan, Bien Hoa, Dong Nai');
    $hotel_phone = $hotel_info['phone'] ?? '(+84-251) 391 8888';
    $hotel_email = $hotel_info['email'] ?? 'info@aurorahotelplaza.com';
    $hotel_website = $hotel_info['website'] ?? 'https://aurorahotelplaza.com';
    
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    // Load CSS
    $css = file_get_contents(__DIR__ . '/email-styles.css');
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{__('email.booking_confirmed_subject')}</title>
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
                            <h1>{$hotel_name}</h1>
                            <p>LUXURY HOSPITALITY EXPERIENCE</p>
                        </td>
                    </tr>
                    <tr><td class="accent-bar"></td></tr>
                    
                    <!-- Body -->
                    <tr>
                        <td class="email-body">
                            <div class="greeting">{__('email.dear')} {$booking['guest_name']},</div>
                            <p class="main-text">{__('email.booking_confirmed_message')}</p>
                            
                            <!-- Highlight Box -->
                            <div class="highlight-box">
                                <div class="highlight-label">{__('email.booking_code')}</div>
                                <div class="highlight-value">{$booking['booking_code']}</div>
                                <div style="margin-top: 15px;">
                                    <span style="background-color: #d4af37; color: #ffffff; padding: 5px 15px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">{__('booking_confirmation.pending')}</span>
                                </div>
                            </div>
                            
                            <!-- Booking Details Card -->
                            <div class="info-card">
                                <div class="card-title">{__('email.booking_info')}</div>
                                <table class="data-table">
                                    <tr>
                                        <td class="label">{__('email.room_type')}</td>
                                        <td class="value">{$booking['type_name']}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">{__('email.check_in_date')}</td>
                                        <td class="value">{$check_in} (14:00)</td>
                                    </tr>
                                    <tr>
                                        <td class="label">{__('email.check_out_date')}</td>
                                        <td class="value">{$check_out} (12:00)</td>
                                    </tr>
                                    <tr>
                                        <td class="label">{__('email.num_nights')}</td>
                                        <td class="value">{$booking['total_nights']} {__('email.night')}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Payment Card -->
                            <div class="info-card">
                                <div class="card-title">{__('booking_confirmation.total_amount')}</div>
                                <div style="text-align: center; padding: 10px 0;">
                                    <div style="font-size: 26px; font-weight: 800; color: #d4af37;">{$booking['total_amount_formatted']} VND</div>
                                    <p style="font-size: 11px; color: #94a3b8; margin-top: 5px;">({__('booking_form.included_tax_service')})</p>
                                </div>
                            </div>
                            
                            <!-- CTA Button -->
                            <div class="button-container">
                                <a href="{$hotel_website}/profile.php" class="btn-cta">{__('email.view_my_bookings')}</a>
                            </div>

                            <p class="main-text" style="font-size: 13px; color: #94a3b8; text-align: center;">
                                * {__('email.contact_note')}
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td class="email-footer">
                            <div class="footer-brand">AURORA HOTEL PLAZA</div>
                            <div class="footer-text">
                                {$hotel_address}<br>
                                Hotline: {$hotel_phone} | Email: {$hotel_email}
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
    
    return $html;
}

function getBookingConfirmationEmailText($booking, $hotel_info = []) {
    $hotel_name = $hotel_info['name'] ?? 'AURORA HOTEL PLAZA';
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    return $hotel_name . "\n" . 
           __('email.booking_confirmed_title') . "\n\n" . 
           __('email.dear') . " " . $booking['guest_name'] . ",\n" . 
           __('email.booking_code') . ": " . $booking['booking_code'] . "\n" . 
           __('email.room_type') . ": " . $booking['type_name'] . "\n" . 
           __('email.check_in_date') . ": " . $check_in . "\n" . 
           __('email.check_out_date') . ": " . $check_out . "\n" . 
           __('booking_confirmation.total_amount') . ": " . $booking['total_amount_formatted'] . " VND\n\n" . 
           __('email.contact_note');
}
?>
