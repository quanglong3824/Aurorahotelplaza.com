<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ./login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Đăng xuất - Aurora Hotel Plaza</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="../assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="./assets/css/auth.css">
</head>
<body class="auth-logout">
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
            <div class="icon-badge">
                <span class="material-symbols-outlined text-3xl text-accent">logout</span>
            </div>
            <h1 class="text-3xl font-bold mb-2">Đăng xuất</h1>
            <p class="text-text-secondary-light dark:text-text-secondary-dark">
                Bạn có chắc chắn muốn đăng xuất?
            </p>
        </div>

        <!-- Logout Confirmation -->
        <div class="auth-card">
            
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 mb-6">
                <div class="flex items-start">
                    <span class="material-symbols-outlined text-yellow-600 mr-3">info</span>
                    <div>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            Xin chào <strong><?php echo htmlspecialchars($user_name); ?></strong>!
                        </p>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                            Bạn sẽ cần đăng nhập lại để sử dụng các tính năng đặt phòng và quản lý tài khoản.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <!-- Confirm Logout -->
                <a href="./logout.php" class="btn-primary w-full flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">logout</span>
                    Xác nhận đăng xuất
                </a>

                <!-- Cancel -->
                <a href="../index.php" class="btn-secondary w-full flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Quay lại trang chủ
                </a>
            </div>

            <!-- Quick Actions -->
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-3">
                    Hoặc bạn có thể:
                </p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="../profile/index.php" class="text-sm text-accent hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">person</span>
                        Xem profile
                    </a>
                    <a href="../profile/bookings.php" class="text-sm text-accent hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">hotel</span>
                        Đặt phòng của tôi
                    </a>
                    <a href="../booking/index.php" class="text-sm text-accent hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add_circle</span>
                        Đặt phòng mới
                    </a>
                    <a href="../profile/loyalty.php" class="text-sm text-accent hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">stars</span>
                        Điểm thưởng
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</div>

<script src="../assets/js/main.js"></script>
<script src="./assets/js/auth.js"></script>

<style>
.btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    font-weight: 600;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    background: transparent;
    color: #6b7280;
    font-weight: 600;
    border: 2px solid #d1d5db;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    border-color: #d4af37;
    color: #d4af37;
}

.dark .btn-secondary {
    color: #9ca3af;
    border-color: #4b5563;
}

.dark .btn-secondary:hover {
    border-color: #d4af37;
    color: #d4af37;
}
</style>

</body>
</html>
