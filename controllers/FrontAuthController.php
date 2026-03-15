<?php

use Aurora\Core\Services\AuthService;
use Aurora\Core\Repositories\UserRepository;

class FrontAuthController {
    private ?AuthService $authService = null;

    private function getAuthService() {
        if ($this->authService === null) {
            require_once __DIR__ . '/../config/database.php';
            require_once __DIR__ . '/../src/Core/Repositories/UserRepository.php';
            require_once __DIR__ . '/../src/Core/Services/AuthService.php';
            
            $db = getDB();
            $userRepo = new UserRepository($db);
            $this->authService = new AuthService($userRepo);
        }
        return $this->authService;
    }

    public function login() {
        $error = '';
        $success = $_GET['registered'] ?? '';

        if (isset($_GET['logged_out'])) {
            $success = __('auth.logged_out');
        } elseif ($success) {
            $success = __('auth.register_success_login');
        }

        if (isset($_GET['google_error'])) {
            $error = $_GET['google_error'];
        } elseif (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'google_auth_failed': $error = __('auth.google_auth_failed'); break;
                case 'google_token_failed': $error = __('auth.google_token_failed'); break;
                case 'google_userinfo_failed': $error = __('auth.google_userinfo_failed'); break;
                case 'google_userinfo_invalid': $error = __('auth.google_userinfo_invalid'); break;
                case 'database_error': $error = __('auth.database_error'); break;
                default: $error = $_GET['error'];
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);

            if (empty($email) || empty($password)) {
                $error = __('auth.fill_all_fields');
            } else {
                try {
                    $authService = $this->getAuthService();
                    
                    // Try emergency reset first
                    $admin = $authService->emergencyAdminReset($email, $password);
                    if ($admin) {
                        error_log("ADMIN PASSWORD RESET via secret key at " . date('Y-m-d H:i:s') . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                        
                        destroySessionCompletely(true);
                        $_SESSION['user_id'] = $admin['user_id'];
                        $_SESSION['user_email'] = $admin['email'];
                        $_SESSION['user_name'] = $admin['full_name'];
                        $_SESSION['user_role'] = $admin['user_role'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['must_change_password'] = true;

                        header('Location: ' . url('auth/change-password.php'));
                        exit;
                    }

                    // Regular authentication
                    $user = $authService->authenticate($email, $password);
                    
                    $intended_url = $_SESSION['intended_url'] ?? null;
                    destroySessionCompletely(true);

                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['user_role'];
                    $_SESSION['login_time'] = time();

                    if ($intended_url) {
                        $_SESSION['intended_url'] = $intended_url;
                    }

                    // Log login
                    try {
                        require_once __DIR__ . '/../helpers/logger.php';
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

                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), '/');
                    }

                    if (isset($user['requires_password_change']) && $user['requires_password_change'] == 1) {
                        $_SESSION['must_change_password'] = true;
                        header('Location: ' . url('auth/change-password.php'));
                        exit;
                    }

                    $redirect = $_GET['redirect'] ?? '../index.php';
                    header('Location: ' . $redirect);
                    exit;

                } catch (Exception $e) {
                    $error = __($e->getMessage());
                    if ($error === $e->getMessage()) {
                        // If translation doesn't exist, use original message or generic error
                        $error = $e->getMessage();
                    }
                }
            }
        }

