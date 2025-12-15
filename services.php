<?php
require_once 'config/database.php';
require_once 'helpers/language.php';
initLanguage();

$category_filter = $_GET['category'] ?? 'all';

try {
    $db = getDB();
    $where = "WHERE is_available = 1";
    $params = [];
    
    if ($category_filter !== 'all') {
        $where .= " AND category = :category";
        $params[':category'] = $category_filter;
    }
    
    $stmt = $db->prepare("SELECT * FROM services $where ORDER BY is_featured DESC, sort_order ASC");
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($services as &$service) {
        $stmt = $db->prepare("SELECT * FROM service_packages WHERE service_id = :service_id AND is_available = 1 ORDER BY sort_order ASC");
        $stmt->execute([':service_id' => $service['service_id']]);
        $service['packages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Services page error: " . $e->getMessage());
    $services = [];
}

$main_services = array_filter($services, fn($s) => !empty($s['packages']));
$utility_services = array_filter($services, fn($s) => empty($s['packages']));
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title><?php _e('services_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<style>
/* Hero Section */
.services-hero {
    position: relative;
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.85), rgba(17, 24, 39, 0.7)), 
                url('assets/img/restaurant/NHA-HANG-AURORA-HOTEL-4.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    padding: 120px 20px 80px;
}

.services-hero::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 150px;
    background: linear-gradient(to top, var(--background-light, #f8fafc), transparent);
    pointer-events: none;
}

.dark .services-hero::before {
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

/* Service Card - Liquid Glass */
.service-card-glass {
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

.dark .service-card-glass {
    background: rgba(30, 41, 59, 0.85);
    border-color: rgba(255, 255, 255, 0.1);
}

.service-card-glass:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(212, 175, 55, 0.2);
}

/* Image Container */
.service-img-container {
    position: relative;
    aspect-ratio: 16/10;
    overflow: hidden;
}

.service-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.service-card-glass:hover .service-img-container img {
    transform: scale(1.08);
}

/* Featured Badge */
.featured-badge {
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

/* Service Icon Overlay */
.service-icon-overlay {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    width: 3.5rem;
    height: 3.5rem;
    background: rgba(17, 24, 39, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d4af37;
}

/* Service Info */
.service-info {
    padding: 1.5rem;
}

.service-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.375rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--text-primary-light);
}

.dark .service-name {
    color: var(--text-primary-dark);
}

.service-desc {
    font-size: 0.9375rem;
    color: var(--text-secondary-light);
    line-height: 1.6;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.dark .service-desc {
    color: var(--text-secondary-dark);
}

/* Packages List */
.packages-list {
    margin-bottom: 1.25rem;
    padding: 1rem;
    background: rgba(212, 175, 55, 0.05);
    border-radius: 0.75rem;
}

.package-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px dashed rgba(0, 0, 0, 0.08);
}

.dark .package-item {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.package-item:last-child {
    border-bottom: none;
}

.package-name {
    font-size: 0.875rem;
    color: var(--text-secondary-light);
}

.dark .package-name {
    color: var(--text-secondary-dark);
}

.package-price {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #d4af37;
}

/* Utility Service Card */
.utility-card-glass {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 1.25rem;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
}

.dark .utility-card-glass {
    background: rgba(30, 41, 59, 0.85);
    border-color: rgba(255, 255, 255, 0.1);
}

.utility-card-glass:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(212, 175, 55, 0.2);
}

.utility-icon {
    width: 4rem;
    height: 4rem;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(212, 175, 55, 0.05));
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d4af37;
    transition: all 0.3s ease;
}

.utility-card-glass:hover .utility-icon {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: white;
    transform: scale(1.1);
}

/* Action Button */
.btn-service {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.875rem 1.25rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: white;
    border-radius: 0.75rem;
    font-weight: 600;
    font-size: 0.9375rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}

.btn-service:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
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
    .services-hero {
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
}

@media (max-width: 480px) {
    .hero-glass-card {
        padding: 1.5rem 1rem;
        border-radius: 1.25rem;
    }
    
    .hero-glass-card h1 {
        font-size: 1.75rem;
    }
    
    .service-card-glass, .utility-card-glass {
        border-radius: 1rem;
    }
    
    .service-info {
        padding: 1.25rem;
    }
    
    .service-name {
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
    <section class="services-hero">
        <div class="hero-glass-card">
            <div class="glass-badge mb-4 inline-flex">
                <span class="material-symbols-outlined text-accent text-sm">room_service</span>
                <?php _e('services_page.five_star'); ?>
            </div>
            <h1 class="font-display text-4xl md:text-5xl font-bold text-white mb-4">
                <?php _e('services_page.page_title'); ?>
            </h1>
            <p class="text-white/80 text-lg max-w-xl mx-auto mb-8">
                <?php _e('services_page.page_subtitle'); ?>
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="contact.php" class="btn-glass-primary">
                    <span class="material-symbols-outlined">mail</span>
                    <?php _e('services_page.contact_now'); ?>
                </a>
                <a href="#services-list" class="btn-glass-secondary">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    <?php _e('services_page.view_services'); ?>
                </a>
            </div>
            
            <!-- Quick Stats -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-value"><?php echo count($services); ?>+</div>
                    <div class="stat-label">Dịch vụ</div>
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

    <!-- Services Section -->
    <section id="services-list" class="py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-4">
            
            <!-- Utility Services -->
            <?php if (!empty($utility_services)): ?>
            <div class="mb-16">
                <div class="text-center mb-10">
                    <span class="text-accent font-semibold text-sm uppercase tracking-wider">Aurora Hotel Plaza</span>
                    <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4 text-accent">
                        <?php _e('services_page.utility_services'); ?>
                    </h2>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <?php foreach ($utility_services as $service): ?>
                        <a href="service-detail.php?slug=<?php echo $service['slug']; ?>" class="utility-card-glass group">
                            <div class="utility-icon">
                                <span class="material-symbols-outlined text-3xl"><?php echo $service['icon']; ?></span>
                            </div>
                            <h3 class="font-bold text-base text-text-primary-light dark:text-text-primary-dark group-hover:text-accent transition-colors">
                                <?php echo htmlspecialchars($service['service_name']); ?>
                            </h3>
                            <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark line-clamp-2">
                                <?php echo htmlspecialchars($service['short_description']); ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Main Services -->
            <?php if (!empty($main_services)): ?>
            <div>
                <div class="text-center mb-10">
                    <span class="text-accent font-semibold text-sm uppercase tracking-wider">Dịch vụ nổi bật</span>
                    <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4">
                        <?php _e('services_page.main_services'); ?>
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($main_services as $service): ?>
                        <div class="service-card-glass">
                            <?php if ($service['thumbnail']): ?>
                            <div class="service-img-container">
                                <img src="<?php echo htmlspecialchars($service['thumbnail']); ?>" 
                                     alt="<?php echo htmlspecialchars($service['service_name']); ?>">
                                
                                <?php if ($service['is_featured']): ?>
                                <div class="featured-badge">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">star</span>
                                    <?php _e('services_page.featured'); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="service-icon-overlay">
                                    <span class="material-symbols-outlined text-2xl"><?php echo $service['icon']; ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="service-info">
                                <h3 class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                <p class="service-desc"><?php echo htmlspecialchars($service['short_description']); ?></p>
                                
                                <?php if (!empty($service['packages'])): ?>
                                <div class="packages-list">
                                    <?php foreach (array_slice($service['packages'], 0, 3) as $pkg): ?>
                                        <div class="package-item">
                                            <span class="package-name"><?php echo htmlspecialchars($pkg['package_name']); ?></span>
                                            <span class="package-price"><?php echo number_format($pkg['price'], 0, ',', '.'); ?>đ</span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($service['packages']) > 3): ?>
                                        <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-2 text-center">
                                            <?php _e('services_page.other_packages', ['count' => count($service['packages']) - 3]); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <a href="service-detail.php?slug=<?php echo $service['slug']; ?>" class="btn-service">
                                    <?php _e('services_page.view_details'); ?>
                                    <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-glass py-16 md:py-20">
        <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
            <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">
                Cần tư vấn dịch vụ?
            </h2>
            <p class="text-white/90 text-lg mb-8 max-w-2xl mx-auto">
                Liên hệ ngay với chúng tôi để được tư vấn và báo giá chi tiết cho các dịch vụ.
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="contact.php" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-accent rounded-xl font-bold hover:bg-gray-100 transition-all shadow-lg">
                    <span class="material-symbols-outlined">mail</span>
                    <?php _e('services_page.contact_now'); ?>
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