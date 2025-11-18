<?php
/**
 * Email Helper using PHPMailer
 * Sử dụng thư viện PHPMailer từ config/PHPMailler
 */

require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../config/PHPMailler/PHPMailer.php';
require_once __DIR__ . '/../config/PHPMailler/SMTP.php';
require_once __DIR__ . '/../config/PHPMailler/Exception.php';
require_once __DIR__ . '/email-templates.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mail;
    private $isConfigured = false;
    
    public function __construct() {
        try {
            $this->mail = new PHPMailer(true);
            $this->configure();
            $this->isConfigured = true;
        } catch (Exception $e) {
            error_log("Mailer initialization error: " . $e->getMessage());
            $this->isConfigured = false;
        }
    }
    
    /**
     * Configure PHPMailer with SMTP settings
     */
    private function configure() {
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = SMTP_HOST;
            $this->mail->SMTPAuth = SMTP_AUTH;
            $this->mail->Username = SMTP_USERNAME;
            $this->mail->Password = SMTP_PASSWORD;
            $this->mail->SMTPSecure = SMTP_SECURE;
            $this->mail->Port = SMTP_PORT;
            $this->mail->CharSet = MAIL_CHARSET;
            
            // Debug level
            $this->mail->SMTPDebug = MAIL_DEBUG;
            
            // Sender information
            $this->mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $this->mail->addReplyTo(MAIL_REPLY_TO, MAIL_REPLY_NAME);
            
            // HTML format
            $this->mail->isHTML(true);
            
        } catch (Exception $e) {
            error_log("Mailer configuration error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if mailer is properly configured
     */
    public function isReady() {
        return $this->isConfigured;
    }
    
    /**
     * Send email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body HTML body
     * @param string $altBody Plain text alternative
     * @return bool Success status
     */
    public function send($to, $subject, $body, $altBody = '') {
        if (!$this->isConfigured) {
            error_log("Mailer not configured properly");
            return false;
        }
        
        try {
            // Clear previous recipients
            $this->mail->clearAddresses();
            
            // Set recipient
            $this->mail->addAddress($to);
            
            // Set subject and body
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = $altBody ?: strip_tags($body);
            
            // Send email
            $result = $this->mail->send();
            
            if ($result) {
                error_log("Email sent successfully to: {$to}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Email send error: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send welcome email after registration
     * 
     * @param string $userEmail User email address
     * @param string $userName User full name
     * @param int $userId User ID
     * @return bool Success status
     */
    public function sendWelcomeEmail($userEmail, $userName, $userId) {
        try {
            $subject = "Chào mừng đến với Aurora Hotel Plaza! 🎉";
            $body = EmailTemplates::getWelcomeTemplate($userName, $userEmail, $userId);
            
            return $this->send($userEmail, $subject, $body);
            
        } catch (Exception $e) {
            error_log("Welcome email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send password reset email
     * 
     * @param string $userEmail User email address
     * @param string $userName User full name
     * @param string $resetToken Reset token
     * @return bool Success status
     */
    public function sendPasswordReset($userEmail, $userName, $resetToken) {
        try {
            $subject = "Đặt lại mật khẩu - Aurora Hotel Plaza";
            
            // Build reset link
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $resetLink = $protocol . "://" . $host . "/auth/reset-password.php?token=" . urlencode($resetToken);
            
            $body = EmailTemplates::getPasswordResetTemplate($userName, $resetLink);
            
            return $this->send($userEmail, $subject, $body);
            
        } catch (Exception $e) {
            error_log("Password reset email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send booking confirmation email
     * 
     * @param string $userEmail User email address
     * @param array $bookingData Booking information
     * @return bool Success status
     */
    public function sendBookingConfirmation($userEmail, $bookingData) {
        try {
            // Load the detailed booking confirmation template
            require_once __DIR__ . '/../includes/email-templates/booking-confirmation.php';
            
            // Prepare hotel info
            $hotel_info = [
                'name' => 'Aurora Hotel Plaza',
                'address' => 'KP2, Phường Tân Hiệp, Thủ Đông Nai',
                'phone' => '(+84-251) 391 8888',
                'email' => 'info@aurorahotelplaza.com',
                'website' => 'https://aurorahotelplaza.com'
            ];
            
            // Format total amount for display
            $bookingData['total_amount_formatted'] = number_format($bookingData['total_amount'], 0, ',', '.');
            
            $subject = "Xác nhận đặt phòng #{$bookingData['booking_code']} - Aurora Hotel Plaza";
            $body = getBookingConfirmationEmailHTML($bookingData, $hotel_info);
            $altBody = getBookingConfirmationEmailText($bookingData, $hotel_info);
            
            return $this->send($userEmail, $subject, $body, $altBody);
            
        } catch (Exception $e) {
            error_log("Booking confirmation email error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Send custom email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $altBody Plain text alternative
     * @return bool Success status
     */
    public function sendCustom($to, $subject, $body, $altBody = '') {
        return $this->send($to, $subject, $body, $altBody);
    }
}

/**
 * Helper function to get mailer instance
 * 
 * @return Mailer
 */
function getMailer() {
    return new Mailer();
}
