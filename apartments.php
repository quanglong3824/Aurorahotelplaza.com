<?php
require_once 'config/database.php';
require_once 'helpers/image-helper.php';
require_once 'helpers/language.php';
initLanguage();

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM room_types 
        WHERE status = 'active' AND category = 'apartment'
        ORDER BY sort_order ASC, type_name ASC
    ");
    $stmt->execute();
    $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Apartments page error: " . $e->getMessage());
    $apartments = [];
}

// Phân loại căn hộ
$new_apartments = array_filter($apartments, fn($apt) => $apt['sort_order'] <= 10);
$old_apartments = array_filter($apartments, fn($apt) => $apt['sort_order'] > 10);
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('apartments_page.title'); ?></title>
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet" />
    <script src="assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/liquid-glass.css">
    <link rel="stylesheet" href="assets/css/pages-glass.css">
    <link rel="stylesheet" href="assets/css/apartments.css">
    <style>
        body.glass-page::before {
            background-image: url('<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-6.jpg'); ?>');
        }
    </style>
</head>

<body class="glass-page font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include 'includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Hero Section -->
            <section class="page-hero-glass">
                <div class="hero-glass-card">
                    <div class="glass-badge-pill mb-4 justify-center mx-auto">
                        <span class="material-symbols-outlined text-sm">apartment</span>
                        <?php _e('apartments_page.premium_apartments'); ?>
                    </div>
                    <h1 class="hero-title-glass">
                        <?php _e('apartments_page.page_title'); ?>
                    </h1>
                    <p class="hero-subtitle-glass">
                        <?php _e('apartments_page.page_subtitle'); ?>
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center">
                        <a href="booking/index.php" class="btn-glass-gold">
                            <span class="material-symbols-outlined">contact_support</span>
                            <?php _e('inquiry.contact_btn'); ?>
                        </a>
                        <a href="#apartments-list" class="btn-glass-outline">
                            <span class="material-symbols-outlined">arrow_downward</span>
                            <?php _e('apartments_page.view_list'); ?>
                        </a>
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-3 gap-8 mt-12 pt-8 border-t border-white/10">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-accent mb-1"><?php echo count($apartments); ?>+</div>
                            <div class="text-white/70 text-sm"><?php _e('home.apartment'); ?></div>
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

            <!-- Apartments Grid Section -->
            <section id="apartments-list" class="py-16 md:py-24">
                <div class="max-w-7xl mx-auto px-4">
                    <?php if (empty($apartments)): ?>
                        <div class="text-center py-12">
                            <div class="glass-card-solid p-8 max-w-md mx-auto">
                                <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">apartment</span>
                                <p class="text-gray-500 text-lg"><?php _e('apartments_page.no_apartments'); ?></p>
                            </div>
                        </div>
                    <?php else: ?>

                        <!-- Căn hộ mới -->
                        <?php if (!empty($new_apartments)): ?>
                            <div class="mb-20">
                                <div class="section-header">
                                    <h2 class="section-title text-accent"><?php _e('apartments_page.new_apartments'); ?></h2>
                                    <span class="section-badge bg-gradient-to-r from-accent to-accent/80 text-white">
                                        <?php _e('apartments_page.apartments_count', ['count' => count($new_apartments)]); ?>
                                    </span>
                                </div>

                                <div class="apt-grid">
                                    <?php foreach ($new_apartments as $apt):
                                        $amenities = !empty($apt['amenities']) ? array_slice(explode(',', $apt['amenities']), 0, 4) : [];
                                        $imageUrl = imgUrl($apt['thumbnail'], 'assets/img/studio-apartment/can-ho-studio-aurora-hotel-1.jpg');
                                        ?>
                                        <div class="apt-card-glass">
                                            <div class="apt-img-container">
                                                <?php if ($apt['thumbnail']): ?>
                                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>"
                                                        alt="<?php echo htmlspecialchars($apt['type_name']); ?>">
                                                <?php else: ?>
                                                    <div
                                                        class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                        <span class="material-symbols-outlined text-6xl text-gray-400">apartment</span>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="type-badge">
                                                    <span class="material-symbols-outlined"
                                                        style="font-size: 14px;">apartment</span>
                                                    <?php _e('apartments_page.apartment'); ?>
                                                </div>

                                                <div class="price-badge">
                                                    <span
                                                        class="price"><?php echo number_format($apt['base_price'], 0, ',', '.'); ?>đ</span>
                                                    <span class="unit"><?php _e('common.per_night'); ?></span>
                                                </div>
                                            </div>

                                            <div class="apt-info">
                                                <h3 class="apt-name"><?php echo htmlspecialchars($apt['type_name']); ?></h3>

                                                <?php if ($apt['short_description']): ?>
                                                    <p class="apt-desc"><?php echo htmlspecialchars($apt['short_description']); ?></p>
                                                <?php endif; ?>

                                                <div class="specs-grid">
                                                    <?php if ($apt['size_sqm']): ?>
                                                        <div class="spec-item">
                                                            <span class="material-symbols-outlined">square_foot</span>
                                                            <?php echo number_format($apt['size_sqm'], 0); ?>m²
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($apt['bed_type']): ?>
                                                        <div class="spec-item">
                                                            <span class="material-symbols-outlined">bed</span>
                                                            <?php echo htmlspecialchars($apt['bed_type']); ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="spec-item">
                                                        <span class="material-symbols-outlined">person</span>
                                                        <?php echo $apt['max_occupancy']; ?>             <?php _e('apartments_page.guests'); ?>
                                                    </div>
                                                </div>

                                                <?php if (!empty($amenities)): ?>
                                                    <div class="amenities-compact">
                                                        <?php foreach ($amenities as $amenity): ?>
                                                            <span class="amenity-tag"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="apt-actions">
                                                    <a href="booking/index.php?room_type=<?php echo $apt['slug']; ?>"
                                                        class="btn-book">
                                                        <span class="material-symbols-outlined"
                                                            style="font-size: 18px;">contact_support</span>
                                                        <?php _e('inquiry.contact_btn'); ?>
                                                    </a>
                                                    <a href="apartment-details/<?php echo $apt['slug']; ?>.php" class="btn-detail">
                                                        <?php _e('apartments_page.details'); ?>
                                                        <span class="material-symbols-outlined">arrow_forward</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Căn hộ cũ/Khác -->
                        <?php if (!empty($old_apartments)): ?>
                            <section class="mb-16 pt-12 border-t border-white/10">
                                <div class="section-header mb-8">
                                    <h2 class="section-title text-white"><?php _e('apartments_page.old_apartments'); ?></h2>
                                    <span class="section-badge bg-white/10 text-white">
                                        <?php _e('apartments_page.apartments_count', ['count' => count($old_apartments)]); ?>
                                    </span>
                                </div>

                                <div class="apt-grid">
                                    <?php foreach ($old_apartments as $apt):
                                        $amenities = !empty($apt['amenities']) ? array_slice(explode(',', $apt['amenities']), 0, 4) : [];
                                        $imageUrl = imgUrl($apt['thumbnail'], 'assets/img/studio-apartment/can-ho-studio-aurora-hotel-1.jpg');
                                        ?>
                                        <div class="apt-card-glass">
                                            <div class="apt-img-container">
                                                <?php if ($apt['thumbnail']): ?>
                                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>"
                                                        alt="<?php echo htmlspecialchars($apt['type_name']); ?>">
                                                <?php else: ?>
                                                    <div
                                                        class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                        <span class="material-symbols-outlined text-6xl text-gray-400">apartment</span>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="type-badge old">
                                                    <span class="material-symbols-outlined"
                                                        style="font-size: 14px;">apartment</span>
                                                    <?php _e('apartments_page.old_apartment'); ?>
                                                </div>

                                                <div class="price-badge">
                                                    <span
                                                        class="price"><?php echo number_format($apt['base_price'], 0, ',', '.'); ?>đ</span>
                                                    <span class="unit"><?php _e('common.per_night'); ?></span>
                                                </div>
                                            </div>

                                            <div class="apt-info">
                                                <h3 class="apt-name"><?php echo htmlspecialchars($apt['type_name']); ?></h3>

                                                <?php if ($apt['short_description']): ?>
                                                    <p class="apt-desc"><?php echo htmlspecialchars($apt['short_description']); ?></p>
                                                <?php endif; ?>

                                                <div class="specs-grid">
                                                    <?php if ($apt['size_sqm']): ?>
                                                        <div class="spec-item">
                                                            <span class="material-symbols-outlined">square_foot</span>
                                                            <?php echo number_format($apt['size_sqm'], 0); ?>m²
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($apt['bed_type']): ?>
                                                        <div class="spec-item">
                                                            <span class="material-symbols-outlined">bed</span>
                                                            <?php echo htmlspecialchars($apt['bed_type']); ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="spec-item">
                                                        <span class="material-symbols-outlined">person</span>
                                                        <?php echo $apt['max_occupancy']; ?>             <?php _e('apartments_page.guests'); ?>
                                                    </div>
                                                </div>

                                                <?php if (!empty($amenities)): ?>
                                                    <div class="amenities-compact">
                                                        <?php foreach ($amenities as $amenity): ?>
                                                            <span class="amenity-tag"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="apt-actions">
                                                    <a href="booking/index.php?room_type=<?php echo $apt['slug']; ?>"
                                                        class="btn-book">
                                                        <span class="material-symbols-outlined"
                                                            style="font-size: 18px;">contact_support</span>
                                                        <?php _e('inquiry.contact_btn'); ?>
                                                    </a>
                                                    <a href="apartment-details/<?php echo $apt['slug']; ?>.php" class="btn-detail">
                                                        <?php _e('apartments_page.details'); ?>
                                                        <span class="material-symbols-outlined">arrow_forward</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                    <?php endif; ?>
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
                        Đặt căn hộ ngay hôm nay để nhận ưu đãi đặc biệt. Giảm 10% cho đặt phòng trực tuyến!
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center">
                        <a href="booking/index.php" class="btn-glass-gold">
                            <span class="material-symbols-outlined">contact_support</span>
                            <?php _e('inquiry.contact_btn'); ?>
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

    <script src="assets/js/apartments.js"></script>
</body>

</html>