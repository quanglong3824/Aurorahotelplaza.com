<?php
require_once 'config/database.php';
require_once 'helpers/image-helper.php';
require_once 'helpers/language.php';
initLanguage();

try {
    $db = getDB();

    // Chỉ lấy phòng (không lấy căn hộ)
    $stmt = $db->prepare("
        SELECT * FROM room_types 
        WHERE status = 'active' AND category = 'room'
        ORDER BY sort_order ASC, type_name ASC
    ");

    $stmt->execute();
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Rooms page error: " . $e->getMessage());
    $room_types = [];
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('rooms_page.title'); ?></title>
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet" />
    <script src="assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/liquid-glass.css">
    <link rel="stylesheet" href="assets/css/pages-glass.css">
    <link rel="stylesheet" href="assets/css/rooms.css">
</head>

<body class="glass-page font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include 'includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
                <!-- Hero Section -->
                <section class="page-hero-glass">
                    <div class="hero-glass-card">
                        <div class="glass-badge-pill mb-4 justify-center mx-auto">
                            <span class="material-symbols-outlined text-sm">hotel</span>
                            <?php _e('rooms_page.premium_rooms'); ?>
                        </div>
                        <h1 class="hero-title-glass">
                            <?php _e('rooms_page.page_title'); ?>
                        </h1>
                        <p class="hero-subtitle-glass">
                            <?php _e('rooms_page.page_subtitle'); ?>
                        </p>
                        <div class="flex flex-wrap gap-4 justify-center">
                            <a href="booking/index.php" class="btn-glass-gold">
                                <span class="material-symbols-outlined">calendar_month</span>
                                <?php _e('rooms_page.book_now'); ?>
                            </a>
                            <a href="#rooms-list" class="btn-glass-outline">
                                <span class="material-symbols-outlined">arrow_downward</span>
                                <?php _e('rooms_page.view_list'); ?>
                            </a>
                        </div>

                        <!-- Quick Stats -->
                        <div class="grid grid-cols-3 gap-8 mt-12 pt-8 border-t border-white/10">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-accent mb-1"><?php echo count($room_types); ?>+
                                </div>
                                <div class="text-white/70 text-sm"><?php _e('rooms_page.page_title'); ?></div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-accent mb-1">5★</div>
                                <div class="text-white/70 text-sm">Tiêu chuẩn</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-accent mb-1">24/7</div>
                                <div class="text-white/70 text-sm">Phục vụ</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Rooms Grid Section -->
                <section id="rooms-list" class="py-16 md:py-24">
                    <div class="max-w-7xl mx-auto px-4">
                        <!-- Section Header -->
                        <div class="text-center mb-12">
                            <span class="text-accent font-semibold text-sm uppercase tracking-wider">Aurora Hotel
                                Plaza</span>
                            <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4 text-white">
                                <?php _e('rooms_page.premium_rooms'); ?>
                            </h2>
                            <p class="text-white/70 max-w-2xl mx-auto">
                                <?php _e('rooms_page.page_subtitle'); ?>
                            </p>
                        </div>

                        <!-- Rooms Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <?php if (empty($room_types)): ?>
                                <div class="col-span-full text-center py-12">
                                    <div class="glass-card-solid p-8 max-w-md mx-auto">
                                        <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">hotel</span>
                                        <p class="text-gray-500 text-lg"><?php _e('rooms_page.no_rooms'); ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($room_types as $room):
                                    $amenities = !empty($room['amenities']) ? explode(',', $room['amenities']) : [];
                                    $amenities = array_slice($amenities, 0, 4);
                                    $thumbnail = normalizeImagePath($room['thumbnail']);
                                    $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                                    ?>
                                    <div class="room-card-glass">
                                        <!-- Image -->
                                        <div class="room-img-container">
                                            <?php if ($room['thumbnail']): ?>
                                                <img src="<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>"
                                                    alt="<?php echo htmlspecialchars($room['type_name']); ?>">
                                            <?php else: ?>
                                                <div
                                                    class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-6xl text-gray-400">hotel</span>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Room Type Badge -->
                                            <div class="room-type-badge">
                                                <span class="material-symbols-outlined text-accent"
                                                    style="font-size: 14px;">hotel</span>
                                                <?php _e('home.room'); ?>
                                            </div>

                                            <!-- Price Badge -->
                                            <div class="price-badge">
                                                <span
                                                    class="price"><?php echo number_format($room['base_price'], 0, ',', '.'); ?>đ</span>
                                                <span class="unit"><?php _e('common.per_night'); ?></span>
                                            </div>
                                        </div>

                                        <!-- Room Info -->
                                        <div class="room-info">
                                            <h3 class="room-name"><?php echo htmlspecialchars($room['type_name']); ?></h3>

                                            <?php if ($room['short_description']): ?>
                                                <p class="room-desc"><?php echo htmlspecialchars($room['short_description']); ?></p>
                                            <?php endif; ?>

                                            <!-- Specs -->
                                            <div class="specs-grid">
                                                <?php if ($room['size_sqm']): ?>
                                                    <div class="spec-item">
                                                        <span class="material-symbols-outlined">square_foot</span>
                                                        <?php echo number_format($room['size_sqm'], 0); ?>m²
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($room['bed_type']): ?>
                                                    <div class="spec-item">
                                                        <span class="material-symbols-outlined">bed</span>
                                                        <?php echo htmlspecialchars($room['bed_type']); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="spec-item">
                                                    <span class="material-symbols-outlined">person</span>
                                                    <?php echo $room['max_occupancy']; ?>         <?php _e('rooms_page.guests'); ?>
                                                </div>
                                            </div>

                                            <!-- Amenities -->
                                            <?php if (!empty($amenities)): ?>
                                                <div class="amenities-compact">
                                                    <?php foreach ($amenities as $amenity): ?>
                                                        <span class="amenity-tag"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Actions -->
                                            <div class="room-actions">
                                                <a href="booking/index.php?room_type=<?php echo $room['slug']; ?>"
                                                    class="btn-book">
                                                    <span class="material-symbols-outlined"
                                                        style="font-size: 18px;">calendar_month</span>
                                                    <?php _e('rooms_page.book'); ?>
                                                </a>
                                                <a href="room-details/<?php echo $room['slug']; ?>.php" class="btn-detail">
                                                    <?php _e('rooms_page.view_details'); ?>
                                                    <span class="material-symbols-outlined">arrow_forward</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- CTA Section -->
                <section class="py-20 relative overflow-hidden">
                    <!-- Glass background with gradient -->
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-slate-900/50"></div>

                    <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
                        <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">
                            <?php _e('home.ready_for_vacation'); ?>
                        </h2>
                        <p class="text-white/80 text-lg mb-8 max-w-2xl mx-auto">
                            Đặt phòng ngay hôm nay để nhận ưu đãi đặc biệt. Giảm 10% cho đặt phòng trực tuyến!
                        </p>
                        <div class="flex flex-wrap gap-4 justify-center">
                            <a href="booking/index.php" class="btn-glass-gold">
                                <span class="material-symbols-outlined">calendar_month</span>
                                <?php _e('home.book_now_cta'); ?>
                            </a>
                            <a href="tel:+842513918888" class="btn-glass-outline">
                                <span class="material-symbols-outlined">phone</span>
                                <?php _e('home.call_now'); ?>
                            </a>
                        </div>
                    </div>
                </section>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

</body>

</html>