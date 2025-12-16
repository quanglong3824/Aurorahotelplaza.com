<?php
require_once 'config/database.php';
require_once 'helpers/language.php';
initLanguage();

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: services.php');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM services WHERE slug = :slug AND is_available = 1");
    $stmt->execute([':slug' => $slug]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        header('Location: services.php');
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM service_packages WHERE service_id = :service_id AND is_available = 1 ORDER BY sort_order ASC");
    $stmt->execute([':service_id' => $service['service_id']]);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Service detail error: " . $e->getMessage());
    header('Location: services.php');
    exit;
}

$page_title = $service['service_name'] . ' - Aurora Hotel Plaza';
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($service['description'], 0, 160)); ?>">
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet" />
    <script src="assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/services-glass.css">
</head>

<body class="bg-slate-900 font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include 'includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Content Wrapper with Dynamic Background -->
            <?php
            $bg_image = $service['thumbnail'] ? htmlspecialchars($service['thumbnail']) : 'assets/img/restaurant/nha-hang-aurora-hotel-4.jpg';
            ?>
            <div class="services-content-wrapper" style="background-image: url('<?php echo $bg_image; ?>');">

                <!-- Hero Detail Section -->
                <section class="detail-hero-section">
                    <div class="max-w-7xl mx-auto px-4 mt-10">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

                            <!-- Left: Info Card -->
                            <div class="detail-glass-card animate-fade-in-up">
                                <div class="glass-badge-pill mb-4">
                                    <span
                                        class="material-symbols-outlined text-sm"><?php echo htmlspecialchars($service['icon']); ?></span>
                                    <?php _e('service_detail.premium_service'); ?>
                                </div>

                                <h1 class="services-hero-title" style="font-size: 3rem; text-align: left;">
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                </h1>

                                <p class="text-white/85 text-lg leading-relaxed mb-8">
                                    <?php echo htmlspecialchars($service['description']); ?>
                                </p>

                                <div class="flex flex-wrap gap-4">
                                    <a href="#packages" class="btn-service-glass" style="width: auto;">
                                        <?php _e('service_detail.view_packages'); ?>
                                        <span class="material-symbols-outlined">arrow_downward</span>
                                    </a>
                                    <a href="contact.php"
                                        class="px-6 py-3 rounded-xl border border-white/20 hover:bg-white/10 text-white transition-all flex items-center gap-2">
                                        <span class="material-symbols-outlined">phone</span>
                                        <?php _e('service_detail.contact_consult'); ?>
                                    </a>
                                </div>
                            </div>

                            <!-- Right: Stats Grid -->
                            <div class="stats-grid animate-fade-in-up" style="animation-delay: 0.2s;">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="stat-card-glass">
                                        <div class="stat-icon">
                                            <span class="material-symbols-outlined">inventory_2</span>
                                        </div>
                                        <div class="stat-value"><?php echo count($packages); ?>+</div>
                                        <div class="stat-label"><?php _e('service_detail.packages'); ?></div>
                                    </div>
                                    <div class="stat-card-glass">
                                        <div class="stat-icon">
                                            <span class="material-symbols-outlined">groups</span>
                                        </div>
                                        <div class="stat-value">300+</div>
                                        <div class="stat-label"><?php _e('service_detail.customers'); ?></div>
                                    </div>
                                    <div class="stat-card-glass">
                                        <div class="stat-icon">
                                            <span class="material-symbols-outlined">star</span>
                                        </div>
                                        <div class="stat-value">5.0</div>
                                        <div class="stat-label"><?php _e('service_detail.rating'); ?></div>
                                    </div>
                                    <div class="stat-card-glass">
                                        <div class="stat-icon">
                                            <span class="material-symbols-outlined">support_agent</span>
                                        </div>
                                        <div class="stat-value">24/7</div>
                                        <div class="stat-label"><?php _e('service_detail.support'); ?></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </section>

                <!-- Packages Section -->
                <?php if (!empty($packages)): ?>
                    <section id="packages" class="py-16 relative z-10">
                        <div class="max-w-7xl mx-auto px-4">
                            <div class="text-center mb-12 animate-fade-in-up">
                                <span
                                    class="text-[#d4af37] font-semibold text-sm uppercase tracking-wider"><?php _e('service_detail.pricing'); ?></span>
                                <h2 class="font-display text-4xl font-bold mt-2 mb-4 text-white">
                                    <?php _e('service_detail.our_packages'); ?>
                                </h2>
                                <p class="text-white/70 max-w-2xl mx-auto">
                                    <?php _e('service_detail.packages_desc'); ?>
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                <?php foreach ($packages as $pkg):
                                    $features = !empty($pkg['features']) ? explode(',', $pkg['features']) : [];
                                    ?>
                                    <div class="package-detail-card <?php echo $pkg['is_featured'] ? 'featured' : ''; ?>">
                                        <?php if ($pkg['is_featured']): ?>
                                            <div class="package-badge">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">star</span>
                                                <?php _e('service_detail.most_popular'); ?>
                                            </div>
                                        <?php endif; ?>

                                        <h3 class="font-display text-2xl font-bold text-white mb-2">
                                            <?php echo htmlspecialchars($pkg['package_name']); ?></h3>
                                        <div class="package-price-large">
                                            <?php echo number_format($pkg['price'], 0, ',', '.'); ?>đ
                                            <span class="text-sm font-sans font-normal text-white/60 ml-1">/
                                                <?php echo htmlspecialchars($pkg['price_unit']); ?></span>
                                        </div>

                                        <?php if (!empty($features)): ?>
                                            <div class="package-features">
                                                <?php foreach ($features as $feature): ?>
                                                    <div class="feature-row">
                                                        <span class="material-symbols-outlined text-sm">check_circle</span>
                                                        <span
                                                            class="text-white/80"><?php echo htmlspecialchars(trim($feature)); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <a href="booking/index.php?service=<?php echo $service['slug']; ?>&package=<?php echo $pkg['slug']; ?>"
                                            class="btn-service-glass <?php echo $pkg['is_featured'] ? 'bg-gradient-to-r from-[#d4af37] to-[#b8941f]' : 'bg-white/10 hover:bg-white/20 border border-white/20'; ?>">
                                            <?php _e('service_detail.book_now'); ?>
                                            <span class="material-symbols-outlined"
                                                style="font-size: 18px;">arrow_forward</span>
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
                            if (!in_array($feature, $all_features))
                                $all_features[] = $feature;
                        }
                    }
                }

                $feature_icons = [
                    'Màn hình' => 'tv',
                    'LED' => 'tv',
                    'Âm thanh' => 'mic',
                    'WiFi' => 'wifi',
                    'Coffee' => 'coffee',
                    'Điều hòa' => 'ac_unit',
                    'Hỗ trợ' => 'support_agent',
                    'Projector' => 'videocam',
                    'Micro' => 'mic',
                    'Loa' => 'volume_up'
                ];

                if (!empty($all_features)):
                    ?>
                    <section class="py-16 relative z-10">
                        <div class="max-w-7xl mx-auto px-4">
                            <div class="text-center mb-12">
                                <span
                                    class="text-[#d4af37] font-semibold text-sm uppercase tracking-wider"><?php _e('service_detail.amenities'); ?></span>
                                <h2 class="font-display text-4xl font-bold mt-2 mb-4 text-white">
                                    <?php _e('service_detail.modern_equipment'); ?>
                                </h2>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                                <?php foreach (array_slice($all_features, 0, 6) as $feature):
                                    $icon = 'check_circle';
                                    foreach ($feature_icons as $key => $ic) {
                                        if (stripos($feature, $key) !== false) {
                                            $icon = $ic;
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="utility-card-glass group h-full justify-center">
                                        <div class="utility-icon mb-0">
                                            <span class="material-symbols-outlined text-2xl"><?php echo $icon; ?></span>
                                        </div>
                                        <h3 class="text-white font-medium text-center"><?php echo htmlspecialchars($feature); ?>
                                        </h3>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- CTA Section -->
                <div class="max-w-7xl mx-auto px-4">
                    <div class="glass-cta-box">
                        <div class="glass-badge-pill mb-6 mx-auto">
                            <span class="material-symbols-outlined text-sm">support_agent</span>
                            <?php _e('service_detail.support_24_7'); ?>
                        </div>
                        <h2 class="font-display text-4xl font-bold text-white mb-4">
                            <?php _e('service_detail.ready_to_book'); ?>
                        </h2>
                        <p class="text-white/90 text-lg mb-8 max-w-2xl mx-auto">
                            <?php _e('service_detail.cta_desc'); ?>
                        </p>
                        <div class="flex flex-wrap gap-4 justify-center relative z-10">
                            <a href="tel:+842513918888"
                                class="px-8 py-4 bg-white text-[#d4af37] rounded-xl font-bold hover:bg-gray-100 transition-all shadow-lg flex items-center gap-2">
                                <span class="material-symbols-outlined">phone</span>
                                (+84-251) 391.8888
                            </a>
                            <a href="contact.php"
                                class="px-8 py-4 bg-white/10 backdrop-blur-sm border-2 border-white/30 text-white rounded-xl font-bold hover:bg-white/20 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined">mail</span>
                                <?php _e('service_detail.send_request'); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="pb-20"></div>

            </div>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="assets/js/services-glass.js"></script>
</body>

</html>