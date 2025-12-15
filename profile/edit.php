<?php
session_start();

// Prevent caching - quan trọng để tránh hiển thị data cũ
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once '../config/database.php';
require_once '../helpers/logger.php';
require_once '../helpers/language.php';
initLanguage();

$success = '';
$error = '';

// Get user information
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: ../auth/logout.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Profile edit error: " . $e->getMessage());
    $error = "Có lỗi xảy ra khi tải thông tin người dùng.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate required fields
    if (empty($full_name)) {
        $error = 'Họ và tên không được để trống';
    } elseif (!empty($phone) && !preg_match('/^(0|\+84)[0-9]{9,10}$/', str_replace(' ', '', $phone))) {
        $error = 'Số điện thoại không hợp lệ';
    } elseif (!empty($new_password)) {
        // Validate password change
        if (empty($current_password)) {
            $error = 'Vui lòng nhập mật khẩu hiện tại để thay đổi mật khẩu';
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $error = 'Mật khẩu hiện tại không đúng';
        } elseif (strlen($new_password) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Xác nhận mật khẩu không khớp';
        }
    }
    
    if (!$error) {
        try {
            // Prepare update data
            $update_data = [
                'full_name' => $full_name,
                'phone' => $phone ?: null,
                'address' => $address ?: null,
                'date_of_birth' => $date_of_birth ?: null,
                'gender' => $gender ?: null,
                'user_id' => $_SESSION['user_id']
            ];
            
            // Update password if provided
            if (!empty($new_password)) {
                $update_data['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET full_name = ?, phone = ?, address = ?, date_of_birth = ?, gender = ?, password_hash = ?, updated_at = NOW() WHERE user_id = ?";
                $params = [$full_name, $phone ?: null, $address ?: null, $date_of_birth ?: null, $gender ?: null, $update_data['password_hash'], $_SESSION['user_id']];
            } else {
                $sql = "UPDATE users SET full_name = ?, phone = ?, address = ?, date_of_birth = ?, gender = ?, updated_at = NOW() WHERE user_id = ?";
                $params = [$full_name, $phone ?: null, $address ?: null, $date_of_birth ?: null, $gender ?: null, $_SESSION['user_id']];
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            // Update session data
            $_SESSION['user_name'] = $full_name;
            
            // Log the update
            $logger = getLogger();
            $logger->logActivity($_SESSION['user_id'], 'profile_update', 'user', $_SESSION['user_id'], 'User updated profile information', [
                'updated_fields' => array_keys(array_filter($update_data, function($v) { return $v !== null; })),
                'password_changed' => !empty($new_password)
            ]);
            
            $success = 'Cập nhật thông tin thành công!';
            
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = 'Có lỗi xảy ra khi cập nhật thông tin: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
    <title><?php _e('profile_edit.title'); ?></title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
<link href="../assets/css/fonts.css" rel="stylesheet"/>
    
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="index.php" class="inline-flex items-center gap-2 text-text-secondary-light dark:text-text-secondary-dark hover:text-accent transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                    <?php _e('profile_edit.back'); ?>
                </a>
            </div>
            <h1 class="text-3xl font-bold text-text-primary-light dark:text-text-primary-dark">
                <?php _e('profile_edit.page_title'); ?>
            </h1>
            <p class="mt-2 text-text-secondary-light dark:text-text-secondary-dark">
                <?php _e('profile_edit.page_subtitle'); ?>
            </p>
        </div>

        <?php if ($success): ?>
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4">
            <div class="flex">
                <span class="material-symbols-outlined text-green-400 mr-2">check_circle</span>
                <p class="text-green-700"><?php echo htmlspecialchars($success); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
            <div class="flex">
                <span class="material-symbols-outlined text-red-400 mr-2">error</span>
                <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            
            <!-- Personal Information Section -->
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">person</span>
                    <?php _e('profile_edit.personal_info'); ?>
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-2">
                            <?php _e('profile_edit.full_name'); ?> *
                        </label>
                        <input type="text" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-text-primary-light dark:text-text-primary-dark focus:ring-2 focus:ring-accent focus:border-accent transition-colors"
                               required>
                    </div>

                    <!-- Email (Read-only) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-2">
                            <?php _e('profile_edit.email'); ?>
                        </label>
                        <input type="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800 text-text-secondary-light dark:text-text-secondary-dark cursor-not-allowed"
                               readonly>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1">
                            <?php _e('profile_edit.email_readonly'); ?>
                        </p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-2">
                            <?php _e('profile_edit.phone'); ?>
                        </label>
                        <input type="tel" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-text-primary-light dark:text-text-primary-dark focus:ring-2 focus:ring-accent focus:border-accent transition-colors"
                               placeholder="0123456789">
                    </div>

                    <!-- Date of Birth -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-2">
                            <?php _e('profile_edit.dob'); ?>
                        </label>
                        <input type="date" name="date_of_birth" 
                               value="<?php echo $user['date_of_birth']; ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-text-primary-light dark:text-text-primary-dark focus:ring-2 focus:ring-accent focus:border-accent transition-colors">
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-2">
                            <?php _e('profile_edit.gender'); ?>
                        </label>
                        <select name="gender" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-text-primary-light dark:text-text-primary-dark focus:ring-2 focus:ring-accent focus:border-accent transition-colors">
                            <option value="">-- <?php _e('profile_edit.select_gender'); ?> --</option>
                            <option value="male" <?php echo $user['gender'] === 'male' ? 'selected' : ''; ?>><?php _e('profile_edit.male'); ?></option>
                            <option value="female" <?php echo $user['gender'] === 'female' ? 'selected' : ''; ?>><?php _e('profile_edit.female'); ?></option>
                            <option value="other" <?php echo $user['gender'] === 'other' ? 'selected' : ''; ?>><?php _e('profile_edit.other'); ?></option>
                        </select>
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-2">
                            <?php _e('profile_edit.address'); ?>
                        </label>
                        <textarea name="address" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-text-primary-light dark:text-text-primary-dark focus:ring-2 focus:ring-accent focus:border-accent transition-colors"
                                  placeholder="<?php _e('profile_edit.enter_address'); ?>"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Password Change Section -->
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">lock</span>
                    <?php _e('profile_edit.change_password'); ?>
                </h2>
                
                <div class="space-y-4">
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                        <?php _e('profile_edit.password_hint'); ?>
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Current Password -->
                        <div>
                            <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-2">
                                <?php _e('profile_edit.current_password'); ?>
                            </label>
                            <div class="relative">
                                <input type="password" name="current_password" id="current_password"
                                       class="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-text-primary-light dark:text-text-primary-dark focus:ring-2 focus:ring-accent focus:border-accent transition-colors"
                                       placeholder="Nhập mật khẩu hiện tại">
                                <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('current_password')">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div>
                            <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-2">
                                <?php _e('profile_edit.new_password'); ?>
                            </label>
                            <div class="relative">
                                <input type="password" name="new_password" id="new_password"
                                       class="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-text-primary-light dark:text-text-primary-dark focus:ring-2 focus:ring-accent focus:border-accent transition-colors"
                                       placeholder="Nhập mật khẩu mới">
                                <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('new_password')">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-2">
                                <?php _e('profile_edit.confirm_password'); ?>
                            </label>
                            <div class="relative">
                                <input type="password" name="confirm_password" id="confirm_password"
                                       class="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-text-primary-light dark:text-text-primary-dark focus:ring-2 focus:ring-accent focus:border-accent transition-colors"
                                       placeholder="Xác nhận mật khẩu mới">
                                <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('confirm_password')">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-4">
                <a href="index.php" class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-text-secondary-light dark:text-text-secondary-dark rounded-lg hover:border-accent hover:text-accent transition-colors">
                    <?php _e('profile_edit.cancel'); ?>
                </a>
                <button type="submit" class="flex-1 px-6 py-3 bg-accent text-white font-semibold rounded-lg hover:bg-accent/90 transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">save</span>
                    <?php _e('profile_edit.save_changes'); ?>
                </button>
            </div>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</div>

<script src="../assets/js/main.js"></script>
<script>
// Password visibility toggle
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector('button');
    const icon = button.querySelector('.material-symbols-outlined');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    // Password match validation
    function validatePasswordMatch() {
        if (newPassword.value && confirmPassword.value) {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Mật khẩu không khớp');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
    }
    
    newPassword.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        const currentPassword = document.getElementById('current_password').value;
        const newPasswordValue = newPassword.value;
        
        if (newPasswordValue && !currentPassword) {
            e.preventDefault();
            alert('Vui lòng nhập mật khẩu hiện tại để thay đổi mật khẩu');
            document.getElementById('current_password').focus();
        }
    });
});
</script>
</body>
</html>