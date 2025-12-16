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
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title><?php _e('apartments_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<link rel="stylesheet" href="assets/css/apartments.css">
</head>

<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Hero Section -->
    <section class="apt-hero">
        <div class="hero-glass-card">
            <div class="glass-badge mb-4 inline-flex">
                <span class="material-symbols-outlined text-accent text-sm">apartment</span>
                <?php _e('apartments_page.premium_apartments'); ?>
            </div>
            <h1 class="font-display text-4xl md:text-5xl font-bold text-white mb-4">
                <?php _e('apartments_page.page_title'); ?>
            </h1>
            <p class="text-white/80 text-lg max-w-xl mx-auto mb-8">
                <?php _e('apartments_page.page_subtitle'); ?>
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="booking/index.php" class="btn-glass-primary">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <?php _e('apartments_page.book_now'); ?>
                </a>
                <a href="#apartments-list" class="btn-glass-secondary">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    <?php _e('apartments_page.view_list'); ?>
                </a>
            </div>
            
            <!-- Quick Stats -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-value"><?php echo count($apartments); ?>+</div>
                    <div class="stat-label"><?php _e('home.apartment'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">5★</div>
                    <div class="stat-label">Tiêu chuẩn</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Phục vụ</div>
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
                <div class="mb-16">
                    <div class="section-header">
                        <h2 class="section-title text-accent"><?php _e('apartments_page.new_apartments'); ?></h2>
                        <span class="section-badge bg-gradient-to-r from-accent to-accent/80 text-white">
                            <?php _e('apartments_page.apartments_count', ['count' => count($new_apartments)]); ?>
                        </span>
                    </div>
                    
                    <div class="apt-grid">
                        <?php foreach ($new_apartments as $apt): 
                            $amenities = !empty($apt['amenities']) ? array_slice(explode(',', $apt['amenities']), 0, 4) : [];
                            $thumbnail = normalizeImagePath($apt['thumbnail']);
                            $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                        ?>
                            <div class="apt-card-glass">
                                <div class="apt-img-container">
                                    <?php if ($apt['thumbnail']): ?>
                                        <img src="<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>" 
                                             alt="<?php echo htmlspecialchars($apt['type_name']); ?>">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-6xl text-gray-400">apartment</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="type-badge">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">apartment</span>
                                        <?php _e('apartments_page.apartment'); ?>
                                    </div>
                                    
                                    <div class="price-badge">
                                        <span class="price"><?php echo number_format($apt['base_price'], 0, ',', '.'); ?>đ</span>
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
                                            <?php echo $apt['max_occupancy']; ?> <?php _e('apartments_page.guests'); ?>
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
                                        <a href="booking/index.php?room_type=<?php echo $apt['slug']; ?>" class="btn-book">
                                            <span class="material-symbols-outlined" style="font-size: 18px;">calendar_month</span>
                                            <?php _e('apartments_page.book'); ?>
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

                <!-- Căn hộ cũ -->
                <?php if (!empty($old_apartments)): ?>
                <div>
                    <div class="section-header">
                        <h2 class="section-title text-text-secondary-light dark:text-text-secondary-dark"><?php _e('apartments_page.old_apartments'); ?></h2>
                        <span class="section-badge bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            <?php _e('apartments_page.apartments_count', ['count' => count($old_apartments)]); ?>
                        </span>
                    </div>
                    
                    <div class="apt-grid">
                        <?php foreach ($old_apartments as $apt): 
                            $amenities = !empty($apt['amenities']) ? array_slice(explode(',', $apt['amenities']), 0, 4) : [];
                            $thumbnail = normalizeImagePath($apt['thumbnail']);
                            $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                        ?>
                            <div class="apt-card-glass">
                                <div class="apt-img-container">
                                    <?php if ($apt['thumbnail']): ?>
                                        <img src="<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>" 
                                             alt="<?php echo htmlspecialchars($apt['type_name']); ?>">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-6xl text-gray-400">apartment</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="type-badge old">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">apartment</span>
                                        <?php _e('apartments_page.old_apartment'); ?>
                                    </div>
                                    
                                    <div class="price-badge">
                                        <span class="price"><?php echo number_format($apt['base_price'], 0, ',', '.'); ?>đ</span>
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
                                            <?php echo $apt['max_occupancy']; ?> <?php _e('apartments_page.guests'); ?>
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
                                        <a href="booking/index.php?room_type=<?php echo $apt['slug']; ?>" class="btn-book">
                                            <span class="material-symbols-outlined" style="font-size: 18px;">calendar_month</span>
                                            <?php _e('apartments_page.book'); ?>
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
                
            <?php endif; ?>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-glass py-16 md:py-20">
        <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
            <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">
                <?php _e('home.ready_for_vacation'); ?>
            </h2>
            <p class="text-white/90 text-lg mb-8 max-w-2xl mx-auto">
                Đặt căn hộ ngay hôm nay để nhận ưu đãi đặc biệt. Giảm 10% cho đặt phòng trực tuyến!
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="booking/index.php" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-accent rounded-xl font-bold hover:bg-gray-100 transition-all shadow-lg">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <?php _e('home.book_now_cta'); ?>
                </a>
                <a href="tel:+842513918888" class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm border-2 border-white/30 text-white rounded-xl font-bold hover:bg-white/20 transition-all">
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