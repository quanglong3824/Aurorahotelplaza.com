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
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title><?php _e('rooms_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<style>
/* Hero Section */
.rooms-hero {
    position: relative;
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.85), rgba(17, 24, 39, 0.7)), 
                url('assets/img/deluxe/deluxe-room-aurora-1.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    padding: 120px 20px 80px;
}

.rooms-hero::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 150px;
    background: linear-gradient(to top, var(--background-light, #f8fafc), transparent);
    pointer-events: none;
}

.dark .rooms-hero::before {
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

/* Room Card - Liquid Glass */
.room-card-glass {
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

.dark .room-card-glass {
    background: rgba(30, 41, 59, 0.85);
    border-color: rgba(255, 255, 255, 0.1);
}

.room-card-glass:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(212, 175, 55, 0.2);
}

/* Image Container */
.room-img-container {
    position: relative;
    aspect-ratio: 4/3;
    overflow: hidden;
}

.room-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.room-card-glass:hover .room-img-container img {
    transform: scale(1.08);
}

/* Price Badge */
.price-badge {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    background: rgba(17, 24, 39, 0.85);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
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

/* Room Type Badge */
.room-type-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 2rem;
    padding: 0.5rem 1rem;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

/* Room Info */
.room-info {
    padding: 1.5rem;
}

.room-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: var(--text-primary-light);
}

.dark .room-name {
    color: var(--text-primary-dark);
}

.room-desc {
    font-size: 0.9375rem;
    color: var(--text-secondary-light);
    line-height: 1.6;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.dark .room-desc {
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
.room-actions {
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
    .rooms-hero {
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
    
    .room-actions {
        flex-direction: column;
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
    
    .room-card-glass {
        border-radius: 1rem;
    }
    
    .room-info {
        padding: 1.25rem;
    }
    
    .room-name {
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
    <section class="rooms-hero">
        <div class="hero-glass-card">
            <div class="glass-badge mb-4 inline-flex">
                <span class="material-symbols-outlined text-accent text-sm">hotel</span>
                <?php _e('rooms_page.premium_rooms'); ?>
            </div>
            <h1 class="font-display text-4xl md:text-5xl font-bold text-white mb-4">
                <?php _e('rooms_page.page_title'); ?>
            </h1>
            <p class="text-white/80 text-lg max-w-xl mx-auto mb-8">
                <?php _e('rooms_page.page_subtitle'); ?>
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="booking/index.php" class="btn-glass-primary">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <?php _e('rooms_page.book_now'); ?>
                </a>
                <a href="#rooms-list" class="btn-glass-secondary">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    <?php _e('rooms_page.view_list'); ?>
                </a>
            </div>
            
            <!-- Quick Stats -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-value"><?php echo count($room_types); ?>+</div>
                    <div class="stat-label"><?php _e('rooms_page.page_title'); ?></div>
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

    <!-- Rooms Grid Section -->
    <section id="rooms-list" class="py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Section Header -->
            <div class="text-center mb-12">
                <span class="text-accent font-semibold text-sm uppercase tracking-wider">Aurora Hotel Plaza</span>
                <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4">
                    <?php _e('rooms_page.premium_rooms'); ?>
                </h2>
                <p class="text-text-secondary-light dark:text-text-secondary-dark max-w-2xl mx-auto">
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
                                    <div class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-6xl text-gray-400">hotel</span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Room Type Badge -->
                                <div class="room-type-badge">
                                    <span class="material-symbols-outlined text-accent" style="font-size: 14px;">hotel</span>
                                    <?php _e('home.room'); ?>
                                </div>
                                
                                <!-- Price Badge -->
                                <div class="price-badge">
                                    <span class="price"><?php echo number_format($room['base_price'], 0, ',', '.'); ?>đ</span>
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
                                        <?php echo $room['max_occupancy']; ?> <?php _e('rooms_page.guests'); ?>
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
                                    <a href="booking/index.php?room_type=<?php echo $room['slug']; ?>" class="btn-book">
                                        <span class="material-symbols-outlined" style="font-size: 18px;">calendar_month</span>
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
    <section class="cta-glass py-16 md:py-20">
        <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
            <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">
                <?php _e('home.ready_for_vacation'); ?>
            </h2>
            <p class="text-white/90 text-lg mb-8 max-w-2xl mx-auto">
                Đặt phòng ngay hôm nay để nhận ưu đãi đặc biệt. Giảm 10% cho đặt phòng trực tuyến!
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