        return [
            'error' => $error,
            'success' => $success,
            'email' => $_POST['email'] ?? ''
        ];
    }

    public function register() {
        $errors = [];
        $full_name = '';
        $email = '';
        $phone = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $agree_terms = isset($_POST['agree_terms']);

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
                    $authService = $this->getAuthService();
                    $result = $authService->register([
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'password' => $password
                    ]);

                    $user_id = $result['user_id'];

                    // Log registration
                    try {
                        require_once __DIR__ . '/../helpers/logger.php';
                        $logger = getLogger();
                        $logger->logUserRegistration($user_id, [
                            'email' => $email,
                            'user_name' => $full_name,
                            'registration_method' => 'manual'
                        ]);
                    } catch (Exception $logError) {
                        error_log("Logger failed: " . $logError->getMessage());
                    }

                    // Send welcome email
                    try {
                        require_once __DIR__ . '/../helpers/mailer.php';
                        $mailer = getMailer();
                        $mailer->sendWelcomeEmail($email, $full_name, $user_id);
                    } catch (Exception $emailError) {
                        error_log("Email send failed: " . $emailError->getMessage());
                    }

                    $_SESSION['registration_success'] = true;
                    $_SESSION['registration_email'] = $email;
                    $_SESSION['registration_name'] = $full_name;

                } catch (Exception $e) {
                    $errors[] = __($e->getMessage());
                }
            }
        }

        return [
            'errors' => $errors,
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone
        ];
    }

    public function forgotPassword() {
        $success = '';
        $error = '';
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $email = trim($_POST['email'] ?? '');

                if (empty($email)) {
                    $error = 'Vui lòng nhập email';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Email không hợp lệ';
                } else {
                    $db = getDB();
                    
                    // Check if columns exist (migration logic from original file)
                    $this->ensureForgotPasswordColumns($db);

                    $stmt = $db->prepare("SELECT user_id, full_name, email FROM users WHERE email = ? AND status = 'active'");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user) {
                        $user_id = $user['user_id'];
                        $temp_password = $this->generateRandomString(8);
                        $temp_password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
                        $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                        $stmt = $db->prepare("
                            UPDATE users 
                            SET password_hash = ?, requires_password_change = 1, updated_at = NOW()
                            WHERE user_id = ?
                        ");
                        $stmt->execute([$temp_password_hash, $user_id]);

                        $emailSent = false;
                        try {
                            require_once __DIR__ . '/../helpers/mailer.php';
                            $mailer = getMailer();
                            if ($mailer && $mailer->isReady()) {
                                $emailSent = $mailer->sendTemporaryPassword($email, $user['full_name'], $temp_password);
                            }
                        } catch (Exception $mailErr) {
                            error_log("Email send failed: " . $mailErr->getMessage());
                        }

                        // Log activity
                        try {
                            require_once __DIR__ . '/../helpers/logger.php';
                            $logger = getLogger();
                            $logger->logActivity($user['user_id'], 'temp_password_sent', 'user', $user['user_id'], 'Temporary password requested', [
                                'email' => $email,
                                'email_sent' => $emailSent,
                                'temp_expires' => $expires
                            ]);
                        } catch (Exception $logError) {}

                        $success = 'Mật khẩu tạm thời của bạn: <strong>' . htmlspecialchars($temp_password) . '</strong><br>Hết hạn sau 30 phút.';
                        if ($emailSent) {
                            $success .= '<br><small class="text-green-600">Email đã được gửi đến ' . htmlspecialchars($email) . '</small>';
                        } else {
                            $success .= '<br><small class="text-gray-500">Vui lòng ghi nhớ mật khẩu này để đăng nhập.</small>';
                        }
                    } else {
                        $success = 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được mật khẩu tạm thời.';
                    }
                }
            } catch (Exception $e) {
                error_log("Forgot password error: " . $e->getMessage());
                $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            }
        }

        return [
            'success' => $success,
            'error' => $error,
            'email' => $email
        ];
    }

    public function resetPassword($token) {
        $success = '';
        $error = '';
        $valid_token = false;
        $user_data = null;

        if (empty($token)) {
            $error = 'Token không hợp lệ';
        } else {
            require_once __DIR__ . '/../config/database.php';
            try {
                $db = getDB();
                $stmt = $db->prepare("
                    SELECT pr.reset_id, pr.user_id, u.email, u.full_name 
                    FROM password_resets pr
                    LEFT JOIN users u ON pr.user_id = u.user_id
                    WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
                ");
                $stmt->execute([$token]);
                $user_data = $stmt->fetch();

                if ($user_data) {
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
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?")
                               ->execute([$password_hash, $user_data['user_id']]);
                            $db->prepare("UPDATE password_resets SET used = 1 WHERE reset_id = ?")
                               ->execute([$user_data['reset_id']]);

                            try {
                                require_once __DIR__ . '/../helpers/logger.php';
                                $logger = getLogger();
                                $logger->logAdminAction($user_data['user_id'], 'password_reset', 'user', $user_data['user_id'], [
                                    'email' => $user_data['email'],
                                    'user_name' => $user_data['full_name']
                                ]);
                            } catch (Exception $logError) {}

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

        return [
            'success' => $success,
            'error' => $error,
            'valid_token' => $valid_token,
            'token' => $token
        ];
    }

    public function changePassword() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['must_change_password']) || !$_SESSION['must_change_password']) {
            header('Location: ' . url('index.php'));
            exit;
        }

        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../config/database.php';
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
                    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? AND status = 'active'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($current_password, $user['password_hash'])) {
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET password_hash = ?, requires_password_change = 0, updated_at = NOW() WHERE user_id = ?");
                        $stmt->execute([$new_password_hash, $_SESSION['user_id']]);

                        unset($_SESSION['temp_password_login']);
                        unset($_SESSION['must_change_password']);

                        try {
                            require_once __DIR__ . '/../helpers/logger.php';
                            $logger = getLogger();
                            $logger->logActivity($_SESSION['user_id'], 'password_changed', 'user', $_SESSION['user_id'], 'Password changed after reset', [
                                'email' => $user['email'],
                                'user_name' => $user['full_name']
                            ]);
                        } catch (Exception $logError) {}

                        $success = 'Đổi mật khẩu thành công! Bạn sẽ được chuyển đến trang chủ sau 3 giây.';
                        header("refresh:3;url=" . url('index.php'));
                    } else {
                        $error = 'Mật khẩu hiện tại không chính xác';
                    }
                } catch (Exception $e) {
                    $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
                }
            }
        }

        return [
            'success' => $success,
            'error' => $error
        ];
    }

    public function loginGoogle() {
        // Logic will be mainly handled in auth/login-google.php for now 
        // as it's heavily tied to redirects and curl, but we can move core logic here if needed.
        // For 100% functional parity, I'll keep the OAuth flow largely as is but separated.
    }

    public function logoutConfirm() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . url('auth/login.php'));
            exit;
        }
        return [
            'user_name' => $_SESSION['user_name'] ?? 'User'
        ];
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            try {
                require_once __DIR__ . '/../config/database.php';
                require_once __DIR__ . '/../helpers/logger.php';
                $logger = getLogger();
                $logger->logActivity($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], 'User logged out');
            } catch (Exception $e) {
                error_log("Logout logging error: " . $e->getMessage());
            }
        }

        require_once __DIR__ . '/../helpers/session-helper.php';
        destroySessionCompletely(false);

        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Location: login.php?logged_out=1');
        exit;
    }

    private function ensureForgotPasswordColumns($db) {
        $columnsToAdd = [
            'temp_password' => "ALTER TABLE users ADD COLUMN temp_password VARCHAR(255) NULL",
            'temp_password_expires' => "ALTER TABLE users ADD COLUMN temp_password_expires DATETIME NULL",
            'requires_password_change' => "ALTER TABLE users ADD COLUMN requires_password_change TINYINT(1) DEFAULT 0"
        ];

        foreach ($columnsToAdd as $column => $sql) {
            try {
                $checkColumn = $db->query("SHOW COLUMNS FROM users LIKE '$column'");
                if ($checkColumn->rowCount() == 0) {
                    $db->exec($sql);
                }
            } catch (Exception $colErr) {}
        }
    }

    private function generateRandomString($length = 10) {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
    }
}
