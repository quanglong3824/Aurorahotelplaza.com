<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine base path based on current directory
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$subdirs = ['room-details', 'apartment-details', 'auth', 'booking', 'profile', 'admin'];
$base_path = in_array($current_dir, $subdirs) ? '../' : '';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'customer';
?>
<!-- TopNavBar - Transparent Overlay -->
<header class="fixed top-0 z-50 flex items-center justify-center w-full bg-transparent transition-all duration-300">
    <div class="flex w-full max-w-7xl items-center justify-between whitespace-nowrap px-6 py-5">
        <div class="flex items-center gap-3">
            <img src="<?php echo $base_path; ?>assets/img/src/logo/logo-white-ui.png" 
                 data-logo-white="<?php echo $base_path; ?>assets/img/src/logo/logo-dark-ui.png"
                 data-logo-dark="<?php echo $base_path; ?>assets/img/src/logo/logo-white-ui.png"
                 alt="Aurora Hotel Plaza Logo" 
                 class="logo-image">
        </div>
        <nav class="hidden items-center gap-10 md:flex">
            <a class="text-base font-medium nav-link" href="<?php echo $base_path; ?>index.php">Trang chủ</a>
            
            <!-- Phòng & Căn hộ with Submenu -->
            <div class="submenu-wrapper">
                <a class="text-base font-medium nav-link submenu-trigger" href="<?php echo $base_path; ?>rooms.php">
                    Phòng & Căn hộ
                    <span class="material-symbols-outlined text-sm">expand_more</span>
                </a>
                <div class="submenu">
                    <a href="<?php echo $base_path; ?>rooms.php" class="submenu-item">Phòng</a>
                    <a href="<?php echo $base_path; ?>apartments.php" class="submenu-item submenu-item-badge">
                        Căn hộ
                        <span class="badge-new">Mới</span>
                    </a>
                </div>
            </div>

            <!-- Dịch vụ with Submenu -->
            <div class="submenu-wrapper">
                <a class="text-base font-medium nav-link submenu-trigger" href="<?php echo $base_path; ?>services.php">
                    Dịch vụ
                    <span class="material-symbols-outlined text-sm">expand_more</span>
                </a>
                <div class="submenu">
                    <a href="<?php echo $base_path; ?>wedding.php" class="submenu-item">Tổ chức tiệc cưới</a>
                    <a href="<?php echo $base_path; ?>conference.php" class="submenu-item">Tổ chức hội nghị</a>
                    <a href="<?php echo $base_path; ?>restaurant.php" class="submenu-item">Nhà hàng</a>
                    <a href="<?php echo $base_path; ?>office.php" class="submenu-item">Văn phòng cho thuê</a>
                </div>
            </div>

            <!-- Khám phá with Submenu -->
            <div class="submenu-wrapper">
                <a class="text-base font-medium nav-link submenu-trigger" href="<?php echo $base_path; ?>explore.php">
                    Khám phá
                    <span class="material-symbols-outlined text-sm">expand_more</span>
                </a>
                <div class="submenu">
                    <a href="<?php echo $base_path; ?>about.php" class="submenu-item">Giới thiệu</a>
                    <a href="<?php echo $base_path; ?>gallery.php" class="submenu-item">Thư viện ảnh</a>
                </div>
            </div>

            <a class="text-base font-medium nav-link" href="<?php echo $base_path; ?>blog.php">Bài viết</a>
            <a class="text-base font-medium nav-link" href="<?php echo $base_path; ?>contact.php">Liên hệ</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="<?php echo $base_path; ?>booking/index.php" class="btn-booking">
                <span class="truncate">Đặt phòng</span>
            </a>
            
            <?php if ($is_logged_in): ?>
            <!-- User Menu -->
            <div class="relative user-menu-wrapper">
                <button class="user-menu-btn">
                    <span class="material-symbols-outlined text-xl">account_circle</span>
                    <span class="hidden md:inline"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="material-symbols-outlined text-sm">expand_more</span>
                </button>
                <div class="user-menu">
                    <div class="user-menu-header">
                        <span class="material-symbols-outlined">account_circle</span>
                        <div>
                            <div class="font-semibold"><?php echo htmlspecialchars($user_name); ?></div>
                            <div class="text-xs text-text-secondary-light dark:text-text-secondary-dark">
                                <?php 
                                $role_names = [
                                    'customer' => 'Khách hàng',
                                    'receptionist' => 'Lễ tân',
                                    'sale' => 'Sale',
                                    'admin' => 'Quản trị viên'
                                ];
                                echo $role_names[$user_role] ?? 'Khách hàng';
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="user-menu-divider"></div>
                    <a href="<?php echo $base_path; ?>profile/index.php" class="user-menu-item">
                        <span class="material-symbols-outlined">person</span>
                        Thông tin cá nhân
                    </a>
                    <a href="<?php echo $base_path; ?>profile/bookings.php" class="user-menu-item">
                        <span class="material-symbols-outlined">hotel</span>
                        Lịch sử đặt phòng
                    </a>
                    <a href="<?php echo $base_path; ?>profile/loyalty.php" class="user-menu-item">
                        <span class="material-symbols-outlined">stars</span>
                        Điểm thưởng
                    </a>
                    <?php if (in_array($user_role, ['admin', 'sale', 'receptionist'])): ?>
                    <div class="user-menu-divider"></div>
                    <a href="<?php echo $base_path; ?>admin/index.php" class="user-menu-item">
                        <span class="material-symbols-outlined">dashboard</span>
                        Quản trị
                    </a>
                    <?php endif; ?>
                    <div class="user-menu-divider"></div>
                    <a href="<?php echo $base_path; ?>auth/logout.php" class="user-menu-item text-red-600 dark:text-red-400">
                        <span class="material-symbols-outlined">logout</span>
                        Đăng xuất
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- Login/Register Buttons -->
            <a href="<?php echo $base_path; ?>auth/login.php" class="auth-btn">
                <span class="material-symbols-outlined text-xl">login</span>
                <span class="hidden md:inline">Đăng nhập</span>
            </a>
            <?php endif; ?>
            
            <button class="lang-btn">
                <span class="material-symbols-outlined text-xl">language</span>
            </button>
        </div>
    </div>
</header>
