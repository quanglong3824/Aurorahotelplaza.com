<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent browser caching of pages with user data
if (isset($_SESSION['user_id'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}

// Load environment configuration if not loaded
if (!defined('BASE_URL')) {
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    $subdirs = ['room-details', 'apartment-details', 'auth', 'booking', 'profile', 'admin', 'services-pages', 'payment'];
    $config_path = in_array($current_dir, $subdirs) ? '../config/environment.php' : 'config/environment.php';
    require_once __DIR__ . '/../config/environment.php';
}

// Load language helper
require_once __DIR__ . '/../helpers/language.php';
$current_lang = initLanguage();

// Load session helper và kiểm tra user còn tồn tại
require_once __DIR__ . '/../helpers/session-helper.php';

// Kiểm tra và xóa session không hợp lệ (user_id = 0 hoặc user đã bị xóa/banned)
if (isset($_SESSION['user_id'])) {
    // Chỉ verify database mỗi 5 phút để tránh query quá nhiều
    $last_verify = $_SESSION['last_user_verify'] ?? 0;
    if (time() - $last_verify > 300) { // 5 phút
        if (!verifyUserExists()) {
            // User đã bị xóa hoặc banned, session đã được xóa bởi verifyUserExists
            // Redirect về trang chủ
            $current_dir = basename(dirname($_SERVER['PHP_SELF']));
            $subdirs = ['room-details', 'apartment-details', 'auth', 'booking', 'profile', 'admin', 'services-pages', 'payment'];
            $redirect_path = in_array($current_dir, $subdirs) ? '../index.php' : 'index.php';
            header('Location: ' . $redirect_path . '?logged_out=account_removed');
            exit;
        }
        $_SESSION['last_user_verify'] = time();
    }
}

// Determine base path based on current directory
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$subdirs = ['room-details', 'apartment-details', 'auth', 'booking', 'profile', 'admin'];
$base_path = in_array($current_dir, $subdirs) ? '../' : '';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'customer';

// Detect if current page has hero banner
$current_page = basename($_SERVER['PHP_SELF'], '.php');
// Detect if current page has hero banner
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$pages_with_hero = ['index', 'rooms', 'apartments', 'about', 'services', 'gallery', 'explore', 'wedding', 'conference', 'restaurant', 'office', 'contact', 'login', 'register', 'forgot-password', 'reset-password', 'blog', 'confirmation']; // Added confirmation
$has_hero = in_array($current_page, $pages_with_hero) || in_array($current_dir, ['room-details', 'apartment-details', 'booking']); // Added booking

// Pages without hero need solid header with dark logo on white background
$pages_solid_header = []; // Removed confirmation
$is_solid_page = in_array($current_page, $pages_solid_header);
$header_class = $has_hero ? 'header-transparent' : 'header-solid';

// Force scrolled state for solid pages (profile, etc.)
$force_scrolled = $is_solid_page ? 'header-scrolled' : '';

// Pages with fixed transparent header (always transparent, always white logo)
$pages_fixed_transparent = ['blog', 'blog-detail', 'login', 'register', 'forgot-password', 'services', 'service-detail', 'about', 'contact', 'cancellation-policy', 'privacy', 'terms', 'rooms', 'apartments', 'explore', 'index', 'bookings', 'loyalty', 'edit', 'booking-detail', 'confirmation']; // Added confirmation
$is_fixed_transparent = in_array($current_page, $pages_fixed_transparent) || in_array($current_dir, ['profile', 'room-details', 'apartment-details', 'booking']); // Added booking
?>
<!-- TopNavBar - Smart Header -->
<header id="main-header"
    class="fixed top-0 z-50 w-full transition-all duration-300 <?php echo $header_class; ?> <?php echo $force_scrolled; ?>"
    data-has-hero="<?php echo $has_hero ? 'true' : 'false'; ?>"
    data-force-scrolled="<?php echo $is_solid_page ? 'true' : 'false'; ?>"
    data-fixed-transparent="<?php echo $is_fixed_transparent ? 'true' : 'false'; ?>">
    <div class="mx-auto flex w-full max-w-7xl items-center justify-between whitespace-nowrap px-6 py-5">
        <div class="flex items-center gap-3">
            <img id="header-logo"
                src="<?php echo $base_path; ?>assets/img/src/logo/<?php echo ($has_hero && !$is_solid_page) ? 'logo-dark-ui.png' : 'logo-white-ui.png'; ?>"
                data-logo-white="<?php echo $base_path; ?>assets/img/src/logo/logo-white-ui.png"
                data-logo-dark="<?php echo $base_path; ?>assets/img/src/logo/logo-dark-ui.png"
                alt="Aurora Hotel Plaza Logo" class="h-16 w-auto transition-all duration-300">
        </div>
        <nav class="hidden items-center gap-10 md:flex">
            <a class="text-base font-medium nav-link"
                href="<?php echo $base_path; ?>index.php"><?php _e('nav.home'); ?></a>

            <!-- Phòng & Căn hộ with Submenu -->
            <div class="submenu-wrapper">
                <a class="text-base font-medium nav-link submenu-trigger" href="<?php echo $base_path; ?>rooms.php">
                    <?php _e('nav.rooms'); ?>
                    <span class="material-symbols-outlined text-sm">expand_more</span>
                </a>
                <div class="submenu">
                    <a href="<?php echo $base_path; ?>rooms.php" class="submenu-item"><?php _e('nav.rooms_only'); ?></a>
                    <a href="<?php echo $base_path; ?>apartments.php" class="submenu-item submenu-item-badge">
                        <?php _e('nav.apartments'); ?>
                        <span class="badge-new"><?php _e('common.new'); ?></span>
                    </a>
                </div>
            </div>

            <!-- Dịch vụ with Submenu -->
            <div class="submenu-wrapper">
                <a class="text-base font-medium nav-link submenu-trigger" href="<?php echo $base_path; ?>services.php">
                    <?php _e('nav.services'); ?>
                    <span class="material-symbols-outlined text-sm">expand_more</span>
                </a>
                <div class="submenu">
                    <a href="<?php echo $base_path; ?>service-detail.php?slug=wedding-service"
                        class="submenu-item"><?php _e('services_menu.wedding'); ?></a>
                    <a href="<?php echo $base_path; ?>service-detail.php?slug=conference-service"
                        class="submenu-item"><?php _e('services_menu.conference'); ?></a>
                    <a href="<?php echo $base_path; ?>service-detail.php?slug=aurora-restaurant"
                        class="submenu-item"><?php _e('services_menu.restaurant'); ?></a>
                    <a href="<?php echo $base_path; ?>service-detail.php?slug=office-rental"
                        class="submenu-item"><?php _e('services_menu.office'); ?></a>
                </div>
            </div>

            <!-- Khám phá with Submenu -->
            <div class="submenu-wrapper">
                <a class="text-base font-medium nav-link submenu-trigger" href="<?php echo $base_path; ?>explore.php">
                    <?php _e('nav.explore'); ?>
                    <span class="material-symbols-outlined text-sm">expand_more</span>
                </a>
                <div class="submenu">
                    <a href="<?php echo $base_path; ?>about.php" class="submenu-item"><?php _e('nav.about'); ?></a>
                    <a href="<?php echo $base_path; ?>gallery.php" class="submenu-item"><?php _e('nav.gallery'); ?></a>
                    <a href="<?php echo $base_path; ?>blog.php" class="submenu-item"><?php _e('nav.blog'); ?></a>
                </div>
            </div>
            <a class="text-base font-medium nav-link"
                href="<?php echo $base_path; ?>contact.php"><?php _e('nav.contact'); ?></a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="<?php echo $base_path; ?>booking/index.php" class="btn-booking">
                <span class="truncate"><?php _e('nav.book_now'); ?></span>
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
                                    <?php echo __('roles.' . $user_role); ?>
                                </div>
                            </div>
                        </div>
                        <div class="user-menu-divider"></div>
                        <a href="<?php echo $base_path; ?>profile/index.php" class="user-menu-item">
                            <span class="material-symbols-outlined">person</span>
                            <?php _e('nav.profile'); ?>
                        </a>
                        <a href="<?php echo $base_path; ?>profile/bookings.php" class="user-menu-item">
                            <span class="material-symbols-outlined">hotel</span>
                            <?php _e('nav.my_bookings'); ?>
                        </a>
                        <a href="<?php echo $base_path; ?>profile/loyalty.php" class="user-menu-item">
                            <span class="material-symbols-outlined">stars</span>
                            <?php _e('nav.loyalty'); ?>
                        </a>
                        <a href="<?php echo $base_path; ?>room-map-user.php" class="user-menu-item">
                            <span class="material-symbols-outlined">map</span>
                            <?php _e('nav.room_map'); ?>
                        </a>
                        <?php if (in_array($user_role, ['admin', 'sale', 'receptionist'])): ?>
                            <div class="user-menu-divider"></div>
                            <a href="<?php echo $base_path; ?>admin/index.php" class="user-menu-item">
                                <span class="material-symbols-outlined">dashboard</span>
                                <?php _e('nav.admin'); ?>
                            </a>
                        <?php endif; ?>
                        <div class="user-menu-divider"></div>
                        <a href="<?php echo $base_path; ?>auth/logout.php"
                            class="user-menu-item text-red-600 dark:text-red-400">
                            <span class="material-symbols-outlined">logout</span>
                            <?php _e('nav.logout'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Login/Register Buttons -->
                <a href="<?php echo $base_path; ?>auth/login.php" class="auth-btn">
                    <span class="material-symbols-outlined text-xl">login</span>
                    <span class="hidden md:inline"><?php _e('nav.login'); ?></span>
                </a>
            <?php endif; ?>

            <!-- Language Switcher - Liquid Glass -->
            <div class="relative lang-switcher-wrapper">
                <button class="lang-btn" id="langBtn" onclick="toggleLangMenu()">
                    <span class="lang-code"><?php echo $current_lang === 'vi' ? 'Vi' : 'En'; ?></span>
                </button>
                <div id="langMenu" class="lang-menu">
                    <a href="?lang=vi" class="lang-option <?php echo $current_lang === 'vi' ? 'active' : ''; ?>">
                        <span class="lang-info">
                            <span>Tiếng Việt</span>
                        </span>
                        <span class="material-symbols-outlined">check</span>
                    </a>
                    <a href="?lang=en" class="lang-option <?php echo $current_lang === 'en' ? 'active' : ''; ?>">
                        <span class="lang-info">
                            <span>English</span>
                        </span>
                        <span class="material-symbols-outlined">check</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Floating Mobile Menu - Liquid Glass -->
<div id="floatingMenu" class="floating-menu">
    <div class="floating-menu-overlay"></div>

    <div class="floating-menu-items">
        <!-- Trang chủ -->
        <div class="floating-menu-item">
            <span class="floating-menu-label"><?php _e('nav.home'); ?></span>
            <a href="<?php echo $base_path; ?>index.php" class="floating-menu-btn"
                aria-label="<?php _e('nav.home'); ?>">
                <span class="material-symbols-outlined">home</span>
            </a>
        </div>

        <!-- Phòng & Căn hộ - có submenu -->
        <div class="floating-menu-item floating-submenu-wrapper">
            <span class="floating-menu-label"><?php _e('nav.rooms'); ?></span>
            <a href="<?php echo $base_path; ?>rooms.php" class="floating-menu-btn"
                aria-label="<?php _e('nav.rooms'); ?>" aria-haspopup="true">
                <span class="material-symbols-outlined">hotel</span>
            </a>
            <div class="floating-submenu">
                <a href="<?php echo $base_path; ?>rooms.php" class="floating-submenu-item">
                    <span class="material-symbols-outlined">bed</span>
                    <?php _e('nav.rooms_only'); ?>
                </a>
                <a href="<?php echo $base_path; ?>apartments.php" class="floating-submenu-item">
                    <span class="material-symbols-outlined">apartment</span>
                    <?php _e('nav.apartments'); ?>
                    <span class="floating-badge-new"><?php _e('common.new'); ?></span>
                </a>
            </div>
        </div>

        <!-- Dịch vụ - có submenu -->
        <div class="floating-menu-item floating-submenu-wrapper">
            <span class="floating-menu-label"><?php _e('nav.services'); ?></span>
            <a href="<?php echo $base_path; ?>services.php" class="floating-menu-btn"
                aria-label="<?php _e('nav.services'); ?>" aria-haspopup="true">
                <span class="material-symbols-outlined">room_service</span>
            </a>
            <div class="floating-submenu">
                <a href="<?php echo $base_path; ?>service-detail.php?slug=wedding-service"
                    class="floating-submenu-item">
                    <span class="material-symbols-outlined">celebration</span>
                    <?php _e('services_menu.wedding'); ?>
                </a>
                <a href="<?php echo $base_path; ?>service-detail.php?slug=conference-service"
                    class="floating-submenu-item">
                    <span class="material-symbols-outlined">groups</span>
                    <?php _e('services_menu.conference'); ?>
                </a>
                <a href="<?php echo $base_path; ?>service-detail.php?slug=aurora-restaurant"
                    class="floating-submenu-item">
                    <span class="material-symbols-outlined">restaurant</span>
                    <?php _e('services_menu.restaurant'); ?>
                </a>
                <a href="<?php echo $base_path; ?>service-detail.php?slug=office-rental" class="floating-submenu-item">
                    <span class="material-symbols-outlined">business</span>
                    <?php _e('services_menu.office'); ?>
                </a>
            </div>
        </div>

        <!-- Khám phá - có submenu -->
        <div class="floating-menu-item floating-submenu-wrapper">
            <span class="floating-menu-label"><?php _e('nav.explore'); ?></span>
            <a href="<?php echo $base_path; ?>explore.php" class="floating-menu-btn"
                aria-label="<?php _e('nav.explore'); ?>" aria-haspopup="true">
                <span class="material-symbols-outlined">explore</span>
            </a>
            <div class="floating-submenu">
                <a href="<?php echo $base_path; ?>about.php" class="floating-submenu-item">
                    <span class="material-symbols-outlined">info</span>
                    <?php _e('nav.about'); ?>
                </a>
                <a href="<?php echo $base_path; ?>gallery.php" class="floating-submenu-item">
                    <span class="material-symbols-outlined">photo_library</span>
                    <?php _e('nav.gallery'); ?>
                </a>
                <a href="<?php echo $base_path; ?>blog.php" class="floating-submenu-item">
                    <span class="material-symbols-outlined">article</span>
                    <?php _e('nav.blog'); ?>
                </a>
            </div>
        </div>

        <!-- Liên hệ -->
        <div class="floating-menu-item">
            <span class="floating-menu-label"><?php _e('nav.contact'); ?></span>
            <a href="<?php echo $base_path; ?>contact.php" class="floating-menu-btn"
                aria-label="<?php _e('nav.contact'); ?>">
                <span class="material-symbols-outlined">call</span>
            </a>
        </div>

        <!-- Đặt phòng -->
        <div class="floating-menu-item">
            <span class="floating-menu-label"><?php _e('nav.book_now'); ?></span>
            <a href="<?php echo $base_path; ?>booking/index.php" class="floating-menu-btn"
                aria-label="<?php _e('nav.book_now'); ?>">
                <span class="material-symbols-outlined">calendar_month</span>
            </a>
        </div>

        <?php if ($is_logged_in): ?>
            <!-- Tài khoản -->
            <div class="floating-menu-item">
                <span class="floating-menu-label"><?php _e('nav.profile'); ?></span>
                <a href="<?php echo $base_path; ?>profile/index.php" class="floating-menu-btn"
                    aria-label="<?php _e('nav.profile'); ?>">
                    <span class="material-symbols-outlined">account_circle</span>
                </a>
            </div>
        <?php else: ?>
            <!-- Đăng nhập -->
            <div class="floating-menu-item">
                <span class="floating-menu-label"><?php _e('nav.login'); ?></span>
                <a href="<?php echo $base_path; ?>auth/login.php" class="floating-menu-btn"
                    aria-label="<?php _e('nav.login'); ?>">
                    <span class="material-symbols-outlined">login</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Toggle Button -->
    <button class="floating-menu-toggle" aria-label="Mở menu" aria-expanded="false" aria-controls="floatingMenu">
        <div class="floating-menu-icon">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </button>
</div>

<?php if ($current_dir !== 'booking'): ?>
    <!-- Floating Booking Toggle Button (Independent - Above Menu) - Hidden on booking page -->
    <button class="floating-booking-toggle" aria-label="<?php _e('nav.book_now'); ?>" onclick="toggleFloatingBookingForm()">
        <span class="material-symbols-outlined">calendar_month</span>
    </button>

    <!-- Floating Booking Form Popup (Mobile) -->
    <div id="floatingBookingForm" class="floating-booking-popup">
        <div class="floating-booking-overlay" onclick="toggleFloatingBookingForm()"></div>
        <div class="floating-booking-content">
            <div class="floating-booking-header">
                <h3><?php _e('hero.search'); ?></h3>
                <button class="floating-booking-close" onclick="toggleFloatingBookingForm()">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="<?php echo $base_path; ?>booking/index.php" method="GET" class="floating-booking-form-inner">
                <div class="floating-booking-field">
                    <label for="floating-checkin">
                        <span class="material-symbols-outlined">calendar_today</span>
                        <?php _e('hero.check_in'); ?>
                    </label>
                    <input type="date" id="floating-checkin" name="check_in" min="<?php echo date('Y-m-d'); ?>"
                        value="<?php echo date('Y-m-d'); ?>" class="glass-input-solid">
                </div>
                <div class="floating-booking-field">
                    <label for="floating-checkout">
                        <span class="material-symbols-outlined">event</span>
                        <?php _e('hero.check_out'); ?>
                    </label>
                    <input type="date" id="floating-checkout" name="check_out"
                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                        value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" class="glass-input-solid">
                </div>
                <div class="floating-booking-row">
                    <div class="floating-booking-field">
                        <label for="floating-adults">
                            <span class="material-symbols-outlined">person</span>
                            <?php _e('hero.adults'); ?>
                        </label>
                        <select id="floating-adults" name="adults" class="glass-input-solid glass-select">
                            <option value="1">1 <?php _e('hero.person'); ?></option>
                            <option value="2" selected>2 <?php _e('hero.person'); ?></option>
                            <option value="3">3 <?php _e('hero.person'); ?></option>
                            <option value="4">4 <?php _e('hero.person'); ?></option>
                        </select>
                    </div>
                    <div class="floating-booking-field">
                        <label for="floating-children">
                            <span class="material-symbols-outlined">child_care</span>
                            <?php _e('hero.children'); ?>
                        </label>
                        <select id="floating-children" name="children" class="glass-input-solid glass-select">
                            <option value="0" selected>0 <?php _e('hero.child'); ?></option>
                            <option value="1">1 <?php _e('hero.child'); ?></option>
                            <option value="2">2 <?php _e('hero.child'); ?></option>
                            <option value="3">3 <?php _e('hero.child'); ?></option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-glass-primary floating-booking-submit">
                    <span class="material-symbols-outlined">search</span>
                    <?php _e('hero.search'); ?>
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Header Styles & Script -->
<?php $asset_version = '1.0.8'; // Update this when assets change ?>
<link rel="stylesheet" href="<?php echo asset('css/header-styles.css'); ?>?v=<?php echo $asset_version; ?>">
<link rel="stylesheet" href="<?php echo asset('css/liquid-glass.css'); ?>?v=<?php echo $asset_version; ?>">
<link rel="stylesheet" href="<?php echo asset('css/floating-menu.css'); ?>?v=<?php echo $asset_version; ?>">
<script src="<?php echo asset('js/header-scroll.js'); ?>?v=<?php echo $asset_version; ?>" defer></script>
<script src="<?php echo asset('js/floating-menu.js'); ?>?v=<?php echo $asset_version; ?>" defer></script>

<script>
    (function () {
        try {
            if (!document.querySelector('link[rel="icon"]')) {
                const link = document.createElement('link');
                link.rel = 'icon';
                link.type = 'image/png';
                link.href = '<?php echo $base_path; ?>assets/img/src/logo/favicon.png';
                document.head.appendChild(link);
            }
        } catch (e) { }
    })();
</script>