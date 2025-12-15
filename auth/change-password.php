<?php
session_start();
require_once '../config/environment.php';
require_once '../helpers/auth-middleware.php';

// Check if user is logged in and must change password
if (!isset($_SESSION['user_id']) || !isset($_SESSION['must_change_password']) || !$_SESSION['must_change_password']) {
    redirect('index.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        try {
            $db = getDB();
            
            // Get current user data
            $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Verify current password (temp password is now stored in password_hash)
                $password_valid = password_verify($current_password, $user['password_hash']);
                
                if ($password_valid) {
                    // Update to new password and clear requires_password_change flag
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET password_hash = ?, requires_password_change = 0, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$new_password_hash, $_SESSION['user_id']]);
                    
                    // Clear session flags
                    unset($_SESSION['temp_password_login']);
                    unset($_SESSION['must_change_password']);
                    
                    // Log password change
                    try {
                        require_once '../helpers/logger.php';
                        $logger = getLogger();
                        if ($logger && method_exists($logger, 'logActivity')) {
                            $logger->logActivity($_SESSION['user_id'], 'password_changed', 'user', $_SESSION['user_id'], 'Password changed after reset', [
                                'email' => $user['email'],
                                'user_name' => $user['full_name']
                            ]);
                        }
                    } catch (Exception $logError) {
                        error_log("Logger failed: " . $logError->getMessage());
                    }
                    
                    $success = 'Đổi mật khẩu thành công! Bạn sẽ được chuyển đến trang chủ sau 3 giây.';
                    
                    // Redirect after 3 seconds
                    header("refresh:3;url=" . url('index.php'));
                } else {
                    $error = 'Mật khẩu hiện tại không chính xác';
                }
            } else {
                $error = 'Không tìm thấy thông tin người dùng';
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
<title>Đổi mật khẩu bắt buộc - Aurora Hotel Plaza</title>
<script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
<link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet"/>

<script src="<?php echo asset('js/tailwind-config.js'); ?>?v=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
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
                <span class="material-symbols-outlined text-4xl text-orange-500">warning</span>
            </div>
            <h1 class="text-4xl font-bold mb-3">Yêu cầu đổi mật khẩu</h1>
            <p class="text-text-secondary-light dark:text-text-secondary-dark">
                Bạn đã đăng nhập bằng mật khẩu tạm thời. Vui lòng đổi mật khẩu mới để tiếp tục.
            </p>
        </div>

        <!-- Change Password Form -->
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
            <form method="POST" action="">
                <div class="space-y-4">
                    <!-- Current Password -->
                    <div class="form-group">
                        <label class="form-label">Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" class="form-input" required 
                               placeholder="Nhập mật khẩu tạm thời bạn nhận được">
                    </div>

                    <!-- New Password -->
                    <div class="form-group">
                        <label class="form-label">Mật khẩu mới</label>
                        <input type="password" name="new_password" class="form-input" required 
                               placeholder="Ít nhất 6 ký tự">
                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                            Mật khẩu phải có ít nhất 6 ký tự
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" name="confirm_password" class="form-input" required 
                               placeholder="Nhập lại mật khẩu mới">
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary w-full">
                        Đổi mật khẩu
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</div>

<script src="<?php echo asset('js/main.js'); ?>?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>/auth/assets/js/auth.js?v=<?php echo time(); ?>"></script>
</body>
</html>
