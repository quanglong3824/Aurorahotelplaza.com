<?php
/**
 * Email Helper Functions
 * Handles sending emails using PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load email configuration
require_once __DIR__ . '/../config/email.php';

// Load language helper for email translations
if (!function_exists('__')) {
    require_once __DIR__ . '/../helpers/language.php';
    initLanguage();
}

// Load PHPMailer if not already loaded
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // Try to load via composer autoload
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }
}

/**
 * Send email using PHPMailer
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '', $fromName = 'Aurora Hotel Plaza') {
    try {
        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // Fallback to mail() function
            return sendEmailFallback($to, $subject, $htmlBody, $textBody, $fromName);
        }
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = !empty(SMTP_USERNAME);
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, $fromName);
        $mail->addAddress($to);
        $mail->addReplyTo(REPLY_TO_EMAIL, $fromName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $e->getMessage()];
    }
}

/**
 * Fallback email sending using PHP mail() function
 */
function sendEmailFallback($to, $subject, $htmlBody, $textBody = '', $fromName = 'Aurora Hotel Plaza') {
    // Check if email is enabled
    if (!defined('EMAIL_ENABLED') || !EMAIL_ENABLED) {
        error_log("Email sending is disabled in configuration");
        return ['success' => false, 'message' => 'Email sending is disabled'];
    }
    
    $fromEmail = FROM_EMAIL;
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: " . REPLY_TO_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $success = mail($to, $subject, $htmlBody, $headers);
    
    if ($success) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        error_log("Email sending failed using mail() function");
        return ['success' => false, 'message' => 'Email could not be sent using mail() function'];
    }
}

/**
 * Send booking confirmation email
 */
function sendBookingConfirmationEmail($booking) {
    require_once __DIR__ . '/email-templates/booking-confirmation.php';
    
    $hotel_info = [
        'name' => 'Aurora Hotel Plaza',
        'address' => '123 Đường ABC, Quận 1, TP.HCM',
        'phone' => '(028) 1234 5678',
        'email' => 'info@aurorahotelplaza.com',
        'website' => 'https://aurorahotelplaza.com'
    ];
    
    // Format total amount
    $booking['total_amount_formatted'] = number_format($booking['total_amount'], 0, ',', '.');
    
    $htmlBody = getBookingConfirmationEmailHTML($booking, $hotel_info);
    $textBody = getBookingConfirmationEmailText($booking, $hotel_info);
    
    $subject = "Xác nhận đặt phòng #{$booking['booking_code']} - Aurora Hotel Plaza";
    
    return sendEmail($booking['guest_email'], $subject, $htmlBody, $textBody);
}

/**
 * Send booking status update email
 */
function sendBookingStatusUpdateEmail($booking, $old_status, $new_status) {
    $status_messages = [
        'confirmed' => [
            'subject' => __('email.booking_confirmed_subject'),
            'title' => '✅ ' . __('email.booking_confirmed_title'),
            'message' => __('email.booking_confirmed_message')
        ],
        'cancelled' => [
            'subject' => __('email.booking_cancelled_subject'),
            'title' => '❌ ' . __('email.booking_cancelled_title'),
            'message' => __('email.booking_cancelled_message')
        ],
        'checked_in' => [
            'subject' => __('email.booking_checked_in_subject'),
            'title' => '🏨 ' . __('email.booking_checked_in_title'),
            'message' => __('email.booking_checked_in_message')
        ]
    ];
    
    if (!isset($status_messages[$new_status])) {
        return ['success' => false, 'message' => 'Invalid status'];
    }
    
    $info = $status_messages[$new_status];
    $booking['total_amount_formatted'] = number_format($booking['total_amount'], 0, ',', '.');
    $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
    $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
    
    $qr_section = '';
    if ($new_status === 'confirmed') {
        $qr_title = __('email.download_qr');
        $qr_instruction = __('email.qr_instruction');
        $view_bookings = __('email.view_my_bookings');
        $qr_section = <<<HTML
        <div style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <h3 style="margin: 0 0 10px; color: #1976D2; font-size: 16px;">{$qr_title}</h3>
            <p>{$qr_instruction}</p>
            <p style="text-align: center; margin-top: 15px;">
                <a href="https://aurorahotelplaza.com/profile/bookings.php" 
                   style="display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">
                    {$view_bookings}
                </a>
            </p>
        </div>
HTML;
    }
    
    // Get translated labels
    $dear = __('email.dear');
    $booking_code_label = __('email.booking_code');
    $booking_info_label = __('email.booking_info');
    $room_type_label = __('email.room_type');
    $check_in_label = __('email.check_in_date');
    $check_out_label = __('email.check_out_date');
    $nights_label = __('email.num_nights');
    $night_unit = __('email.night');
    $total_label = __('email.total_cost');
    $contact_note = __('email.contact_note');
    $regards = __('email.regards');
    $team = __('email.team');
    $auto_note = __('email.auto_email_note');
    $copyright = __('email.copyright');
    $lang = getLang();
    
    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$info['subject']}</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;">
    <div style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;">
            <h1 style="margin: 0; font-size: 28px; font-weight: 600;">🏨 Aurora Hotel Plaza</h1>
            <p style="margin: 10px 0 0; font-size: 16px; opacity: 0.9;">{$info['subject']}</p>
        </div>
        
        <div style="padding: 30px 20px;">
            <h2 style="color: #667eea; font-size: 20px;">{$info['title']}</h2>
            
            <p>{$dear} <strong>{$booking['guest_name']}</strong>,</p>
            
            <p>{$info['message']}</p>
            
            <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <div style="margin-bottom: 5px;">{$booking_code_label}:</div>
                <strong style="color: #667eea; font-size: 20px; font-family: 'Courier New', monospace;">{$booking['booking_code']}</strong>
            </div>
            
            <div style="margin: 25px 0;">
                <h3 style="color: #667eea; font-size: 16px; margin-bottom: 10px;">{$booking_info_label}:</h3>
                <p style="margin: 5px 0;"><strong>{$room_type_label}:</strong> {$booking['type_name']}</p>
                <p style="margin: 5px 0;"><strong>{$check_in_label}:</strong> {$check_in}</p>
                <p style="margin: 5px 0;"><strong>{$check_out_label}:</strong> {$check_out}</p>
                <p style="margin: 5px 0;"><strong>{$nights_label}:</strong> {$booking['total_nights']} {$night_unit}</p>
                <p style="margin: 5px 0;"><strong>{$total_label}:</strong> {$booking['total_amount_formatted']} VNĐ</p>
            </div>
            
            {$qr_section}
            
            <p style="margin-top: 30px;">{$contact_note}</p>
            
            <p style="margin-top: 20px;">{$regards},<br><strong>{$team}</strong></p>
        </div>
        
        <div style="background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px;">
            <p>{$auto_note}</p>
            <p>© 2025 {$copyright}</p>
        </div>
    </div>
</body>
</html>
HTML;
    
    $subject = "{$info['subject']} - Mã đặt phòng #{$booking['booking_code']}";
    
    return sendEmail($booking['guest_email'], $subject, $htmlBody);
}
?>
