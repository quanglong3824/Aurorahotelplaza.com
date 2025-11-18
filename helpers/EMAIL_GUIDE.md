# Hướng Dẫn Sử Dụng Hệ Thống Email

## 📋 Tổng Quan

Hệ thống email của Aurora Hotel Plaza sử dụng **PHPMailer** để gửi email qua SMTP. Thư viện PHPMailer được lưu trữ tại `config/PHPMailler/`.

## 🔧 Cấu Hình

### 1. File Cấu Hình: `config/email.php`

```php
// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // tls or ssl
define('SMTP_AUTH', true);

// Email credentials
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Sender information
define('MAIL_FROM_EMAIL', 'your-email@gmail.com');
define('MAIL_FROM_NAME', 'Aurora Hotel Plaza');
```

### 2. Cấu Hình Gmail (Khuyến Nghị)

Nếu sử dụng Gmail:

1. Bật **2-Step Verification** trong tài khoản Google
2. Tạo **App Password** tại: https://myaccount.google.com/apppasswords
3. Sử dụng App Password thay vì mật khẩu thường
4. Cấu hình:
   - SMTP_HOST: `smtp.gmail.com`
   - SMTP_PORT: `587`
   - SMTP_SECURE: `tls`
   - SMTP_USERNAME: Email Gmail của bạn
   - SMTP_PASSWORD: App Password (16 ký tự)

## 📧 Các Loại Email

### 1. Welcome Email (Email Chào Mừng)

**Khi nào gửi:** Sau khi người dùng đăng ký thành công

**File:** `auth/register.php`

**Cách sử dụng:**
```php
require_once '../helpers/mailer.php';
$mailer = getMailer();
$mailer->sendWelcomeEmail($email, $fullName, $userId);
```

**Template:** `helpers/email-templates.php` → `EmailTemplates::getWelcomeTemplate()`

### 2. Password Reset Email (Email Đặt Lại Mật Khẩu)

**Khi nào gửi:** Khi người dùng yêu cầu đặt lại mật khẩu

**File:** `auth/forgot-password.php`

**Cách sử dụng:**
```php
require_once '../helpers/mailer.php';
$mailer = getMailer();
$mailer->sendPasswordReset($email, $fullName, $resetToken);
```

**Template:** `helpers/email-templates.php` → `EmailTemplates::getPasswordResetTemplate()`

### 3. Booking Confirmation Email (Email Xác Nhận Đặt Phòng)

**Khi nào gửi:** Sau khi đặt phòng thành công

**Cách sử dụng:**
```php
require_once '../helpers/mailer.php';
$mailer = getMailer();
$mailer->sendBookingConfirmation($email, $bookingData);
```

**Template:** `helpers/email-templates.php` → `EmailTemplates::getBookingConfirmationTemplate()`

**Dữ liệu cần thiết:**
```php
$bookingData = [
    'booking_code' => 'BK123456',
    'room_type_name' => 'Deluxe Room',
    'check_in_date' => '2024-12-25',
    'check_out_date' => '2024-12-27',
    'num_nights' => 2,
    'total_amount' => 5000000
];
```

## 🧪 Test Email

### Cách 1: Sử dụng File Test

1. Truy cập: `http://yoursite.com/helpers/test-mailer.php?test=1`
2. Nhập email test
3. Kiểm tra kết quả

### Cách 2: Test Bằng Code

```php
require_once 'helpers/mailer.php';

$mailer = getMailer();

if ($mailer->isReady()) {
    $result = $mailer->send(
        'test@example.com',
        'Test Subject',
        '<h1>Test Email</h1><p>This is a test email</p>'
    );
    
    if ($result) {
        echo "Email sent successfully!";
    } else {
        echo "Failed to send email";
    }
} else {
    echo "Mailer is not configured properly";
}
```

## 📝 Tạo Email Tùy Chỉnh

### Phương Pháp 1: Sử dụng Mailer Class

```php
require_once 'helpers/mailer.php';

$mailer = getMailer();
$mailer->sendCustom(
    'recipient@example.com',
    'Subject',
    '<h1>Hello</h1><p>Custom email body</p>'
);
```

### Phương Pháp 2: Thêm Template Mới

1. Thêm method vào `helpers/email-templates.php`:

```php
public static function getCustomTemplate($data) {
    return <<<HTML
<!DOCTYPE html>
<html>
<body>
    <h1>Custom Email</h1>
    <p>{$data['message']}</p>
</body>
</html>
HTML;
}
```

2. Thêm method vào `helpers/mailer.php`:

```php
public function sendCustomEmail($email, $data) {
    $subject = "Custom Subject";
    $body = EmailTemplates::getCustomTemplate($data);
    return $this->send($email, $subject, $body);
}
```

## 🐛 Troubleshooting

### Email không được gửi

**Kiểm tra:**
1. Cấu hình SMTP trong `config/email.php` có chính xác không?
2. Tài khoản email có bật 2-Step Verification không? (nếu dùng Gmail)
3. App Password có chính xác không?
4. Firewall/Server có chặn port 587 không?

### Email vào thư mục Spam

**Giải pháp:**
1. Kiểm tra SPF, DKIM, DMARC records
2. Sử dụng domain email chính thức thay vì Gmail
3. Thêm unsubscribe link trong email

### Lỗi "SMTP connect() failed"

**Nguyên nhân:**
- Port SMTP sai
- SMTP_SECURE cấu hình sai (tls vs ssl)
- Firewall chặn kết nối

**Giải pháp:**
- Thử port 465 với ssl
- Thử port 587 với tls
- Kiểm tra firewall settings

## 🔒 Bảo Mật

1. **Không commit credentials:**
   - Thêm `config/email.php` vào `.gitignore`
   - Sử dụng environment variables cho production

2. **Bảo vệ file test:**
   - Xóa `helpers/test-mailer.php` sau khi test
   - Hoặc thêm authentication check

3. **Rate Limiting:**
   - Giới hạn số email gửi trong một khoảng thời gian
   - Tránh spam

## 📚 Tài Liệu Tham Khảo

- PHPMailer: https://github.com/PHPMailer/PHPMailer
- Gmail App Passwords: https://support.google.com/accounts/answer/185833
- SMTP Configuration: https://www.phpmailer.pro/

## 📞 Hỗ Trợ

Nếu gặp vấn đề:
1. Kiểm tra error log
2. Chạy test email
3. Kiểm tra cấu hình SMTP
4. Liên hệ với nhà cung cấp email service
