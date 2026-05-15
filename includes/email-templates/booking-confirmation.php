<?php
/**
 * Email Template: Booking Confirmation
 * Sent when a new booking is created
 * Style: Clean white background, Gold brand colors
 */

function getBookingConfirmationEmailHTML($booking, $hotel_info = []) {
    $hotel_name = $hotel_info['name'] ?? 'Aurora Hotel Plaza';
    $hotel_address = $hotel_info['address'] ?? '253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai';
    $hotel_phone = $hotel_info['phone'] ?? '(+84-251) 391 8888';
    $hotel_email = $hotel_info['email'] ?? 'info@aurorahotelplaza.com';
    $hotel_website = $hotel_info['website'] ?? 'https://aurorahotelplaza.com';
    $hotel_phone_clean = preg_replace('/[^0-9+]/', '', $hotel_phone);
    
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    // Load CSS
    $css = file_get_contents(__DIR__ . '/email-styles.css');
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - {$hotel_name}</title>
    <style>{$css}</style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <h1>{$hotel_name}</h1>
                <p>Booking Received — Pending Confirmation</p>
            </div>
            
            <!-- Content -->
            <div class="email-content">
                <p class="email-greeting">Dear <strong>{$booking['guest_name']}</strong>,</p>
                
                <p class="email-text">Thank you for choosing {$hotel_name}. We have received your booking request and our team is reviewing it now. You will receive a confirmation email once your booking is approved.</p>
                
                <!-- Booking Code -->
                <div class="booking-code-box">
                    <div class="booking-code-label">Booking Code</div>
                    <div class="booking-code">{$booking['booking_code']}</div>
                </div>
                
                <!-- Status -->
                <div style="text-align: center;">
                    <span class="status-badge status-pending">Pending Confirmation</span>
                </div>
                
                <!-- Booking Info -->
                <div class="info-box">
                    <div class="info-box-title">Booking Information</div>
                    <div class="info-row">
                        <span class="info-label">Room Type</span>
                        <span class="info-value">{$booking['type_name']}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-in Date</span>
                        <span class="info-value">{$check_in}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-out Date</span>
                        <span class="info-value">{$check_out}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Number of Nights</span>
                        <span class="info-value">{$booking['total_nights']} nights</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Number of Guests</span>
                        <span class="info-value">{$booking['num_adults']} guests</span>
                    </div>
                </div>
                
                <!-- Guest Info -->
                <div class="info-box">
                    <div class="info-box-title">Guest Information</div>
                    <div class="info-row">
                        <span class="info-label">Full Name</span>
                        <span class="info-value">{$booking['guest_name']}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{$booking['guest_email']}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value">{$booking['guest_phone']}</span>
                    </div>
                </div>
                
                <!-- Total Amount -->
                <div class="total-amount-box">
                    <div class="total-label">Estimated Total Cost</div>
                    <div class="total-amount">{$booking['total_amount_formatted']} VND</div>
                </div>
                
                <!-- Important Notes -->
                <div class="alert-box">
                    <div class="alert-box-title">What Happens Next?</div>
                    <ul>
                        <li>Our staff will review your request and confirm within 24 hours.</li>
                        <li>You will receive a confirmation email with full details once approved.</li>
                        <li>After confirmation, you can download a QR code for quick check-in.</li>
                        <li>Payment can be made online or at the hotel upon check-in.</li>
                        <li>Free cancellation up to 24 hours before check-in time.</li>
                    </ul>
                </div>
                
                <div class="divider"></div>
                
                <!-- Contact Info -->
                <div class="contact-info">
                    <div class="contact-info-title">Need Help?</div>
                    <div class="contact-item">Phone: <strong><a href="tel:{$hotel_phone_clean}" style="color: #b8941f; text-decoration: none;">{$hotel_phone}</a></strong></div>
                    <div class="contact-item">Email: <strong><a href="mailto:{$hotel_email}" style="color: #b8941f; text-decoration: none;">{$hotel_email}</a></strong></div>
                    <div class="contact-item">Website: <strong><a href="{$hotel_website}" style="color: #b8941f; text-decoration: none;">{$hotel_website}</a></strong></div>
                    <div class="contact-item">Address: <strong>{$hotel_address}</strong></div>
                </div>
                
                <p class="email-text">We look forward to welcoming you!</p>
                
                <p class="email-text">Warm regards,<br><strong>The {$hotel_name} Team</strong></p>
            </div>
            
            <!-- Footer -->
            <div class="email-footer">
                <p class="footer-text" style="color: #64748b; font-size: 12px; margin-bottom: 8px;">This is an automated email, please do not reply directly.</p>
                <p class="footer-text" style="font-weight: 600; color: #b8941f; font-size: 14px;">{$hotel_name}</p>
                <p class="footer-text">{$hotel_address}</p>
                <p class="footer-text">{$hotel_phone} | {$hotel_email}</p>
                <p class="footer-text" style="margin-top: 12px; color: #94a3b8;">© 2025 {$hotel_name}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    
    return $html;
}

function getBookingConfirmationEmailText($booking, $hotel_info = []) {
    $hotel_name = $hotel_info['name'] ?? 'Aurora Hotel Plaza';
    $hotel_phone = $hotel_info['phone'] ?? '(+84-251) 391 8888';
    $hotel_email = $hotel_info['email'] ?? 'info@aurorahotelplaza.com';
    
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    $text = <<<TEXT
{$hotel_name}
BOOKING RECEIVED — PENDING CONFIRMATION

Dear {$booking['guest_name']},

Thank you for choosing {$hotel_name}. We have received your booking request and our team is reviewing it now.

BOOKING CODE: {$booking['booking_code']}
STATUS: Pending Confirmation

BOOKING INFORMATION:
- Room Type: {$booking['type_name']}
- Check-in Date: {$check_in}
- Check-out Date: {$check_out}
- Number of Nights: {$booking['total_nights']} nights
- Number of Guests: {$booking['num_adults']} adults

GUEST INFORMATION:
- Full Name: {$booking['guest_name']}
- Email: {$booking['guest_email']}
- Phone: {$booking['guest_phone']}

ESTIMATED TOTAL COST: {$booking['total_amount_formatted']} VND

WHAT HAPPENS NEXT?
- Staff will review and confirm within 24 hours
- You will receive a confirmation email once approved
- Free cancellation up to 24 hours before check-in time

CONTACT:
Phone: {$hotel_phone}
Email: {$hotel_email}

We look forward to welcoming you!

Warm regards,
The {$hotel_name} Team
TEXT;
    
    return $text;
}
?>
