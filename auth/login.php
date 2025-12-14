<?php
session_start();
require_once '../config/environment.php';
require_once '../helpers/session-helper.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';
$success = $_GET['registered'] ?? '';

// Handle logout success message
if (isset($_GET['logged_out'])) {
    $success = 'Bạn đã đăng xuất thành công!';
} elseif ($success) {
    $success = 'Đăng ký thành công! Vui lòng đăng nhập.';
}

// Handle Google login errors
if (isset($_GET['google_error'])) {
    $error = $_GET['google_error'];
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'google_auth_failed':
            $error = 'Xác thực Google thất bại. Vui lòng thử lại.';
            break;
        case 'google_token_failed':
            $error = 'Không thể lấy token từ Google. Vui lòng thử lại.';
            break;
        case 'google_userinfo_failed':
            $error = 'Không thể lấy thông tin người dùng từ Google.';
            break;
        case 'google_userinfo_invalid':
            $error = 'Thông tin người dùng từ Google không hợp lệ.';
            break;
        case 'database_error':
            $error = 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau.';
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful - Xóa session cũ hoàn toàn trước khi set mới
                $intended_url = $_SESSION['intended_url'] ?? null;
                
                // Sử dụng helper function để xóa session hoàn toàn và tạo mới
                destroySessionCompletely(true);
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['login_time'] = time();
                
                if ($intended_url) {
                    $_SESSION['intended_url'] = $intended_url;
                }
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                
                // Log login
                try {
                    require_once '../helpers/logger.php';
                    $logger = getLogger();
                    $logger->logUserLogin($user['user_id'], [
                        'email' => $user['email'],
                        'user_name' => $user['full_name'],
                        'role' => $user['user_role'],
                        'remember_me' => $remember
                    ]);
                } catch (Exception $logError) {
                    error_log("Logger failed: " . $logError->getMessage());
                }
                
                // Remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                    // TODO: Store token in user_sessions table
                }
                
                // Redirect to main page or intended destination
                $redirect = $_GET['redirect'] ?? '../index.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Email hoặc mật khẩu không đúng';
            }
        } catch (Exception $e) {
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Đăng nhập - Aurora Hotel Plaza</title>
<script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
<link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet"/>
<script src="<?php echo asset('js/tailwind-config.js'); ?>?v=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/auth/assets/css/auth.css?v=<?php echo time(); ?>">
</head>
<body class="auth-login">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col items-center justify-center py-24 px-4 min-h-screen">
    <div class="auth-container">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="icon-badge">
                <span class="material-symbols-outlined">account_circle</span>
            </div>
            <h1 class="auth-title">Đăng nhập</h1>
            <p class="auth-subtitle">Chào mừng bạn trở lại Aurora Hotel Plaza</p>
        </div>

        <!-- Login Form -->
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

            <form method="POST" action="" id="loginForm">
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

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="material-symbols-outlined">lock</span>
                            Mật khẩu
                        </label>
                        <div class="input-wrapper password-wrapper">
                            <input type="password" name="password" class="form-input" required 
                                   placeholder="Nhập mật khẩu của bạn" id="password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="form-options">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            <span class="checkbox-text">Ghi nhớ đăng nhập</span>
                        </label>
                        <a href="<?php echo url('auth/forgot-password.php'); ?>" class="forgot-link">
                            Quên mật khẩu?
                        </a>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary" id="loginBtn">
                        <span class="btn-text">Đăng nhập</span>
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
                    Đăng nhập với Google
                </button>
                <button type="button" class="social-btn facebook-btn" disabled style="opacity: 0.5;">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Đăng nhập với Facebook (Sắp có)
                </button>
            </div>

            <!-- Register Link -->
            <div class="auth-footer">
                <p>Chưa có tài khoản? 
                    <a href="<?php echo url('auth/register.php'); ?>" class="auth-link">
                        Đăng ký ngay
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
