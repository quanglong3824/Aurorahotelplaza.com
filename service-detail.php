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
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col pt-20">
    <!-- Hero Section -->
    <section class="py-16 bg-gray-50 dark:bg-slate-900">
        <div class="container-custom">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Content -->
                <div>
                    <h1 class="text-4xl lg:text-5xl font-bold mb-6"><?php echo htmlspecialchars($service['service_name']); ?></h1>
                    <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed mb-8">
                        <?php echo htmlspecialchars($service['description']); ?>
                    </p>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="text-4xl font-bold mb-2" style="color: var(--accent);">
                                <?php echo count($packages); ?>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?php echo count($packages) > 0 ? 'Gói dịch vụ' : 'Dịch vụ'; ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold mb-2" style="color: var(--accent);">300</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Người tối đa</div>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold mb-2" style="color: var(--accent);">24/7</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Hỗ trợ kỹ thuật</div>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Image -->
                <div class="relative">
                    <?php if ($service['thumbnail']): ?>
                        <img src="<?php echo htmlspecialchars($service['thumbnail']); ?>" 
                             alt="<?php echo htmlspecialchars($service['service_name']); ?>"
                             class="w-full h-96 object-cover rounded-2xl shadow-2xl">
                    <?php else: ?>
                        <div class="w-full h-96 bg-gradient-to-br from-accent to-accent/80 rounded-2xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-9xl"><?php echo $service['icon']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <?php if (!empty($packages)): ?>
    <section class="py-16">
        <div class="container-custom">
            <h2 class="text-4xl font-bold mb-12 text-center">Gói dịch vụ hội nghị</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <?php foreach ($packages as $index => $pkg): 
                    $features = !empty($pkg['features']) ? explode(',', $pkg['features']) : [];
                ?>
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all <?php echo $pkg['is_featured'] ? 'border-4 border-accent' : 'border border-gray-200 dark:border-slate-700'; ?>">
                        <?php if ($pkg['is_featured']): ?>
                            <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-accent text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg">
                                Phổ biến
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="text-2xl font-bold mb-6 text-center mt-2"><?php echo htmlspecialchars($pkg['package_name']); ?></h3>
                        
                        <div class="text-center mb-8">
                            <div class="text-5xl font-bold mb-2" style="color: var(--accent);">
                                <?php echo number_format($pkg['price'], 0, ',', '.'); ?>đ
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?php echo htmlspecialchars($pkg['price_unit']); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($features)): ?>
                            <ul class="space-y-4 mb-8">
                                <?php foreach ($features as $feature): ?>
                                    <li class="flex items-start gap-3">
                                        <span class="material-symbols-outlined text-green-500 text-xl flex-shrink-0 mt-0.5">check</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars(trim($feature)); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <a href="booking/index.php?service=<?php echo $service['slug']; ?>&package=<?php echo $pkg['slug']; ?>" 
                           class="block w-full text-center px-6 py-3 bg-accent text-white font-semibold rounded-lg hover:bg-accent/90 transition-all">
                            Liên hệ đặt phòng
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            
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
            
            // Map features to icons
            $feature_icons = [
                'Màn hình LED' => 'tv',
                'Màn hình LED lớn' => 'tv',
                'Màn hình LED 3D' => 'tv',
                'Âm thanh' => 'mic',
                'Âm thanh cao cấp' => 'mic',
                'Âm thanh chuyên nghiệp' => 'mic',
                'Hệ thống âm thanh' => 'mic',
                'WiFi' => 'wifi',
                'WiFi tốc độ cao' => 'wifi',
                'WiFi doanh nghiệp' => 'wifi',
                'Coffee break' => 'coffee',
                'Điều hòa' => 'ac_unit',
                'Điều hòa trung tâm' => 'ac_unit',
                'Hỗ trợ kỹ thuật' => 'support_agent'
            ];
            
            if (!empty($all_features)):
            ?>
            <div class="mt-16">
                <h2 class="text-4xl font-bold mb-12 text-center">Tiện nghi hội nghị</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach (array_slice($all_features, 0, 6) as $feature): 
                        $icon = 'check_circle';
                        foreach ($feature_icons as $key => $value) {
                            if (stripos($feature, $key) !== false) {
                                $icon = $value;
                                break;
                            }
                        }
                    ?>
                        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 text-center hover:shadow-lg transition-all border border-gray-100 dark:border-slate-700">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-accent flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-3xl"><?php echo $icon; ?></span>
                            </div>
                            <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($feature); ?></h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <?php 
                                if (stripos($feature, 'LED') !== false) echo 'Màn hình LED lớn, projector độ phân giải cao';
                                elseif (stripos($feature, 'âm thanh') !== false) echo 'Hệ thống micro không dây, loa chất lượng cao';
                                elseif (stripos($feature, 'WiFi') !== false) echo 'Internet tốc độ cao, ổn định cho mọi thiết bị';
                                elseif (stripos($feature, 'Coffee') !== false) echo 'Dịch vụ trà, cà phê và đồ ăn nhẹ';
                                elseif (stripos($feature, 'Điều hòa') !== false) echo 'Hệ thống điều hòa hiện đại, mát mẻ';
                                else echo 'Đội ngũ kỹ thuật viên hỗ trợ suốt sự kiện';
                                ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
</div>

</body>
</html>
