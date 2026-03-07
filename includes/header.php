<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── AI Error Tracker ─────────────────────────────────────────────────────────
require_once __DIR__ . '/../helpers/error-tracker.php';
AuroraErrorTracker::init();

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

// Load DB bilingual helper (db_text, db_echo, db_html)
require_once __DIR__ . '/../helpers/lang-db.php';

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
$pages_with_hero = ['index', 'rooms', 'apartments', 'about', 'services', 'gallery', 'explore', 'wedding', 'conference', 'restaurant', 'office', 'contact', 'login', 'register', 'forgot-password', 'reset-password', 'blog', 'confirmation'];
$has_hero = in_array($current_page, $pages_with_hero) || in_array($current_dir, ['room-details', 'apartment-details', 'booking']);

// Pages without hero need solid header
$pages_solid_header = [];
$is_solid_page = in_array($current_page, $pages_solid_header);
$header_class = $has_hero ? 'header-transparent' : 'header-solid';

// Force scrolled state for solid pages
$force_scrolled = $is_solid_page ? 'header-scrolled' : '';

// Pages with fixed transparent header
$pages_fixed_transparent = ['blog', 'blog-detail', 'login', 'register', 'forgot-password', 'services', 'service-detail', 'about', 'contact', 'cancellation-policy', 'privacy', 'terms', 'rooms', 'apartments', 'explore', 'index', 'bookings', 'loyalty', 'edit', 'booking-detail', 'confirmation'];
$is_fixed_transparent = in_array($current_page, $pages_fixed_transparent) || in_array($current_dir, ['profile', 'room-details', 'apartment-details', 'booking']);
?>
<!-- Scroll Progress Bar -->
<div id="scroll-progress"
    style="position: fixed; top: 0; left: 0; width: 0%; height: 3px; background: #d4af37; z-index: 99999; transition: width 0.1s ease-out;">
</div>

