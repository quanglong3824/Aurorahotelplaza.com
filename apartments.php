<?php
require_once 'config/database.php';
require_once 'helpers/image-helper.php';
require_once 'helpers/language.php';
initLanguage();

try {
    $db = getDB();
    
    // Chỉ lấy căn hộ (không lấy phòng)
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
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php _e('apartments_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<link rel="stylesheet" href="assets/css/apartments.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-apartments">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="badge-liquid-glass mb-6">
                <span class="material-symbols-outlined text-accent">apartment</span>
                <?php _e('apartments_page.premium_apartments'); ?>
            </span>
            <h1 class="page-title"><?php _e('apartments_page.page_title'); ?></h1>
            <p class="page-subtitle"><?php _e('apartments_page.page_subtitle'); ?></p>
            <div class="flex flex-wrap gap-4 justify-center mt-8">
                <a href="booking/index.php" class="btn-liquid-primary">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <?php _e('apartments_page.book_now'); ?>
                </a>
                <a href="#apartments-list" class="btn-liquid-glass">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    <?php _e('apartments_page.view_list'); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Apartments Section -->
    <section id="apartments-list" class="section-padding">
        <div class="container-custom">
            <?php if (empty($apartments)): ?>
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg"><?php _e('apartments_page.no_apartments'); ?></p>
                </div>
            <?php else: ?>
                <?php 
                // Phân loại căn hộ mới và cũ
                $new_apartments = array_filter($apartments, function($apt) {
                    return $apt['sort_order'] <= 10; // 5-10 là căn hộ mới
                });
                $old_apartments = array_filter($apartments, function($apt) {
                    return $apt['sort_order'] > 10; // 11-13 là căn hộ cũ
                });
                ?>
                
                <!-- Căn hộ mới -->
                <?php if (!empty($new_apartments)): ?>
                    <div class="mb-12">
                        <div class="flex items-center gap-3 mb-6">
                            <h2 class="text-2xl font-bold" style="color: #d4af37;"><?php _e('apartments_page.new_apartments'); ?></h2>
                            <span class="px-3 py-1 bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white text-sm font-semibold rounded-full">
                                <?php _e('apartments_page.apartments_count', ['count' => count($new_apartments)]); ?>
                            </span>
                        </div>
                        <div class="apartments-grid">
                            <?php foreach ($new_apartments as $apt): 
                                $amenities = !empty($apt['amenities']) ? explode(',', $apt['amenities']) : [];
                                $amenities = array_slice($amenities, 0, 8);
                                $thumbnail = normalizeImagePath($apt['thumbnail']);
                                $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                            ?>
                        <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 dark:border-slate-700">
                            <!-- Image -->
                            <div class="relative h-56 overflow-hidden">
                                <?php if ($apt['thumbnail']): ?>
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>" 
                                         alt="<?php echo htmlspecialchars($apt['type_name']); ?>" 
                                         class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-6xl text-gray-400">apartment</span>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-4 right-4 px-3 py-1.5 bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white text-xs font-bold rounded-full shadow-lg">
                                    <?php _e('apartments_page.apartment'); ?>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="p-6">
                                <!-- Title & Description -->
                                <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($apt['type_name']); ?>
                                </h3>
                                <?php if ($apt['short_description']): ?>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                        <?php echo htmlspecialchars($apt['short_description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <!-- Features -->
                                <div class="flex flex-wrap gap-3 mb-4 pb-4 border-b border-gray-200 dark:border-slate-700">
                                    <?php if ($apt['bed_type']): ?>
                                        <div class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                                            <span class="material-symbols-outlined text-lg" style="color: #d4af37;">bed</span>
                                            <span><?php echo htmlspecialchars($apt['bed_type']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($apt['size_sqm']): ?>
                                        <div class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                                            <span class="material-symbols-outlined text-lg" style="color: #d4af37;">square_foot</span>
                                            <span><?php echo number_format($apt['size_sqm'], 0); ?>m²</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="material-symbols-outlined text-lg" style="color: #d4af37;">person</span>
                                        <span><?php echo $apt['max_occupancy']; ?> <?php _e('apartments_page.guests'); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Amenities (Top 4 only) -->
                                <?php if (!empty($amenities)): 
                                    $top_amenities = array_slice($amenities, 0, 4);
                                ?>
                                    <div class="mb-4">
                                        <div class="grid grid-cols-2 gap-2">
                                            <?php foreach ($top_amenities as $amenity): ?>
                                                <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                                    <span class="material-symbols-outlined text-sm text-green-500">check_circle</span>
                                                    <span class="truncate"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (count($amenities) > 4): ?>
                                            <p class="text-xs text-gray-500 mt-2"><?php _e('apartments_page.other_amenities', ['count' => count($amenities) - 4]); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Price & Actions -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-700">
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1"><?php _e('apartments_page.price_from'); ?></p>
                                        <p class="text-2xl font-bold" style="color: #d4af37;">
                                            <?php echo number_format($apt['base_price'], 0, ',', '.'); ?>đ
                                            <span class="text-sm font-normal text-gray-500"><?php _e('common.per_night'); ?></span>
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="booking/index.php?room_type=<?php echo $apt['slug']; ?>" 
                                           class="px-4 py-2 bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white text-sm font-semibold rounded-lg hover:shadow-lg transition-all">
                                            <?php _e('apartments_page.book'); ?>
                                        </a>
                                        <a href="apartment-details/<?php echo $apt['slug']; ?>.php" 
                                           class="px-4 py-2 border-2 border-[#d4af37] text-[#d4af37] text-sm font-semibold rounded-lg hover:bg-[#d4af37] hover:text-white transition-all">
                                            <?php _e('apartments_page.details'); ?>
                                        </a>
                                    </div>
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
                        <div class="flex items-center gap-3 mb-6">
                            <h2 class="text-2xl font-bold text-gray-700 dark:text-gray-300"><?php _e('apartments_page.old_apartments'); ?></h2>
                            <span class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-full">
                                <?php _e('apartments_page.apartments_count', ['count' => count($old_apartments)]); ?>
                            </span>
                        </div>
                        <div class="apartments-grid">
                            <?php foreach ($old_apartments as $apt): 
                                $amenities = !empty($apt['amenities']) ? explode(',', $apt['amenities']) : [];
                                $amenities = array_slice($amenities, 0, 8);
                                $thumbnail = normalizeImagePath($apt['thumbnail']);
                                $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                            ?>
                        <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 dark:border-slate-700">
                            <!-- Image -->
                            <div class="relative h-56 overflow-hidden">
                                <?php if ($apt['thumbnail']): ?>
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>" 
                                         alt="<?php echo htmlspecialchars($apt['type_name']); ?>" 
                                         class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-6xl text-gray-400">apartment</span>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-4 right-4 px-3 py-1.5 bg-gray-500 text-white text-xs font-bold rounded-full shadow-lg">
                                    <?php _e('apartments_page.old_apartment'); ?>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="p-6">
                                <!-- Title & Description -->
                                <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($apt['type_name']); ?>
                                </h3>
                                <?php if ($apt['short_description']): ?>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                        <?php echo htmlspecialchars($apt['short_description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <!-- Features -->
                                <div class="flex flex-wrap gap-3 mb-4 pb-4 border-b border-gray-200 dark:border-slate-700">
                                    <?php if ($apt['bed_type']): ?>
                                        <div class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                                            <span class="material-symbols-outlined text-lg" style="color: #d4af37;">bed</span>
                                            <span><?php echo htmlspecialchars($apt['bed_type']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($apt['size_sqm']): ?>
                                        <div class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                                            <span class="material-symbols-outlined text-lg" style="color: #d4af37;">square_foot</span>
                                            <span><?php echo number_format($apt['size_sqm'], 0); ?>m²</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="material-symbols-outlined text-lg" style="color: #d4af37;">person</span>
                                        <span><?php echo $apt['max_occupancy']; ?> <?php _e('apartments_page.guests'); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Amenities (Top 4 only) -->
                                <?php if (!empty($amenities)): 
                                    $top_amenities = array_slice($amenities, 0, 4);
                                ?>
                                    <div class="mb-4">
                                        <div class="grid grid-cols-2 gap-2">
                                            <?php foreach ($top_amenities as $amenity): ?>
                                                <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                                    <span class="material-symbols-outlined text-sm text-green-500">check_circle</span>
                                                    <span class="truncate"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (count($amenities) > 4): ?>
                                            <p class="text-xs text-gray-500 mt-2"><?php _e('apartments_page.other_amenities', ['count' => count($amenities) - 4]); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Price & Actions -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-700">
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1"><?php _e('apartments_page.price_from'); ?></p>
                                        <p class="text-2xl font-bold" style="color: #d4af37;">
                                            <?php echo number_format($apt['base_price'], 0, ',', '.'); ?>đ
                                            <span class="text-sm font-normal text-gray-500"><?php _e('common.per_night'); ?></span>
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="booking/index.php?room_type=<?php echo $apt['slug']; ?>" 
                                           class="px-4 py-2 bg-gradient-to-r from-[#d4af37] to-[#b8941f] text-white text-sm font-semibold rounded-lg hover:shadow-lg transition-all">
                                            <?php _e('apartments_page.book'); ?>
                                        </a>
                                        <a href="apartment-details/<?php echo $apt['slug']; ?>.php" 
                                           class="px-4 py-2 border-2 border-[#d4af37] text-[#d4af37] text-sm font-semibold rounded-lg hover:bg-[#d4af37] hover:text-white transition-all">
                                            <?php _e('apartments_page.details'); ?>
                                        </a>
                                    </div>
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
</main>

<?php include 'includes/footer.php'; ?>
</div>

</body>
</html>
