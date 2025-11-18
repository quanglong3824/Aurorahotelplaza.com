<?php
session_start();

// Check if user is logged in and has admin/staff role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản trị - Aurora Hotel Plaza</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-24 pb-16">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-text-primary-light dark:text-text-primary-dark">
                Bảng điều khiển quản trị
            </h1>
            <p class="mt-2 text-text-secondary-light dark:text-text-secondary-dark">
                Chào mừng <?php echo htmlspecialchars($_SESSION['user_name']); ?> - 
                <?php 
                $role_names = [
                    'admin' => 'Quản trị viên',
                    'sale' => 'Nhân viên bán hàng',
                    'receptionist' => 'Lễ tân'
                ];
                echo $role_names[$_SESSION['user_role']] ?? $_SESSION['user_role'];
                ?>
            </p>
        </div>

        <!-- Coming Soon Message -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm p-8 text-center">
            <span class="material-symbols-outlined text-6xl text-accent mb-4 block">dashboard</span>
            <h2 class="text-2xl font-bold text-text-primary-light dark:text-text-primary-dark mb-2">
                Hệ thống quản trị đang phát triển
            </h2>
            <p class="text-text-secondary-light dark:text-text-secondary-dark mb-6">
                Các tính năng quản lý đặt phòng, khách hàng và báo cáo sẽ sớm được cập nhật.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-2xl mx-auto">
                <div class="p-4 bg-background-light dark:bg-background-dark rounded-lg">
                    <span class="material-symbols-outlined text-2xl text-accent block mb-2">hotel</span>
                    <h3 class="font-semibold">Quản lý đặt phòng</h3>
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Sắp có</p>
                </div>
                <div class="p-4 bg-background-light dark:bg-background-dark rounded-lg">
                    <span class="material-symbols-outlined text-2xl text-accent block mb-2">people</span>
                    <h3 class="font-semibold">Quản lý khách hàng</h3>
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Sắp có</p>
                </div>
                <div class="p-4 bg-background-light dark:bg-background-dark rounded-lg">
                    <span class="material-symbols-outlined text-2xl text-accent block mb-2">analytics</span>
                    <h3 class="font-semibold">Báo cáo</h3>
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Sắp có</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</div>

<script src="../assets/js/main.js"></script>
</body>
</html>