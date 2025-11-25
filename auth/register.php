<?php
// Start output buffering to prevent header issues
ob_start();

session_start();
require_once '../config/environment.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $agree_terms = isset($_POST['agree_terms']);
    
    // Validation
    if (empty($full_name)) $errors[] = 'Vui lòng nhập họ tên';
    if (empty($email)) $errors[] = 'Vui lòng nhập email';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    if (empty($phone)) $errors[] = 'Vui lòng nhập số điện thoại';
    if (empty($password)) $errors[] = 'Vui lòng nhập mật khẩu';
    if (strlen($password) < 6) $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
    if ($password !== $confirm_password) $errors[] = 'Mật khẩu xác nhận không khớp';
    if (!$agree_terms) $errors[] = 'Vui lòng đồng ý với điều khoản sử dụng';
    
    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Check if email exists
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email đã được sử dụng';
            } else {
                // Create user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("
                    INSERT INTO users (email, password_hash, full_name, phone, user_role, status, email_verified)
                    VALUES (?, ?, ?, ?, 'customer', 'active', 0)
                ");
                $stmt->execute([$email, $password_hash, $full_name, $phone]);
                $user_id = $db->lastInsertId();
                
                // Send welcome email (optional - don't block registration if email fails)
                $emailSent = false;
                try {
                    require_once '../helpers/mailer.php';
                    $mailer = getMailer();
                    $emailSent = $mailer->sendWelcomeEmail($email, $full_name, $user_id);
                } catch (Exception $emailError) {
                    // Log email error but don't stop registration
                    error_log("Email send failed: " . $emailError->getMessage());
                }
                
                // Log registration
                try {
                    require_once '../helpers/logger.php';
                    $logger = getLogger();
                    $logger->logUserRegister($user_id, [
                        'email' => $email,
                        'full_name' => $full_name,
                        'phone' => $phone,
                        'email_sent' => $emailSent
                    ]);
                } catch (Exception $logError) {
                    // Log error but don't stop registration
                    error_log("Logger failed: " . $logError->getMessage());
                }
                
                // Set success flag to show popup
                $_SESSION['registration_success'] = true;
                $_SESSION['registration_email'] = $email;
                $_SESSION['registration_name'] = $full_name;
            }
        } catch (Exception $e) {
            $errors[] = 'Có lỗi xảy ra: ' . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Đăng ký - Aurora Hotel Plaza</title>
<script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
<link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet"/>

<script src="<?php echo asset('js/tailwind-config.js'); ?>?v=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/auth/assets/css/auth.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/auth/assets/css/success-modal.css?v=<?php echo time(); ?>">
</head>
<body class="auth-register">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col items-center justify-center py-24 px-4 min-h-screen">
    <div class="auth-container">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="icon-badge">
                <span class="material-symbols-outlined">person_add</span>
            </div>
            <h1 class="auth-title">Đăng ký tài khoản</h1>
            <p class="auth-subtitle">Tạo tài khoản để trải nghiệm dịch vụ tốt nhất</p>
        </div>

        <!-- Register Form -->
        <div class="auth-card">
            
            <?php if (isset($_SESSION['registration_success']) && $_SESSION['registration_success']): ?>
            <div class="success-modal">
                <div class="success-modal-content">
                    <div class="success-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2>Đăng ký thành công!</h2>
                    <p>Tài khoản của bạn đã được tạo thành công. Vui lòng đăng nhập để tiếp tục.</p>
                    <div class="modal-buttons">
                        <button class="modal-btn modal-btn-primary" onclick="window.location.href='<?php echo url('auth/login.php'); ?>'">
                            Đăng nhập ngay
                        </button>
                        <button class="modal-btn modal-btn-secondary" onclick="window.location.href='<?php echo url('index.php'); ?>'">
                            Quay lại trang chủ
                        </button>
                    </div>
                </div>
            </div>
            <?php 
                unset($_SESSION['registration_success']);
                unset($_SESSION['registration_email']);
                unset($_SESSION['registration_name']);
            ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <span class="material-symbols-outlined">error</span>
                <div>
                    <strong>Có lỗi xảy ra!</strong>
                    <ul class="error-list">
                        <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <div class="form-fields">
                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">person</span>
                            Họ và tên *
                        </label>
                        <div class="input-wrapper">
                            <input type="text" name="full_name" class="form-input" required 
                                   value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
                                   placeholder="Nhập họ và tên đầy đủ">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">email</span>
                            Email *
                        </label>
                        <div class="input-wrapper">
                            <input type="email" name="email" class="form-input" required 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   placeholder="Nhập địa chỉ email của bạn">
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">phone</span>
                            Số điện thoại *
                        </label>
                        <div class="input-wrapper">
                            <input type="tel" name="phone" class="form-input" required 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                   placeholder="Nhập số điện thoại">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">lock</span>
                            Mật khẩu *
                        </label>
                        <div class="input-wrapper password-wrapper">
                            <input type="password" name="password" class="form-input" required 
                                   placeholder="Tối thiểu 6 ký tự" id="password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-fill"></div>
                            </div>
                            <span class="strength-text">Độ mạnh mật khẩu</span>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">lock_reset</span>
                            Xác nhận mật khẩu *
                        </label>
                        <div class="input-wrapper password-wrapper">
                            <input type="password" name="confirm_password" class="form-input" required 
                                   placeholder="Nhập lại mật khẩu" id="confirmPassword">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </div>
                        <div class="password-match" id="passwordMatch"></div>
                    </div>

                    <!-- Terms -->
                    <div class="form-group">
                        <label class="checkbox-wrapper terms-checkbox">
                            <input type="checkbox" name="agree_terms" required>
                            <span class="checkmark"></span>
                            <span class="checkbox-text">
                                Tôi đồng ý với 
                                <a href="#" class="terms-link">điều khoản sử dụng</a> 
                                và 
                                <a href="#" class="terms-link">chính sách bảo mật</a>
                            </span>
                        </label>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary" id="registerBtn">
                        <span class="btn-text">Tạo tài khoản</span>
                        <span class="btn-icon">
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </span>
                    </button>
                </div>
            </form>

            <!-- Divider -->
            <div class="auth-divider">
                <span>Hoặc</span>
            </div>

            <!-- Social Login -->
            <div class="social-login">
                <button type="button" class="social-btn google-btn" id="googleLoginBtn">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Đăng ký với Google
                </button>
                <button type="button" class="social-btn facebook-btn" disabled style="opacity: 0.5;">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Đăng ký với Facebook (Sắp có)
                </button>
            </div>

            <!-- Login Link -->
            <div class="auth-footer">
                <p>Đã có tài khoản? 
                    <a href="<?php echo url('auth/login.php'); ?>" class="auth-link">
                        Đăng nhập
                    </a>
                </p>
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
