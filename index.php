<?php
// Start session for user authentication
session_start();

// Load environment configuration
require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/performance.php';
require_once __DIR__ . '/helpers/image-helper.php';
require_once __DIR__ . '/helpers/language.php';
initLanguage();

// Fetch featured rooms from database
$featured_rooms = [];
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM room_types 
        WHERE status = 'active' AND category = 'room'
        ORDER BY sort_order ASC
        LIMIT 3
    ");
    $stmt->execute();
    $featured_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Index page error: " . $e->getMessage());
}

// Fetch featured apartments from database
$featured_apartments = [];
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM room_types 
        WHERE status = 'active' AND category = 'apartment'
        ORDER BY sort_order ASC
        LIMIT 3
    ");
    $stmt->execute();
    $featured_apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Index page (apartments) error: " . $e->getMessage());
}

// Fetch latest blog posts
$latest_posts = [];
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.title, p.title_en, p.slug, p.excerpt, p.excerpt_en, p.featured_image, p.published_at, u.full_name as author_name
        FROM blog_posts p
        LEFT JOIN users u ON p.author_id = u.user_id
        WHERE p.status = 'published'
        ORDER BY p.published_at DESC
        LIMIT 3
    ");
    $stmt->execute();
    $latest_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Index page (blog) error: " . $e->getMessage());
}

// Fetch customer reviews
$customer_reviews = [];
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, u.full_name, rt.type_name, rt.type_name_en
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.user_id
        LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE r.status = 'approved' AND r.rating >= 4
        ORDER BY r.created_at DESC
        LIMIT 6
    ");
    $stmt->execute();
    $customer_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Index page (reviews) error: " . $e->getMessage());
}

// Fetch active promotions
$active_promotions = [];
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM promotions 
        WHERE status = 'active' 
        AND start_date <= CURDATE() 
        AND end_date >= CURDATE()
        ORDER BY discount_percent DESC
        LIMIT 3
    ");
    $stmt->execute();
    $active_promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Index page (promotions) error: " . $e->getMessage());
}

// Fetch services
$hotel_services = [];
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM services 
        WHERE status = 'active'
        ORDER BY sort_order ASC
        LIMIT 6
    ");
    $stmt->execute();
    $hotel_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Index page (services) error: " . $e->getMessage());
}

