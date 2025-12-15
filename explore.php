<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/image-helper.php';
require_once 'helpers/language.php';
initLanguage();

// Fetch featured rooms
$featured_rooms = [];
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM room_types WHERE status = 'active' AND category = 'room' ORDER BY sort_order ASC LIMIT 4");
    $stmt->execute();
    $featured_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Explore page error: " . $e->getMessage());
}

// Fetch featured apartments
$featured_apartments = [];
try {
    $stmt = $db->prepare("SELECT * FROM room_types WHERE status = 'active' AND category = 'apartment' ORDER BY sort_order ASC LIMIT 4");
    $stmt->execute();
    $featured_apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Explore page (apartments) error: " . $e->getMessage());
}

// Fetch featured services
$featured_services = [];
try {
    $stmt = $db->prepare("SELECT * FROM services WHERE is_available = 1 AND is_featured = 1 ORDER BY sort_order ASC LIMIT 6");
    $stmt->execute();
    $featured_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Explore page (services) error: " . $e->getMessage());
}

// Fetch latest blog posts
$latest_posts = [];
try {
    $stmt = $db->prepare("SELECT p.*, u.full_name as author_name FROM blog_posts p LEFT JOIN users u ON p.author_id = u.user_id WHERE p.status = 'published' ORDER BY p.published_at DESC LIMIT 3");
    $stmt->execute();
    $latest_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Explore page (blog) error: " . $e->getMessage());
}

