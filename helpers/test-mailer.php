<?php
/**
 * Test Mailer Configuration
 * File này dùng để test xem PHPMailer có hoạt động không
 * 
 * Cách sử dụng:
 * 1. Truy cập: http://yoursite.com/helpers/test-mailer.php
 * 2. Nhập email test
 * 3. Kiểm tra kết quả
 * 
 * LƯU Ý: Xóa file này sau khi test xong vì lý do bảo mật
 */

// Prevent direct access from web if not in development
if (php_sapi_name() !== 'cli' && !isset($_GET['test'])) {
    die('Access denied');
}

require_once __DIR__ . '/mailer.php';

$result = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['test'])) {
    $testEmail = $_POST['test_email'] ?? $_GET['test_email'] ?? '';
    
    if (empty($testEmail)) {
        $error = 'Vui lòng nhập email test';
    } elseif (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        try {
            $mailer = getMailer();
            
            if (!$mailer->isReady()) {
                $error = 'Mailer không được cấu hình đúng';
            } else {
                // Send test email
                $subject = "Test Email - Aurora Hotel Plaza";
                $body = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="background-color: #ffffff; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto;">
        <h1 style="color: #667eea; margin-bottom: 20px;">✅ Test Email Thành Công!</h1>
        <p style="color: #666666; line-height: 1.6; margin-bottom: 15px;">
            Đây là email test từ Aurora Hotel Plaza.
        </p>
        <p style="color: #666666; line-height: 1.6; margin-bottom: 15px;">
            Nếu bạn nhận được email này, điều đó có nghĩa là hệ thống gửi mail đã được cấu hình đúng.
        </p>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="color: #666666; margin: 0; font-size: 14px;">
                <strong>Thông tin test:</strong><br>
                Thời gian: {date('d/m/Y H:i:s')}<br>
                Email nhận: {$testEmail}
            </p>
        </div>
        <p style="color: #999999; font-size: 12px; margin-top: 20px;">
            © 2024 Aurora Hotel Plaza
        </p>
    </div>
</body>
</html>
HTML;
                
                if ($mailer->send($testEmail, $subject, $body)) {
                    $result = "✅ Email test đã được gửi thành công đến: {$testEmail}";
                } else {
                    $error = "❌ Không thể gửi email. Vui lòng kiểm tra cấu hình SMTP.";
                }
            }
        } catch (Exception $e) {
            $error = "❌ Lỗi: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Mailer - Aurora Hotel Plaza</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
            line-height: 1.6;
        }
        
        .info-box strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Mailer</h1>
        <p class="subtitle">Kiểm tra cấu hình gửi email</p>
        
        <?php if ($result): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($result); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="test_email">Email Test</label>
                <input 
                    type="email" 
                    id="test_email" 
                    name="test_email" 
                    placeholder="your-email@example.com"
                    required
                >
            </div>
            
            <button type="submit">Gửi Email Test</button>
        </form>
        
        <div class="info-box">
            <strong>ℹ️ Lưu ý:</strong><br>
            • File này chỉ dùng để test cấu hình<br>
            • Xóa file sau khi test xong<br>
            • Kiểm tra thư mục Spam nếu không nhận được email<br>
            • Đảm bảo cấu hình SMTP trong config/email.php là chính xác
        </div>
    </div>
</body>
</html>
