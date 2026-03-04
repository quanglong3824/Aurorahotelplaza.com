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
            <!-- Track Booking Button -->
            <button class="btn-track-booking" onclick="toggleTrackingSidebar()" aria-label="Tra cứu đặt phòng" title="Tra cứu đặt phòng">
                <span class="material-symbols-outlined text-xl">travel_explore</span>
                <span class="hidden md:inline font-medium text-sm ml-1 truncate">Tra cứu</span>
            </button>

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

<!-- Tracking Booking Sidebar -->
<div id="trackingSidebar" class="tracking-sidebar">
    <div class="tracking-sidebar-overlay" onclick="toggleTrackingSidebar()"></div>
    <div class="tracking-sidebar-content">
        <div class="tracking-sidebar-header">
            <h3>Tra cứu đặt phòng</h3>
            <button class="tracking-sidebar-close" onclick="toggleTrackingSidebar()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="tracking-sidebar-body">
            <p class="text-sm text-gray-500 mb-4 dark:text-gray-400">Dành cho khách vãng lai. Vui lòng nhập thông tin để kiểm tra trạng thái phòng.</p>
            <form id="formTrackBooking" onsubmit="handleTrackBooking(event)">
                <div class="floating-booking-field mb-4" style="flex-direction: column; align-items: flex-start;">
                    <label style="display: flex; gap: 8px; font-weight: 500; font-size: 0.875rem; color: #4b5563; margin-bottom: 8px;">
                        <span class="material-symbols-outlined" style="font-size: 1.25rem;">search</span>
                        Mã đặt phòng / Email / SĐT
                    </label>
                    <input type="text" id="trackInput" required placeholder="Nhập mã, email hoặc số điện thoại..." class="glass-input-solid border border-gray-300 rounded-lg px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-primary-500" style="width: 100%; color: #1f2937; background-color: rgba(255,255,255,0.7);">
                </div>
                <button type="submit" class="btn-glass-primary w-full py-3 rounded-lg flex items-center justify-center gap-2" style="width: 100%;">
                    <span class="material-symbols-outlined">search</span>
                    Tìm kiếm
                </button>
            </form>
            <div id="trackResult" class="mt-6 hidden">
                <!-- Result will be rendered here -->
            </div>
        </div>
    </div>
</div>

<script>
function toggleTrackingSidebar() {
    const sidebar = document.getElementById('trackingSidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
        if(sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
            setTimeout(() => document.getElementById('trackInput').focus(), 300);
        } else {
            document.body.style.overflow = '';
        }
    }
}

