<?php
/**
 * Email Helper using PHPMailer
 * Uses PHPMailer library from config/PHPMailler
 */

require_once __DIR__ . '/../config/environment.php';
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
    private $lastError = '';
    
    public function __construct() {
        try {
            $this->mail = new PHPMailer(true);
            $this->configure();
            $this->isConfigured = true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Mailer initialization error: " . $this->lastError);
            $this->isConfigured = false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Mailer PHP exception: " . $this->lastError);
            $this->isConfigured = false;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("Mailer fatal error: " . $this->lastError);
            $this->isConfigured = false;
        }
    }
    
    public function getLastError() {
        return $this->lastError;
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
            
            // Bypass SSL Verification for local environments
            $this->mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Timeout settings for faster response
            $this->mail->Timeout = 10; // 10 seconds timeout
            $this->mail->SMTPKeepAlive = false; // Don't keep connection alive
            
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
        if (!$this->isConfigured || !$this->mail) {
            $this->lastError = "Mailer not configured properly";
            error_log($this->lastError);
            return false;
        }
        
        try {
            // Clear previous recipients
            $this->mail->clearAddresses();
            $this->mail->clearAllRecipients();
            
            // Handle multiple recipients (comma separated)
            $emails = explode(',', $to);
            foreach ($emails as $email) {
                $email = trim($email);
                if (!empty($email)) {
                    $this->mail->addAddress($email);
                }
            }
            
            // Set subject and body
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = $altBody ?: strip_tags($body);
            
            // Send email with timeout
            $result = $this->mail->send();
            
            if ($result) {
                error_log("Email sent successfully to: {$to}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->lastError = $this->mail->ErrorInfo ?: $e->getMessage();
            error_log("Email send PHPMailer error: " . $this->lastError);
            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Email send PHP error: " . $this->lastError);
            return false;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("Email send fatal error: " . $this->lastError);
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
            $subject = "Welcome to Aurora Hotel Plaza!";
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
            $subject = "Reset Password - Aurora Hotel Plaza";
            
            // Build reset link - Uses url() function from environment.php
            $resetLink = url("auth/reset-password.php?token=" . urlencode($resetToken));
            
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
            require_once __DIR__ . '/../includes/email-templates/booking-confirmation-noprice.php';
            
            $hotel_info = [
                'name' => 'Aurora Hotel Plaza',
                'address' => '253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai',
                'phone' => '(+84-251) 391 8888',
                'email' => 'info@aurorahotelplaza.com',
                'website' => 'https://aurorahotelplaza.com'
            ];
            
            $bookingData['total_amount_formatted'] = number_format($bookingData['total_amount'], 0, ',', '.');
            
            $subject = "Booking Request Submitted #{$bookingData['booking_code']} - Aurora Hotel Plaza";
            $body = getBookingConfirmationNoPriceEmailHTML($bookingData, $hotel_info);
            $altBody = getBookingConfirmationNoPriceEmailText($bookingData, $hotel_info);
            
            return $this->send($userEmail, $subject, $body, $altBody);
            
        } catch (Exception $e) {
            error_log("Booking confirmation email error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Send booking notification to Hotel staff (multiple recipients)
     * using the Customer's email as From/Reply-To
     * 
     * @param string $guestEmail Customer email
     * @param string $guestName Customer name
     * @param array $bookingData Booking info
     * @return bool Success status
     */
    public function sendBookingNotificationToHotel($guestEmail, $guestName, $bookingData) {
        try {
            require_once __DIR__ . '/../includes/email-templates/booking-confirmation.php';
            
            $hotel_info = [
                'name' => 'Aurora Hotel Plaza',
                'address' => '253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai',
                'phone' => '(+84-251) 391 8888',
                'email' => 'info@aurorahotelplaza.com',
                'website' => 'https://aurorahotelplaza.com'
            ];
            
            $bookingData['total_amount_formatted'] = number_format($bookingData['total_amount'], 0, ',', '.');
            
            $hotelEmail = defined('HOTEL_RECEIVE_EMAIL') ? HOTEL_RECEIVE_EMAIL : 'info@aurorahotelplaza.com';
            $subject = "[New Booking #{$bookingData['booking_code']}] {$bookingData['type_name']} - Guest: {$guestName}";
            
            // Re-use the same beautiful template but send to hotel
            $body = getBookingConfirmationEmailHTML($bookingData, $hotel_info);
            $altBody = getBookingConfirmationEmailText($bookingData, $hotel_info);
            
            // Spoof sender exactly like Contact module
            $this->setCustomFrom($guestEmail, $guestName);
            $this->setCustomReplyTo($guestEmail, $guestName);
            
            $result = $this->send($hotelEmail, $subject, $body, $altBody);
            
            $this->resetFrom();
            $this->resetReplyTo();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Booking notification to hotel error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send temporary password email
     * 
     * @param string $userEmail User email address
     * @param string $userName User full name
     * @param string $tempPassword Temporary password
     * @return bool Success status
     */
    public function sendTemporaryPassword($userEmail, $userName, $tempPassword) {
        try {
            $subject = "Temporary Password - Aurora Hotel Plaza";
            
            // Try to get template, fallback to simple HTML if fails
            try {
                $body = EmailTemplates::getTemporaryPasswordTemplate($userName, $tempPassword);
            } catch (\Throwable $templateErr) {
                error_log("Template error, using fallback: " . $templateErr->getMessage());
                // Simple fallback template
                $body = "
                <html><body style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2>Temporary Password - Aurora Hotel Plaza</h2>
                <p>Hello <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                <p>Your temporary password is: <strong style='font-size: 18px; color: #2196f3;'>" . htmlspecialchars($tempPassword) . "</strong></p>
                <p>This password is valid for 30 minutes.</p>
                <p>Please log in and change your password immediately.</p>
                <hr><p style='color: #666; font-size: 12px;'>Aurora Hotel Plaza</p>
                </body></html>";
            }
            
            return $this->send($userEmail, $subject, $body);
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Temporary password email error: " . $this->lastError);
            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Temporary password PHP error: " . $this->lastError);
            return false;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("Temporary password fatal error: " . $this->lastError);
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
    
    /**
     * Set custom From address dynamically
     * Note: May be overwritten by the SMTP server (like Gmail)
     * 
     * @param string $email
     * @param string $name
     * @return bool
     */
    public function setCustomFrom($email, $name = '') {
        try {
            if ($this->mail) {
                $this->mail->setFrom($email, $name);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Set Custom From error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset From address to default configuration
     * @return bool
     */
    public function resetFrom() {
        try {
            if ($this->mail) {
                $this->mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Set custom Reply-To address dynamically
     * 
     * @param string $email
     * @param string $name
     * @return bool
     */
    public function setCustomReplyTo($email, $name = '') {
        try {
            if ($this->mail) {
                $this->mail->clearReplyTos();
                $this->mail->addReplyTo($email, $name);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Set Custom Reply-To error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset Reply-To address to default configuration
     * @return bool
     */
    public function resetReplyTo() {
        try {
            if ($this->mail) {
                $this->mail->clearReplyTos();
                $this->mail->addReplyTo(MAIL_REPLY_TO, MAIL_REPLY_NAME);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
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