// Stats
$stats = ['total_rooms' => 150, 'happy_customers' => 5000, 'years_experience' => 10];
try {
    $stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status != 'inactive'");
    $stats['total_rooms'] = $stmt->fetchColumn() ?: 150;
    $stmt = $db->query("SELECT COUNT(DISTINCT user_id) FROM bookings WHERE status IN ('completed', 'checked_out')");
    $stats['happy_customers'] = $stmt->fetchColumn() ?: 5000;
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php _e('explore_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<style>
.explore-hero {
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.9), rgba(17, 24, 39, 0.7)), url('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg');
    background-size: cover;
    background-position: center;
    min-height: 500px;
}
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Hero Section -->
    <section class="explore-hero flex items-center justify-center pt-20">
        <div class="mx-auto max-w-7xl px-4 py-20 text-center">
            <span class="badge-liquid-glass mb-6">
                <span class="material-symbols-outlined text-accent">explore</span>
                <?php _e('explore_page.badge'); ?>
            </span>
            <h1 class="font-display text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
                <?php _e('explore_page.hero_title'); ?>
            </h1>
            <p class="text-lg text-white/80 max-w-2xl mx-auto mb-10">
                <?php _e('explore_page.hero_desc'); ?>
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="booking/index.php" class="btn-liquid-primary">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <?php _e('explore_page.book_now'); ?>
                </a>
                <a href="#quick-links" class="btn-liquid-glass">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    <?php _e('explore_page.explore_more'); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Quick Links Section -->
    <section id="quick-links" class="py-16 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900 dark:to-slate-800">
        <div class="mx-auto max-w-7xl px-4">
            <div class="text-center mb-12">
                <span class="glass-section-badge mb-4">
                    <span class="material-symbols-outlined text-sm">bolt</span>
                    <?php _e('explore_page.quick_access'); ?>
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4"><?php _e('explore_page.what_looking_for'); ?></h2>
                <p class="text-gray-600 dark:text-gray-400 max-w-xl mx-auto"><?php _e('explore_page.quick_access_desc'); ?></p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <!-- Phòng nghỉ -->
                <a href="rooms.php" class="glass-quick-link group">
                    <div class="glass-quick-link-icon">
                        <span class="material-symbols-outlined text-2xl">hotel</span>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1"><?php _e('explore_page.rooms'); ?></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php _e('explore_page.rooms_desc'); ?></p>
                </a>
                
                <!-- Căn hộ -->
                <a href="apartments.php" class="glass-quick-link group">
                    <div class="glass-quick-link-icon">
                        <span class="material-symbols-outlined text-2xl">apartment</span>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1"><?php _e('explore_page.apartments'); ?></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php _e('explore_page.apartments_desc'); ?></p>
                </a>
                
                <!-- Dịch vụ -->
                <a href="services.php" class="glass-quick-link group">
                    <div class="glass-quick-link-icon">
                        <span class="material-symbols-outlined text-2xl">room_service</span>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1"><?php _e('explore_page.services'); ?></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php _e('explore_page.services_desc'); ?></p>
                </a>
                
                <!-- Thư viện ảnh -->
                <a href="gallery.php" class="glass-quick-link group">
                    <div class="glass-quick-link-icon">
                        <span class="material-symbols-outlined text-2xl">photo_library</span>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1"><?php _e('explore_page.gallery'); ?></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php _e('explore_page.gallery_desc'); ?></p>
                </a>
                
                <!-- Blog -->
                <a href="blog.php" class="glass-quick-link group">
                    <div class="glass-quick-link-icon">
                        <span class="material-symbols-outlined text-2xl">article</span>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1"><?php _e('explore_page.news'); ?></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php _e('explore_page.news_desc'); ?></p>
                </a>
                
                <!-- Liên hệ -->
                <a href="contact.php" class="glass-quick-link group">
                    <div class="glass-quick-link-icon">
                        <span class="material-symbols-outlined text-2xl">contact_support</span>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1"><?php _e('explore_page.contact'); ?></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php _e('explore_page.contact_desc'); ?></p>
                </a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-4">
            <div class="text-center mb-12">
                <span class="glass-section-badge mb-4">
                    <span class="material-symbols-outlined text-sm">category</span>
                    <?php _e('explore_page.categories'); ?>
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4"><?php _e('explore_page.explore_by_category'); ?></h2>
                <p class="text-gray-600 dark:text-gray-400 max-w-xl mx-auto"><?php _e('explore_page.category_desc'); ?></p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Phòng nghỉ -->
                <a href="rooms.php" class="glass-category-card">
                    <img src="assets/img/deluxe/deluxe-room-aurora-1.jpg" alt="<?php _e('explore_page.rooms'); ?>">
                    <div class="glass-category-overlay"></div>
                    <div class="glass-category-content">
                        <span class="glass-category-badge">
                            <span class="material-symbols-outlined text-sm">hotel</span>
                            <?php _e('explore_page.room_types_count', ['count' => count($featured_rooms)]); ?>
                        </span>
                        <h3 class="text-xl font-bold mb-2"><?php _e('explore_page.rooms'); ?></h3>
                        <p class="text-white/80 text-sm"><?php _e('explore_page.rooms_category_desc'); ?></p>
                    </div>
                </a>
                
                <!-- Căn hộ -->
                <a href="apartments.php" class="glass-category-card">
                    <img src="assets/img/apartment/can-ho-aurora-1.jpg" alt="<?php _e('explore_page.apartments'); ?>" onerror="this.src='assets/img/deluxe/deluxe-room-aurora-3.jpg'">
                    <div class="glass-category-overlay"></div>
                    <div class="glass-category-content">
                        <span class="glass-category-badge">
                            <span class="material-symbols-outlined text-sm">apartment</span>
                            <?php _e('explore_page.apartments_count', ['count' => count($featured_apartments)]); ?>
                        </span>
                        <h3 class="text-xl font-bold mb-2"><?php _e('explore_page.service_apartments'); ?></h3>
                        <p class="text-white/80 text-sm"><?php _e('explore_page.apartments_category_desc'); ?></p>
                    </div>
                </a>
                
                <!-- Nhà hàng -->
                <a href="services.php?category=restaurant" class="glass-category-card">
                    <img src="assets/img/restaurant/nha-hang-aurora-hotel-4.jpg" alt="<?php _e('explore_page.restaurant_aurora'); ?>">
                    <div class="glass-category-overlay"></div>
                    <div class="glass-category-content">
                        <span class="glass-category-badge">
                            <span class="material-symbols-outlined text-sm">restaurant</span>
                            <?php _e('explore_page.cuisine'); ?>
                        </span>
                        <h3 class="text-xl font-bold mb-2"><?php _e('explore_page.restaurant_aurora'); ?></h3>
                        <p class="text-white/80 text-sm"><?php _e('explore_page.restaurant_desc'); ?></p>
                    </div>
                </a>
                
                <!-- Sự kiện -->
                <a href="services.php?category=event" class="glass-category-card">
                    <img src="assets/img/post/wedding/tiec-cuoi-tai-aurora-5.jpg" alt="<?php _e('explore_page.events'); ?>">
                    <div class="glass-category-overlay"></div>
                    <div class="glass-category-content">
                        <span class="glass-category-badge">
                            <span class="material-symbols-outlined text-sm">celebration</span>
                            <?php _e('explore_page.events'); ?>
                        </span>
                        <h3 class="text-xl font-bold mb-2"><?php _e('explore_page.wedding_conference'); ?></h3>
                        <p class="text-white/80 text-sm"><?php _e('explore_page.events_desc'); ?></p>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Rooms Section -->
    <?php if (!empty($featured_rooms)): ?>
    <section class="py-16 bg-surface-light dark:bg-surface-dark">
        <div class="mx-auto max-w-7xl px-4">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
                <div>
                    <span class="glass-section-badge mb-4">
                        <span class="material-symbols-outlined text-sm">hotel</span>
                        <?php _e('explore_page.rooms'); ?>
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2"><?php _e('explore_page.featured_rooms'); ?></h2>
                    <p class="text-gray-600 dark:text-gray-400"><?php _e('explore_page.featured_rooms_desc'); ?></p>
                </div>
                <a href="rooms.php" class="inline-flex items-center gap-2 text-accent font-bold hover:underline mt-4 md:mt-0">
                    <?php _e('explore_page.view_all_rooms'); ?>
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($featured_rooms as $room): 
                    $thumbnail = normalizeImagePath($room['thumbnail']);
                    $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                ?>
                <a href="room-details/<?php echo htmlspecialchars($room['slug']); ?>.php" class="group">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300">
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($room['type_name']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute top-3 right-3 px-3 py-1 bg-accent text-white text-xs font-bold rounded-full">
                                <?php echo number_format($room['base_price'], 0, ',', '.'); ?>đ<?php _e('explore_page.per_night'); ?>
                            </div>
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2 group-hover:text-accent transition-colors"><?php echo htmlspecialchars($room['type_name']); ?></h3>
                            <div class="flex flex-wrap gap-3 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base text-accent">square_foot</span>
                                    <?php echo number_format($room['size_sqm'], 0); ?>m²
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base text-accent">person</span>
                                    <?php echo $room['max_occupancy']; ?> <?php _e('explore_page.guests'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Apartments Section -->
    <?php if (!empty($featured_apartments)): ?>
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-4">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
                <div>
                    <span class="glass-section-badge mb-4">
                        <span class="material-symbols-outlined text-sm">apartment</span>
                        <?php _e('explore_page.apartments'); ?>
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2"><?php _e('explore_page.service_apartments'); ?></h2>
                    <p class="text-gray-600 dark:text-gray-400"><?php _e('explore_page.apartments_section_desc'); ?></p>
                </div>
                <a href="apartments.php" class="inline-flex items-center gap-2 text-accent font-bold hover:underline mt-4 md:mt-0">
                    <?php _e('explore_page.view_all_apartments'); ?>
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($featured_apartments as $apt): 
                    $thumbnail = normalizeImagePath($apt['thumbnail']);
                    $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                ?>
                <a href="apartment-details/<?php echo htmlspecialchars($apt['slug']); ?>.php" class="group">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300">
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($apt['type_name']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute top-3 left-3 px-3 py-1 bg-gradient-to-r from-accent to-primary text-white text-xs font-bold rounded-full">
                                <?php _e('explore_page.apartment_badge'); ?>
                            </div>
                            <div class="absolute top-3 right-3 px-3 py-1 bg-white/90 text-accent text-xs font-bold rounded-full">
                                <?php echo number_format($apt['base_price'], 0, ',', '.'); ?>đ<?php _e('explore_page.per_night'); ?>
                            </div>
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2 group-hover:text-accent transition-colors"><?php echo htmlspecialchars($apt['type_name']); ?></h3>
                            <div class="flex flex-wrap gap-3 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base text-accent">square_foot</span>
                                    <?php echo number_format($apt['size_sqm'], 0); ?>m²
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base text-accent">person</span>
                                    <?php echo $apt['max_occupancy']; ?> <?php _e('explore_page.guests'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Services Section -->
    <?php if (!empty($featured_services)): ?>
    <section class="py-16 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900 dark:to-slate-800">
        <div class="mx-auto max-w-7xl px-4">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
                <div>
                    <span class="glass-section-badge mb-4">
                        <span class="material-symbols-outlined text-sm">star</span>
                        <?php _e('explore_page.featured_services'); ?>
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2"><?php _e('explore_page.five_star_services'); ?></h2>
                    <p class="text-gray-600 dark:text-gray-400"><?php _e('explore_page.services_section_desc'); ?></p>
                </div>
                <a href="services.php" class="inline-flex items-center gap-2 text-accent font-bold hover:underline mt-4 md:mt-0">
                    <?php _e('explore_page.view_all_services'); ?>
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <?php foreach ($featured_services as $service): ?>
                <a href="service-detail.php?slug=<?php echo htmlspecialchars($service['slug']); ?>" class="group">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 text-center shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-accent/20 to-accent/5 flex items-center justify-center group-hover:from-accent group-hover:to-primary transition-all">
                            <span class="material-symbols-outlined text-3xl text-accent group-hover:text-white transition-colors"><?php echo htmlspecialchars($service['icon']); ?></span>
                        </div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white mb-1 group-hover:text-accent transition-colors"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <?php if ($service['price'] > 0): ?>
                        <p class="text-xs text-accent font-bold"><?php _e('explore_page.from_price'); ?> <?php echo number_format($service['price'], 0, ',', '.'); ?>đ</p>
                        <?php else: ?>
                        <p class="text-xs text-green-600 font-bold"><?php _e('explore_page.free'); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Stats Section -->
    <section class="py-16 bg-gradient-to-r from-gray-900 to-gray-800">
        <div class="mx-auto max-w-7xl px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-accent mb-2"><?php echo $stats['total_rooms']; ?>+</div>
                    <p class="text-white/80"><?php _e('home.rooms_apartments'); ?></p>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-accent mb-2"><?php echo number_format($stats['happy_customers']); ?>+</div>
                    <p class="text-white/80"><?php _e('home.happy_customers'); ?></p>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-accent mb-2"><?php echo $stats['years_experience']; ?>+</div>
                    <p class="text-white/80"><?php _e('home.years_experience'); ?></p>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-accent mb-2">24/7</div>
                    <p class="text-white/80"><?php _e('home.customer_support'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Blog Section -->
    <?php if (!empty($latest_posts)): ?>
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-4">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
                <div>
                    <span class="glass-section-badge mb-4">
                        <span class="material-symbols-outlined text-sm">article</span>
                        <?php _e('explore_page.news'); ?>
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2"><?php _e('explore_page.latest_posts'); ?></h2>
                    <p class="text-gray-600 dark:text-gray-400"><?php _e('explore_page.latest_posts_desc'); ?></p>
                </div>
                <a href="blog.php" class="inline-flex items-center gap-2 text-accent font-bold hover:underline mt-4 md:mt-0">
                    <?php _e('explore_page.view_all_posts'); ?>
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($latest_posts as $post): ?>
                <a href="blog-detail.php?slug=<?php echo urlencode($post['slug']); ?>" class="group">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300">
                        <div class="relative h-48 overflow-hidden">
                            <?php if ($post['featured_image']): ?>
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-accent/20 to-primary/20 flex items-center justify-center">
                                <span class="material-symbols-outlined text-5xl text-accent">article</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-3">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">calendar_today</span>
                                    <?php echo date('d/m/Y', strtotime($post['published_at'])); ?>
                                </span>
                                <?php if ($post['author_name']): ?>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">person</span>
                                    <?php echo htmlspecialchars($post['author_name']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2 group-hover:text-accent transition-colors line-clamp-2"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <?php if ($post['excerpt']): ?>
                            <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section - Dark Style -->
    <section class="py-20 relative overflow-hidden" style="background: linear-gradient(135deg, rgba(17, 24, 39, 0.95), rgba(17, 24, 39, 0.85)), url('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg'); background-size: cover; background-position: center;">
        <div class="mx-auto max-w-7xl px-4 text-center relative z-10">
            <span class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white text-sm font-semibold mb-6 border border-white/20">
                <span class="material-symbols-outlined">support_agent</span>
                Hỗ trợ 24/7
            </span>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Sẵn sàng đặt phòng?</h2>
            <p class="text-white/80 max-w-2xl mx-auto mb-8">Liên hệ ngay với chúng tôi để được tư vấn và đặt phòng với giá tốt nhất</p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="booking/index.php" class="btn-liquid-primary">
                    <span class="material-symbols-outlined">calendar_month</span>
                    Đặt phòng ngay
                </a>
                <a href="tel:+842513918888" class="btn-liquid-glass">
                    <span class="material-symbols-outlined">phone</span>
                    (+84-251) 391.8888
                </a>
            </div>
        </div>
    </section>

    <!-- More Links Section -->
    <section class="py-16 bg-surface-light dark:bg-surface-dark">
        <div class="mx-auto max-w-7xl px-4">
            <div class="text-center mb-10">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">Khám phá thêm</h2>
                <p class="text-gray-600 dark:text-gray-400">Các trang hữu ích khác</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                <a href="about.php" class="flex items-center gap-3 md:gap-4 p-4 bg-white dark:bg-slate-800 rounded-xl shadow hover:shadow-lg transition-all group">
                    <div class="w-10 h-10 md:w-12 md:h-12 flex-shrink-0 rounded-xl bg-accent/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-accent text-xl md:text-2xl">info</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-sm md:text-base text-gray-900 dark:text-white group-hover:text-accent transition-colors truncate">Về chúng tôi</h3>
                        <p class="text-xs md:text-sm text-gray-500 truncate">Giới thiệu Aurora</p>
                    </div>
                </a>
                
                <a href="room-map-user.php" class="flex items-center gap-3 md:gap-4 p-4 bg-white dark:bg-slate-800 rounded-xl shadow hover:shadow-lg transition-all group">
                    <div class="w-10 h-10 md:w-12 md:h-12 flex-shrink-0 rounded-xl bg-accent/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-accent text-xl md:text-2xl">map</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-sm md:text-base text-gray-900 dark:text-white group-hover:text-accent transition-colors truncate">Sơ đồ phòng</h3>
                        <p class="text-xs md:text-sm text-gray-500 truncate">Xem vị trí phòng</p>
                    </div>
                </a>
                
                <a href="gallery.php" class="flex items-center gap-3 md:gap-4 p-4 bg-white dark:bg-slate-800 rounded-xl shadow hover:shadow-lg transition-all group">
                    <div class="w-10 h-10 md:w-12 md:h-12 flex-shrink-0 rounded-xl bg-accent/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-accent text-xl md:text-2xl">photo_library</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-sm md:text-base text-gray-900 dark:text-white group-hover:text-accent transition-colors truncate">Thư viện ảnh</h3>
                        <p class="text-xs md:text-sm text-gray-500 truncate">Hình ảnh khách sạn</p>
                    </div>
                </a>
                
                <a href="contact.php" class="flex items-center gap-3 md:gap-4 p-4 bg-white dark:bg-slate-800 rounded-xl shadow hover:shadow-lg transition-all group">
                    <div class="w-10 h-10 md:w-12 md:h-12 flex-shrink-0 rounded-xl bg-accent/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-accent text-xl md:text-2xl">mail</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-sm md:text-base text-gray-900 dark:text-white group-hover:text-accent transition-colors truncate">Liên hệ</h3>
                        <p class="text-xs md:text-sm text-gray-500 truncate">Gửi yêu cầu</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>

