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
<style>
/* Hero Section */
.apt-hero {
    position: relative;
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.85), rgba(17, 24, 39, 0.7)), 
                url('assets/img/apartment/can-ho-aurora-1.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    padding: 120px 20px 80px;
}

.apt-hero::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 150px;
    background: linear-gradient(to top, var(--background-light, #f8fafc), transparent);
    pointer-events: none;
}

.dark .apt-hero::before {
    background: linear-gradient(to top, var(--background-dark, #0f172a), transparent);
}

/* Glass Hero Card */
.hero-glass-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 2rem;
    padding: 3rem;
    max-width: 800px;
    text-align: center;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}

/* Apartment Card - Liquid Glass */
.apt-card-glass {
    position: relative;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 1.5rem;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.dark .apt-card-glass {
    background: rgba(30, 41, 59, 0.85);
    border-color: rgba(255, 255, 255, 0.1);
}

.apt-card-glass:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(212, 175, 55, 0.2);
}

/* Image Container */
.apt-img-container {
    position: relative;
    aspect-ratio: 4/3;
    overflow: hidden;
}

.apt-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.apt-card-glass:hover .apt-img-container img {
    transform: scale(1.08);
}

/* Price Badge */
.price-badge {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    background: rgba(17, 24, 39, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
    padding: 0.75rem 1.25rem;
    color: white;
}

.price-badge .price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #d4af37;
}

.price-badge .unit {
    font-size: 0.875rem;
    opacity: 0.8;
}

/* Type Badge */
.type-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    border-radius: 2rem;
    padding: 0.5rem 1rem;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
}

.type-badge.old {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    box-shadow: 0 4px 15px rgba(107, 114, 128, 0.4);
}

/* Apartment Info */
.apt-info {
    padding: 1.5rem;
}

.apt-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: var(--text-primary-light);
}

.dark .apt-name {
    color: var(--text-primary-dark);
}

.apt-desc {
    font-size: 0.9375rem;
    color: var(--text-secondary-light);
    line-height: 1.6;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.dark .apt-desc {
    color: var(--text-secondary-dark);
}

/* Specs Grid */
.specs-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.dark .specs-grid {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.spec-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    background: rgba(212, 175, 55, 0.08);
    border-radius: 2rem;
    font-size: 0.8125rem;
    color: var(--text-secondary-light);
}

.dark .spec-item {
    background: rgba(212, 175, 55, 0.15);
    color: var(--text-secondary-dark);
}

.spec-item .material-symbols-outlined {
    font-size: 1rem;
    color: #d4af37;
}

/* Amenities */
.amenities-compact {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}

.amenity-tag {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
    background: rgba(0, 0, 0, 0.04);
    border-radius: 1rem;
    color: var(--text-secondary-light);
}

.dark .amenity-tag {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-secondary-dark);
}

/* Action Buttons */
.apt-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-book {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.25rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: white;
    border-radius: 0.75rem;
    font-weight: 600;
    font-size: 0.9375rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}

.btn-book:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
}

.btn-detail {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.875rem 1.25rem;
    background: rgba(26, 35, 126, 0.08);
    color: #1A237E;
    border: 1px solid rgba(26, 35, 126, 0.15);
    border-radius: 0.75rem;
    font-weight: 600;
    font-size: 0.9375rem;
    transition: all 0.3s ease;
}

.dark .btn-detail {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-primary-dark);
    border-color: rgba(255, 255, 255, 0.15);
}

.btn-detail:hover {
    background: rgba(26, 35, 126, 0.15);
    transform: translateY(-2px);
}

.dark .btn-detail:hover {
    background: rgba(255, 255, 255, 0.15);
}

.btn-detail .material-symbols-outlined {
    font-size: 1.125rem;
    transition: transform 0.3s ease;
}

.btn-detail:hover .material-symbols-outlined {
    transform: translateX(3px);
}

/* Stats Bar */
.stats-bar {
    display: flex;
    justify-content: center;
    gap: 3rem;
    padding: 1.5rem 0;
    margin-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-item {
    text-align: center;
    color: white;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #d4af37;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.8;
}

/* Section Header */
.section-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.75rem;
    font-weight: 700;
}

.section-badge {
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 600;
}

/* CTA Section */
.cta-glass {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.9), rgba(184, 136, 42, 0.85));
    position: relative;
    overflow: hidden;
}

.cta-glass::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 60%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

/* Responsive */
@media (max-width: 768px) {
    .apt-hero {
        min-height: 60vh;
        padding: 100px 16px 60px;
    }
    
    .hero-glass-card {
        padding: 2rem 1.5rem;
    }
    
    .hero-glass-card h1 {
        font-size: 2rem;
    }
    
    .stats-bar {
        gap: 1.5rem;
        flex-wrap: wrap;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .apt-actions {
        flex-direction: column;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}

@media (max-width: 480px) {
    .hero-glass-card {
        padding: 1.5rem 1rem;
        border-radius: 1.25rem;
    }
    
    .hero-glass-card h1 {
        font-size: 1.75rem;
    }
    
    .apt-card-glass {
        border-radius: 1rem;
    }
    
    .apt-info {
        padding: 1.25rem;
    }
    
    .apt-name {
        font-size: 1.25rem;
    }
}
</style>
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
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