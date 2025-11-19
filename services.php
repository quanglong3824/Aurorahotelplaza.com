<?php
require_once 'config/database.php';

// Get filter
$category_filter = $_GET['category'] ?? 'all';

try {
    $db = getDB();
    
    // Build query
    $where = "WHERE is_available = 1";
    $params = [];
    
    if ($category_filter !== 'all') {
        $where .= " AND category = :category";
        $params[':category'] = $category_filter;
    }
    
    $stmt = $db->prepare("
        SELECT * FROM services 
        $where
        ORDER BY is_featured DESC, sort_order ASC
    ");
    
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get packages for each service
    foreach ($services as &$service) {
        $stmt = $db->prepare("
            SELECT * FROM service_packages 
            WHERE service_id = :service_id AND is_available = 1
            ORDER BY sort_order ASC
        ");
        $stmt->execute([':service_id' => $service['service_id']]);
        $service['packages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get category counts
    $stmt = $db->query("
        SELECT category, COUNT(*) as count
        FROM services
        WHERE is_available = 1
        GROUP BY category
    ");
    $category_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (Exception $e) {
    error_log("Services page error: " . $e->getMessage());
    $services = [];
    $category_counts = [];
}

$category_names = [
    'room_service' => 'Dịch vụ phòng',
    'spa' => 'Spa & Wellness',
    'restaurant' => 'Ẩm thực',
    'event' => 'Sự kiện',
    'transport' => 'Vận chuyển',
    'laundry' => 'Giặt ủi',
    'other' => 'Khác'
];
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Dịch vụ - Aurora Hotel Plaza</title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/services.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-services">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <h1 class="page-title">Dịch vụ của chúng tôi</h1>
            <p class="page-subtitle">Trải nghiệm đẳng cấp với các dịch vụ chuyên nghiệp và tiện nghi hiện đại</p>
        </div>
    </section>

    <!-- Services Grid -->
    <section class="section-padding">
        <div class="container-custom">
            <!-- Additional Services (icon blocks) - Moved to top -->
            <?php 
            $additional_services = array_filter($services, function($s) {
                return empty($s['packages']);
            });
            if (!empty($additional_services)): 
            ?>
                <div class="mb-16">
                    <h2 class="text-3xl font-bold text-center mb-10" style="color: var(--accent);">Dịch vụ tiện ích</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <?php foreach ($additional_services as $service): ?>
                            <a href="service-detail.php?slug=<?php echo $service['slug']; ?>" 
                               class="bg-white dark:bg-slate-800 rounded-xl p-8 text-center hover:shadow-xl transition-all border border-gray-100 dark:border-slate-700 hover:scale-105 block">
                                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-accent to-accent/80 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-4xl"><?php echo $service['icon']; ?></span>
                                </div>
                                <h3 class="font-bold text-base mb-2 text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                    <?php echo htmlspecialchars($service['short_description']); ?>
                                </p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Services (with packages) - Moved to bottom -->
            <div>
                <h2 class="text-3xl font-bold text-center mb-10" style="color: var(--accent);">Dịch vụ chính</h2>
                <div class="services-grid">
                <?php 
                $main_services = array_filter($services, function($s) {
                    return !empty($s['packages']);
                });
                foreach ($main_services as $service): 
                ?>
                    <!-- Service Card -->
                    <div class="service-card">
                        <?php if ($service['thumbnail']): ?>
                            <div class="service-image-wrapper">
                                <img src="<?php echo htmlspecialchars($service['thumbnail']); ?>" 
                                     alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                                     class="service-image">
                                <div class="service-overlay">
                                    <a href="service-detail.php?slug=<?php echo $service['slug']; ?>" class="service-link">
                                        <span class="material-symbols-outlined">arrow_forward</span>
                                    </a>
                                </div>
                                <?php if ($service['is_featured']): ?>
                                    <div class="absolute top-4 right-4 bg-accent text-white px-3 py-1 rounded-full text-xs font-bold">
                                        Nổi bật
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="service-content">
                            <div class="service-icon">
                                <span class="material-symbols-outlined"><?php echo $service['icon']; ?></span>
                            </div>
                            <h3 class="service-title"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                            <p class="service-description">
                                <?php echo htmlspecialchars($service['short_description']); ?>
                            </p>
                            
                            <?php if (!empty($service['packages'])): ?>
                                <!-- Show packages -->
                                <div class="mt-4 mb-4">
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Gói dịch vụ:</p>
                                    <?php foreach (array_slice($service['packages'], 0, 3) as $pkg): ?>
                                        <div class="flex justify-between items-center py-1 text-sm">
                                            <span class="text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($pkg['package_name']); ?></span>
                                            <span class="font-bold text-accent">
                                                <?php echo number_format($pkg['price'], 0, ',', '.'); ?>đ
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($service['packages']) > 3): ?>
                                        <p class="text-xs text-gray-500 mt-1">+<?php echo count($service['packages']) - 3; ?> gói khác</p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Show single price -->
                                <ul class="service-features">
                                    <?php 
                                    $features = !empty($service['description']) ? explode('.', $service['description']) : [];
                                    foreach (array_slice($features, 0, 4) as $feature): 
                                        $feature = trim($feature);
                                        if (!empty($feature)):
                                    ?>
                                        <li><?php echo htmlspecialchars($feature); ?></li>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </ul>
                            <?php endif; ?>
                            
                            <a href="service-detail.php?slug=<?php echo $service['slug']; ?>" class="service-button">Xem chi tiết</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

</body>
</html>
