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
    <link rel="stylesheet" href="assets/css/pages-glass.css">
</head>

<body class="glass-page font-body text-white">
<div class="relative flex min-h-screen w-full flex-col">
    <?php include 'includes/header.php'; ?>

    <main class="flex h-full grow flex-col">
            <!-- Hero Section -->
            <section class="page-hero-glass">
                <div class="hero-glass-card">
                    <div class="glass-badge-pill mb-4">
                        <span class="material-symbols-outlined text-sm">room_service</span>
                        <?php _e('services_page.five_star'); ?>
                    </div>
                    <h1 class="hero-title-glass">
                        <?php _e('services_page.page_title'); ?>
                    </h1>
                    <p class="hero-subtitle-glass">
                        <?php _e('services_page.page_subtitle'); ?>
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center mb-8">
                        <a href="contact.php" class="btn-glass-gold" style="width: auto; margin-top:0;">
                            <span class="material-symbols-outlined">mail</span>
                            <?php _e('services_page.contact_now'); ?>
                        </a>
                        <a href="#services-list" class="px-6 py-3 rounded-xl border border-white/20 hover:bg-white/10 text-white transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined">arrow_downward</span>
                            <?php _e('services_page.view_services'); ?>
                        </a>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-3 gap-8 pt-8 border-t border-white/10">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-[#d4af37] mb-1 font-display"><?php echo count($services); ?>+</div>
                            <div class="text-sm opacity-70">Dịch vụ</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-[#d4af37] mb-1 font-display">5★</div>
                            <div class="text-sm opacity-70">Tiêu chuẩn</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-[#d4af37] mb-1 font-display">24/7</div>
                            <div class="text-sm opacity-70">Phục vụ</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Services List -->
            <section id="services-list" class="py-16 pb-24 relative z-10">
                <div class="max-w-7xl mx-auto px-4">
                    
                    <!-- Utility Services -->
                    <?php if (!empty($utility_services)): ?>
                    <div class="mb-20">
                        <div class="text-center mb-10">
                            <span class="text-[#d4af37] font-semibold text-sm uppercase tracking-wider">Aurora Hotel Plaza</span>
                            <h2 class="font-display text-4xl font-bold mt-2 mb-4 text-white">
                                <?php _e('services_page.utility_services'); ?>
                            </h2>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <?php foreach ($utility_services as $service): ?>
                                <a href="service-detail.php?slug=<?php echo $service['slug']; ?>" class="utility-card-glass group">
                                    <div class="utility-icon">
                                        <span class="material-symbols-outlined"><?php echo $service['icon']; ?></span>
                                    </div>
                                    <h3 class="font-bold text-lg text-white group-hover:text-[#d4af37] transition-colors">
                                        <?php echo htmlspecialchars($service['service_name']); ?>
                                    </h3>
                                    <p class="text-xs text-white/60 line-clamp-2">
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
                        <div class="text-center mb-12">
                            <span class="text-[#d4af37] font-semibold text-sm uppercase tracking-wider">Dịch vụ nổi bật</span>
                            <h2 class="font-display text-4xl font-bold mt-2 mb-4 text-white">
                                <?php _e('services_page.main_services'); ?>
                            </h2>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <?php foreach ($main_services as $service): ?>
                                <div class="glass-card">
                                    <?php if ($service['thumbnail']): ?>
                                    <div class="service-img-container">
                                        <img src="<?php echo htmlspecialchars($service['thumbnail']); ?>" 
                                             alt="<?php echo htmlspecialchars($service['service_name']); ?>">
                                        
                                        <?php if ($service['is_featured']): ?>
                                        <div class="absolute top-4 right-4 bg-[#d4af37] text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg flex items-center gap-1">
                                            <span class="material-symbols-outlined" style="font-size: 14px;">star</span>
                                            <?php _e('services_page.featured'); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="service-info">
                                        <h3 class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                        <p class="service-desc"><?php echo htmlspecialchars($service['short_description']); ?></p>
                                        
                                        <?php if (!empty($service['packages'])): ?>
                                        <div class="packages-list-mini">
                                            <?php foreach (array_slice($service['packages'], 0, 3) as $pkg): ?>
                                                <div class="package-item-mini">
                                                    <span><?php echo htmlspecialchars($pkg['package_name']); ?></span>
                                                    <span class="package-price-mini"><?php echo number_format($pkg['price'], 0, ',', '.'); ?>đ</span>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($service['packages']) > 3): ?>
                                                <p class="text-xs text-center mt-2 text-white/50">
                                                    <?php _e('services_page.other_packages', ['count' => count($service['packages']) - 3]); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <a href="service-detail.php?slug=<?php echo $service['slug']; ?>" class="btn-glass-gold">
                                            <?php _e('services_page.view_details'); ?>
                                            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- CTA Section -->
                    <div class="glass-cta-box">
                        <h2 class="font-display text-4xl font-bold text-white mb-4">Cần tư vấn dịch vụ?</h2>
                        <p class="text-white/90 text-lg mb-8 max-w-2xl mx-auto">
                            Liên hệ ngay với chúng tôi để được tư vấn và báo giá chi tiết cho các dịch vụ.
                        </p>
                        <div class="flex flex-wrap gap-4 justify-center relative z-10">
                            <a href="contact.php" class="px-8 py-4 bg-white text-[#d4af37] rounded-xl font-bold hover:bg-gray-100 transition-all shadow-lg flex items-center gap-2">
                                <span class="material-symbols-outlined">mail</span>
                                <?php _e('services_page.contact_now'); ?>
                            </a>
                            <a href="tel:+842513918888" class="px-8 py-4 bg-white/10 backdrop-blur-sm border-2 border-white/30 text-white rounded-xl font-bold hover:bg-white/20 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined">phone</span>
                                <?php _e('home.call_now'); ?>
                            </a>
                        </div>
                    </div>

                </div>
            </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</div>

<script src="assets/js/glass-pages.js"></script>
</body>
</html>