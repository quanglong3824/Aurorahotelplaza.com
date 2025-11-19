<?php
require_once 'config/database.php';

// Get slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: services.php');
    exit;
}

try {
    $db = getDB();
    
    // Get service details
    $stmt = $db->prepare("
        SELECT * FROM services 
        WHERE slug = :slug AND is_available = 1
    ");
    $stmt->execute([':slug' => $slug]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        header('Location: services.php');
        exit;
    }
    
    // Get packages
    $stmt = $db->prepare("
        SELECT * FROM service_packages 
        WHERE service_id = :service_id AND is_available = 1
        ORDER BY sort_order ASC
    ");
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
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo htmlspecialchars($page_title); ?></title>
<meta name="description" content="<?php echo htmlspecialchars(substr($service['description'], 0, 160)); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($service['service_name']); ?> - Aurora Hotel Plaza">
<meta property="og:description" content="<?php echo htmlspecialchars(substr($service['description'], 0, 160)); ?>">
<?php if ($service['thumbnail']): ?>
<meta property="og:image" content="<?php echo htmlspecialchars($service['thumbnail']); ?>">
<?php endif; ?>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/service-detail-new.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Hero Section - Full Width -->
    <section class="service-hero relative overflow-hidden">
        <?php if ($service['thumbnail']): ?>
            <div class="service-hero-bg" style="background-image: url('<?php echo htmlspecialchars($service['thumbnail']); ?>');"></div>
        <?php else: ?>
            <div class="service-hero-bg" style="background: linear-gradient(135deg, #1A237E 0%, #cc9a2c 100%);"></div>
        <?php endif; ?>
        <div class="service-hero-overlay"></div>
        
        <div class="service-hero-content">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div class="text-white space-y-6">
                        <div class="inline-flex items-center gap-3 bg-white/20 backdrop-blur-sm px-6 py-3 rounded-full">
                            <span class="material-symbols-outlined text-2xl"><?php echo htmlspecialchars($service['icon']); ?></span>
                            <span class="font-semibold">Dịch vụ cao cấp</span>
                        </div>
                        
                        <h1 class="service-hero-title">
                            <?php echo htmlspecialchars($service['service_name']); ?>
                        </h1>
                        
                        <p class="service-hero-description">
                            <?php echo htmlspecialchars($service['description']); ?>
                        </p>
                        
                        <div class="flex flex-wrap gap-4">
                            <a href="#packages" class="btn-primary">
                                <span>Xem gói dịch vụ</span>
                                <span class="material-symbols-outlined">arrow_downward</span>
                            </a>
                            <a href="contact.php" class="btn-secondary">
                                <span class="material-symbols-outlined">phone</span>
                                <span>Liên hệ tư vấn</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 gap-6">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">inventory_2</span>
                            </div>
                            <div class="stat-number"><?php echo count($packages); ?>+</div>
                            <div class="stat-label">Gói dịch vụ</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">groups</span>
                            </div>
                            <div class="stat-number">300+</div>
                            <div class="stat-label">Khách hàng</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">star</span>
                            </div>
                            <div class="stat-number">5.0</div>
                            <div class="stat-label">Đánh giá</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">support_agent</span>
                            </div>
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Hỗ trợ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <?php if (!empty($packages)): ?>
    <section id="packages" class="packages-section">
        <div class="max-w-7xl mx-auto px-6 lg:px-12">
            <div class="section-header">
                <span class="section-badge">Bảng giá</span>
                <h2 class="section-title">Gói dịch vụ của chúng tôi</h2>
                <p class="section-description">
                    Lựa chọn gói dịch vụ phù hợp với nhu cầu và ngân sách của bạn
                </p>
            </div>
            
            <div class="packages-grid">
                <?php foreach ($packages as $index => $pkg): 
                    $features = !empty($pkg['features']) ? explode(',', $pkg['features']) : [];
                ?>
                    <div class="package-card <?php echo $pkg['is_featured'] ? 'package-featured' : ''; ?>">
                        <?php if ($pkg['is_featured']): ?>
                            <div class="package-badge">
                                <span class="material-symbols-outlined">star</span>
                                <span>Phổ biến nhất</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="package-header">
                            <h3 class="package-name"><?php echo htmlspecialchars($pkg['package_name']); ?></h3>
                            <div class="package-price">
                                <span class="price-currency">đ</span>
                                <span class="price-amount"><?php echo number_format($pkg['price'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="package-unit"><?php echo htmlspecialchars($pkg['price_unit']); ?></div>
                        </div>
                        
                        <?php if (!empty($features)): ?>
                            <ul class="package-features">
                                <?php foreach ($features as $feature): ?>
                                    <li class="feature-item">
                                        <span class="feature-icon">
                                            <span class="material-symbols-outlined">check_circle</span>
                                        </span>
                                        <span class="feature-text"><?php echo htmlspecialchars(trim($feature)); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <a href="booking/index.php?service=<?php echo $service['slug']; ?>&package=<?php echo $pkg['slug']; ?>" 
                           class="package-button <?php echo $pkg['is_featured'] ? 'package-button-featured' : ''; ?>">
                            <span>Đặt dịch vụ ngay</span>
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Amenities Section -->
    <?php 
    // Get all features from packages and create unique amenities
    $all_features = [];
    foreach ($packages as $pkg) {
        if (!empty($pkg['features'])) {
            $features = explode(',', $pkg['features']);
            foreach ($features as $feature) {
                $feature = trim($feature);
                if (!in_array($feature, $all_features)) {
                    $all_features[] = $feature;
                }
            }
        }
    }
    
    // Map features to icons and descriptions
    $feature_data = [
        'Màn hình LED' => ['icon' => 'tv', 'desc' => 'Màn hình LED lớn, projector độ phân giải cao'],
        'Màn hình LED lớn' => ['icon' => 'tv', 'desc' => 'Màn hình LED lớn, projector độ phân giải cao'],
        'Màn hình LED 3D' => ['icon' => 'tv', 'desc' => 'Công nghệ màn hình LED 3D hiện đại'],
        'Âm thanh' => ['icon' => 'mic', 'desc' => 'Hệ thống âm thanh chuyên nghiệp'],
        'Âm thanh cao cấp' => ['icon' => 'mic', 'desc' => 'Hệ thống micro không dây, loa chất lượng cao'],
        'Hệ thống âm thanh' => ['icon' => 'volume_up', 'desc' => 'Âm thanh vòm chất lượng cao'],
        'WiFi' => ['icon' => 'wifi', 'desc' => 'Internet tốc độ cao, ổn định'],
        'WiFi tốc độ cao' => ['icon' => 'wifi', 'desc' => 'Internet tốc độ cao cho mọi thiết bị'],
        'WiFi doanh nghiệp' => ['icon' => 'wifi', 'desc' => 'Mạng doanh nghiệp bảo mật cao'],
        'Coffee break' => ['icon' => 'coffee', 'desc' => 'Dịch vụ trà, cà phê và đồ ăn nhẹ'],
        'Điều hòa' => ['icon' => 'ac_unit', 'desc' => 'Hệ thống điều hòa hiện đại'],
        'Điều hòa trung tâm' => ['icon' => 'ac_unit', 'desc' => 'Điều hòa trung tâm mát mẻ'],
        'Hỗ trợ kỹ thuật' => ['icon' => 'support_agent', 'desc' => 'Đội ngũ kỹ thuật hỗ trợ 24/7']
    ];
    
    if (!empty($all_features)):
    ?>
    <section class="amenities-section">
        <div class="max-w-7xl mx-auto px-6 lg:px-12">
            <div class="section-header">
                <span class="section-badge">Tiện nghi</span>
                <h2 class="section-title">Trang thiết bị hiện đại</h2>
                <p class="section-description">
                    Đầy đủ tiện nghi và trang thiết bị cao cấp cho sự kiện hoàn hảo
                </p>
            </div>
            
            <div class="amenities-grid">
                <?php foreach (array_slice($all_features, 0, 6) as $feature): 
                    $icon = 'check_circle';
                    $desc = 'Tiện nghi cao cấp';
                    
                    foreach ($feature_data as $key => $data) {
                        if (stripos($feature, $key) !== false) {
                            $icon = $data['icon'];
                            $desc = $data['desc'];
                            break;
                        }
                    }
                ?>
                    <div class="amenity-card">
                        <div class="amenity-icon">
                            <span class="material-symbols-outlined"><?php echo $icon; ?></span>
                        </div>
                        <h3 class="amenity-title"><?php echo htmlspecialchars($feature); ?></h3>
                        <p class="amenity-description"><?php echo $desc; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="max-w-5xl mx-auto px-6 lg:px-12 text-center">
            <div class="cta-icon">
                <span class="material-symbols-outlined">phone_in_talk</span>
            </div>
            <h2 class="cta-title">Sẵn sàng đặt dịch vụ?</h2>
            <p class="cta-description">
                Liên hệ với chúng tôi ngay hôm nay để được tư vấn miễn phí và nhận ưu đãi đặc biệt
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="tel:+842513918888" class="cta-button cta-button-primary">
                    <span class="material-symbols-outlined">phone</span>
                    <span>(+84-251) 391.8888</span>
                </a>
                <a href="contact.php" class="cta-button cta-button-secondary">
                    <span class="material-symbols-outlined">mail</span>
                    <span>Gửi yêu cầu</span>
                </a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

<script>
// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

</body>
</html>
