<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = $_GET['registered'] ?? '';

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
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
                $stmt->execute([$_SERVER['REMOTE_ADDR'], $user['id']]);
                
                // Remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                    // TODO: Store token in database
                }
                
                // Redirect
                $redirect = $_GET['redirect'] ?? '../index.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Email hoặc mật khẩu không đúng';
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
<title>Đăng nhập - Aurora Hotel Plaza</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="../assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="./assets/css/auth.css">
</head>
<body class="auth-login">
<!-- Decorative Elements -->
<div class="auth-decoration auth-decoration-1"></div>
<div class="auth-decoration auth-decoration-2"></div>
<div class="auth-decoration auth-decoration-3"></div>

<!-- Particles -->
<div class="particles">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
</div>

<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col items-center justify-center py-24 px-4 min-h-screen">
    <div class="auth-container">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2">Đăng nhập</h1>
            <p class="text-text-secondary-light dark:text-text-secondary-dark">Chào mừng bạn trở lại Aurora Hotel Plaza</p>
        </div>

        <!-- Login Form -->
        <div class="auth-card">
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ Đăng ký thành công! Vui lòng đăng nhập.
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                ✕ <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="space-y-4">
                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="email@example.com">
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" name="password" class="form-input" required 
                               placeholder="••••••••">
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="remember" class="rounded">
                            <span class="text-sm">Ghi nhớ đăng nhập</span>
                        </label>
                        <a href="./forgot-password.php" class="text-sm text-accent hover:underline">
                            Quên mật khẩu?
                        </a>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary w-full">
                        Đăng nhập
                    </button>
                </div>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-surface-light dark:bg-surface-dark text-text-secondary-light dark:text-text-secondary-dark">
                        Hoặc
                    </span>
                </div>
            </div>

            <!-- Register Link -->
            <div class="text-center">
                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                    Chưa có tài khoản? 
                    <a href="./register.php" class="text-accent font-semibold hover:underline">
                        Đăng ký ngay
                    </a>
                </p>
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