async function handleTrackBooking(e) {
    e.preventDefault();
    const input = document.getElementById('trackInput').value.trim();
    if (!input) return;
    
    const resultDiv = document.getElementById('trackResult');
    resultDiv.classList.remove('hidden');
    resultDiv.innerHTML = '<div class="text-center py-4"><span class="material-symbols-outlined animate-spin" style="animation: spin 1s linear infinite;">refresh</span> <p class="mt-2 text-sm text-gray-600">Đang kiểm tra...</p></div>';
    
    try {
        const res = await fetch('<?php echo $base_path; ?>booking/api/track.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ query: input })
        });
        const data = await res.json();
        if (data.success) {
            let statusColor = 'bg-gray-100 text-gray-800';
            if(data.booking.status_raw === 'confirmed') statusColor = 'bg-blue-100 text-blue-800';
            if(data.booking.status_raw === 'checked_in') statusColor = 'bg-green-100 text-green-800';
            if(data.booking.status_raw === 'cancelled' || data.booking.status_raw === 'no_show') statusColor = 'bg-red-100 text-red-800';
            if(data.booking.status_raw === 'pending') statusColor = 'bg-yellow-100 text-yellow-800';

            let html = '<div class="bg-gray-50/80 backdrop-blur rounded-xl p-4 shadow-sm border border-gray-200 dark:bg-gray-800/80 dark:border-gray-700">';
            html += '<h4 class="font-bold text-lg text-primary-900 dark:text-primary-100 border-b pb-2 mb-3">Mã Đặt: ' + data.booking.booking_code + '</h4>';
            html += '<div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">';
            html += '<div class="flex justify-between items-center"><strong class="w-1/3">Trạng thái:</strong> <span class="badge ' + statusColor + ' px-2 py-1 flex-1 text-center rounded font-medium">' + data.booking.status + '</span></div>';
            html += '<div class="flex"><strong class="w-1/3">Khách hàng:</strong> <span class="flex-1">' + data.booking.customer_name + '</span></div>';
            html += '<div class="flex"><strong class="w-1/3">Nhận phòng:</strong> <span class="flex-1">' + data.booking.check_in + '</span></div>';
            html += '<div class="flex"><strong class="w-1/3">Trả phòng:</strong> <span class="flex-1">' + data.booking.check_out + '</span></div>';
            html += '<div class="flex"><strong class="w-1/3">SĐT:</strong> <span class="flex-1">' + data.booking.phone + '</span></div>';
            html += '<div class="flex items-center pt-2 border-t mt-2"><strong class="w-1/3 text-lg">Tổng:</strong> <span class="flex-1 text-lg font-bold text-primary-600">' + new Intl.NumberFormat('vi-VN').format(data.booking.total_amount) + ' VNĐ</span></div>';
            html += '</div></div>';
            resultDiv.innerHTML = html;
        } else {
            resultDiv.innerHTML = '<div class="bg-red-50 text-red-600 p-3 rounded-lg flex items-start gap-2"><span class="material-symbols-outlined text-red-500">error</span><p class="text-sm font-medium">' + data.message + '</p></div>';
        }
    } catch(err) {
        resultDiv.innerHTML = '<div class="bg-red-50 text-red-600 p-3 rounded-lg flex items-start gap-2"><span class="material-symbols-outlined text-red-500">error</span><p class="text-sm font-medium">Đã xảy ra lỗi, vui lòng thử lại sau.</p></div>';
    }
}
</script>

<style>
@keyframes spin { 100% { transform: rotate(360deg); } }
.tracking-sidebar {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    max-width: 100vw;
    height: 100vh;
    z-index: 100000;
    transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
}
.tracking-sidebar.active {
    right: 0;
}
.tracking-sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: -1;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}
.tracking-sidebar.active .tracking-sidebar-overlay {
    opacity: 1;
    pointer-events: auto;
}
.tracking-sidebar-content {
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    box-shadow: -5px 0 25px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}
.dark .tracking-sidebar-content {
    background: rgba(31, 41, 55, 0.95);
}
.tracking-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.dark .tracking-sidebar-header {
    border-color: rgba(255,255,255,0.05);
}
.tracking-sidebar-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
}
.dark .tracking-sidebar-header h3 {
    color: white;
}
.tracking-sidebar-close {
    background: rgba(0,0,0,0.05);
    border: none;
    cursor: pointer;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, transform 0.2s;
    color: var(--text-dark);
}
.dark .tracking-sidebar-close {
    background: rgba(255,255,255,0.1);
    color: white;
}
.tracking-sidebar-close:hover {
    background: rgba(0,0,0,0.1);
    transform: rotate(90deg);
}
.dark .tracking-sidebar-close:hover {
    background: rgba(255,255,255,0.2);
}
.tracking-sidebar-body {
    padding: 24px;
    flex: 1;
    overflow-y: auto;
}

/* Button style */
.btn-track-booking {
    display: flex;
    align-items: center;
    padding: 8px 16px;
    border-radius: 50px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    backdrop-filter: blur(10px);
}
.btn-track-booking:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.header-solid .btn-track-booking, .header-scrolled .btn-track-booking {
    background: rgba(0,0,0,0.05);
    border-color: rgba(0,0,0,0.1);
    color: var(--text-dark);
}
.header-solid .btn-track-booking:hover, .header-scrolled .btn-track-booking:hover {
    background: rgba(0,0,0,0.1);
}
</style>

<!-- Header Styles & Script -->
<?php $asset_version = time(); // Update this when assets change ?>
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