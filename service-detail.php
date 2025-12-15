<?php
require_once 'config/database.php';
require_once 'helpers/language.php';
initLanguage();

$slug = $_GET['slug'] ?? '';
if (empty($slug)) { header('Location: services.php'); exit; }

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM services WHERE slug = :slug AND is_available = 1");
    $stmt->execute([':slug' => $slug]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) { header('Location: services.php'); exit; }
    
    $stmt = $db->prepare("SELECT * FROM service_packages WHERE service_id = :service_id AND is_available = 1 ORDER BY sort_order ASC");
    $stmt->execute([':service_id' => $service['service_id']]);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Service detail error: " . $e->getMessage());
    header('Location: services.php'); exit;
}

$page_title = $service['service_name'] . ' - Aurora Hotel Plaza';
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo htmlspecialchars($page_title); ?></title>
<meta name="description" content="<?php echo htmlspecialchars(substr($service['description'], 0, 160)); ?>">
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<style>
/* Hero Section */
.service-hero {
    position: relative;
    min-height: 75vh;
    display: flex;
    align-items: center;
    padding: 120px 20px 80px;
}

.service-hero-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
}

.service-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.9), rgba(17, 24, 39, 0.7));
}

.service-hero::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 150px;
    background: linear-gradient(to top, var(--background-light, #f8fafc), transparent);
    pointer-events: none;
    z-index: 2;
}

.dark .service-hero::after {
    background: linear-gradient(to top, var(--background-dark, #0f172a), transparent);
}

/* Glass Hero Card */
.hero-info-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 2rem;
    padding: 2.5rem;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}

/* Stat Card Glass */
.stat-card-glass {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 1.25rem;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card-glass:hover {
    background: rgba(255, 255, 255, 0.18);
    transform: translateY(-6px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
}

.stat-icon-glass {
    width: 3rem;
    height: 3rem;
    margin: 0 auto 0.75rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.stat-value-glass {
    font-family: 'Playfair Display', serif;
    font-size: 1.75rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.25rem;
}

.stat-label-glass {
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.8);
}

/* Package Card Glass */
.package-card-glass {
    position: relative;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 1.5rem;
    padding: 2rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.dark .package-card-glass {
    background: rgba(30, 41, 59, 0.85);
    border-color: rgba(255, 255, 255, 0.1);
}

.package-card-glass:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
}

.package-card-glass.featured {
    border: 2px solid #d4af37;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.08), rgba(212, 175, 55, 0.03));
}

.package-badge-glass {
    position: absolute;
    top: -0.75rem;
    right: 1.5rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
}

.package-header-glass {
    text-align: center;
    padding-bottom: 1.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.dark .package-header-glass {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.package-name-glass {
    font-family: 'Playfair Display', serif;
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--text-primary-light);
    margin-bottom: 1rem;
}

.dark .package-name-glass {
    color: var(--text-primary-dark);
}

.package-price-glass {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 0.25rem;
}

.price-currency-glass {
    font-size: 1.125rem;
    font-weight: 700;
    color: #d4af37;
    margin-top: 0.25rem;
}

.price-amount-glass {
    font-family: 'Playfair Display', serif;
    font-size: 2.25rem;
    font-weight: 700;
    color: #d4af37;
    line-height: 1;
}

.price-unit-glass {
    font-size: 0.875rem;
    color: var(--text-secondary-light);
    margin-top: 0.5rem;
}

.dark .price-unit-glass {
    color: var(--text-secondary-dark);
}

/* Features List */
.features-list-glass {
    flex: 1;
    margin-bottom: 1.5rem;
}

.feature-item-glass {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px dashed rgba(0, 0, 0, 0.06);
}

.dark .feature-item-glass {
    border-bottom-color: rgba(255, 255, 255, 0.08);
}

.feature-item-glass:last-child {
    border-bottom: none;
}

.feature-icon-glass {
    flex-shrink: 0;
    color: #10b981;
}

.feature-text-glass {
    font-size: 0.9375rem;
    color: var(--text-secondary-light);
    line-height: 1.5;
}

.dark .feature-text-glass {
    color: var(--text-secondary-dark);
}

/* Amenity Card Glass */
.amenity-card-glass {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 1.25rem;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.dark .amenity-card-glass {
    background: rgba(30, 41, 59, 0.85);
    border-color: rgba(255, 255, 255, 0.1);
}

.amenity-card-glass:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
}

.amenity-icon-glass {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(212, 175, 55, 0.05));
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d4af37;
    transition: all 0.3s ease;
}

.amenity-card-glass:hover .amenity-icon-glass {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: white;
    transform: scale(1.1);
}

.amenity-title-glass {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary-light);
    margin-bottom: 0.375rem;
}

.dark .amenity-title-glass {
    color: var(--text-primary-dark);
}

.amenity-desc-glass {
    font-size: 0.8125rem;
    color: var(--text-secondary-light);
    line-height: 1.5;
}

.dark .amenity-desc-glass {
    color: var(--text-secondary-dark);
}

/* CTA Section */
.cta-glass-section {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.9), rgba(184, 136, 42, 0.85));
    position: relative;
    overflow: hidden;
}

.cta-glass-section::before {
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
@media (max-width: 1024px) {
    .service-hero {
        min-height: 65vh;
    }
}

@media (max-width: 768px) {
    .service-hero {
        min-height: auto;
        padding: 100px 16px 60px;
    }
    
    .hero-info-card {
        padding: 1.5rem;
    }
    
    .stat-value-glass {
        font-size: 1.5rem;
    }
    
    .price-amount-glass {
        font-size: 1.875rem;
    }
    
    .package-card-glass {
        padding: 1.5rem;
    }
}

@media (max-width: 480px) {
    .service-hero {
        padding: 90px 12px 50px;
    }
    
    .hero-info-card {
        padding: 1.25rem;
        border-radius: 1.25rem;
    }
    
    .stat-card-glass {
        padding: 1rem;
    }
    
    .stat-value-glass {
        font-size: 1.25rem;
    }
    
    .package-card-glass {
        padding: 1.25rem;
        border-radius: 1rem;
    }
    
    .price-amount-glass {
        font-size: 1.5rem;
    }
}
</style>
</head>

<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Hero Section -->
    <section class="service-hero">
        <?php if ($service['thumbnail']): ?>
            <div class="service-hero-bg" style="background-image: url('<?php echo htmlspecialchars($service['thumbnail']); ?>');"></div>
        <?php else: ?>
            <div class="service-hero-bg" style="background: linear-gradient(135deg, #111827 0%, #1f2937 100%);"></div>
        <?php endif; ?>
        <div class="service-hero-overlay"></div>
        
        <div class="relative z-10 w-full max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Info -->
                <div class="hero-info-card">
                    <div class="glass-badge mb-4 inline-flex">
                        <span class="material-symbols-outlined text-accent text-sm"><?php echo htmlspecialchars($service['icon']); ?></span>
                        <?php _e('service_detail.premium_service'); ?>
                    </div>
                    
                    <h1 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">
                        <?php echo htmlspecialchars($service['service_name']); ?>
                    </h1>
                    
                    <p class="text-white/85 text-base leading-relaxed mb-6">
                        <?php echo htmlspecialchars($service['description']); ?>
                    </p>
                    
                    <div class="flex flex-wrap gap-3">
                        <a href="#packages" class="btn-glass-primary">
                            <?php _e('service_detail.view_packages'); ?>
                            <span class="material-symbols-outlined">arrow_downward</span>
                        </a>
                        <a href="contact.php" class="btn-glass-secondary">
                            <span class="material-symbols-outlined">phone</span>
                            <?php _e('service_detail.contact_consult'); ?>
                        </a>
                    </div>
                </div>
                
                <!-- Right: Stats -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="stat-card-glass">
                        <div class="stat-icon-glass">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                        <div class="stat-value-glass"><?php echo count($packages); ?>+</div>
                        <div class="stat-label-glass"><?php _e('service_detail.packages'); ?></div>
                    </div>
                    
                    <div class="stat-card-glass">
                        <div class="stat-icon-glass">
                            <span class="material-symbols-outlined">groups</span>
                        </div>
                        <div class="stat-value-glass">300+</div>
                        <div class="stat-label-glass"><?php _e('service_detail.customers'); ?></div>
                    </div>
                    
                    <div class="stat-card-glass">
                        <div class="stat-icon-glass">
                            <span class="material-symbols-outlined">star</span>
                        </div>
                        <div class="stat-value-glass">5.0</div>
                        <div class="stat-label-glass"><?php _e('service_detail.rating'); ?></div>
                    </div>
                    
                    <div class="stat-card-glass">
                        <div class="stat-icon-glass">
                            <span class="material-symbols-outlined">support_agent</span>
                        </div>
                        <div class="stat-value-glass">24/7</div>
                        <div class="stat-label-glass"><?php _e('service_detail.support'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <?php if (!empty($packages)): ?>
    <section id="packages" class="py-16 md:py-24 bg-surface-light dark:bg-surface-dark">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <span class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('service_detail.pricing'); ?></span>
                <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4">
                    <?php _e('service_detail.our_packages'); ?>
                </h2>
                <p class="text-text-secondary-light dark:text-text-secondary-dark max-w-2xl mx-auto">
                    <?php _e('service_detail.packages_desc'); ?>
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($packages as $pkg): 
                    $features = !empty($pkg['features']) ? explode(',', $pkg['features']) : [];
                ?>
                    <div class="package-card-glass <?php echo $pkg['is_featured'] ? 'featured' : ''; ?>">
                        <?php if ($pkg['is_featured']): ?>
                            <div class="package-badge-glass">
                                <span class="material-symbols-outlined" style="font-size: 14px;">star</span>
                                <?php _e('service_detail.most_popular'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="package-header-glass">
                            <h3 class="package-name-glass"><?php echo htmlspecialchars($pkg['package_name']); ?></h3>
                            <div class="package-price-glass">
                                <span class="price-currency-glass">đ</span>
                                <span class="price-amount-glass"><?php echo number_format($pkg['price'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="price-unit-glass"><?php echo htmlspecialchars($pkg['price_unit']); ?></div>
                        </div>
                        
                        <?php if (!empty($features)): ?>
                            <div class="features-list-glass">
                                <?php foreach ($features as $feature): ?>
                                    <div class="feature-item-glass">
                                        <span class="feature-icon-glass">
                                            <span class="material-symbols-outlined">check_circle</span>
                                        </span>
                                        <span class="feature-text-glass"><?php echo htmlspecialchars(trim($feature)); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="booking/index.php?service=<?php echo $service['slug']; ?>&package=<?php echo $pkg['slug']; ?>" 
                           class="btn-glass-primary w-full justify-center mt-auto <?php echo $pkg['is_featured'] ? '' : 'bg-gradient-to-r from-primary to-primary/80'; ?>">
                            <?php _e('service_detail.book_now'); ?>
                            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Amenities Section -->
    <?php 
    $all_features = [];
    foreach ($packages as $pkg) {
        if (!empty($pkg['features'])) {
            foreach (explode(',', $pkg['features']) as $feature) {
                $feature = trim($feature);
                if (!in_array($feature, $all_features)) $all_features[] = $feature;
            }
        }
    }
    
    $feature_icons = [
        'Màn hình' => 'tv', 'LED' => 'tv', 'Âm thanh' => 'mic', 'WiFi' => 'wifi',
        'Coffee' => 'coffee', 'Điều hòa' => 'ac_unit', 'Hỗ trợ' => 'support_agent',
        'Projector' => 'videocam', 'Micro' => 'mic', 'Loa' => 'volume_up'
    ];
    
    if (!empty($all_features)):
    ?>
    <section class="py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <span class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('service_detail.amenities'); ?></span>
                <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4">
                    <?php _e('service_detail.modern_equipment'); ?>
                </h2>
                <p class="text-text-secondary-light dark:text-text-secondary-dark max-w-2xl mx-auto">
                    <?php _e('service_detail.amenities_desc'); ?>
                </p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php foreach (array_slice($all_features, 0, 6) as $feature): 
                    $icon = 'check_circle';
                    foreach ($feature_icons as $key => $ic) {
                        if (stripos($feature, $key) !== false) { $icon = $ic; break; }
                    }
                ?>
                    <div class="amenity-card-glass">
                        <div class="amenity-icon-glass">
                            <span class="material-symbols-outlined text-2xl"><?php echo $icon; ?></span>
                        </div>
                        <h3 class="amenity-title-glass"><?php echo htmlspecialchars($feature); ?></h3>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- CTA Section -->
    <section class="cta-glass-section py-16 md:py-20">
        <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
            <div class="glass-badge mb-4 inline-flex">
                <span class="material-symbols-outlined text-accent text-sm">support_agent</span>
                <?php _e('service_detail.support_24_7'); ?>
            </div>
            <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">
                <?php _e('service_detail.ready_to_book'); ?>
            </h2>
            <p class="text-white/90 text-lg mb-8 max-w-2xl mx-auto">
                <?php _e('service_detail.cta_desc'); ?>
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="tel:+842513918888" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-accent rounded-xl font-bold hover:bg-gray-100 transition-all shadow-lg">
                    <span class="material-symbols-outlined">phone</span>
                    (+84-251) 391.8888
                </a>
                <a href="contact.php" class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm border-2 border-white/30 text-white rounded-xl font-bold hover:bg-white/20 transition-all">
                    <span class="material-symbols-outlined">mail</span>
                    <?php _e('service_detail.send_request'); ?>
                </a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

<script>
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
</script>

</body>
</html>