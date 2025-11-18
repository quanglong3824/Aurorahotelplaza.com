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
        ORDER BY is_featured DESC, sort_order ASC, service_name ASC
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

$category_links = [
    'event' => ['wedding.php', 'conference.php'],
    'restaurant' => ['restaurant.php'],
    'other' => ['office.php']
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
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="relative h-80 flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#d4af37] to-[#b8941f]"></div>
        <div class="relative z-10 text-center text-white px-6">
            <h1 class="text-5xl font-bold mb-4">Dịch vụ của chúng tôi</h1>
            <p class="text-xl opacity-90">Trải nghiệm đẳng cấp với dịch vụ chuyên nghiệp</p>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-12">
        <div class="container-custom">
            <!-- Category Filter -->
            <div class="flex flex-wrap gap-3 mb-8">
                <a href="?category=all" 
                   class="px-6 py-2.5 rounded-full font-semibold transition-all <?php echo $category_filter === 'all' ? 'bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'; ?>">
                    Tất cả (<?php echo count($services); ?>)
                </a>
                <?php foreach ($category_names as $cat_key => $cat_name): ?>
                    <?php if (isset($category_counts[$cat_key]) && $category_counts[$cat_key] > 0): ?>
                        <a href="?category=<?php echo $cat_key; ?>" 
                           class="px-6 py-2.5 rounded-full font-semibold transition-all <?php echo $category_filter === $cat_key ? 'bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'; ?>">
                            <?php echo $cat_name; ?> (<?php echo $category_counts[$cat_key]; ?>)
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Services Grid -->
            <?php if (empty($services)): ?>
                <div class="text-center py-20">
                    <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">sentiment_dissatisfied</span>
                    <p class="text-xl text-gray-500">Không có dịch vụ nào</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($services as $service): ?>
                        <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 group">
                            <!-- Image -->
                            <?php if ($service['thumbnail']): ?>
                                <div class="relative h-48 overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($service['thumbnail']); ?>" 
                                         alt="<?php echo htmlspecialchars($service['service_name']); ?>"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    <?php if ($service['is_featured']): ?>
                                        <div class="absolute top-4 right-4 px-3 py-1 bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white text-xs font-bold rounded-full">
                                            Nổi bật
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Content -->
                            <div class="p-6">
                                <!-- Icon & Title -->
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-[#d4af37] to-[#b8941f] flex items-center justify-center">
                                        <span class="material-symbols-outlined text-white text-2xl"><?php echo $service['icon']; ?></span>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                                            <?php echo htmlspecialchars($service['service_name']); ?>
                                        </h3>
                                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                                            <?php echo $category_names[$service['category']]; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <?php if ($service['short_description']): ?>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                        <?php echo htmlspecialchars($service['short_description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <!-- Packages or Price -->
                                <?php if (!empty($service['packages'])): ?>
                                    <div class="pt-4 border-t border-gray-200 dark:border-slate-700">
                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3">Gói dịch vụ:</p>
                                        <div class="space-y-2 mb-4">
                                            <?php foreach (array_slice($service['packages'], 0, 2) as $pkg): ?>
                                                <div class="flex items-center justify-between text-sm">
                                                    <span class="text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($pkg['package_name']); ?></span>
                                                    <span class="font-bold" style="color: #d4af37;">
                                                        <?php echo number_format($pkg['price'], 0, ',', '.'); ?>đ
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($service['packages']) > 2): ?>
                                                <p class="text-xs text-gray-500">+<?php echo count($service['packages']) - 2; ?> gói khác</p>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?php echo $service['slug']; ?>.php" 
                                           class="block w-full text-center px-4 py-2 bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white text-sm font-semibold rounded-lg hover:shadow-lg transition-all">
                                            Xem chi tiết
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-700">
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">Giá dịch vụ</p>
                                            <p class="text-lg font-bold" style="color: #d4af37;">
                                                <?php 
                                                if ($service['price'] > 0) {
                                                    echo number_format($service['price'], 0, ',', '.') . 'đ';
                                                } else {
                                                    echo $service['price_unit'];
                                                }
                                                ?>
                                            </p>
                                        </div>
                                        <button onclick="alert('Liên hệ: 0123456789')" 
                                                class="px-4 py-2 bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white text-sm font-semibold rounded-lg hover:shadow-lg transition-all">
                                            Liên hệ
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

</body>
</html>
