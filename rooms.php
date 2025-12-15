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
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php _e('rooms_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<link rel="stylesheet" href="assets/css/rooms.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-rooms">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="badge-liquid-glass mb-6">
                <span class="material-symbols-outlined text-accent">hotel</span>
                <?php _e('rooms_page.premium_rooms'); ?>
            </span>
            <h1 class="page-title"><?php _e('rooms_page.page_title'); ?></h1>
            <p class="page-subtitle"><?php _e('rooms_page.page_subtitle'); ?></p>
            <div class="flex flex-wrap gap-4 justify-center mt-8">
                <a href="booking/index.php" class="btn-liquid-primary">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <?php _e('rooms_page.book_now'); ?>
                </a>
                <a href="#rooms-list" class="btn-liquid-glass">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    <?php _e('rooms_page.view_list'); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Rooms Section -->
    <section id="rooms-list" class="section-padding">
        <div class="container-custom">
            <!-- Rooms Grid -->
            <div class="rooms-grid">
                <?php if (empty($room_types)): ?>
                    <div class="col-span-full text-center py-12">
                        <p class="text-gray-500 text-lg"><?php _e('rooms_page.no_rooms'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($room_types as $room): 
                        // Parse amenities
                        $amenities = !empty($room['amenities']) ? explode(',', $room['amenities']) : [];
                        $amenities = array_slice($amenities, 0, 6); // Chỉ hiển thị 6 tiện nghi đầu
                        $thumbnail = normalizeImagePath($room['thumbnail']);
                        $imageUrl = dirname($_SERVER['PHP_SELF']) . $thumbnail;
                    ?>
                        <div class="room-card" data-category="<?php echo $room['category']; ?>">
                            <div class="room-image-wrapper">
                                <?php if ($room['thumbnail']): ?>
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>?v=<?php echo time(); ?>" 
                                         alt="<?php echo htmlspecialchars($room['type_name']); ?>" 
                                         class="room-image">
                                <?php else: ?>
                                    <div class="room-image bg-gray-200 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-6xl text-gray-400">hotel</span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($room['category'] === 'apartment'): ?>
                                    <div class="room-badge"><?php _e('rooms_page.apartment_badge'); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="room-content">
                                <h3 class="room-title"><?php echo htmlspecialchars($room['type_name']); ?></h3>
                                
                                <?php if ($room['short_description']): ?>
                                    <p class="room-description">
                                        <?php echo htmlspecialchars($room['short_description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="room-features">
                                    <?php if ($room['bed_type']): ?>
                                        <span class="feature-item">
                                            <span class="material-symbols-outlined">bed</span>
                                            <?php echo htmlspecialchars($room['bed_type']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($room['size_sqm']): ?>
                                        <span class="feature-item">
                                            <span class="material-symbols-outlined">square_foot</span>
                                            <?php echo number_format($room['size_sqm'], 0); ?> m²
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="feature-item">
                                        <span class="material-symbols-outlined">person</span>
                                        <?php echo $room['max_occupancy']; ?> <?php _e('rooms_page.guests'); ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($amenities)): ?>
                                    <div class="room-amenities">
                                        <h4 class="amenities-title"><?php _e('rooms_page.amenities'); ?></h4>
                                        <div class="amenities-list">
                                            <?php foreach ($amenities as $amenity): ?>
                                                <div class="amenity-item"><?php echo htmlspecialchars(trim($amenity)); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="room-footer">
                                    <div class="room-price">
                                        <span class="price-label"><?php _e('rooms_page.price_from'); ?></span>
                                        <div>
                                            <span class="price-amount"><?php echo number_format($room['base_price'], 0, ',', '.'); ?>đ</span>
                                            <span class="price-unit"><?php _e('common.per_night'); ?></span>
                                        </div>
                                    </div>
                                    <div class="room-actions">
                                        <a href="booking/index.php?room_type=<?php echo $room['slug']; ?>" class="btn-book-now"><?php _e('rooms_page.book'); ?></a>
                                        <a href="room-details/<?php echo $room['slug']; ?>.php" class="btn-view-details">
                                            <?php _e('rooms_page.view_details'); ?>
                                            <span class="material-symbols-outlined">arrow_forward</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

<script>
// Filter functionality
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        // Remove active from all
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        // Add active to clicked
        this.classList.add('active');
    });
});
</script>

</body>
</html>
