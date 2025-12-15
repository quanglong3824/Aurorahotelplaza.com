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
            
            // Ensure temp_password columns exist (run once, cached by MySQL)
            try {
                $db->exec("ALTER TABLE users ADD COLUMN temp_password VARCHAR(255) NULL");
            } catch (PDOException $e) { /* Column exists */ }
            try {
                $db->exec("ALTER TABLE users ADD COLUMN temp_password_expires TIMESTAMP NULL");
            } catch (PDOException $e) { /* Column exists */ }
            try {
                $db->exec("ALTER TABLE users ADD COLUMN requires_password_change TINYINT(1) DEFAULT 0");
            } catch (PDOException $e) { /* Column exists */ }
            
            $stmt = $db->prepare("SELECT user_id, full_name, email FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $user_id = $user['user_id'];
                
                // Generate random temporary password (8-12 characters)
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
                $temp_password = substr(str_shuffle($chars), 0, rand(8, 12));
                $temp_password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
                $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                
                // Update user with temporary password
                $stmt = $db->prepare("
                    UPDATE users 
                    SET temp_password = ?, temp_password_expires = ?, requires_password_change = 1, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$temp_password_hash, $expires, $user_id]);
                
                // Try to send email with temporary password
                $emailSent = false;
                try {
                    require_once '../helpers/mailer.php';
                    $mailer = getMailer();
                    $emailSent = $mailer->sendTemporaryPassword($email, $user['full_name'], $temp_password);
                } catch (Exception $emailError) {
                    error_log("Email send failed: " . $emailError->getMessage());
                }
                
                // Log password reset request
                try {
                    require_once '../helpers/logger.php';
                    $logger = getLogger();
                    $logger->logAdminAction($user['user_id'], 'temp_password_sent', 'user', $user['user_id'], [
                        'email' => $email,
                        'email_sent' => $emailSent,
                        'temp_expires' => $expires
                    ]);
                } catch (Exception $logError) {
                    error_log("Logger failed: " . $logError->getMessage());
                }
                
                // Show temporary password for testing (remove in production)
                if ($emailSent) {
                    $success = 'Mật khẩu tạm thời đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư (có thể trong thư mục Spam).';
                } else {
                    // For testing purposes, show the password directly
                    $success = 'Mật khẩu tạm thời của bạn: <strong>' . htmlspecialchars($temp_password) . '</strong><br>Hết hạn sau 30 phút.<br>Bạn cũng có thể kiểm tra email (có thể trong thư mục Spam).';
                }
            } else {
                // Don't reveal if email exists or not (security)
                $success = 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được mật khẩu tạm thời.';
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
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/auth/assets/css/auth.css?v=<?php echo time(); ?>">
<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin { animation: spin 1s linear infinite; }
</style>
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
                Nhập email của bạn để nhận mật khẩu tạm thời
            </p>
        </div>

        <!-- Forgot Password Form -->
        <div class="auth-card">
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="material-symbols-outlined">check_circle</span>
                <div>
                    <strong>Thành công!</strong>
                    <p><?php echo $success; ?></p>
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
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <span class="btn-text" id="btnText">Gửi mật khẩu tạm thời</span>
                        <span class="btn-icon" id="btnIcon">
                            <span class="material-symbols-outlined">send</span>
                        </span>
                        <span class="btn-loading" id="btnLoading" style="display: none;">
                            <svg class="animate-spin" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span style="margin-left: 8px;">Đang gửi...</span>
                        </span>
                    </button>
                </div>
            </form>
            
            <script>
            document.getElementById('forgotForm').addEventListener('submit', function(e) {
                var btn = document.getElementById('submitBtn');
                var btnText = document.getElementById('btnText');
                var btnIcon = document.getElementById('btnIcon');
                var btnLoading = document.getElementById('btnLoading');
                
                btn.disabled = true;
                btn.style.opacity = '0.7';
                btnText.style.display = 'none';
                btnIcon.style.display = 'none';
                btnLoading.style.display = 'flex';
                btnLoading.style.alignItems = 'center';
                btnLoading.style.justifyContent = 'center';
            });
            </script>
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
<script src="<?php echo BASE_URL; ?>/auth/assets/js/auth.js?v=<?php echo time(); ?>"></script>
</body>
</html>