<!-- TopNavBar - Smart Header -->
<header id="main-header"
    class="fixed top-0 z-50 w-full transition-all duration-300 <?php echo $header_class; ?> <?php echo $force_scrolled; ?>"
    data-has-hero="<?php echo $has_hero ? 'true' : 'false'; ?>"
    data-force-scrolled="<?php echo $is_solid_page ? 'true' : 'false'; ?>"
    data-fixed-transparent="<?php echo $is_fixed_transparent ? 'true' : 'false'; ?>">

    <!-- Mini Tracking Topbar -->
    <div class="tracking-topbar w-full flex items-center justify-between px-4 py-1.5 md:px-6 z-[60] bg-gray-900 text-xs shadow relative overflow-hidden min-h-[40px] sm:min-h-0"
        id="trackingTopbar">

        <!-- Left side / Mobile Default State -->
        <div
            class="flex items-center gap-1.5 text-gray-400 font-medium w-full sm:w-auto justify-between sm:justify-start">
            <div class="flex items-center gap-1.5 overflow-hidden">
                <span class="material-symbols-outlined shrink-0" style="font-size: 16px;">travel_explore</span>
                <span class="truncate"><?php _e('tracking.title'); ?></span>
            </div>
            <button type="button" onclick="toggleTrackForm(true)"
                class="sm:hidden flex items-center justify-center gap-1 bg-gray-800 hover:bg-gray-700 border border-gray-700 text-white px-2 py-1 rounded shrink-0 ml-2 transition-colors">
                <span class="material-symbols-outlined" style="font-size: 14px;">search</span>
                <?php _e('tracking.search_btn'); ?>
            </button>
            <input type="hidden" id="trackMode" value="latest" />
        </div>

        <!-- Form Container -->
        <form id="topbarTrackForm"
            class="absolute inset-0 px-4 bg-gray-900 flex items-center gap-2 m-0 w-full justify-end opacity-0 pointer-events-none sm:relative sm:inset-auto sm:px-0 sm:bg-transparent sm:w-auto sm:opacity-100 sm:pointer-events-auto transition-all duration-300 ease-in-out z-10"
            onsubmit="handleTrackBooking(event)">

            <!-- Mobile Close Form Button -->
            <button type="button" onclick="toggleTrackForm(false)"
                class="sm:hidden flex items-center text-gray-400 hover:text-white shrink-0 mr-1 p-1">
                <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back_ios_new</span>
            </button>

            <span id="trackErrorMsg" class="hidden text-red-400 font-medium text-[11px] whitespace-nowrap"><span
                    class="error-text">Lỗi</span></span>

            <div class="relative w-full sm:w-64">
                <input type="text" id="trackInput"
                    placeholder="<?php echo htmlspecialchars(__('tracking.placeholder')); ?>" required
                    class="px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-md placeholder-gray-500 focus:outline-none w-full transition-all pr-8"
                    style="color: #f3f4f6 !important; -webkit-text-fill-color: #f3f4f6 !important; background-color: #1f2937 !important;"
                    oninput="document.getElementById('trackClearBtn').style.display = this.value ? 'flex' : 'none';" />
                <button type="button" id="trackClearBtn" style="display: none;"
                    onclick="document.getElementById('trackInput').value=''; this.style.display='none'; document.getElementById('trackInput').focus();"
                    class="absolute inset-y-0 right-0 flex items-center justify-center w-8 text-white hover:text-gray-300 z-10 bg-transparent">
                    <span class="material-symbols-outlined" style="font-size: 14px; font-weight: bold;">close</span>
                </button>
            </div>

            <button type="submit"
                class="bg-[#d4af37] hover:bg-[#b5952f] text-white font-bold px-3 py-1.5 rounded-md transition-colors whitespace-nowrap flex items-center justify-center min-w-[36px] gap-1 shrink-0">
                <span class="material-symbols-outlined hidden sm:inline-block" style="font-size:16px;">search</span>
                <span class="sm:hidden material-symbols-outlined" style="font-size:18px;">search</span>
                <span class="hidden sm:inline-block"><?php _e('tracking.search_btn'); ?></span>
            </button>
        </form>
    </div>

    <div class="mx-auto flex w-full max-w-7xl items-center justify-between whitespace-nowrap px-6 py-5">
        <div class="flex items-center gap-3">
            <a href="<?php echo $base_path; ?>index.php">
                <img id="header-logo"
                    src="<?php echo $base_path; ?>assets/img/src/logo/<?php echo ($has_hero && !$is_solid_page) ? 'logo-dark-ui.png' : 'logo-white-ui.png'; ?>"
                    data-logo-white="<?php echo $base_path; ?>assets/img/src/logo/logo-white-ui.png"
                    data-logo-dark="<?php echo $base_path; ?>assets/img/src/logo/logo-dark-ui.png"
                    alt="Aurora Hotel Plaza Logo" class="h-16 w-auto transition-all duration-300">
            </a>
        </div>
        <nav class="hidden items-center gap-10 md:flex">
            <a class="text-base font-medium nav-link"
                href="<?php echo $base_path; ?>index.php"><?php _e('nav.home'); ?></a>

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
            <a href="<?php echo $base_path; ?>index.php" class="floating-menu-label"
                style="text-decoration: none;"><?php _e('nav.home'); ?></a>
            <a href="<?php echo $base_path; ?>index.php" class="floating-menu-btn"
                aria-label="<?php _e('nav.home'); ?>">
                <span class="material-symbols-outlined">home</span>
            </a>
        </div>

        <!-- Phòng & Căn hộ - có submenu -->
        <div class="floating-menu-item floating-submenu-wrapper">
            <a href="<?php echo $base_path; ?>rooms.php" class="floating-menu-label"
                style="text-decoration: none;"><?php _e('nav.rooms'); ?></a>
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
            <a href="<?php echo $base_path; ?>services.php" class="floating-menu-label"
                style="text-decoration: none;"><?php _e('nav.services'); ?></a>
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
            <a href="<?php echo $base_path; ?>explore.php" class="floating-menu-label"
                style="text-decoration: none;"><?php _e('nav.explore'); ?></a>
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
            <a href="<?php echo $base_path; ?>contact.php" class="floating-menu-label"
                style="text-decoration: none;"><?php _e('nav.contact'); ?></a>
            <a href="<?php echo $base_path; ?>contact.php" class="floating-menu-btn"
                aria-label="<?php _e('nav.contact'); ?>">
                <span class="material-symbols-outlined">call</span>
            </a>
        </div>

        <!-- Đặt phòng -->
        <div class="floating-menu-item">
            <a href="<?php echo $base_path; ?>booking/index.php" class="floating-menu-label"
                style="text-decoration: none;"><?php _e('nav.book_now'); ?></a>
            <a href="<?php echo $base_path; ?>booking/index.php" class="floating-menu-btn"
                aria-label="<?php _e('nav.book_now'); ?>">
                <span class="material-symbols-outlined">calendar_month</span>
            </a>
        </div>

        <?php if ($is_logged_in): ?>
            <!-- Tài khoản -->
            <div class="floating-menu-item">
                <a href="<?php echo $base_path; ?>profile/index.php" class="floating-menu-label"
                    style="text-decoration: none;"><?php _e('nav.profile'); ?></a>
                <a href="<?php echo $base_path; ?>profile/index.php" class="floating-menu-btn"
                    aria-label="<?php _e('nav.profile'); ?>">
                    <span class="material-symbols-outlined">account_circle</span>
                </a>
            </div>
        <?php else: ?>
            <!-- Đăng nhập -->
            <div class="floating-menu-item">
                <a href="<?php echo $base_path; ?>auth/login.php" class="floating-menu-label"
                    style="text-decoration: none;"><?php _e('nav.login'); ?></a>
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
                        max="2030-12-31" value="<?php echo date('Y-m-d'); ?>" class="glass-input-solid">
                </div>
                <div class="floating-booking-field">
                    <label for="floating-checkout">
                        <span class="material-symbols-outlined">event</span>
                        <?php _e('hero.check_out'); ?>
                    </label>
                    <input type="date" id="floating-checkout" name="check_out"
                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" max="2030-12-31"
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

