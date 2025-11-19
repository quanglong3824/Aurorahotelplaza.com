<?php
session_start();
require_once '../config/environment.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT user_id, full_name, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $userDetails = $user;
                
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save token in password_resets table
                $stmt = $db->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at, used)
                    VALUES (?, ?, ?, 0)
                ");
                $stmt->execute([$user['user_id'], $token, $expires]);
                
                // Send email with PHPMailer (optional - don't block if email fails)
                $emailSent = false;
                try {
                    require_once '../helpers/mailer.php';
                    $mailer = getMailer();
                    $emailSent = $mailer->sendPasswordReset($email, $userDetails['full_name'], $token);
                } catch (Exception $emailError) {
                    error_log("Email send failed: " . $emailError->getMessage());
                }
                
                // Log password reset request
                try {
                    require_once '../helpers/logger.php';
                    $logger = getLogger();
                    $logger->logAdminAction($user['user_id'], 'password_reset_requested', 'user', $user['user_id'], [
                        'email' => $email,
                        'email_sent' => $emailSent,
                        'token_expires' => $expires
                    ]);
                } catch (Exception $logError) {
                    error_log("Logger failed: " . $logError->getMessage());
                }
                
                if ($emailSent) {
                    $success = 'Đã gửi link đặt lại mật khẩu đến email của bạn. Vui lòng kiểm tra hộp thư (có thể trong thư mục Spam).';
                } else {
                    // Show success anyway for security (don't reveal if email exists)
                    $success = 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được link đặt lại mật khẩu.';
                }
            } else {
                // Don't reveal if email exists or not (security)
                $success = 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được link đặt lại mật khẩu.';
            }
        } catch (Exception $e) {
            $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Quên mật khẩu - Aurora Hotel Plaza</title>
<script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
<link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet"/>

<script src="<?php echo asset('js/tailwind-config.js'); ?>?v=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo asset('auth/assets/css/auth.css'); ?>?v=<?php echo time(); ?>">
</head>
<body class="auth-forgot">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col items-center justify-center py-24 px-4 min-h-screen">
    <div class="auth-container">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="icon-badge">
                <span class="material-symbols-outlined">lock_reset</span>
            </div>
            <h1 class="auth-title">Quên mật khẩu?</h1>
            <p class="auth-subtitle">
                Nhập email của bạn để nhận link đặt lại mật khẩu
            </p>
        </div>

        <!-- Forgot Password Form -->
        <div class="auth-card">
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="material-symbols-outlined">check_circle</span>
                <div>
                    <strong>Thành công!</strong>
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="material-symbols-outlined">error</span>
                <div>
                    <strong>Lỗi!</strong>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="" id="forgotForm">
                <div class="form-fields">
                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">email</span>
                            Email
                        </label>
                        <div class="input-wrapper">
                            <input type="email" name="email" class="form-input" required 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   placeholder="Nhập địa chỉ email của bạn">
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary">
                        <span class="btn-text">Gửi link đặt lại mật khẩu</span>
                        <span class="btn-icon">
                            <span class="material-symbols-outlined">send</span>
                        </span>
                    </button>
                </div>
            </form>
            <?php else: ?>
            <!-- Success Actions -->
            <div class="text-center space-y-4">
                <div class="success-actions">
                    <a href="<?php echo url('auth/login.php'); ?>" class="btn-primary">
                        <span class="btn-text">Đăng nhập</span>
                        <span class="btn-icon">
                            <span class="material-symbols-outlined">login</span>
                        </span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Back to Login -->
            <div class="auth-footer">
                <a href="<?php echo url('auth/login.php'); ?>" class="back-link">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Quay lại đăng nhập
                </a>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</div>

<script src="<?php echo asset('js/main.js'); ?>?v=<?php echo time(); ?>"></script>
<script src="<?php echo asset('auth/assets/js/auth.js'); ?>?v=<?php echo time(); ?>"></script>
</body>
</html>
