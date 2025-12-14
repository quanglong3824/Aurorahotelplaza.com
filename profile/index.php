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

// Get user information
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // User not found, logout
        header('Location: ../auth/logout.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    $error = "Có lỗi xảy ra khi tải thông tin người dùng.";
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Thông tin cá nhân - Aurora Hotel Plaza</title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
<link href="../assets/css/fonts.css" rel="stylesheet"/>
    
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-24 pb-16">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-text-primary-light dark:text-text-primary-dark">
                Thông tin cá nhân
            </h1>
            <p class="mt-2 text-text-secondary-light dark:text-text-secondary-dark">
                Quản lý thông tin tài khoản và cài đặt của bạn
            </p>
        </div>

        <?php if (isset($error)): ?>
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
            <div class="flex">
                <span class="material-symbols-outlined text-red-400 mr-2">error</span>
                <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- User Information Card -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center space-x-4 mb-6">
                <?php if ($user['avatar']): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                         alt="Avatar" class="w-20 h-20 rounded-full object-cover">
                <?php else: ?>
                    <div class="w-20 h-20 bg-accent rounded-full flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div>
                    <h2 class="text-2xl font-bold text-text-primary-light dark:text-text-primary-dark">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </h2>
                    <p class="text-text-secondary-light dark:text-text-secondary-dark">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <div class="flex items-center mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            <?php echo $user['user_role'] === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                       ($user['user_role'] === 'staff' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'); ?>">
                            <?php 
                            $roles = [
                                'customer' => 'Khách hàng',
                                'receptionist' => 'Lễ tân',
                                'sale' => 'Nhân viên bán hàng',
                                'admin' => 'Quản trị viên'
                            ];
                            echo $roles[$user['user_role']] ?? $user['user_role'];
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Account Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-1">
                        Họ và tên
                    </label>
                    <p class="text-text-primary-light dark:text-text-primary-dark">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-1">
                        Email
                    </label>
                    <p class="text-text-primary-light dark:text-text-primary-dark">
                        <?php echo htmlspecialchars($user['email']); ?>
                        <?php if ($user['email_verified']): ?>
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <span class="material-symbols-outlined text-xs mr-1">verified</span>
                                Đã xác thực
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-1">
                        Số điện thoại
                    </label>
                    <p class="text-text-primary-light dark:text-text-primary-dark">
                        <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Chưa cập nhật'; ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-1">
                        Ngày sinh
                    </label>
                    <p class="text-text-primary-light dark:text-text-primary-dark">
                        <?php echo $user['date_of_birth'] ? date('d/m/Y', strtotime($user['date_of_birth'])) : 'Chưa cập nhật'; ?>
                    </p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-1">
                        Địa chỉ
                    </label>
                    <p class="text-text-primary-light dark:text-text-primary-dark">
                        <?php echo $user['address'] ? htmlspecialchars($user['address']) : 'Chưa cập nhật'; ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-1">
                        Ngày tham gia
                    </label>
                    <p class="text-text-primary-light dark:text-text-primary-dark">
                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark mb-1">
                        Lần đăng nhập cuối
                    </label>
                    <p class="text-text-primary-light dark:text-text-primary-dark">
                        <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa có'; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="bookings.php" class="block p-6 bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <span class="material-symbols-outlined text-2xl text-accent mr-4">hotel</span>
                    <div>
                        <h3 class="font-semibold text-text-primary-light dark:text-text-primary-dark">Lịch sử đặt phòng</h3>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Xem các đặt phòng của bạn</p>
                    </div>
                </div>
            </a>
            
            <a href="loyalty.php" class="block p-6 bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <span class="material-symbols-outlined text-2xl text-accent mr-4">stars</span>
                    <div>
                        <h3 class="font-semibold text-text-primary-light dark:text-text-primary-dark">Điểm thưởng</h3>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Quản lý điểm tích lũy</p>
                    </div>
                </div>
            </a>
            
            <a href="edit.php" class="block p-6 bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <span class="material-symbols-outlined text-2xl text-accent mr-4">edit</span>
                    <div>
                        <h3 class="font-semibold text-text-primary-light dark:text-text-primary-dark">Chỉnh sửa thông tin</h3>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Cập nhật hồ sơ cá nhân</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</div>

<script src="../assets/js/main.js"></script>
</body>
</html>