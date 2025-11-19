<?php
/**
 * Email Helper for Aurora Hotel
 * Handles email sending and templates
 */

// Load environment helper for URL functions
require_once __DIR__ . '/../config/environment.php';

class EmailHelper {
    private $from_email;
    private $from_name;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    
    public function __construct() {
        // Load email configuration
        require_once __DIR__ . '/../config/email.php';
        
        $this->from_email = FROM_EMAIL;
        $this->from_name = FROM_NAME;
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->smtp_username = SMTP_USERNAME;
        $this->smtp_password = SMTP_PASSWORD;
    }
    
    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation($booking_data) {
        $to = $booking_data['guest_email'];
        $subject = 'Xác nhận đặt phòng - Mã: ' . $booking_data['booking_code'];
        
        $html_content = $this->getBookingConfirmationTemplate($booking_data);
        $text_content = $this->getBookingConfirmationText($booking_data);
        
        return $this->sendEmail($to, $subject, $html_content, $text_content);
    }
    
    /**
     * Send booking update email
     */
    public function sendBookingUpdate($booking_data, $old_status, $new_status) {
        $to = $booking_data['guest_email'];
        $subject = 'Cập nhật đặt phòng - Mã: ' . $booking_data['booking_code'];
        
        $html_content = $this->getBookingUpdateTemplate($booking_data, $old_status, $new_status);
        $text_content = $this->getBookingUpdateText($booking_data, $old_status, $new_status);
        
        return $this->sendEmail($to, $subject, $html_content, $text_content);
    }
    
    /**
     * Send payment confirmation email
     */
    public function sendPaymentConfirmation($booking_data, $payment_data) {
        $to = $booking_data['guest_email'];
        $subject = 'Xác nhận thanh toán - Mã: ' . $booking_data['booking_code'];
        
        $html_content = $this->getPaymentConfirmationTemplate($booking_data, $payment_data);
        $text_content = $this->getPaymentConfirmationText($booking_data, $payment_data);
        
        return $this->sendEmail($to, $subject, $html_content, $text_content);
    }
    