<!-- Tracking Booking Modal -->
<div id="trackingModal" class="tracking-modal">
    <div class="tracking-modal-overlay" onclick="closeTrackingModal()"></div>
    <div class="tracking-modal-content">
        <button class="tracking-modal-close" onclick="closeTrackingModal()">
            <span class="material-symbols-outlined">close</span>
        </button>
        <div class="tracking-modal-header"
            style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem;">
            <h3><span class="material-symbols-outlined text-primary-500">travel_explore</span>
                <?php _e('tracking.result_title'); ?></h3>
            <div class="track-mode-toggle flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1" style="font-size: 0.75rem;">
                <button onclick="changeTrackMode('latest')" id="btnTrackLatest"
                    class="px-3 py-1 rounded-md font-medium bg-white text-gray-900 shadow-sm transition-all"><?php _e('tracking.latest_only'); ?></button>
                <button onclick="changeTrackMode('all')" id="btnTrackAll"
                    class="px-3 py-1 rounded-md font-medium text-gray-500 hover:text-gray-700 transition-all"><?php _e('tracking.all_history'); ?></button>
            </div>
        </div>
        <div id="trackResultContent" class="tracking-modal-body">
            <!-- Data will be populated here -->
        </div>
    </div>
</div>

<script>
    const trackingLang = {
        bookingCode: <?php echo json_encode(__('tracking.booking_code')); ?>,
        status: <?php echo json_encode(__('tracking.status')); ?>,
        customer: <?php echo json_encode(__('tracking.customer')); ?>,
        checkIn: <?php echo json_encode(__('tracking.check_in')); ?>,
        checkOut: <?php echo json_encode(__('tracking.check_out')); ?>,
        phone: <?php echo json_encode(__('tracking.phone')); ?>,
        total: <?php echo json_encode(__('tracking.total')); ?>,
        errorSystem: <?php echo json_encode(__('tracking.error_system')); ?>,
        errorEmpty: <?php echo json_encode(__('tracking.error_empty')); ?>,
        errorNotFound: <?php echo json_encode(__('tracking.error_not_found')); ?>,
        searching: <?php echo json_encode(__('tracking.searching')); ?>,
        statusText: {
            confirmed: <?php echo json_encode(__('tracking.status_confirmed')); ?>,
            checked_in: <?php echo json_encode(__('tracking.status_checked_in')); ?>,
            checked_out: <?php echo json_encode(__('tracking.status_checked_out')); ?>,
            cancelled: <?php echo json_encode(__('tracking.status_cancelled')); ?>,
            no_show: <?php echo json_encode(__('tracking.status_no_show')); ?>,
            pending: <?php echo json_encode(__('tracking.status_pending')); ?>
        }
    };

    function toggleTrackForm(show) {
        const form = document.getElementById('topbarTrackForm');
        const input = document.getElementById('trackInput');

        if (show) {
            form.classList.remove('opacity-0', 'pointer-events-none');
            form.classList.add('opacity-100', 'pointer-events-auto');
            setTimeout(() => input.focus(), 300);
        } else {
            form.classList.remove('opacity-100', 'pointer-events-auto');
            form.classList.add('opacity-0', 'pointer-events-none');
            input.blur();
        }
    }

    function closeTrackingModal() {
        const modal = document.getElementById('trackingModal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function openTrackingModal(htmlContent) {
        const modal = document.getElementById('trackingModal');
        if (modal) {
            document.getElementById('trackResultContent').innerHTML = htmlContent;
            modal.classList.add('active');
        }
    }

    function changeTrackMode(mode) {
        document.getElementById('trackMode').value = mode;

        // Update active button styling
        const btnLatest = document.getElementById('btnTrackLatest');
        const btnAll = document.getElementById('btnTrackAll');

        if (mode === 'latest') {
            btnLatest.className = "px-3 py-1 rounded-md font-medium bg-white text-gray-900 shadow-sm transition-all";
            btnAll.className = "px-3 py-1 rounded-md font-medium text-gray-500 hover:text-gray-700 transition-all";
        } else {
            btnAll.className = "px-3 py-1 rounded-md font-medium bg-white text-gray-900 shadow-sm transition-all";
            btnLatest.className = "px-3 py-1 rounded-md font-medium text-gray-500 hover:text-gray-700 transition-all";
        }

        // Retrigger search without submitting form
        if (document.getElementById('trackInput').value.trim() !== '') {
            performTrackSearch();
        }
    }

    async function handleTrackBooking(e) {
        e.preventDefault();
        await performTrackSearch();
    }

    async function performTrackSearch() {
        const input = document.getElementById('trackInput').value.trim();
        const mode = document.getElementById('trackMode').value;
        if (!input) {
            shakeTrackInput();
            return;
        }

        const submitBtn = document.getElementById('topbarTrackForm').querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin hidden sm:inline-block" style="font-size:16px;">refresh</span> ' + trackingLang.searching;
        submitBtn.disabled = true;

        document.getElementById('trackErrorMsg').classList.add('hidden');

        try {
            const res = await fetch('<?php echo $base_path; ?>booking/api/track.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: input, mode: mode })
            });
            const data = await res.json();

            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;

            if (data.success && data.bookings && data.bookings.length > 0) {
                let html = '<div class="space-y-4">';

                data.bookings.forEach((bookingItem) => {
                    let statusColor = 'bg-gray-100 text-gray-800';
                    if (bookingItem.status_raw === 'confirmed') statusColor = 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200';
                    if (bookingItem.status_raw === 'checked_in') statusColor = 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200';
                    if (bookingItem.status_raw === 'cancelled' || bookingItem.status_raw === 'no_show') statusColor = 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200';
                    if (bookingItem.status_raw === 'pending') statusColor = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200';

                    let statusLabel = trackingLang.statusText[bookingItem.status_raw] || bookingItem.status_raw;

                    html += '<div class="bg-gray-50/80 dark:bg-gray-800/80 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">';
                    html += '<h4 class="font-bold text-lg text-primary-600 dark:text-primary-400 border-b pb-2 mb-3">' + trackingLang.bookingCode + ': ' + bookingItem.booking_code + '</h4>';
                    html += '<div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">';
                    html += '<div class="flex justify-between items-center"><strong class="w-1/3">' + trackingLang.status + ':</strong> <span class="badge ' + statusColor + ' px-2 py-0.5 rounded font-semibold text-center flex-1">' + statusLabel + '</span></div>';
                    html += '<div class="flex"><strong class="w-1/3">' + trackingLang.customer + ':</strong> <span class="flex-1">' + bookingItem.customer_name + '</span></div>';
                    html += '<div class="flex"><strong class="w-1/3">' + trackingLang.checkIn + ':</strong> <span class="flex-1">' + bookingItem.check_in + '</span></div>';
                    html += '<div class="flex"><strong class="w-1/3">' + trackingLang.checkOut + ':</strong> <span class="flex-1">' + bookingItem.check_out + '</span></div>';
                    html += '<div class="flex"><strong class="w-1/3">' + trackingLang.phone + ':</strong> <span class="flex-1">' + (bookingItem.phone || '') + '</span></div>';
                    html += '</div></div>';
                    html += '<div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-3 pb-1">';
                    html += '<strong class="text-gray-800 dark:text-gray-200">' + trackingLang.total + ':</strong>';
                    html += '<span class="text-xl font-bold text-primary-600 mt-auto">' + new Intl.NumberFormat('vi-VN').format(bookingItem.total_amount) + ' VND</span>';
                    html += '</div></div>';
                    html += '<hr class="my-3 border-gray-200 dark:border-gray-700">';
                });

                html += '</div>';
                openTrackingModal(html);
            } else {
                // Not found or error → shake input and quick toast
                shakeTrackInput();
                if (data.error_code === 'system') {
                    showTrackError(trackingLang.errorSystem + (data.message || ''));
                }
                // not_found / empty: just shake, no long text blocking the topbar
            }
        } catch (err) {
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
            showTrackError(trackingLang.errorSystem + err.message);
        }
    }

    function showTrackError(message) {
        const errObj = document.getElementById('trackErrorMsg');
        errObj.querySelector('.error-text').innerText = message;
        errObj.classList.remove('hidden');
        setTimeout(() => errObj.classList.add('hidden'), 2500);
    }

    function shakeTrackInput() {
        const inputWrap = document.getElementById('trackInput').closest('.relative') || document.getElementById('trackInput');
        inputWrap.classList.add('track-shake');
        setTimeout(() => inputWrap.classList.remove('track-shake'), 600);
    }

    // Measure Header height dynamically for exact padding
    function syncHeaderHeight() {
        const headerObj = document.getElementById('main-header');
        if (headerObj) {
            document.documentElement.style.setProperty('--header-height', headerObj.offsetHeight + 'px');
        }
    }
    window.addEventListener('load', syncHeaderHeight);
    window.addEventListener('resize', syncHeaderHeight);
    document.addEventListener('DOMContentLoaded', syncHeaderHeight);
    syncHeaderHeight(); // init
