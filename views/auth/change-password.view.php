<!DOCTYPE html>
<html translate="no" class="light" lang="vi">
<head>
    <meta name="google" content="notranslate" />
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title>Đổi mật khẩu bắt buộc - Aurora Hotel Plaza</title>
<script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
<link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet"/>

<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo asset('css/liquid-glass.css'); ?>?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/auth/assets/css/auth.css?v=<?php echo time(); ?>">
</head>
<body class="auth-reset">
<div class="relative flex min-h-screen w-full flex-col">

<?php include __DIR__ . '/../../includes/header.php'; ?>

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
                <?php echo Security::getCSRFInput(); ?>
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>

</div>

<script src="<?php echo asset('js/main.js'); ?>?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>/auth/assets/js/auth.js?v=<?php echo time(); ?>"></script>
</body>
</html>
