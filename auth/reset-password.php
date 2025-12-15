<?php
session_start();
require_once '../config/environment.php';

$token = $_GET['token'] ?? '';
$success = '';
$error = '';
$valid_token = false;

if (empty($token)) {
    $error = 'Token không hợp lệ';
} else {
    require_once '../config/database.php';
    
    try {
        $db = getDB();
        
        // Verify token from password_resets table
        $stmt = $db->prepare("
            SELECT pr.reset_id, pr.user_id, u.email, u.full_name 
            FROM password_resets pr
            LEFT JOIN users u ON pr.user_id = u.user_id
            WHERE pr.token = ? 
            AND pr.expires_at > NOW()
            AND pr.used = 0
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $valid_token = true;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($password)) {
                    $error = 'Vui lòng nhập mật khẩu mới';
                } elseif (strlen($password) < 6) {
                    $error = 'Mật khẩu phải có ít nhất 6 ký tự';
                } elseif ($password !== $confirm_password) {
                    $error = 'Mật khẩu xác nhận không khớp';
                } else {
                    // Update password
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET password_hash = ?, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$password_hash, $user['user_id']]);
                    
                    // Mark token as used
                    $stmt = $db->prepare("
                        UPDATE password_resets 
                        SET used = 1 
                        WHERE reset_id = ?
                    ");
                    $stmt->execute([$user['reset_id']]);
                    
                    // Log password reset success
                    try {
                        require_once '../helpers/logger.php';
                        $logger = getLogger();
                        $logger->logAdminAction($user['user_id'], 'password_reset', 'user', $user['user_id'], [
                            'email' => $user['email'],
                            'user_name' => $user['full_name']
                        ]);
                    } catch (Exception $logError) {
                        error_log("Logger failed: " . $logError->getMessage());
                    }
                    
                    $success = 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay.';
                }
            }
        } else {
            $error = 'Token không hợp lệ hoặc đã hết hạn';
        }
    } catch (Exception $e) {
        $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Đặt lại mật khẩu - Aurora Hotel Plaza</title>
<script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
<link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet"/>
<script src="<?php echo asset('js/tailwind-config.js'); ?>?v=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo asset('css/liquid-glass.css'); ?>?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/auth/assets/css/auth.css?v=<?php echo time(); ?>">
</head>
<body class="auth-reset">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col items-center justify-center py-24 px-4 min-h-screen">
    <div class="auth-container">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="icon-badge">
                <span class="material-symbols-outlined text-4xl text-accent">key</span>
            </div>
            <h1 class="text-4xl font-bold mb-3">Đặt lại mật khẩu</h1>
            <p class="text-text-secondary-light dark:text-text-secondary-dark">
                Nhập mật khẩu mới cho tài khoản của bạn
            </p>
        </div>

        <!-- Reset Password Form -->
        <div class="auth-card">
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($success); ?>
            </div>
            <a href="<?php echo url('auth/login.php'); ?>" class="btn-primary w-full block text-center">
                Đăng nhập ngay
            </a>
            <?php elseif ($error): ?>
            <div class="alert alert-error">
                ✕ <?php echo htmlspecialchars($error); ?>
            </div>
            <?php if (!$valid_token): ?>
            <a href="<?php echo url('auth/forgot-password.php'); ?>" class="btn-primary w-full block text-center">
                Yêu cầu link mới
            </a>
            <?php endif; ?>
            <?php elseif ($valid_token): ?>
            
            <form method="POST" action="">
                <div class="space-y-4">
                    <!-- New Password -->
                    <div class="form-group">
                        <label class="form-label">Mật khẩu mới</label>
                        <input type="password" name="password" class="form-input" required 
                               placeholder="Ít nhất 6 ký tự">
                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                            Mật khẩu phải có ít nhất 6 ký tự
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" name="confirm_password" class="form-input" required 
                               placeholder="Nhập lại mật khẩu mới">
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary w-full">
                        Đặt lại mật khẩu
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <a href="<?php echo url('auth/login.php'); ?>" class="text-sm text-accent hover:underline flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
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