</script>

<style>
    @keyframes spin {
        100% {
            transform: rotate(360deg);
        }
    }

    @keyframes track-shake-kf {

        0%,
        100% {
            transform: translateX(0);
        }

        15% {
            transform: translateX(-6px);
        }

        35% {
            transform: translateX(6px);
        }

        55% {
            transform: translateX(-4px);
        }

        75% {
            transform: translateX(4px);
        }
    }

    .track-shake {
        animation: track-shake-kf 0.5s ease-in-out;
        border-color: #ef4444 !important;
        outline-color: #ef4444 !important;
    }

    .track-shake input {
        border-color: #ef4444 !important;
    }

    /* Topbar Styles (Header Tracking Mini Bar) */
    .tracking-topbar {
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .header-transparent .tracking-topbar {
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(10px);
    }

    .header-solid .tracking-topbar,
    .header-scrolled .tracking-topbar {
        background: rgba(17, 24, 39, 1);
    }

    /* Modal Styles */
    .tracking-modal {
        position: fixed;
        inset: 0;
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .tracking-modal.active {
        opacity: 1;
        pointer-events: auto;
    }

    .tracking-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    .tracking-modal-content {
        background: white;
        width: 90%;
        max-width: 450px;
        max-height: 85vh;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        position: relative;
        z-index: 10;
        transform: translateY(-50px);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .dark .tracking-modal-content {
        background: var(--bg-dark);
    }

    .tracking-modal.active .tracking-modal-content {
        transform: translateY(0);
    }

    .tracking-modal-close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 32px;
        height: 32px;
        border: none;
        background: rgba(0, 0, 0, 0.05);
        color: var(--text-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        cursor: pointer;
        transition: background 0.2s, transform 0.2s;
        z-index: 20;
    }

    .dark .tracking-modal-close {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .tracking-modal-close:hover {
        background: rgba(0, 0, 0, 0.1);
        transform: rotate(90deg);
    }

    .dark .tracking-modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .tracking-modal-header {
        padding: 24px 24px 16px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .dark .tracking-modal-header {
        border-color: rgba(255, 255, 255, 0.05);
    }

    .tracking-modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .dark .tracking-modal-header h3 {
        color: white;
    }

    .tracking-modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
        max-height: calc(85vh - 90px);
    }

    .tracking-modal-body::-webkit-scrollbar {
        width: 4px;
    }

    .tracking-modal-body::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.15);
        border-radius: 4px;
    }

    .dark .tracking-modal-body::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
    }
</style>

<!-- Header Styles & Script -->
<?php $asset_version = time(); ?>
<link rel="stylesheet" href="<?php echo asset('css/header-styles.css'); ?>?v=<?php echo $asset_version; ?>">
<link rel="stylesheet" href="<?php echo asset('css/liquid-glass.css'); ?>?v=<?php echo $asset_version; ?>">
<link rel="stylesheet" href="<?php echo asset('css/floating-menu.css'); ?>?v=<?php echo $asset_version; ?>">
<link rel="stylesheet" href="<?php echo asset('css/ui-fixes.css'); ?>?v=<?php echo $asset_version; ?>">
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