    /**
     * Get booking confirmation HTML template
     */
    private function getBookingConfirmationTemplate($booking) {
        $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
        $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
        $booking_date = date('d/m/Y H:i', strtotime($booking['created_at']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Xác nhận đặt phòng</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .booking-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .info-row:last-child { border-bottom: none; }
                .label { font-weight: bold; color: #666; }
                .value { color: #333; }
                .total { background: #d4af37; color: white; padding: 15px; text-align: center; border-radius: 8px; font-size: 18px; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .button { display: inline-block; background: #d4af37; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Aurora Hotel Plaza</h1>
                    <h2>Xác nhận đặt phòng</h2>
                </div>
                
                <div class='content'>
                    <p>Kính chào <strong>{$booking['guest_name']}</strong>,</p>
                    
                    <p>Cảm ơn bạn đã chọn Aurora Hotel Plaza. Chúng tôi xác nhận đã nhận được đặt phòng của bạn với thông tin như sau:</p>
                    
                    <div class='booking-info'>
                        <h3 style='color: #d4af37; margin-top: 0;'>Thông tin đặt phòng</h3>
                        
                        <div class='info-row'>
                            <span class='label'>Mã đặt phòng:</span>
                            <span class='value'><strong>{$booking['booking_code']}</strong></span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>Loại phòng:</span>
                            <span class='value'>{$booking['type_name']}</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>Ngày nhận phòng:</span>
                            <span class='value'>{$check_in}</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>Ngày trả phòng:</span>
                            <span class='value'>{$check_out}</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>Số đêm:</span>
                            <span class='value'>{$booking['total_nights']} đêm</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>Số khách:</span>
                            <span class='value'>{$booking['num_adults']} người</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>Ngày đặt:</span>
                            <span class='value'>{$booking_date}</span>
                        </div>
                    </div>
                    
                    <div class='total'>
                        Tổng tiền: " . number_format($booking['total_amount']) . " VNĐ
                    </div>
                    
                    <p><strong>Thông tin liên hệ:</strong></p>
                    <ul>
                        <li>Họ tên: {$booking['guest_name']}</li>
                        <li>Email: {$booking['guest_email']}</li>
                        <li>Điện thoại: {$booking['guest_phone']}</li>
                    </ul>
                    
                    " . ($booking['special_requests'] ? "<p><strong>Yêu cầu đặc biệt:</strong><br>{$booking['special_requests']}</p>" : "") . "
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . url("profile/booking-detail.php?code={$booking['booking_code']}") . "' class='button'>
                            Xem chi tiết đặt phòng
                        </a>
                    </div>
                    
                    <p><strong>Lưu ý quan trọng:</strong></p>
                    <ul>
                        <li>Vui lòng mang theo CMND/CCCD khi nhận phòng</li>
                        <li>Thời gian nhận phòng: 14:00 - 22:00</li>
                        <li>Thời gian trả phòng: 06:00 - 12:00</li>
                        <li>Liên hệ: +84 123 456 789 nếu cần hỗ trợ</li>
                    </ul>
                    
                    <p>Chúng tôi rất mong được phục vụ bạn tại Aurora Hotel Plaza!</p>
                    
                    <p>Trân trọng,<br>
                    <strong>Đội ngũ Aurora Hotel Plaza</strong></p>
                </div>
                
                <div class='footer'>
                    <p>Aurora Hotel Plaza - Khách sạn sang trọng tại Biên Hòa</p>
                    <p>Email: info@aurorahotel.com | Hotline: +84 123 456 789</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get booking confirmation text template
     */
    private function getBookingConfirmationText($booking) {
        $check_in = date('d/m/Y', strtotime($booking['check_in_date']));
        $check_out = date('d/m/Y', strtotime($booking['check_out_date']));
        $booking_date = date('d/m/Y H:i', strtotime($booking['created_at']));
        
        return "
AURORA HOTEL PLAZA - XÁC NHẬN ĐẶT PHÒNG

Kính chào {$booking['guest_name']},

Cảm ơn bạn đã chọn Aurora Hotel Plaza. Chúng tôi xác nhận đã nhận được đặt phòng của bạn:

THÔNG TIN ĐẶT PHÒNG:
- Mã đặt phòng: {$booking['booking_code']}
- Loại phòng: {$booking['type_name']}
- Ngày nhận phòng: {$check_in}
- Ngày trả phòng: {$check_out}
- Số đêm: {$booking['total_nights']} đêm
- Số khách: {$booking['num_adults']} người
- Ngày đặt: {$booking_date}

TỔNG TIỀN: " . number_format($booking['total_amount']) . " VNĐ

THÔNG TIN LIÊN HỆ:
- Họ tên: {$booking['guest_name']}
- Email: {$booking['guest_email']}
- Điện thoại: {$booking['guest_phone']}

" . ($booking['special_requests'] ? "YÊU CẦU ĐẶC BIỆT: {$booking['special_requests']}\n\n" : "") . "

LƯU Ý QUAN TRỌNG:
- Vui lòng mang theo CMND/CCCD khi nhận phòng
- Thời gian nhận phòng: 14:00 - 22:00
- Thời gian trả phòng: 06:00 - 12:00
- Liên hệ: +84 123 456 789 nếu cần hỗ trợ

Tra cứu đặt phòng: " . url("profile/booking-detail.php?code={$booking['booking_code']}") . "

Chúng tôi rất mong được phục vụ bạn tại Aurora Hotel Plaza!

Trân trọng,
Đội ngũ Aurora Hotel Plaza
        ";
    }
    
    /**
     * Get booking update templates
     */
    private function getBookingUpdateTemplate($booking, $old_status, $new_status) {
        $status_messages = [
            'confirmed' => 'Đặt phòng của bạn đã được xác nhận!',
            'cancelled' => 'Đặt phòng của bạn đã bị hủy.',
            'checked_in' => 'Bạn đã nhận phòng thành công!',
            'checked_out' => 'Cảm ơn bạn đã lưu trú tại Aurora Hotel Plaza!'
        ];
        
        $message = $status_messages[$new_status] ?? 'Trạng thái đặt phòng đã được cập nhật.';
        
        return str_replace(
            ['Xác nhận đặt phòng', 'Cảm ơn bạn đã chọn Aurora Hotel Plaza. Chúng tôi xác nhận đã nhận được đặt phòng của bạn với thông tin như sau:'],
            ['Cập nhật đặt phòng', $message . ' Thông tin đặt phòng của bạn:'],
            $this->getBookingConfirmationTemplate($booking)
        );
    }
    
    /**
     * Get booking update text
     */
    private function getBookingUpdateText($booking, $old_status, $new_status) {
        $status_messages = [
            'confirmed' => 'Đặt phòng của bạn đã được xác nhận!',
            'cancelled' => 'Đặt phòng của bạn đã bị hủy.',
            'checked_in' => 'Bạn đã nhận phòng thành công!',
            'checked_out' => 'Cảm ơn bạn đã lưu trú tại Aurora Hotel Plaza!'
        ];
        
        $message = $status_messages[$new_status] ?? 'Trạng thái đặt phòng đã được cập nhật.';
        
        return str_replace(
            ['XÁC NHẬN ĐẶT PHÒNG', 'Cảm ơn bạn đã chọn Aurora Hotel Plaza. Chúng tôi xác nhận đã nhận được đặt phòng của bạn:'],
            ['CẬP NHẬT ĐẶT PHÒNG', $message . ' Thông tin đặt phòng của bạn:'],
            $this->getBookingConfirmationText($booking)
        );
    }
    
    /**
     * Get payment confirmation templates
     */
    private function getPaymentConfirmationTemplate($booking, $payment) {
        $payment_date = date('d/m/Y H:i', strtotime($payment['paid_at']));
        
        $payment_content = "
        <div class='booking-info'>
            <h3 style='color: #d4af37; margin-top: 0;'>Thông tin thanh toán</h3>
            
            <div class='info-row'>
                <span class='label'>Phương thức:</span>
                <span class='value'>" . ucfirst($payment['payment_method']) . "</span>
            </div>
            
            <div class='info-row'>
                <span class='label'>Mã giao dịch:</span>
                <span class='value'>{$payment['transaction_id']}</span>
            </div>
            
            <div class='info-row'>
                <span class='label'>Thời gian:</span>
                <span class='value'>{$payment_date}</span>
            </div>
            
            <div class='info-row'>
                <span class='label'>Số tiền:</span>
                <span class='value'><strong>" . number_format($payment['amount']) . " VNĐ</strong></span>
            </div>
        </div>
        ";
        
        return str_replace(
            ['Xác nhận đặt phòng', 'Cảm ơn bạn đã chọn Aurora Hotel Plaza. Chúng tôi xác nhận đã nhận được đặt phòng của bạn với thông tin như sau:', '<div class=\'booking-info\'>'],
            ['Xác nhận thanh toán', 'Thanh toán của bạn đã được xử lý thành công! Thông tin chi tiết:', $payment_content . '<div class=\'booking-info\'>'],
            $this->getBookingConfirmationTemplate($booking)
        );
    }
    
    /**
     * Get payment confirmation text
     */
    private function getPaymentConfirmationText($booking, $payment) {
        $payment_date = date('d/m/Y H:i', strtotime($payment['paid_at']));
        
        $payment_text = "
            THÔNG TIN THANH TOÁN:
            - Phương thức: " . ucfirst($payment['payment_method']) . "
            - Mã giao dịch: {$payment['transaction_id']}
            - Thời gian: {$payment_date}
            - Số tiền: " . number_format($payment['amount']) . " VNĐ";
        
        return str_replace(
            ['XÁC NHẬN ĐẶT PHÒNG', 'Cảm ơn bạn đã chọn Aurora Hotel Plaza. Chúng tôi xác nhận đã nhận được đặt phòng của bạn:', 'THÔNG TIN ĐẶT PHÒNG:'],
            ['XÁC NHẬN THANH TOÁN', 'Thanh toán của bạn đã được xử lý thành công! Thông tin chi tiết:', $payment_text . 'THÔNG TIN ĐẶT PHÒNG:'],
            $this->getBookingConfirmationText($booking)
        );
    }
    
    /**
     * Send email using PHP mail() function
     * In production, use PHPMailer or similar library for better reliability
     */
    private function sendEmail($to, $subject, $html_content, $text_content = '') {
        try {
            // Log email to database
            $this->logEmail($to, $subject, 'booking_confirmation');
            
            // For development, just log the email content
            if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
                error_log("EMAIL TO: $to");
                error_log("EMAIL SUBJECT: $subject");
                error_log("EMAIL CONTENT: " . substr(strip_tags($html_content), 0, 200) . "...");
                return true;
            }
            
            // Headers for HTML email
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $this->from_name . ' <' . $this->from_email . '>',
                'Reply-To: ' . $this->from_email,
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // Send email
            $result = mail($to, $subject, $html_content, implode("\r\n", $headers));
            
            if ($result) {
                $this->updateEmailStatus($to, $subject, 'sent');
            } else {
                $this->updateEmailStatus($to, $subject, 'failed', 'Mail function returned false');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            $this->updateEmailStatus($to, $subject, 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log email to database
     */
    private function logEmail($recipient, $subject, $template) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = getDB();
            
            $stmt = $db->prepare("
                INSERT INTO email_logs (recipient, subject, template, status, created_at) 
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$recipient, $subject, $template]);
            
        } catch (Exception $e) {
            error_log("Email logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Update email status in database
     */
    private function updateEmailStatus($recipient, $subject, $status, $error_message = null) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = getDB();
            
            $stmt = $db->prepare("
                UPDATE email_logs 
                SET status = ?, error_message = ?, sent_at = NOW() 
                WHERE recipient = ? AND subject = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$status, $error_message, $recipient, $subject]);
            
        } catch (Exception $e) {
            error_log("Email status update error: " . $e->getMessage());
        }
    }
}

/**
 * Get email helper instance
 */
function getEmailHelper() {
    return new EmailHelper();
}
?>