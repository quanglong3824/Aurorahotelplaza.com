<?php
session_start();

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
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="../assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="./assets/css/auth.css">
</head>
<body class="auth-forgot">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col items-center justify-center py-24 px-4 min-h-screen">
    <div class="auth-container">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="icon-badge">
                <span class="material-symbols-outlined text-4xl text-accent">lock_reset</span>
            </div>
            <h1 class="text-4xl font-bold mb-3">Quên mật khẩu?</h1>
            <p class="text-text-secondary-light dark:text-text-secondary-dark">
                Nhập email của bạn để nhận link đặt lại mật khẩu
            </p>
        </div>

        <!-- Forgot Password Form -->
        <div class="auth-card">
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                ✕ <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="space-y-4">
                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="email@example.com">
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary w-full">
                        Gửi link đặt lại mật khẩu
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <a href="./login.php" class="text-sm text-accent hover:underline flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    Quay lại đăng nhập
                </a>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</div>

<script src="../assets/js/main.js"></script>
<script src="./assets/js/auth.js"></script>
</body>
</html>
