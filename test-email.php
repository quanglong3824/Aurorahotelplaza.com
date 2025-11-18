<?php
/**
 * Test Email Sending
 * Kiểm tra xem hệ thống có gửi được email không
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Email System</h1>";
echo "<hr>";

// Test 1: Check if PHPMailer exists
echo "<h2>1. Kiểm tra PHPMailer</h2>";
$phpmailer_path = __DIR__ . '/config/PHPMailler/PHPMailer.php';
if (file_exists($phpmailer_path)) {
    echo "✅ PHPMailer file exists: $phpmailer_path<br>";
} else {
    echo "❌ PHPMailer file NOT found: $phpmailer_path<br>";
}

// Test 2: Check email config
echo "<h2>2. Kiểm tra Email Config</h2>";
require_once __DIR__ . '/config/email.php';
echo "SMTP_HOST: " . SMTP_HOST . "<br>";
echo "SMTP_PORT: " . SMTP_PORT . "<br>";
echo "SMTP_AUTH: " . (SMTP_AUTH ? 'true' : 'false') . "<br>";
echo "FROM_EMAIL: " . FROM_EMAIL . "<br>";
echo "EMAIL_ENABLED: " . (EMAIL_ENABLED ? 'true' : 'false') . "<br>";

// Test 3: Try to load Mailer class
echo "<h2>3. Kiểm tra Mailer Class</h2>";
try {
    require_once __DIR__ . '/helpers/mailer.php';
    $mailer = getMailer();
    
    if ($mailer->isReady()) {
        echo "✅ Mailer is ready<br>";
    } else {
        echo "❌ Mailer is NOT ready<br>";
    }
} catch (Exception $e) {
    echo "❌ Error loading Mailer: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 4: Check email template
echo "<h2>4. Kiểm tra Email Template</h2>";
$template_path = __DIR__ . '/includes/email-templates/booking-confirmation.php';
if (file_exists($template_path)) {
    echo "✅ Template exists: $template_path<br>";
    require_once $template_path;
    
    // Test template with dummy data
    $dummy_booking = [
        'booking_code' => 'TEST123',
        'guest_name' => 'Nguyễn Văn A',
        'guest_email' => 'test@example.com',
        'guest_phone' => '0123456789',
        'type_name' => 'Deluxe Room',
        'check_in_date' => date('Y-m-d'),
        'check_out_date' => date('Y-m-d', strtotime('+2 days')),
        'total_nights' => 2,
        'num_adults' => 2,
        'total_amount' => 1200000,
        'total_amount_formatted' => '1,200,000'
    ];
    
    $hotel_info = [
        'name' => 'Aurora Hotel Plaza',
        'address' => 'KP2, Phường Tân Hiệp, Thủ Đông Nai',
        'phone' => '(+84-251) 391 8888',
        'email' => 'info@aurorahotelplaza.com',
        'website' => 'https://aurorahotelplaza.com'
    ];
    
    try {
        $html = getBookingConfirmationEmailHTML($dummy_booking, $hotel_info);
        echo "✅ Template generated successfully<br>";
        echo "<details><summary>Xem HTML template</summary><pre>" . htmlspecialchars($html) . "</pre></details>";
    } catch (Exception $e) {
        echo "❌ Template error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Template NOT found: $template_path<br>";
}

// Test 5: Try sending test email
echo "<h2>5. Gửi Email Test</h2>";
echo "<form method='POST'>";
echo "Email nhận: <input type='email' name='test_email' value='your-email@example.com' required><br><br>";
echo "<button type='submit' name='send_test'>Gửi Email Test</button>";
echo "</form>";

if (isset($_POST['send_test']) && !empty($_POST['test_email'])) {
    $test_email = $_POST['test_email'];
    echo "<br><strong>Đang gửi email đến: $test_email</strong><br>";
    
    try {
        require_once __DIR__ . '/helpers/mailer.php';
        require_once __DIR__ . '/includes/email-templates/booking-confirmation.php';
        
        $mailer = getMailer();
        
        $dummy_booking = [
            'booking_code' => 'TEST' . date('YmdHis'),
            'guest_name' => 'Khách Hàng Test',
            'guest_email' => $test_email,
            'guest_phone' => '0123456789',
            'type_name' => 'Phòng Deluxe',
            'check_in_date' => date('Y-m-d'),
            'check_out_date' => date('Y-m-d', strtotime('+2 days')),
            'total_nights' => 2,
            'num_adults' => 2,
            'total_amount' => 1200000,
            'total_amount_formatted' => '1,200,000'
        ];
        
        $result = $mailer->sendBookingConfirmation($test_email, $dummy_booking);
        
        if ($result) {
            echo "✅ <strong style='color: green;'>Email đã được gửi thành công!</strong><br>";
            echo "Vui lòng kiểm tra hộp thư (và cả thư mục spam) của bạn.<br>";
        } else {
            echo "❌ <strong style='color: red;'>Không thể gửi email</strong><br>";
            echo "Kiểm tra PHP error log để xem chi tiết lỗi.<br>";
        }
    } catch (Exception $e) {
        echo "❌ <strong style='color: red;'>Lỗi: " . $e->getMessage() . "</strong><br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Test 6: Check PHP mail function
echo "<h2>6. Kiểm tra PHP mail() function</h2>";
if (function_exists('mail')) {
    echo "✅ PHP mail() function is available<br>";
    
    // Check if sendmail is configured
    $sendmail_path = ini_get('sendmail_path');
    echo "Sendmail path: " . ($sendmail_path ?: 'Not configured') . "<br>";
    
    echo "<br><strong>Lưu ý:</strong> Trên localhost (XAMPP), bạn cần cấu hình SMTP để gửi email được.<br>";
    echo "Các tùy chọn:<br>";
    echo "1. Sử dụng Gmail SMTP (cần App Password)<br>";
    echo "2. Sử dụng Mailtrap.io (cho testing)<br>";
    echo "3. Sử dụng SendGrid, Mailgun, etc.<br>";
} else {
    echo "❌ PHP mail() function is NOT available<br>";
}

echo "<hr>";
echo "<h2>Hướng dẫn cấu hình SMTP</h2>";
echo "<p>Để gửi email được trên localhost, bạn cần cấu hình SMTP trong file <code>config/email.php</code>:</p>";
echo "<pre>";
echo "// Ví dụ với Gmail:
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password'); // App Password, không phải mật khẩu Gmail
define('SMTP_AUTH', true);
define('SMTP_SECURE', 'tls');

// Hoặc với Mailtrap (cho testing):
define('SMTP_HOST', 'smtp.mailtrap.io');
define('SMTP_PORT', 2525);
define('SMTP_USERNAME', 'your-mailtrap-username');
define('SMTP_PASSWORD', 'your-mailtrap-password');
define('SMTP_AUTH', true);
";
echo "</pre>";
?>
