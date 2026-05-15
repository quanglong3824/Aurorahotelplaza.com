<?php
/**
 * Email Templates Helper
 * Manages email templates for Aurora Hotel Plaza
 * Style: Clean white background, Gold brand colors
 */

// Load environment helper for URL functions
require_once __DIR__ . '/../config/environment.php';

class EmailTemplates {
    
    /**
     * Welcome email template
     */
    public static function getWelcomeTemplate($userName, $userEmail, $userId) {
        $currentDate = date('d/m/Y H:i');
        $hotelUrl = self::getBaseUrl();
        
        // Load CSS
        $css = file_get_contents(__DIR__ . '/../includes/email-templates/email-styles.css');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Aurora Hotel Plaza</h1>
                <p>Welcome to the Aurora Family</p>
            </div>
            
            <div class="email-content">
                <p class="email-greeting">Hello <strong>{$userName}</strong>!</p>
                
                <p class="email-text">We are delighted that you have become a member of <strong>Aurora Hotel Plaza</strong>.</p>
                
                <p class="email-text">Your account has been successfully created with the following information:</p>
                
                <div class="info-box">
                    <div class="info-box-title">Account Information</div>
                    <div class="info-row">
                        <span class="info-label">Full Name</span>
                        <span class="info-value">{$userName}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{$userEmail}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Member Code</span>
                        <span class="info-value">#{$userId}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Registration Date</span>
                        <span class="info-value">{$currentDate}</span>
                    </div>
                </div>
                
                <div class="alert-box" style="background-color: #d1fae5; border-left-color: #059669;">
                    <div class="alert-box-title" style="color: #065f46;">Member Benefits</div>
                    <ul>
                        <li style="color: #065f46;">Earn points with every booking (1 point = 10,000 VND)</li>
                        <li style="color: #065f46;">Exclusive offers for members</li>
                        <li style="color: #065f46;">Upgrade to VIP membership when you earn enough points</li>
                        <li style="color: #065f46;">Receive notifications about promotions</li>
                        <li style="color: #065f46;">Priority support 24/7</li>
                    </ul>
                </div>
                
                <div class="button-wrapper">
                    <a href="{$hotelUrl}/rooms.php" class="email-button">Book Now</a>
                </div>
                
                <p class="email-text">If you have any questions, please don't hesitate to contact us via email or hotline: <strong>(+84-251) 391 8888</strong></p>
            </div>
            
            <div class="email-footer">
                <p class="footer-text" style="font-weight: 600; color: #b8941f; font-size: 14px;">Aurora Hotel Plaza</p>
                <p class="footer-text">253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai</p>
                <p class="footer-text">(+84-251) 391 8888 | info@aurorahotelplaza.com</p>
                <p class="footer-text" style="margin-top: 12px; color: #94a3b8;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Password reset email template
     */
    public static function getPasswordResetTemplate($userName, $resetLink) {
        // Load CSS
        $css = file_get_contents(__DIR__ . '/../includes/email-templates/email-styles.css');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Reset Password</h1>
                <p>Aurora Hotel Plaza</p>
            </div>
            
            <div class="email-content">
                <p class="email-greeting">Xin chào <strong>{$userName}</strong>,</p>
                
                <p class="email-text">We received a password reset request for your account. Click the button below to create a new password:</p>
                
                <div class="button-wrapper">
                    <a href="{$resetLink}" class="email-button">Reset Password</a>
                </div>
                
                <p class="email-text" style="font-size: 13px; color: #64748b;">
                    Or copy the link below into your browser:<br>
                    <a href="{$resetLink}" style="color: #b8941f; word-break: break-all;">{$resetLink}</a>
                </p>
                
                <div class="alert-box" style="background-color: #fef3c7; border-left-color: #d97706;">
                    <div class="alert-box-title" style="color: #92400e;">Important Notes</div>
                    <ul>
                        <li style="color: #92400e;">This link is valid for <strong>1 hour</strong>.</li>
                        <li style="color: #92400e;">If you did not request a password reset, please ignore this email.</li>
                        <li style="color: #92400e;">Do not share this link with anyone.</li>
                    </ul>
                </div>
            </div>
            
            <div class="email-footer">
                <p class="footer-text" style="color: #64748b; font-size: 12px; margin-bottom: 8px;">This is an automated email, please do not reply directly.</p>
                <p class="footer-text" style="font-weight: 600; color: #b8941f; font-size: 14px;">Aurora Hotel Plaza</p>
                <p class="footer-text" style="margin-top: 12px; color: #94a3b8;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Temporary password email template
     */
    public static function getTemporaryPasswordTemplate($userName, $tempPassword) {
        $currentDate = date('d/m/Y H:i');
        $hotelUrl = self::getBaseUrl();
        
        // Load CSS with fallback
        $cssFile = __DIR__ . '/../includes/email-templates/email-styles.css';
        $css = '';
        if (file_exists($cssFile)) {
            $css = @file_get_contents($cssFile);
        }
        if (empty($css)) {
            $css = 'body{font-family:Arial,sans-serif;margin:0;padding:0;background:#f1f5f9}.email-wrapper{padding:20px}.email-container{max-width:620px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06)}.email-header{background:linear-gradient(135deg,#d4af37,#b8941f);color:#fff;padding:40px 30px;text-align:center}.email-header h1{margin:0;font-size:26px;font-weight:700}.email-content{padding:36px 32px}.email-greeting{font-size:16px;margin-bottom:18px;color:#1e293b}.email-text{color:#475569;line-height:1.7;font-size:15px}.info-box{background:#f8fafc;border-left:3px solid #d4af37;padding:20px;margin:24px 0;border-radius:0 8px 8px 0}.info-box-title{font-weight:600;margin-bottom:14px;color:#1e293b}.button-wrapper{text-align:center;margin:28px 0}.email-button{display:inline-block;background:linear-gradient(135deg,#d4af37,#b8941f);color:#fff;padding:13px 30px;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;box-shadow:0 4px 12px rgba(212,175,55,0.3)}.alert-box{background:#fef3c7;border-left:3px solid #d97706;padding:18px 20px;margin:24px 0;border-radius:0 8px 8px 0}.alert-box-title{font-weight:600;color:#92400e}.email-footer{background:#f8fafc;padding:28px 32px;text-align:center;border-top:1px solid #e2e8f0}.footer-text{margin:4px 0;color:#94a3b8;font-size:13px}';
        }
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporary Password - Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Temporary Password</h1>
                <p>Aurora Hotel Plaza</p>
            </div>
            
            <div class="email-content">
                <p class="email-greeting">Xin chào <strong>{$userName}</strong>,</p>
                
                <p class="email-text">Below is the temporary password for your account:</p>
                
                <div class="info-box" style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.08) 0%, rgba(184, 148, 31, 0.05) 100%); border: 2px dashed #d4af37; text-align: center; border-radius: 10px;">
                    <div class="info-box-title" style="color: #78716c; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Temporary Password</div>
                    <div style="font-size: 24px; font-weight: 700; color: #b8941f; letter-spacing: 3px; padding: 8px 0; font-family: 'Courier New', monospace;">
                        {$tempPassword}
                    </div>
                </div>
                
                <p class="email-text">Please use this password to log in and change your password immediately.</p>
                
                <div class="button-wrapper">
                    <a href="{$hotelUrl}/auth/login.php" class="email-button">Login Now</a>
                </div>
                
                <div class="alert-box" style="background-color: #fef3c7; border-left-color: #d97706;">
                    <div class="alert-box-title" style="color: #92400e;">Important Notes</div>
                    <ul>
                        <li style="color: #92400e;">This temporary password is valid for <strong>30 minutes</strong>.</li>
                        <li style="color: #92400e;">Please change your password immediately after logging in.</li>
                        <li style="color: #92400e;">If you did not request a password reset, please ignore this email.</li>
                    </ul>
                </div>
                
                <p class="email-text">If you have any questions, please contact us via hotline: <strong>(+84-251) 391 8888</strong></p>
            </div>
            
            <div class="email-footer">
                <p class="footer-text" style="color: #64748b; font-size: 12px; margin-bottom: 8px;">This is an automated email, please do not reply directly.</p>
                <p class="footer-text" style="font-weight: 600; color: #b8941f; font-size: 14px;">Aurora Hotel Plaza</p>
                <p class="footer-text">(+84-251) 391 8888 | info@aurorahotelplaza.com</p>
                <p class="footer-text" style="margin-top: 12px; color: #94a3b8;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Booking confirmation email template (Simple version - for backward compatibility)
     * For detailed template, use getBookingConfirmationEmailHTML in includes/email-templates/booking-confirmation.php
     */
    public static function getBookingConfirmationTemplate($bookingData) {
        $checkIn = date('d/m/Y', strtotime($bookingData['check_in_date']));
        $checkOut = date('d/m/Y', strtotime($bookingData['check_out_date']));
        $hotelUrl = self::getBaseUrl();

        $full_code = $bookingData['booking_code'];
        $prefix = substr($full_code, 0, -6);
        $suffix = substr($full_code, -6);
        $highlighted_code = htmlspecialchars($prefix) . '<span style="background-color: #d4af37; color: #000; padding: 2px 6px; border-radius: 4px; font-weight: 700;">' . htmlspecialchars($suffix) . '</span>';
        
        $css = file_get_contents(__DIR__ . '/../includes/email-templates/email-styles.css');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Request Submitted - Aurora Hotel Plaza</title>
    <style>{$css}</style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f1f5f9;">
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Booking Request Submitted</h1>
                <p>Aurora Hotel Plaza</p>
            </div>
            
            <div class="email-content">
                <p class="email-text">Your booking request has been <strong style="color: #059669;">successfully submitted</strong> to <strong>Aurora Hotel Plaza</strong>. Our reception team will confirm as soon as possible.</p>
                
                <div class="info-box">
                    <div class="info-box-title">Booking Information</div>
                    <div class="info-row">
                        <span class="info-label">Booking Code</span>
                        <span class="info-value">
                            {$highlighted_code}
                            <br>
                            <span style="font-size: 11px; color: #78716c; font-style: italic;">
                                * Short code: <strong>{$suffix}</strong>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Room Type</span>
                        <span class="info-value">{$bookingData['room_type_name']}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-in Date</span>
                        <span class="info-value">{$checkIn}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-out Date</span>
                        <span class="info-value">{$checkOut}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Number of Nights</span>
                        <span class="info-value">{$bookingData['num_nights']} nights</span>
                    </div>
                </div>
                
                <div class="alert-box" style="background-color: #d1fae5; border-left-color: #059669;">
                    <div class="alert-box-title" style="color: #065f46;">Status: Pending Confirmation</div>
                    <p style="font-size: 14px; color: #475569; margin: 8px 0;">Hotel staff will contact you to confirm via phone or email. Pricing and payment details will be provided after confirmation.</p>
                </div>
                
                <div class="button-wrapper">
                    <a href="{$hotelUrl}/booking/confirmation.php?booking_code={$bookingData['booking_code']}" class="email-button">View Booking Details</a>
                </div>
            </div>
            
            <div class="email-footer">
                <p class="footer-text" style="font-weight: 600; color: #b8941f; font-size: 14px;">Aurora Hotel Plaza</p>
                <p class="footer-text" style="margin-top: 12px; color: #94a3b8;">© 2025 Aurora Hotel Plaza. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Get base URL - Uses function from environment.php
     */
    private static function getBaseUrl() {
        return getBaseUrl();
    }
}
