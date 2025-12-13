<?php
// Start session for user authentication
session_start();

// Load environment configuration
require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/image-helper.php';

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
        SELECT p.title, p.slug, p.excerpt, p.featured_image, p.published_at, u.full_name as author_name
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
        SELECT r.*, u.full_name, rt.type_name
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
<html class="light" lang="vi">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Aurora Hotel Plaza - Khách sạn sang trọng tại Biên Hòa</title>

    <!-- Tailwind CSS -->
    <script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
    <link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet" />

    <!-- Tailwind Configuration -->
    <script src="<?php echo asset('js/tailwind-config.js'); ?>?v=<?php echo time(); ?>"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/liquid-glass.css'); ?>?v=<?php echo time(); ?>">
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
                        <a href="mailto:booking@aurorahotelplaza.com" class="glass-tag hover:bg-white/20 transition-all">
                            <span class="material-symbols-outlined text-accent text-base">email</span>
                            <span class="font-medium">booking@aurorahotelplaza.com</span>
                        </a>
                        <div class="glass-tag">
                            <span class="material-symbols-outlined text-accent text-base">location_on</span>
                            <span class="font-medium">253 Phạm Văn Thuận, Biên Hòa</span>
                        </div>
                        <div class="glass-tag">
                            <span class="material-symbols-outlined text-accent text-base">schedule</span>
                            <span class="font-medium">Lễ tân 24/7</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- About Section -->
            <section class="w-full justify-center py-16 sm:py-24" id="about">
                <div class="mx-auto flex max-w-7xl flex-col gap-10 px-4">
                    <div class="flex flex-col gap-4 text-center">
                        <span class="text-accent font-semibold text-sm uppercase tracking-wider">Về chúng tôi</span>
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Chào mừng đến với Aurora Hotel Plaza</h2>
                        <p class="mx-auto max-w-3xl text-base leading-relaxed text-text-secondary-light dark:text-text-secondary-dark">
                            Tọa lạc tại trung tâm thành phố Biên Hòa, Aurora Hotel Plaza mang đến trải nghiệm sang trọng
                            và thanh bình vô song. Với hơn 10 năm kinh nghiệm trong ngành khách sạn, chúng tôi cam kết 
                            mang đến dịch vụ 5 sao với giá cả hợp lý nhất.</p>
                    </div>
                    
                    <!-- Stats Counter - Liquid Glass -->
                    <div class="grid grid-cols-2 gap-6 md:grid-cols-4 my-8">
                        <div class="glass-stat-card-light glass-glow">
                            <span class="stat-value"><?php echo $stats['total_rooms']; ?>+</span>
                            <span class="stat-label">Phòng & Căn hộ</span>
                        </div>
                        <div class="glass-stat-card-light glass-glow">
                            <span class="stat-value"><?php echo number_format($stats['happy_customers']); ?>+</span>
                            <span class="stat-label">Khách hàng hài lòng</span>
                        </div>
                        <div class="glass-stat-card-light glass-glow">
                            <span class="stat-value"><?php echo $stats['years_experience']; ?>+</span>
                            <span class="stat-label">Năm kinh nghiệm</span>
                        </div>
                        <div class="glass-stat-card-light glass-glow">
                            <span class="stat-value">24/7</span>
                            <span class="stat-label">Hỗ trợ khách hàng</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="glass-card-solid p-6 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-3xl text-accent">restaurant</span>
                            </div>
                            <h3 class="text-lg font-bold mb-2">Ẩm thực tinh tế</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Nhà hàng Aurora phục vụ ẩm thực Á - Âu với đầu bếp giàu kinh nghiệm.</p>
                        </div>
                        <div class="glass-card-solid p-6 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-3xl text-accent">celebration</span>
                            </div>
                            <h3 class="text-lg font-bold mb-2">Tiệc cưới & Sự kiện</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tổ chức tiệc cưới, hội nghị với sảnh tiệc sang trọng lên đến 500 khách.</p>
                        </div>
                        <div class="glass-card-solid p-6 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-3xl text-accent">apartment</span>
                            </div>
                            <h3 class="text-lg font-bold mb-2">Căn hộ dịch vụ</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Căn hộ cao cấp cho thuê dài hạn với đầy đủ tiện nghi như ở nhà.</p>
                        </div>
                        <div class="glass-card-solid p-6 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-3xl text-accent">business_center</span>
                            </div>
                            <h3 class="text-lg font-bold mb-2">Văn phòng cho thuê</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Không gian văn phòng chuyên nghiệp với vị trí đắc địa tại trung tâm.</p>
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
                            <div class="w-16 h-16 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center">
                                <span class="material-symbols-outlined text-4xl animate-pulse">local_offer</span>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold">Ưu đãi đặc biệt!</h3>
                                <p class="text-white/90">Giảm đến <?php echo max(array_column($active_promotions, 'discount_percent')); ?>% cho đặt phòng trực tuyến</p>
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
                            Đặt ngay
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Featured Rooms Section -->
            <section class="w-full justify-center bg-primary-light/30 py-16 dark:bg-surface-dark sm:py-24" id="rooms">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
                    <div class="flex flex-col gap-2 text-center">
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl"></h2>
                            Phòng &amp; Suite</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark">Được thiết kế cho sự thoải mái, tạo nên những giấc mơ.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php if (!empty($featured_rooms)): ?>
                            <?php foreach ($featured_rooms as $room): 
                                // Parse thumbnail image path
                                $thumbnail = normalizeImagePath($room['thumbnail']);
                                $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                            ?>
                                <div class="flex flex-col overflow-hidden rounded-xl bg-surface-light shadow-md transition-shadow hover:shadow-xl dark:bg-background-dark dark:shadow-none dark:ring-1 dark:ring-gray-700 transform translate-y-0 transition-transform duration-300 hover:-translate-y-1">
                                    <div class="aspect-video w-full bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>');"></div>
                                    <div class="flex flex-1 flex-col justify-between p-6">
                                        <div>
                                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($room['type_name']); ?></h3>
                                            <p class="mt-1 text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                                <?php echo number_format($room['size_sqm'], 0); ?> m², 
                                                <?php echo htmlspecialchars($room['bed_type']); ?>, 
                                                <?php echo $room['max_occupancy']; ?> người
                                            </p>
                                        </div>
                                        <div class="mt-4 flex flex-col gap-2">
                                            <div class="text-lg font-bold text-accent">
                                                <?php echo number_format($room['base_price'], 0, ',', '.'); ?>đ <span class="text-sm font-normal">/đêm</span>
                                            </div>
                                            <a href="room-details/<?php echo htmlspecialchars($room['slug']); ?>.php" 
                                               class="flex h-10 w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg bg-primary-light text-primary dark:bg-gray-700 dark:text-primary-light text-sm font-bold transition-colors hover:bg-primary/20 dark:hover:bg-gray-600">
                                                <span class="truncate">Xem chi tiết</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center py-12">
                                <p class="text-gray-500 text-lg">Không có phòng nào</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-center pt-4">
                        <a href="rooms.php" class="btn-glass-primary">
                            Xem tất cả phòng
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Featured Apartments Section -->
            <section class="w-full justify-center py-16 sm:py-24" id="apartments">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
                    <div class="flex flex-col gap-2 text-center">
                        <span class="glass-badge-solid mx-auto mb-2">
                            <span class="material-symbols-outlined text-accent text-sm">apartment</span>
                            Căn hộ cao cấp
                        </span>
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Căn Hộ Nổi Bật</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark">Không gian sống hiện đại và tiện nghi.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php if (!empty($featured_apartments)): ?>
                            <?php foreach ($featured_apartments as $apartment): 
                                // Parse thumbnail image path
                                $thumbnail = normalizeImagePath($apartment['thumbnail']);
                                $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                            ?>
                                <div class="flex flex-col overflow-hidden rounded-xl bg-surface-light shadow-md transition-shadow hover:shadow-xl dark:bg-background-dark dark:shadow-none dark:ring-1 dark:ring-gray-700 transform translate-y-0 transition-transform duration-300 hover:-translate-y-1">
                                    <div class="aspect-video w-full bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>');"></div>
                                    <div class="flex flex-1 flex-col justify-between p-6">
                                        <div>
                                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($apartment['type_name']); ?></h3>
                                            <p class="mt-1 text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                                <?php echo number_format($apartment['size_sqm'], 0); ?> m², 
                                                <?php echo htmlspecialchars($apartment['bed_type']); ?>, 
                                                <?php echo $apartment['max_occupancy']; ?> người
                                            </p>
                                        </div>
                                        <div class="mt-4 flex flex-col gap-2">
                                            <div class="text-lg font-bold text-accent">
                                                <?php echo number_format($apartment['base_price'], 0, ',', '.'); ?>đ <span class="text-sm font-normal">/đêm</span>
                                            </div>
                                            <a href="apartment-details/<?php echo htmlspecialchars($apartment['slug']); ?>.php" 
                                               class="flex h-10 w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg bg-primary-light text-primary dark:bg-gray-700 dark:text-primary-light text-sm font-bold transition-colors hover:bg-primary/20 dark:hover:bg-gray-600">
                                                <span class="truncate">Xem chi tiết</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center py-12">
                                <p class="text-gray-500 text-lg">Chưa có căn hộ nào được giới thiệu.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-center pt-4">
                        <a href="apartments.php" class="inline-flex items-center gap-2 px-6 py-3 bg-accent text-white rounded-lg font-bold hover:opacity-90 transition-opacity">
                            Xem tất cả căn hộ
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Services Section -->
            <section class="w-full py-16 sm:py-24 bg-surface-light dark:bg-surface-dark" id="services">
                <div class="mx-auto max-w-7xl px-4">
                    <div class="flex flex-col gap-2 text-center mb-10">
                        <span class="text-accent font-semibold text-sm uppercase tracking-wider">Dịch vụ của chúng tôi</span>
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Dịch vụ &amp; Tiện nghi đẳng cấp</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark max-w-2xl mx-auto">
                            Aurora Hotel Plaza cung cấp đầy đủ các dịch vụ tiện ích để đáp ứng mọi nhu cầu của quý khách trong suốt thời gian lưu trú.</p>
                    </div>
                    
                    <!-- Main Services Grid - Liquid Glass -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 mb-10">
                        <!-- Wedding Service -->
                        <a href="service-detail.php?slug=wedding-service" class="glass-service-card group">
                            <img src="assets/img/post/wedding/Tiec-cuoi-tai-aurora-5.jpg" alt="Tiệc cưới">
                            <div class="glass-service-overlay"></div>
                            <div class="glass-service-content">
                                <div class="glass-service-badge">
                                    <span class="material-symbols-outlined">celebration</span>
                                    Dịch vụ nổi bật
                                </div>
                                <h3 class="text-xl font-bold mb-2">Tổ chức Tiệc Cưới</h3>
                                <p class="text-white/80 text-sm">Sảnh tiệc sang trọng, sức chứa lên đến 500 khách với dịch vụ trọn gói chuyên nghiệp.</p>
                            </div>
                        </a>

                        <!-- Conference Service -->
                        <a href="service-detail.php?slug=conference-service" class="glass-service-card group">
                            <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-4.jpg" alt="Hội nghị">
                            <div class="glass-service-overlay"></div>
                            <div class="glass-service-content">
                                <div class="glass-service-badge">
                                    <span class="material-symbols-outlined">groups</span>
                                    Dịch vụ nổi bật
                                </div>
                                <h3 class="text-xl font-bold mb-2">Hội nghị &amp; Sự kiện</h3>
                                <p class="text-white/80 text-sm">Phòng họp hiện đại với đầy đủ trang thiết bị, phù hợp cho mọi quy mô sự kiện.</p>
                            </div>
                        </a>

                        <!-- Restaurant Service -->
                        <a href="service-detail.php?slug=aurora-restaurant" class="glass-service-card group">
                            <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-6.jpg" alt="Nhà hàng">
                            <div class="glass-service-overlay"></div>
                            <div class="glass-service-content">
                                <div class="glass-service-badge">
                                    <span class="material-symbols-outlined">restaurant</span>
                                    Ẩm thực
                                </div>
                                <h3 class="text-xl font-bold mb-2">Nhà hàng Aurora</h3>
                                <p class="text-white/80 text-sm">Thưởng thức ẩm thực Á - Âu tinh tế với không gian sang trọng và view đẹp.</p>
                            </div>
                        </a>
                    </div>

                    <!-- Amenities Grid - Liquid Glass -->
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 mb-8">
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">wifi</span>
                            <span class="text-sm font-medium text-center">WiFi miễn phí</span>
                        </div>
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">local_parking</span>
                            <span class="text-sm font-medium text-center">Bãi đỗ xe</span>
                        </div>
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">room_service</span>
                            <span class="text-sm font-medium text-center">Phục vụ phòng 24/7</span>
                        </div>
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">fitness_center</span>
                            <span class="text-sm font-medium text-center">Phòng Gym</span>
                        </div>
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">local_laundry_service</span>
                            <span class="text-sm font-medium text-center">Giặt ủi</span>
                        </div>
                        <div class="glass-amenity-card">
                            <span class="material-symbols-outlined">airport_shuttle</span>
                            <span class="text-sm font-medium text-center">Đưa đón sân bay</span>
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <a href="services.php" class="btn-glass-primary">
                            Xem tất cả dịch vụ
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Why Choose Us Section -->
            <section class="w-full py-16 sm:py-24 bg-primary-light/30 dark:bg-gray-900">
                <div class="mx-auto max-w-7xl px-4">
                    <div class="flex flex-col gap-2 text-center mb-10">
                        <span class="text-accent font-semibold text-sm uppercase tracking-wider">Tại sao chọn chúng tôi</span>
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Tại sao chọn Aurora Hotel Plaza</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark max-w-2xl mx-auto">
                            Với cam kết mang đến trải nghiệm tốt nhất, Aurora Hotel Plaza là lựa chọn hàng đầu cho kỳ nghỉ và công tác của bạn.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <div class="glass-card-solid flex gap-4 p-6">
                            <div class="flex-shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-accent/80 text-white shadow-lg">
                                    <span class="material-symbols-outlined text-2xl">location_on</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold mb-2">Vị trí đắc địa</h3>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark text-sm">Tọa lạc tại 253 Phạm Văn Thuận - trung tâm Biên Hòa, gần các điểm du lịch, trung tâm thương mại và khu công nghiệp.</p>
                            </div>
                        </div>
                        <div class="glass-card-solid flex gap-4 p-6">
                            <div class="flex-shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-accent/80 text-white shadow-lg">
                                    <span class="material-symbols-outlined text-2xl">support_agent</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold mb-2">Dịch vụ chuyên nghiệp</h3>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark text-sm">Đội ngũ nhân viên được đào tạo bài bản, thân thiện và tận tâm phục vụ 24/7.</p>
                            </div>
                        </div>
                        <div class="glass-card-solid flex gap-4 p-6">
                            <div class="flex-shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-accent/80 text-white shadow-lg">
                                    <span class="material-symbols-outlined text-2xl">payments</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold mb-2">Giá cả hợp lý</h3>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark text-sm">Dịch vụ 5 sao với mức giá cạnh tranh, nhiều chương trình khuyến mãi hấp dẫn quanh năm.</p>
                            </div>
                        </div>
                        <div class="glass-card-solid flex gap-4 p-6">
                            <div class="flex-shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-accent/80 text-white shadow-lg">
                                    <span class="material-symbols-outlined text-2xl">verified</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold mb-2">Chất lượng đảm bảo</h3>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark text-sm">Phòng ốc sạch sẽ, trang thiết bị hiện đại, được bảo trì thường xuyên đảm bảo chất lượng.</p>
                            </div>
                        </div>
                        <div class="glass-card-solid flex gap-4 p-6">
                            <div class="flex-shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-accent/80 text-white shadow-lg">
                                    <span class="material-symbols-outlined text-2xl">security</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold mb-2">An ninh 24/7</h3>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark text-sm">Hệ thống camera giám sát, bảo vệ chuyên nghiệp đảm bảo an toàn tuyệt đối cho khách hàng.</p>
                            </div>
                        </div>
                        <div class="glass-card-solid flex gap-4 p-6">
                            <div class="flex-shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-accent/80 text-white shadow-lg">
                                    <span class="material-symbols-outlined text-2xl">diversity_3</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold mb-2">Đa dạng lựa chọn</h3>
                                <p class="text-text-secondary-light dark:text-text-secondary-dark text-sm">Từ phòng tiêu chuẩn đến căn hộ cao cấp, đáp ứng mọi nhu cầu từ du lịch đến công tác dài hạn.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Customer Reviews Section -->
            <section class="w-full justify-center py-16 sm:py-24" id="reviews">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
                    <div class="flex flex-col gap-2 text-center">
                        <span class="text-accent font-semibold text-sm uppercase tracking-wider">Đánh giá từ khách hàng</span>
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Khách hàng nói gì về chúng tôi</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark">
                            Hơn 5.000 khách hàng đã tin tưởng và hài lòng với dịch vụ của Aurora Hotel Plaza.</p>
                    </div>
                    
                    <!-- Rating Summary -->
                    <div class="flex flex-col md:flex-row items-center justify-center gap-8 py-6">
                        <div class="flex flex-col items-center gap-2">
                            <span class="text-6xl font-bold text-accent">4.8</span>
                            <div class="flex gap-1">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                <span class="material-symbols-outlined text-2xl text-yellow-500">star</span>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Dựa trên 500+ đánh giá</span>
                        </div>
                        <div class="h-20 w-px bg-gray-300 dark:bg-gray-700 hidden md:block"></div>
                        <div class="flex gap-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">98%</div>
                                <div class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Hài lòng</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">95%</div>
                                <div class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Sẽ quay lại</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600">97%</div>
                                <div class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Giới thiệu bạn bè</div>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Grid -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <?php if (!empty($customer_reviews)): ?>
                            <?php foreach ($customer_reviews as $review): ?>
                            <div class="flex flex-col gap-4 p-6 rounded-xl bg-surface-light dark:bg-surface-dark border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-accent/20 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-accent text-xl">person</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold"><?php echo htmlspecialchars($review['full_name'] ?? 'Khách hàng'); ?></h4>
                                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php echo htmlspecialchars($review['type_name'] ?? 'Phòng'); ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                    <span class="material-symbols-outlined text-lg text-yellow-500">star</span>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark italic">
                                    "<?php echo htmlspecialchars($review['comment'] ?? 'Dịch vụ tuyệt vời!'); ?>"
                                </p>
                                <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark">
                                    <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Default reviews if no data -->
                            <div class="flex flex-col gap-4 p-6 rounded-xl bg-surface-light dark:bg-surface-dark border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-accent/20 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-accent text-xl">person</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold">Nguyễn Văn A</h4>
                                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Premium Deluxe</p>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="material-symbols-outlined text-lg text-yellow-500">star</span>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark italic">
                                    "Phòng rất sạch sẽ và thoải mái. Nhân viên thân thiện, nhiệt tình. Chắc chắn sẽ quay lại!"
                                </p>
                                <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark">15/12/2024</p>
                            </div>
                            <div class="flex flex-col gap-4 p-6 rounded-xl bg-surface-light dark:bg-surface-dark border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-accent/20 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-accent text-xl">person</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold">Trần Thị B</h4>
                                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Studio Apartment</p>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="material-symbols-outlined text-lg text-yellow-500">star</span>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark italic">
                                    "Căn hộ rộng rãi, đầy đủ tiện nghi. Vị trí thuận tiện, gần trung tâm. Giá cả hợp lý!"
                                </p>
                                <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark">10/12/2024</p>
                            </div>
                            <div class="flex flex-col gap-4 p-6 rounded-xl bg-surface-light dark:bg-surface-dark border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-accent/20 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-accent text-xl">person</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold">Lê Văn C</h4>
                                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">VIP Suite</p>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="material-symbols-outlined text-lg text-yellow-500">star</span>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark italic">
                                    "Tổ chức tiệc cưới tại đây rất hài lòng. Sảnh đẹp, thức ăn ngon, dịch vụ chu đáo!"
                                </p>
                                <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark">05/12/2024</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex justify-center pt-4">
                        <a href="reviews.php" class="inline-flex items-center gap-2 px-6 py-3 border-2 border-accent text-accent rounded-lg font-bold hover:bg-accent/10 transition-colors">
                            Xem tất cả đánh giá
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Blog Section -->
            <section class="w-full justify-center bg-primary-light/30 py-16 dark:bg-surface-dark sm:py-24" id="blog">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
                    <div class="flex flex-col gap-2 text-center">
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Tin Tức &amp; Sự Kiện</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark">Cập nhật những thông tin mới nhất từ chúng tôi.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                        <?php if (!empty($latest_posts)): ?>
                            <?php foreach ($latest_posts as $post): 
                                $post_image = !empty($post['featured_image']) ? htmlspecialchars($post['featured_image']) : 'assets/img/hero banner/AURORA-HOTEL-BIEN-HOA-1.jpg';
                            ?>
                                <article class="flex flex-col overflow-hidden rounded-xl bg-surface-light shadow-md transition-transform duration-300 hover:-translate-y-1 dark:bg-background-dark dark:shadow-none dark:ring-1 dark:ring-gray-700">
                                    <a href="blog-detail.php?slug=<?php echo urlencode($post['slug']); ?>" class="block">
                                        <div class="aspect-video w-full bg-cover bg-center" style="background-image: url('<?php echo $post_image; ?>?v=<?php echo time(); ?>');"></div>
                                    </a>
                                    <div class="flex flex-1 flex-col justify-between p-6">
                                        <div>
                                            <a href="blog-detail.php?slug=<?php echo urlencode($post['slug']); ?>" class="block">
                                                <h3 class="text-lg font-bold hover:text-accent transition-colors"><?php echo htmlspecialchars($post['title']); ?></h3>
                                            </a>
                                            <p class="mt-2 text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                                <?php echo htmlspecialchars($post['excerpt']); ?>
                                            </p>
                                        </div>
                                        <div class="mt-4 flex items-center gap-2 text-xs text-text-secondary-light dark:text-text-secondary-dark">
                                            <span class="material-symbols-outlined text-sm">calendar_today</span>
                                            <span><?php echo date('d/m/Y', strtotime($post['published_at'])); ?></span>
                                            <span class="mx-1">|</span>
                                            <span class="material-symbols-outlined text-sm">person</span>
                                            <span><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center py-12">
                                <p class="text-gray-500 text-lg">Chưa có bài viết nào.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-center pt-4">
                        <a href="blog.php" class="inline-flex items-center gap-2 px-6 py-3 bg-accent text-white rounded-lg font-bold hover:opacity-90 transition-opacity">
                            Xem tất cả bài viết
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Location & Contact Section -->
            <section class="w-full justify-center py-16 sm:py-24 bg-surface-light dark:bg-surface-dark" id="location">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4">
                    <div class="flex flex-col gap-2 text-center">
                        <span class="text-accent font-semibold text-sm uppercase tracking-wider">Vị trí & Liên hệ</span>
                        <h2 class="font-display text-3xl font-bold text-text-primary-light dark:text-text-primary-dark md:text-4xl">
                            Đến với Aurora Hotel Plaza</h2>
                        <p class="text-base text-text-secondary-light dark:text-text-secondary-dark max-w-2xl mx-auto">
                            Tọa lạc tại vị trí trung tâm, dễ dàng di chuyển đến các điểm đến quan trọng trong khu vực.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Map -->
                        <div class="rounded-2xl overflow-hidden shadow-lg h-[400px]">
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3917.0824374942113!2d106.84213347417513!3d10.957145355836081!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dc27705d362d%3A0xc1fb19ec2c2b1806!2zS2jDoWNoIHPhuqFuIEF1cm9yYQ!5e0!3m2!1svi!2s!4v1764044215451!5m2!1svi!2s"
                                class="w-full h-full"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>

                        <!-- Contact Info -->
                        <div class="flex flex-col gap-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="flex gap-4 p-5 rounded-xl bg-white dark:bg-background-dark shadow-sm">
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 rounded-lg bg-accent/10 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-accent text-xl">location_on</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold mb-1">Địa chỉ</h4>
                                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                            Số 253, Phạm Văn Thuận, KP2<br>
                                            Phường Tam Hiệp, TP. Biên Hòa<br>
                                            Tỉnh Đồng Nai
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-4 p-5 rounded-xl bg-white dark:bg-background-dark shadow-sm">
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 rounded-lg bg-accent/10 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-accent text-xl">phone</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold mb-1">Điện thoại</h4>
                                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                            <a href="tel:+842513918888" class="hover:text-accent">(+84-251) 391.8888</a><br>
                                            <a href="tel:+84909123456" class="hover:text-accent">Hotline: 0909.123.456</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-4 p-5 rounded-xl bg-white dark:bg-background-dark shadow-sm">
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 rounded-lg bg-accent/10 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-accent text-xl">email</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold mb-1">Email</h4>
                                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                            <a href="mailto:info@aurorahotelplaza.com" class="hover:text-accent">info@aurorahotelplaza.com</a><br>
                                            <a href="mailto:booking@aurorahotelplaza.com" class="hover:text-accent">booking@aurorahotelplaza.com</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-4 p-5 rounded-xl bg-white dark:bg-background-dark shadow-sm">
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 rounded-lg bg-accent/10 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-accent text-xl">schedule</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold mb-1">Giờ làm việc</h4>
                                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                                            Lễ tân: 24/7<br>
                                            Nhà hàng: 6:00 - 22:00<br>
                                            Check-in: 14:00 | Check-out: 12:00
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Nearby Places -->
                            <div class="p-5 rounded-xl bg-white dark:bg-background-dark shadow-sm">
                                <h4 class="font-bold mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-accent">near_me</span>
                                    Khoảng cách đến các địa điểm
                                </h4>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-text-secondary-light dark:text-text-secondary-dark">Sân bay Tân Sơn Nhất</span>
                                        <span class="font-medium">35 km</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-text-secondary-light dark:text-text-secondary-dark">Sân bay Long Thành</span>
                                        <span class="font-medium">25 km</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-text-secondary-light dark:text-text-secondary-dark">KCN Amata</span>
                                        <span class="font-medium">5 km</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-text-secondary-light dark:text-text-secondary-dark">KCN Biên Hòa 2</span>
                                        <span class="font-medium">3 km</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-text-secondary-light dark:text-text-secondary-dark">Big C Biên Hòa</span>
                                        <span class="font-medium">2 km</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-text-secondary-light dark:text-text-secondary-dark">Bến xe Biên Hòa</span>
                                        <span class="font-medium">1.5 km</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="w-full justify-center py-16 sm:py-24 bg-gradient-to-br from-accent/90 to-accent">
                <div class="mx-auto flex max-w-4xl flex-col gap-8 px-4 text-center">
                    <div class="flex flex-col gap-4">
                        <h2 class="font-display text-3xl font-bold text-white md:text-4xl">
                            Sẵn sàng cho kỳ nghỉ của bạn?</h2>
                        <p class="text-lg text-white/90">
                            Đặt phòng ngay hôm nay và nhận ưu đãi đặc biệt cho khách hàng mới.<br>
                            <span class="text-sm">Giảm 10% cho đặt phòng trực tuyến đầu tiên!</span>
                        </p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <a href="booking/index.php" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-accent rounded-lg font-bold hover:bg-gray-100 transition-colors shadow-lg">
                            <span class="material-symbols-outlined">calendar_month</span>
                            Đặt phòng ngay
                        </a>
                        <a href="tel:+842513918888" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent border-2 border-white text-white rounded-lg font-bold hover:bg-white/10 transition-colors">
                            <span class="material-symbols-outlined">phone</span>
                            Gọi ngay: (0251) 391.8888
                        </a>
                    </div>
                    <div class="flex flex-wrap justify-center gap-6 mt-4 text-white/80 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">check_circle</span>
                            <span>Xác nhận tức thì</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">check_circle</span>
                            <span>Hủy miễn phí 24h</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">check_circle</span>
                            <span>Thanh toán linh hoạt</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">check_circle</span>
                            <span>Giá tốt nhất đảm bảo</span>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <?php include 'includes/footer.php'; ?>

    </div>

    <!-- Main JavaScript -->
    <script src="<?php echo asset('js/main.js'); ?>?v=<?php echo time(); ?>"></script>

</body>

</html>