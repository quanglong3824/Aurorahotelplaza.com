<?php
// Start output buffering to prevent header issues
ob_start();

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
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
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="../assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="./assets/css/auth.css">
<link rel="stylesheet" href="./assets/css/success-modal.css">
</head>
<body class="auth-register">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col items-center justify-center py-24 px-4 min-h-screen">
    <div class="auth-container">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="icon-badge">
                <span class="material-symbols-outlined text-4xl text-accent">person_add</span>
            </div>
            <h1 class="text-4xl font-bold mb-3">Đăng ký tài khoản</h1>
            <p class="text-text-secondary-light dark:text-text-secondary-dark">Tạo tài khoản để trải nghiệm dịch vụ tốt nhất</p>
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
                        <button class="modal-btn modal-btn-primary" onclick="window.location.href='./login.php'">
                            Đăng nhập ngay
                        </button>
                        <button class="modal-btn modal-btn-secondary" onclick="window.location.href='../index.php'">
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
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="space-y-4">
                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label">Họ và tên *</label>
                        <input type="text" name="full_name" class="form-input" required 
                               value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
                               placeholder="Nguyễn Văn A">
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="email@example.com">
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="tel" name="phone" class="form-input" required 
                               value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                               placeholder="0912345678">
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label">Mật khẩu *</label>
                        <input type="password" name="password" class="form-input" required 
                               placeholder="Ít nhất 6 ký tự">
                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                            Mật khẩu phải có ít nhất 6 ký tự
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label">Xác nhận mật khẩu *</label>
                        <input type="password" name="confirm_password" class="form-input" required 
                               placeholder="Nhập lại mật khẩu">
                    </div>

                    <!-- Terms -->
                    <div class="form-group">
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="agree_terms" class="mt-1" required>
                            <span class="text-sm">
                                Tôi đồng ý với 
                                <a href="#" class="text-accent hover:underline">điều khoản sử dụng</a> 
                                và 
                                <a href="#" class="text-accent hover:underline">chính sách bảo mật</a>
                            </span>
                        </label>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary w-full">
                        Đăng ký
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

            <!-- Login Link -->
            <div class="text-center">
                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                    Đã có tài khoản? 
                    <a href="./login.php" class="text-accent font-semibold hover:underline">
                        Đăng nhập
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
