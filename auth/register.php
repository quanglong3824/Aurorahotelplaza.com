<?php
// Start output buffering to prevent header issues
ob_start();

session_start();
require_once '../config/environment.php';
require_once '../helpers/session-helper.php';
require_once '../helpers/language.php';
initLanguage();

// Kiểm tra và xóa session không hợp lệ (user_id = 0)
validateAndCleanSession();

// Redirect if already logged in với session hợp lệ
if (isValidSession()) {
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
    if (empty($full_name)) $errors[] = __('auth.error_name_required');
    if (empty($email)) $errors[] = __('auth.error_email_required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = __('auth.error_email_invalid');
    if (empty($phone)) $errors[] = __('auth.error_phone_required');
    if (empty($password)) $errors[] = __('auth.error_password_required');
    if (strlen($password) < 6) $errors[] = __('auth.error_password_min');
    if ($password !== $confirm_password) $errors[] = __('auth.error_password_mismatch');
    if (!$agree_terms) $errors[] = __('auth.error_terms_required');
    
    if (empty($errors)) {
        try {
            $db = getDB();
            
            if (!$db) {
                throw new Exception(__('auth.database_error'));
            }
            
            // Check if email exists
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = __('auth.error_email_exists');
            } else {
                // Create user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("
                    INSERT INTO users (email, password_hash, full_name, phone, user_role, status, email_verified, created_at)
                    VALUES (?, ?, ?, ?, 'customer', 'active', 0, NOW())
                ");
                $result = $stmt->execute([$email, $password_hash, $full_name, $phone]);
                
                if (!$result) {
                    throw new Exception("Không thể tạo tài khoản - INSERT failed");
                }
                
                $user_id = $db->lastInsertId();
                
                // Nếu lastInsertId trả về 0, có thể do bảng thiếu AUTO_INCREMENT
                // Lấy user_id bằng cách query lại
                if (!$user_id || $user_id == 0) {
                    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? ORDER BY created_at DESC LIMIT 1");
                    $stmt->execute([$email]);
                    $new_user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $user_id = $new_user['user_id'] ?? 0;
                }
                
                if (!$user_id) {
                    error_log("Warning: user_id is 0 after registration for email: $email - Database may be missing AUTO_INCREMENT");
                }
                
                // Tạo loyalty record cho user mới
                try {
                    $stmt = $db->prepare("
                        INSERT INTO user_loyalty (user_id, current_points, lifetime_points, created_at) 
                        VALUES (?, 0, 0, NOW())
                    ");
                    $stmt->execute([$user_id]);
                } catch (Exception $loyaltyError) {
                    // Ignore if loyalty table doesn't exist or duplicate
                    error_log("Loyalty insert failed: " . $loyaltyError->getMessage());
                }
                
                // Log registration (optional - don't block if fails)
                try {
                    $loggerPath = __DIR__ . '/../helpers/logger.php';
                    if (file_exists($loggerPath)) {
                        require_once $loggerPath;
                        if (function_exists('getLogger')) {
                            $logger = getLogger();
                            $logger->logUserRegistration($user_id, [
                                'email' => $email,
                                'user_name' => $full_name,
                                'registration_method' => 'manual'
                            ]);
                        }
                    }
                } catch (Exception $logError) {
                    error_log("Logger failed: " . $logError->getMessage());
                }
                
                // Send welcome email (optional - don't block registration)
                try {
                    $mailerPath = __DIR__ . '/../helpers/mailer.php';
                    if (file_exists($mailerPath)) {
                        require_once $mailerPath;
                        if (function_exists('getMailer')) {
                            $mailer = getMailer();
                            $mailer->sendWelcomeEmail($email, $full_name, $user_id);
                        }
                    }
                } catch (Exception $emailError) {
                    error_log("Email send failed: " . $emailError->getMessage());
                }
                
                // Set success flag to show popup
                $_SESSION['registration_success'] = true;
                $_SESSION['registration_email'] = $email;
                $_SESSION['registration_name'] = $full_name;
            }
        } catch (PDOException $e) {
            $errors[] = __('auth.database_error');
            error_log("Registration PDO error: " . $e->getMessage());
        } catch (Exception $e) {
            $errors[] = __('auth.error_general');
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php _e('auth.register_title'); ?></title>
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
            <h1 class="auth-title"><?php _e('auth.create_account'); ?></h1>
            <p class="auth-subtitle"><?php _e('auth.join_aurora'); ?></p>
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
                    <h2><?php _e('auth.register_success'); ?></h2>
                    <p><?php _e('auth.register_success_desc'); ?></p>
                    <div class="modal-buttons">
                        <button class="modal-btn modal-btn-primary" onclick="window.location.href='<?php echo url('auth/login.php'); ?>'">
                            <?php _e('auth.login_now'); ?>
                        </button>
                        <button class="modal-btn modal-btn-secondary" onclick="window.location.href='<?php echo url('index.php'); ?>'">
                            <?php _e('auth.back_to_home'); ?>
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
                    <strong><?php _e('auth.error'); ?></strong>
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
                            <?php _e('auth.full_name'); ?> *
                        </label>
                        <div class="input-wrapper">
                            <input type="text" name="full_name" class="form-input" required 
                                   value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
                                   placeholder="<?php _e('auth.enter_name'); ?>">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">email</span>
                            <?php _e('auth.email'); ?> *
                        </label>
                        <div class="input-wrapper">
                            <input type="email" name="email" class="form-input" required 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   placeholder="<?php _e('auth.enter_email'); ?>">
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">phone</span>
                            <?php _e('auth.phone'); ?> *
                        </label>
                        <div class="input-wrapper">
                            <input type="tel" name="phone" class="form-input" required 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                   placeholder="<?php _e('auth.enter_phone'); ?>">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">lock</span>
                            <?php _e('auth.password'); ?> *
                        </label>
                        <div class="input-wrapper password-wrapper">
                            <input type="password" name="password" class="form-input" required 
                                   placeholder="<?php _e('auth.min_6_chars'); ?>" id="password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-fill"></div>
                            </div>
                            <span class="strength-text"><?php _e('auth.password_strength'); ?></span>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">lock_reset</span>
                            <?php _e('auth.confirm_password'); ?> *
                        </label>
                        <div class="input-wrapper password-wrapper">
                            <input type="password" name="confirm_password" class="form-input" required 
                                   placeholder="<?php _e('auth.enter_confirm_password'); ?>" id="confirmPassword">
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
                                <?php _e('auth.agree_terms'); ?> 
                                <a href="#" class="terms-link"><?php _e('auth.terms_conditions'); ?></a> 
                                <?php _e('auth.and'); ?> 
                                <a href="#" class="terms-link"><?php _e('auth.privacy_policy'); ?></a>
                            </span>
                        </label>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary" id="registerBtn">
                        <span class="btn-text"><?php _e('auth.register_btn'); ?></span>
                        <span class="btn-icon">
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </span>
                    </button>
                </div>
            </form>

            <!-- Divider -->
            <div class="auth-divider">
                <span><?php _e('auth.or_login_with'); ?></span>
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
                    <?php _e('auth.register_with_google'); ?>
                </button>
                <button type="button" class="social-btn facebook-btn" disabled style="opacity: 0.5;">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <?php _e('auth.register_with_facebook'); ?>
                </button>
            </div>

            <!-- Login Link -->
            <div class="auth-footer">
                <p><?php _e('auth.have_account'); ?> 
                    <a href="<?php echo url('auth/login.php'); ?>" class="auth-link">
                        <?php _e('auth.login_now'); ?>
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