// Calculate statistics
$stats = [
    'total_rooms' => 150,
    'happy_customers' => 5000,
    'years_experience' => 10,
    'awards' => 15
];
try {
    $db = getDB();
    // Count total rooms
    $stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status != 'inactive'");
    $stats['total_rooms'] = $stmt->fetchColumn() ?: 150;

    // Count completed bookings
    $stmt = $db->query("SELECT COUNT(DISTINCT user_id) FROM bookings WHERE status IN ('completed', 'checked_out')");
    $stats['happy_customers'] = $stmt->fetchColumn() ?: 5000;
} catch (Exception $e) {
    error_log("Index page (stats) error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>" translate="no">

<head>
    <meta charset="utf-8" />
    <meta name="google" content="notranslate" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('home.meta_title'); ?></title>
    <?php require_once 'includes/seo.php'; ?>
    <meta name="description" content="<?php echo get_meta_description(); ?>">

    <!-- DNS Prefetch & Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Preload Critical Assets -->
    <link rel="preload" href="<?php echo assetVersion('css/fonts.css'); ?>" as="style">
    <link rel="preload" href="<?php echo assetVersion('css/style.css'); ?>" as="style">
    
    <!-- Critical CSS (Inline) - Above the fold styles -->
    <style>
        /* Critical Layout Styles - First Paint Optimization */
        body { margin: 0; font-family: 'Be Vietnam Pro', Inter, system-ui, -apple-system, sans-serif; }
        .min-h-screen { min-height: 100vh; }
        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .w-full { width: 100%; }
        .flex-grow { flex-grow: 1; }
        .h-full { height: 100%; }
        .min-h-screen { min-height: 100vh; }
        .font-body { font-family: 'Be Vietnam Pro', system-ui, sans-serif; }
        .font-display { font-family: 'Montserrat', 'Be Vietnam Pro', sans-serif; }
        .text-accent { color: #d4af37; }
        .bg-background-light { background-color: #ffffff; }
        .dark .bg-background-dark { background-color: #111827; }
        .text-text-primary-light { color: #1f2937; }
        .dark .text-text-primary-dark { color: #f3f4f6; }
        .text-text-secondary-light { color: #6b7280; }
        .dark .text-text-secondary-dark { color: #9ca3af; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .py-16 { padding-top: 4rem; padding-bottom: 4rem; }
        .py-20 { padding-top: 5rem; padding-bottom: 5rem; }
        .py-24 { padding-top: 6rem; padding-bottom: 6rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .max-w-7xl { max-width: 80rem; }
        .gap-3 { gap: 0.75rem; }
        .gap-4 { gap: 1rem; }
        .gap-6 { gap: 1.5rem; }
        .gap-8 { gap: 2rem; }
        .gap-10 { gap: 2.5rem; }
        .gap-12 { gap: 3rem; }
        .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
        .text-base { font-size: 1rem; line-height: 1.5rem; }
        .text-lg { font-size: 1.125rem; line-height: 1.75rem; }
        .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
        .text-2xl { font-size: 1.5rem; line-height: 2rem; }
        .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
        .text-4xl { font-size: 2.25rem; line-height: 2.5rem; }
        .font-medium { font-weight: 500; }
        .font-semibold { font-weight: 600; }
        .font-bold { font-weight: 700; }
        .font-display { font-weight: 700; }
        .uppercase { text-transform: uppercase; }
        .tracking-wider { letter-spacing: 0.05em; }
        .tracking-widest { letter-spacing: 0.1em; }
        .leading-relaxed { line-height: 1.625; }
        .rounded-lg { border-radius: 0.5rem; }
        .rounded-xl { border-radius: 0.75rem; }
        .rounded-full { border-radius: 9999px; }
        .overflow-hidden { overflow: hidden; }
        .relative { position: relative; }
        .absolute { position: absolute; }
        .sticky { position: sticky; }
        .top-0 { top: 0; }
        .fixed { position: fixed; }
        .z-50 { z-index: 50; }
        .z-[10000] { z-index: 10000; }
        .hidden { display: none; }
        .invisible { visibility: hidden; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
        .pointer-events-none { pointer-events: none; }
        .pointer-events-auto { pointer-events: auto; }
        .cursor-pointer { cursor: pointer; }
        .cursor-default { cursor: default; }
        .select-none { user-select: none; }
        .outline-none { outline: none; }
        .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); }
        .shadow-xl { box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); }
        .shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .transition-all { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .transition { transition: all 0.3s ease; }
        .duration-300 { transition-duration: 0.3s; }
        .duration-500 { transition-duration: 0.5s; }
        .ease-in-out { transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); }
        .ease-out { transition-timing-function: cubic-bezier(0, 0, 0.2, 1); }
        .hover\\:scale-105:hover { transform: scale(1.05); }
        .hover\\:scale-110:hover { transform: scale(1.1); }
        .hover\\:scale-115:hover { transform: scale(1.15); }
        .hover\\:rotate-5:hover { transform: rotate(5deg); }
        .hover\\:translate-y-\\-2\\/:hover { transform: translateY(-2px); }
        .hover\\:translate-y-\\-4\\/:hover { transform: translateY(-4px); }
        .hover\\:translate-y-\\-6\\/:hover { transform: translateY(-6px); }
        .hover\\:translate-y-\\-8\\/:hover { transform: translateY(-8px); }
        .hover\\:translate-y-\\-10\\/:hover { transform: translateY(-10px); }
        .active\\:scale-95:active { transform: scale(0.95); }
        .focus\\:outline-none:focus { outline: none; }
        .focus\\:ring-2:focus { ring-width: 2px; }
        .focus\\:ring-4:focus { ring-width: 4px; }
        .focus\\:ring-inset:focus { ring-inset: inset; }
        .aspect-w-16 { position: relative; padding-bottom: calc(100% / (16 / 9)); }
        .aspect-w-16\\.aspect-h-9 { position: relative; padding-bottom: calc(100% / (16 / 9)); height: 0; }
        .aspect-w-4\\.aspect-h-3 { position: relative; padding-bottom: calc(100% / (4 / 3)); height: 0; }
    </style>

    <!-- Tailwind CSS - Now loading via CDN -->
    <script src="<?php echo assetVersion('js/tailwindcss-cdn.js'); ?>" defer></script>
    <link href="<?php echo assetVersion('css/fonts.css'); ?>" rel="stylesheet" />

    <!-- Tailwind Configuration -->

    <!-- Custom CSS - Essential styles loaded synchronously -->
    <link rel="stylesheet" href="<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('css/liquid-glass.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('css/pages-glass.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('css/responsive-index.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('css/index-upgrade.css'); ?>">
    <link rel="stylesheet" href="<?php echo assetVersion('css/featured-apartments-glass.css'); ?>">
    
    <!-- Preload Hero Images -->
    <link rel="preload" as="image" href="<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-2.jpg'); ?>">
</head>

<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
    <div class="relative flex min-h-screen w-full flex-col">

        <?php include 'includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <?php include 'includes/hero-slider.php'; ?>

            <!-- Quick Info Bar - Liquid Glass -->
            <section class="w-full py-4 glass-info-bar">
                <div class="mx-auto max-w-7xl px-4">
                    <div class="flex flex-wrap items-center justify-center gap-4 md:gap-8 text-white text-sm">
                        <a href="tel:+842513918888" class="glass-tag hover:bg-white/20 transition-all">
                            <span class="material-symbols-outlined text-accent text-base">phone</span>
                            <span class="font-medium">(+84-251) 391.8888</span>
                        </a>
                        <a href="mailto:booking@aurorahotelplaza.com"
                            class="glass-tag hover:bg-white/20 transition-all">
                            <span class="material-symbols-outlined text-accent text-base">email</span>
                            <span class="font-medium">booking@aurorahotelplaza.com</span>
                        </a>
                        <div class="glass-tag">
                            <span class="material-symbols-outlined text-accent text-base">location_on</span>
                            <span class="font-medium"><?php _e('home.address'); ?></span>
                        </div>
                        <div class="glass-tag">
                            <span class="material-symbols-outlined text-accent text-base">schedule</span>
                            <span class="font-medium"><?php _e('home.reception_24_7'); ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- About Section -->
            <section class="w-full justify-center py-16 sm:py-24" id="about">
                <div class="mx-auto flex max-w-7xl flex-col gap-10 px-4">
                    <div class="flex flex-col gap-4 text-center">
                        <span
                            class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('home.about_us'); ?></span>
                        <h2
                            class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            <?php _e('home.welcome_title'); ?>
                        </h2>
                        <p
                            class="mx-auto max-w-3xl text-base leading-relaxed text-text-secondary-light dark:text-text-secondary-dark">
                            <?php _e('home.welcome_desc'); ?>
                        </p>
                    </div>

                    <!-- Stats Counter - Liquid Glass -->
                    <div class="grid grid-cols-2 gap-6 md:grid-cols-4 my-8">
                        <div class="glass-stat-card-light glass-glow">
                            <span class="stat-value"><?php echo $stats['total_rooms']; ?>+</span>
                            <span class="stat-label"><?php _e('home.rooms_apartments'); ?></span>
                        </div>
                        <div class="glass-stat-card-light glass-glow">
                            <span class="stat-value"><?php echo number_format($stats['happy_customers']); ?>+</span>
                            <span class="stat-label"><?php _e('home.happy_customers'); ?></span>
                        </div>
                        <div class="glass-stat-card-light glass-glow">
                            <span class="stat-value"><?php echo $stats['years_experience']; ?>+</span>
                            <span class="stat-label"><?php _e('home.years_experience'); ?></span>
                        </div>
                        <div class="glass-stat-card-light glass-glow">
                            <span class="stat-value">24/7</span>
                            <span class="stat-label"><?php _e('home.customer_support'); ?></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="glass-card-solid p-6 text-center">
                            <div
                                class="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-3xl text-accent">restaurant</span>
                            </div>
                            <h3 class="text-lg font-bold mb-2"><?php _e('home.fine_dining'); ?></h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                <?php _e('home.fine_dining_desc'); ?>
                            </p>
                        </div>
                        <div class="glass-card-solid p-6 text-center">
                            <div
                                class="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-3xl text-accent">celebration</span>
                            </div>
                            <h3 class="text-lg font-bold mb-2"><?php _e('home.wedding_events'); ?></h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                <?php _e('home.wedding_events_desc'); ?>
                            </p>
                        </div>
                        <div class="glass-card-solid p-6 text-center">
                            <div
                                class="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-3xl text-accent">apartment</span>
                            </div>
                            <h3 class="text-lg font-bold mb-2"><?php _e('home.service_apartment'); ?></h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                <?php _e('home.service_apartment_desc'); ?>
                            </p>
                        </div>
                        <div class="glass-card-solid p-6 text-center">
                            <div
                                class="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-3xl text-accent">business_center</span>
                            </div>
                            <h3 class="text-lg font-bold mb-2"><?php _e('home.office_rental'); ?></h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                <?php _e('home.office_rental_desc'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Promotions Section - Liquid Glass -->
            <?php if (!empty($active_promotions)): ?>
                <section class="w-full py-12 glass-promo-banner">
                    <div class="mx-auto max-w-7xl px-4 relative z-10">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex items-center gap-4 text-white">
                                <div
                                    class="w-16 h-16 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center">
                                    <span class="material-symbols-outlined text-4xl animate-pulse">local_offer</span>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold"><?php _e('home.special_offer'); ?></h3>
                                    <p class="text-white/90">
                                        <?php _e('home.discount_online', ['percent' => max(array_column($active_promotions, 'discount_percent'))]); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <?php foreach ($active_promotions as $promo): ?>
                                    <div class="glass-promo-code">
                                        <span><?php echo htmlspecialchars($promo['code']); ?></span>
                                        <span class="text-sm opacity-80">-<?php echo $promo['discount_percent']; ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="booking/index.php" class="btn-glass-secondary">
                                <?php _e('home.book_now'); ?>
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Featured Rooms Section - Liquid Glass Upgrade -->
            <section class="w-full justify-center py-16 sm:py-24" id="rooms">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
                    <div class="flex flex-col gap-2 text-center">
                        <span class="glass-badge-solid mx-auto mb-2"
                            style="background: rgba(255,255,255,0.1); color: white; border-color: rgba(255,255,255,0.2);">
                            <span class="material-symbols-outlined text-accent text-sm">hotel</span>
                            <?php _e('home.rooms_suite'); ?>
                        </span>
                        <h2 class="font-display text-3xl font-bold section-title md:text-4xl">
                            <?php _e('home.featured_rooms'); ?>
                        </h2>
                        <p class="text-base section-desc max-w-2xl mx-auto">
                            <?php _e('home.rooms_suite_desc'); ?>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <?php if (!empty($featured_rooms)): ?>
                            <?php foreach ($featured_rooms as $room):
                                $imageUrl = imgUrl($room['thumbnail'], 'assets/img/deluxe/deluxe-room-aurora-1.jpg');
                                ?>
                                <a href="room-details/<?php echo htmlspecialchars($room['slug']); ?>.php"
                                    class="liquid-glass-card group">

                                    <!-- Image Layer -->
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                                        alt="<?php echo htmlspecialchars(_f($room, 'type_name')); ?>" loading="lazy"
                                        onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/deluxe/deluxe-room-aurora-1.jpg'); ?>'">

                                    <!-- Badge Top Left -->
                                    <div class="card-badge">
                                        <span class="material-symbols-outlined">hotel</span>
                                        <?php _e('home.room'); ?>
                                    </div>

                                    <!-- Content Overlay Bottom -->
                                    <div class="card-content">
                                        <!-- Price -->
                                        <div class="price-display">
                                            <?php echo number_format($room['base_price'], 0, ',', '.'); ?>
                                            <?php _e('common.currency'); ?>
                                            <span class="price-unit">/<?php _e('common.night'); ?></span>
                                        </div>

                                        <!-- Title -->
                                        <h3><?php echo htmlspecialchars(_f($room, 'type_name')); ?></h3>

                                        <!-- Info Icons Row -->
                                        <div class="info-row">
                                            <div class="info-item">
                                                <span class="material-symbols-outlined">square_foot</span>
                                                <?php echo number_format($room['size_sqm'], 0); ?>m²
                                            </div>
                                            <div class="info-item">
                                                <span class="material-symbols-outlined">bed</span>
                                                <?php echo htmlspecialchars(_f($room, 'bed_type')); ?>
                                            </div>
                                            <div class="info-item">
                                                <span class="material-symbols-outlined">person</span>
                                                <?php echo $room['max_occupancy']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center py-12">
                                <p class="text-white/70 text-lg"><?php _e('home.no_rooms'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex justify-center pt-8">
                        <a href="rooms.php"
                            class="inline-flex items-center gap-2 px-8 py-4 btn-view-all rounded-xl font-bold">
                            <?php _e('home.view_all_rooms'); ?>
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Featured Apartments Section - Liquid Glass Upgrade -->
            <section class="w-full justify-center py-16 sm:py-24" id="apartments">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
                    <div class="flex flex-col gap-2 text-center">
                        <span class="glass-badge-solid mx-auto mb-2"
                            style="background: rgba(255,255,255,0.1); color: white; border-color: rgba(255,255,255,0.2);">
                            <span class="material-symbols-outlined text-accent text-sm">apartment</span>
                            <?php _e('home.premium_apartments'); ?>
                        </span>
                        <h2 class="font-display text-3xl font-bold section-title md:text-4xl text-white">
                            <?php _e('home.featured_apartments'); ?>
                        </h2>
                        <p class="text-base section-desc max-w-2xl mx-auto">
                            <?php _e('home.modern_living'); ?>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <?php if (!empty($featured_apartments)): ?>
                            <?php foreach ($featured_apartments as $apartment):
                                $imageUrl = imgUrl($apartment['thumbnail'], 'assets/img/studio-apartment/can-ho-studio-aurora-hotel-3.jpg');
                                ?>
                                <a href="apartment-details/<?php echo htmlspecialchars($apartment['slug']); ?>.php"
                                    class="liquid-glass-card group">

                                    <!-- Image Layer -->
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                                        alt="<?php echo htmlspecialchars(_f($apartment, 'type_name')); ?>" loading="lazy"
                                        onerror="this.onerror=null; this.src='<?php echo imgUrl('assets/img/studio-apartment/can-ho-studio-aurora-hotel-3.jpg'); ?>'">

                                    <!-- Badge Top Left -->
                                    <div class="card-badge">
                                        <span class="material-symbols-outlined">apartment</span>
                                        <?php _e('home.apartment'); ?>
                                    </div>

                                    <!-- Content Overlay Bottom -->
                                    <div class="card-content">
                                        <!-- Price -->
                                        <div class="price-display">
                                            <?php echo number_format($apartment['base_price'], 0, ',', '.'); ?>
                                            <?php _e('common.currency'); ?>
                                            <span class="price-unit">/<?php _e('common.night'); ?></span>
                                        </div>

                                        <!-- Title -->
                                        <h3><?php echo htmlspecialchars(_f($apartment, 'type_name')); ?></h3>

                                        <!-- Info Icons Row -->
                                        <div class="info-row">
                                            <div class="info-item">
                                                <span class="material-symbols-outlined">square_foot</span>
                                                <?php echo number_format($apartment['size_sqm'], 0); ?>m²
                                            </div>
                                            <div class="info-item">
                                                <span class="material-symbols-outlined">bed</span>
                                                <?php echo htmlspecialchars(_f($apartment, 'bed_type')); ?>
                                            </div>
                                            <div class="info-item">
                                                <span class="material-symbols-outlined">person</span>
                                                <?php echo $apartment['max_occupancy']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center py-12">
                                <p class="text-white/70 text-lg"><?php _e('home.no_apartments'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex justify-center pt-8">
                        <a href="apartments.php"
                            class="inline-flex items-center gap-2 px-8 py-4 btn-view-all rounded-xl font-bold">
                            <?php _e('home.view_all_apartments'); ?>
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Services Section -->
            <section class="w-full py-16 sm:py-24 bg-surface-light dark:bg-surface-dark" id="services">
                <div class="mx-auto max-w-7xl px-4">
                    <div class="flex flex-col gap-2 text-center mb-10">
                        <span
                            class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('home.our_services'); ?></span>
                        <h2
                            class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            <?php _e('home.services_amenities'); ?>
                        </h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark max-w-2xl mx-auto">
                            <?php _e('home.services_desc'); ?>
                        </p>
                    </div>

                    <!-- Main Services Grid - Liquid Glass -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 mb-10">
                        <!-- Wedding Service -->
                        <a href="service-detail.php?slug=wedding-service" class="glass-service-card group">
                            <img src="assets/img/post/wedding/tiec-cuoi-tai-aurora-5.jpg"
                                alt="<?php _e('home.wedding_service'); ?>" loading="lazy">
                            <div class="glass-service-overlay"></div>
                            <div class="glass-service-content">
                                <div class="glass-service-badge">
                                    <span class="material-symbols-outlined">celebration</span>
                                    <?php _e('home.featured_service'); ?>
                                </div>
                                <h3 class="text-xl font-bold mb-2"><?php _e('home.wedding_service'); ?></h3>
                                <p class="text-white/80 text-sm"><?php _e('home.wedding_service_desc'); ?></p>
                            </div>
                        </a>

                        <!-- Conference Service -->
                        <a href="service-detail.php?slug=conference-service" class="glass-service-card group">
                            <img src="assets/img/restaurant/nha-hang-aurora-hotel-4.jpg"
                                alt="<?php _e('home.conference_service'); ?>" loading="lazy">
                            <div class="glass-service-overlay"></div>
                            <div class="glass-service-content">
                                <div class="glass-service-badge">
                                    <span class="material-symbols-outlined">groups</span>
                                    <?php _e('home.featured_service'); ?>
                                </div>
                                <h3 class="text-xl font-bold mb-2"><?php _e('home.conference_service'); ?></h3>
                                <p class="text-white/80 text-sm"><?php _e('home.conference_service_desc'); ?></p>
                            </div>
                        </a>

                        <!-- Restaurant Service -->
                        <a href="service-detail.php?slug=aurora-restaurant" class="glass-service-card group">
                            <img src="assets/img/restaurant/nha-hang-aurora-hotel-6.jpg"
                                alt="<?php _e('home.restaurant_aurora'); ?>" loading="lazy">
                            <div class="glass-service-overlay"></div>
                            <div class="glass-service-content">
                                <div class="glass-service-badge">
                                    <span class="material-symbols-outlined">restaurant</span>
                                    <?php _e('home.cuisine'); ?>
                                </div>
                                <h3 class="text-xl font-bold mb-2"><?php _e('home.restaurant_aurora'); ?></h3>
                                <p class="text-white/80 text-sm"><?php _e('home.restaurant_desc'); ?></p>
                            </div>
                        </a>
                    </div>

                    <!-- Amenities Grid - Liquid Glass -->
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 mb-8">
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">wifi</span>
                            <span class="text-sm font-medium text-center"><?php _e('home.free_wifi'); ?></span>
                        </div>
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">local_parking</span>
                            <span class="text-sm font-medium text-center"><?php _e('home.parking'); ?></span>
                        </div>
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">room_service</span>
                            <span class="text-sm font-medium text-center"><?php _e('home.room_service_24_7'); ?></span>
                        </div>
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">fitness_center</span>
                            <span class="text-sm font-medium text-center"><?php _e('home.gym'); ?></span>
                        </div>
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">local_laundry_service</span>
                            <span class="text-sm font-medium text-center"><?php _e('home.laundry'); ?></span>
                        </div>
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">airport_shuttle</span>
                            <span class="text-sm font-medium text-center"><?php _e('home.airport_shuttle'); ?></span>
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <a href="services.php" class="btn-glass-primary">
                            <?php _e('home.view_all_services'); ?>
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Why Choose Us Section - Premium Dark Glass -->
            <section class="w-full relative py-20" id="why-choose-us">
                <!-- Parallax Background & Overlay -->
                <div class="absolute inset-0 z-0 bg-cover bg-center"
                    style="background-image: url('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg');"></div>
                <div class="absolute inset-0 z-0 bg-slate-900/90"></div>

                <div class="relative z-10 mx-auto max-w-7xl px-4">
                    <div class="flex flex-col gap-2 text-center mb-10">
                        <span
                            class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('home.why_choose_us'); ?></span>
                        <h2 class="font-display text-3xl font-bold text-white md:text-4xl">
                            <?php _e('home.why_choose_title'); ?>
                        </h2>
                        <p class="text-base text-white/70 max-w-2xl mx-auto">
                            <?php _e('home.why_choose_desc'); ?>
                        </p>
                    </div>

                    <?php
                    $why_features = [
                        ['icon' => 'location_on', 'title' => 'home.prime_location', 'desc' => 'home.prime_location_desc'],
                        ['icon' => 'support_agent', 'title' => 'home.professional_service', 'desc' => 'home.professional_service_desc'],
                        ['icon' => 'payments', 'title' => 'home.reasonable_price', 'desc' => 'home.reasonable_price_desc'],
                        ['icon' => 'verified', 'title' => 'home.quality_guaranteed', 'desc' => 'home.quality_guaranteed_desc'],
                        ['icon' => 'security', 'title' => 'home.security_24_7', 'desc' => 'home.security_24_7_desc'],
                        ['icon' => 'diversity_3', 'title' => 'home.diverse_options', 'desc' => 'home.diverse_options_desc']
                    ];
                    ?>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($why_features as $feature): ?>
                            <div
                                class="glass-card flex gap-4 p-6 items-start hover:-translate-y-2 transition-transform duration-300">
                                <div class="flex-shrink-0">
                                    <div
                                        class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-accent/80 text-white shadow-lg shadow-accent/20">
                                        <span
                                            class="material-symbols-outlined text-2xl"><?php echo $feature['icon']; ?></span>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold mb-2 text-white"><?php _e($feature['title']); ?></h3>
                                    <p class="text-white/70 text-sm">
                                        <?php _e($feature['desc']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <!-- Blog Section - Premium Dark Glass Style -->
            <section class="w-full justify-center py-16 sm:py-24 relative bg-slate-900" id="blog">
                <!-- Decorative Glow -->
                <div
                    class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-full pointer-events-none overflow-hidden">
                    <div
                        class="absolute -top-[20%] left-[10%] w-[500px] h-[500px] bg-accent/5 rounded-full blur-[100px]">
                    </div>
                </div>

                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 relative z-10">
                    <div class="flex flex-col gap-2 text-center">
                        <h2 class="font-display text-3xl font-bold text-white md:text-4xl">
                            <?php _e('home.news_events'); ?>
                        </h2>
                        <p class="text-base text-white/70">
                            <?php _e('home.news_events_desc'); ?>
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <?php if (!empty($latest_posts)): ?>
                            <?php foreach ($latest_posts as $post):
                                $post_image = !empty($post['featured_image']) ? htmlspecialchars($post['featured_image']) : 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg';
                                ?>
                                <a href="blog-detail.php?slug=<?php echo urlencode($post['slug']); ?>"
                                    class="group glass-card overflow-hidden hover:-translate-y-2 transition-all duration-300 p-0 flex flex-col h-full">
                                    <div class="relative aspect-video overflow-hidden bg-slate-800 shrink-0">
                                        <?php
                                        $fallback_img = 'assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg';
                                        $display_img = !empty($post['featured_image']) ? $post['featured_image'] : $fallback_img;
                                        ?>
                                        <img src="<?php echo imgUrl($display_img, $fallback_img); ?>"
                                            alt="<?php echo htmlspecialchars(_f($post, 'title')); ?>" loading="lazy"
                                            onerror="this.onerror=null; this.src='<?php echo imgUrl($fallback_img); ?>'"
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        <div class="absolute top-3 left-3 glass-badge text-xs">
                                            <span class="material-symbols-outlined text-accent text-sm">article</span>
                                            <?php _e('home.news'); ?>
                                        </div>
                                    </div>
                                    <div class="p-5 flex flex-col grow">
                                        <div class="flex items-center gap-3 text-xs text-white/60 mb-3">
                                            <span class="flex items-center gap-1">
                                                <span
                                                    class="material-symbols-outlined text-accent text-sm">calendar_today</span>
                                                <?php echo date('m/d/Y', strtotime($post['published_at'])); ?>
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-accent text-sm">person</span>
                                                <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?>
                                            </span>
                                        </div>
                                        <h3
                                            class="font-bold text-lg mb-2 text-white group-hover:text-accent transition-colors line-clamp-2">
                                            <?php echo htmlspecialchars(_f($post, 'title')); ?>
                                        </h3>
                                        <p class="text-sm text-white/70 line-clamp-3 mb-4">
                                            <?php echo htmlspecialchars(_f($post, 'excerpt')); ?>
                                        </p>
                                        <div
                                            class="mt-auto pt-4 border-t border-white/10 flex items-center text-accent text-sm font-semibold group-hover:translate-x-1 transition-transform">
                                            <?php _e('home.read_more'); ?>
                                            <span class="material-symbols-outlined text-lg ml-1">arrow_forward</span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center py-12">
                                <p class="text-white/50 text-lg"><?php _e('home.no_posts'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-center pt-4">
                        <a href="<?php echo route('tin-tuc'); ?>"
                            class="inline-flex items-center gap-2 px-6 py-3 btn-glass-gold hover:opacity-90 transition-opacity">
                            <?php _e('home.view_all_posts'); ?>
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Location & Contact Section - Premium Dark Glass -->
            <section class="w-full justify-center py-16 sm:py-24 relative" id="location">
                <!-- Background & Overlay -->
                <!-- Optimization: Removed fixed attachment for smoother scroll -->
                <div class="absolute inset-0 z-0 bg-cover bg-center"
                    style="background-image: url('assets/img/hero-banner/aurora-hotel-bien-hoa-3.jpg');">
                </div>
                <div class="absolute inset-0 z-0 bg-slate-900/90"></div>

                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 relative z-10">
                    <div class="flex flex-col gap-2 text-center">
                        <span
                            class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('home.location_contact'); ?></span>
                        <h2 class="font-display text-3xl font-bold text-white md:text-4xl">
                            <?php _e('home.come_to_aurora'); ?>
                        </h2>
                        <p class="text-base text-white/70 max-w-2xl mx-auto">
                            <?php _e('home.location_desc'); ?>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Map -->
                        <div
                            class="rounded-2xl overflow-hidden shadow-2xl border border-white/10 glass-card p-0 h-[500px]">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3917.0824374942376!2d106.84213347514152!3d10.957145355834111!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dc27705d362d%3A0xc1fb19ec2c2b1806!2zS2jDoWNoIHPhuqFuIEF1cm9yYQ!5e0!3m2!1svi!2s!4v1765630076897!5m2!1svi!2s"
                                class="w-full h-full" style="border:0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>

                        <!-- Contact Info -->
                        <div class="flex flex-col gap-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="glass-card flex gap-4 p-5 hover:-translate-y-1 transition-all duration-300">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-accent">
                                            <span class="material-symbols-outlined text-xl">location_on</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold mb-1 text-white"><?php _e('home.address_label'); ?></h4>
                                        <p class="text-sm text-white/70">
                                            <?php _e('home.address_full'); ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="tel:+842513918888"
                                    class="glass-card flex gap-4 p-5 hover:-translate-y-1 transition-all duration-300 group">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-colors">
                                            <span class="material-symbols-outlined text-xl">phone</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold mb-1 text-white group-hover:text-accent transition-colors">
                                            <?php _e('home.phone_label'); ?>
                                        </h4>
                                        <p class="text-sm text-white/70">
                                            (+84-251) 391.8888<br>
                                            Hotline: 0909.123.456
                                        </p>
                                    </div>
                                </a>
                                <a href="mailto:info@aurorahotelplaza.com"
                                    class="glass-card flex gap-4 p-5 hover:-translate-y-1 transition-all duration-300 group">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-colors">
                                            <span class="material-symbols-outlined text-xl">email</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold mb-1 text-white group-hover:text-accent transition-colors">
                                            <?php _e('home.email_label'); ?>
                                        </h4>
                                        <p class="text-sm text-white/70">
                                            info@aurorahotelplaza.com<br>
                                            booking@aurorahotelplaza.com
                                        </p>
                                    </div>
                                </a>
                                <div class="glass-card flex gap-4 p-5 hover:-translate-y-1 transition-all duration-300">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-accent">
                                            <span class="material-symbols-outlined text-xl">schedule</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold mb-1 text-white"><?php _e('home.working_hours_label'); ?>
                                        </h4>
                                        <p class="text-sm text-white/70">
                                            <?php _e('home.working_hours_detail'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Nearby Places -->
                            <div class="glass-card p-6">
                                <h4 class="font-bold mb-4 flex items-center gap-2 text-white">
                                    <span class="material-symbols-outlined text-accent">near_me</span>
                                    <?php _e('home.nearby_places'); ?>
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                    <?php
                                    $places = [
                                        ['name' => 'home.tan_son_nhat_airport', 'dist' => '35 km'],
                                        ['name' => 'home.long_thanh_airport', 'dist' => '25 km'],
                                        ['name' => 'home.amata_industrial', 'dist' => '5 km'],
                                        ['name' => 'home.bien_hoa_2_industrial', 'dist' => '3 km'],
                                        ['name' => 'home.big_c_bien_hoa', 'dist' => '2 km'],
                                        ['name' => 'home.bien_hoa_bus_station', 'dist' => '1.5 km']
                                    ];
                                    foreach ($places as $place):
                                        ?>
                                        <div
                                            class="flex justify-between items-center p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-colors border border-white/5">
                                            <span class="text-white/80"><?php _e($place['name']); ?></span>
                                            <span class="font-bold text-accent"><?php echo $place['dist']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section - Premium Dark Glass -->
            <section class="w-full justify-center py-24 relative overflow-hidden">
                <!-- Dark Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900"></div>
                <!-- Decorative Glow -->
                <div
                    class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-accent/5 rounded-full blur-[120px] pointer-events-none">
                </div>

                <div class="mx-auto flex max-w-4xl flex-col gap-8 px-4 text-center relative z-10">
                    <div class="flex flex-col gap-4">
                        <h2 class="font-display text-3xl font-bold text-white md:text-5xl drop-shadow-lg">
                            <?php _e('home.ready_for_vacation'); ?>
                        </h2>
                        <p class="text-lg text-white/80 max-w-2xl mx-auto">
                            <?php _e('home.cta_desc'); ?>
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row sm:justify-center mt-4">
                        <a href="booking/index.php"
                            class="btn-glass-gold px-8 py-4 text-lg shadow-xl shadow-accent/20 hover:shadow-accent/40 hover:-translate-y-1">
                            <span class="material-symbols-outlined">calendar_month</span>
                            <?php _e('home.book_now_cta'); ?>
                        </a>
                        <a href="tel:+842513918888"
                            class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border border-white/20 hover:bg-white/10 text-white font-bold transition-all hover:-translate-y-1 backdrop-blur-md">
                            <span class="material-symbols-outlined">phone</span>
                            <?php _e('home.call_now'); ?>
                        </a>
                    </div>

                    <div class="flex flex-wrap justify-center gap-6 mt-8 text-white/60 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg text-accent">check_circle</span>
                            <span><?php _e('home.instant_confirm'); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg text-accent">check_circle</span>
                            <span><?php _e('home.free_cancel_24h'); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg text-accent">check_circle</span>
                            <span><?php _e('home.flexible_payment'); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg text-accent">check_circle</span>
                            <span><?php _e('home.best_price_guarantee'); ?></span>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <?php include 'includes/footer.php'; ?>

    </div>

    <!--  JavaScript -->
    <script src="<?php echo assetVersion('js/main.js'); ?>" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'track_booking') {
                // Clear URL to prevent re-opening on refresh
                window.history.replaceState({}, document.title, window.location.pathname);

                setTimeout(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    if (typeof toggleTrackForm === 'function') {
                        toggleTrackForm(true);

                        // Show enhanced tooltip with higher z-index - Gold theme
                        const trackInput = document.getElementById('trackInput');
                        if (trackInput) {
                            const tooltip = document.createElement('div');
                            tooltip.className = 'help-tooltip-highlight';
                            tooltip.innerHTML = `
                                <div class="tooltip-content">
                                    <span class="tooltip-icon">🔍</span>
                                    <span class="tooltip-text"><strong>Theo dõi đơn đặt phòng</strong><br>Nhập mã đặt phòng, SĐT hoặc email của bạn vào đây!</span>
                                </div>
                            `;

                            // Style the tooltip - Gold theme matching website
                            tooltip.style.cssText = `
                                position: fixed;
                                z-index: 100000;
                                background: rgba(30, 41, 59, 0.95);
                                backdrop-filter: blur(16px) saturate(120%);
                                -webkit-backdrop-filter: blur(16px) saturate(120%);
                                color: white;
                                padding: 16px 20px;
                                border-radius: 12px;
                                border: 1px solid rgba(212, 175, 55, 0.3);
                                box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3);
                                font-size: 14px;
                                font-weight: 500;
                                line-height: 1.5;
                                pointer-events: none;
                                animation: tooltipPulse 2s ease-in-out infinite;
                                max-width: 320px;
                            `;

                            // Position the tooltip above the input
                            const rect = trackInput.getBoundingClientRect();
                            const isMobile = window.innerWidth < 640;

                            if (isMobile) {
                                // Mobile: show below the input
                                tooltip.style.left = '50%';
                                tooltip.style.top = (rect.bottom + 15) + 'px';
                                tooltip.style.transform = 'translateX(-50%)';
                                tooltip.style.marginTop = '10px';
                            } else {
                                // Desktop: show above and to the left
                                tooltip.style.right = '100%';
                                tooltip.style.top = '50%';
                                tooltip.style.transform = 'translateY(-50%)';
                                tooltip.style.marginRight = '20px';
                            }

                            trackInput.parentNode.appendChild(tooltip);

                            // Add animation keyframes
                            const style = document.createElement('style');
                            style.textContent = `
                                @keyframes tooltipPulse {
                                    0%, 100% {
                                        transform: ${isMobile ? 'translateX(-50%) scale(1)' : 'translateY(-50%) scale(1)'};
                                        box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3);
                                        border-color: rgba(212, 175, 55, 0.3);
                                    }
                                    50% {
                                        transform: ${isMobile ? 'translateX(-50%) scale(1.05)' : 'translateY(-50%) scale(1.05)'};
                                        box-shadow: 0 12px 40px rgba(212, 175, 55, 0.5);
                                        border-color: rgba(212, 175, 55, 0.5);
                                    }
                                }
                                .tooltip-content {
                                    display: flex;
                                    align-items: center;
                                    gap: 12px;
                                }
                                .tooltip-icon {
                                    font-size: 24px;
                                    flex-shrink: 0;
                                }
                                .tooltip-text strong {
                                    display: block;
                                    margin-bottom: 4px;
                                    font-size: 15px;
                                    color: #d4af37;
                                }
                            `;
                            document.head.appendChild(style);

                            // Remove tooltip after 8 seconds
                            setTimeout(() => {
                                tooltip.style.opacity = '0';
                                tooltip.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                                tooltip.style.transform = isMobile ? 'translateX(-50%) translateY(-10px)' : 'translateY(-50%) translateX(10px)';
                                setTimeout(() => tooltip.remove(), 500);
                            }, 8000);
                        }
                    }
                }, 500);
            }
        });
    </script>
</body>

</html>
       }, 500);
            }
        });
    </script>
</body>

</html>
