<?php
/**
 * Gallery Page - Aurora Hotel Plaza
 * Thư viện ảnh với phân trang và hiệu ứng liquid glass
 * Dữ liệu lấy từ database
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

// Cấu hình phân trang
$images_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$current_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Lấy dữ liệu từ database
$all_images = [];
$category_counts = [];

try {
    $db = getDB();
    
    // Lấy tất cả ảnh active
    $stmt = $db->prepare("
        SELECT gallery_id, title, image_url as src, category 
        FROM gallery 
        WHERE status = 'active' 
        ORDER BY sort_order ASC, gallery_id ASC
    ");
    $stmt->execute();
    $all_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Đếm số lượng theo category
    $stmt = $db->prepare("
        SELECT category, COUNT(*) as count 
        FROM gallery 
        WHERE status = 'active' 
        GROUP BY category
    ");
    $stmt->execute();
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($counts as $row) {
        $category_counts[$row['category']] = $row['count'];
    }
} catch (Exception $e) {
    error_log("Gallery error: " . $e->getMessage());
    $all_images = [];
}

// Lọc theo danh mục
$filtered_images = $current_category === 'all' 
    ? $all_images 
    : array_filter($all_images, fn($img) => $img['category'] === $current_category);
$filtered_images = array_values($filtered_images);

// Tính toán phân trang
$total_images = count($filtered_images);
$total_pages = max(1, ceil($total_images / $images_per_page));
$current_page = max(1, min($current_page, $total_pages));
$offset = ($current_page - 1) * $images_per_page;
$page_images = array_slice($filtered_images, $offset, $images_per_page);

// Danh mục với số lượng từ database
$categories = [
    'all' => ['name' => 'Tất cả', 'icon' => 'apps', 'count' => count($all_images)],
    'rooms' => ['name' => 'Phòng nghỉ', 'icon' => 'hotel', 'count' => $category_counts['rooms'] ?? 0],
    'apartments' => ['name' => 'Căn hộ', 'icon' => 'apartment', 'count' => $category_counts['apartments'] ?? 0],
    'restaurant' => ['name' => 'Nhà hàng', 'icon' => 'restaurant', 'count' => $category_counts['restaurant'] ?? 0],
    'facilities' => ['name' => 'Tiện nghi', 'icon' => 'fitness_center', 'count' => $category_counts['facilities'] ?? 0],
    'events' => ['name' => 'Sự kiện', 'icon' => 'celebration', 'count' => $category_counts['events'] ?? 0],
    'exterior' => ['name' => 'Ngoại cảnh', 'icon' => 'location_city', 'count' => $category_counts['exterior'] ?? 0],
];

// Category names for display
$category_names = [
    'rooms' => 'Phòng nghỉ',
    'apartments' => 'Căn hộ',
    'restaurant' => 'Nhà hàng',
    'facilities' => 'Tiện nghi',
    'events' => 'Sự kiện',
    'exterior' => 'Ngoại cảnh',
];
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Thư viện ảnh - Aurora Hotel Plaza</title>
<meta name="description" content="Khám phá không gian sang trọng và tiện nghi tại Aurora Hotel Plaza qua bộ sưu tập hình ảnh đẹp nhất">
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Hero Section with Liquid Glass -->
    <section class="relative min-h-[600px] flex items-center justify-center overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0">
            <img src="assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg" alt="Aurora Hotel" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/70"></div>
        </div>
        
        <!-- Liquid Glass Content -->
        <div class="relative z-10 text-center px-4 py-32">
            <!-- Glass Badge -->
            <div class="inline-flex items-center gap-2 px-6 py-3 mb-8 rounded-full 
                        bg-white/10 backdrop-blur-xl border border-white/20 shadow-xl">
                <span class="material-symbols-outlined text-accent">photo_library</span>
                <span class="text-white font-medium">Bộ sưu tập hình ảnh</span>
            </div>
            
            <h1 class="font-heading text-5xl md:text-6xl lg:text-7xl font-bold text-white mb-6 
                       drop-shadow-2xl">
                Thư viện ảnh
            </h1>
            
            <p class="text-xl text-white/90 max-w-2xl mx-auto mb-10 leading-relaxed">
                Khám phá không gian sang trọng và tiện nghi tại Aurora Hotel Plaza 
                qua <?php echo $total_images; ?>+ hình ảnh đẹp nhất
            </p>
            
            <!-- Stats Glass Cards -->
            <div class="flex flex-wrap justify-center gap-4 mb-10">
                <?php foreach (array_slice($categories, 1, 4) as $key => $cat): ?>
                <div class="px-6 py-4 rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20
                            hover:bg-white/20 transition-all duration-300 cursor-pointer group"
                     onclick="window.location.href='?category=<?php echo $key; ?>'">
                    <span class="material-symbols-outlined text-accent text-2xl mb-1 
                                group-hover:scale-110 transition-transform"><?php echo $cat['icon']; ?></span>
                    <p class="text-white font-bold text-lg"><?php echo $cat['count']; ?></p>
                    <p class="text-white/70 text-sm"><?php echo $cat['name']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- CTA Buttons -->
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="#gallery" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl
                                         bg-accent text-white font-bold text-lg
                                         hover:bg-accent/90 transition-all duration-300
                                         shadow-lg shadow-accent/30 hover:shadow-xl hover:shadow-accent/40
                                         hover:-translate-y-1">
                    <span class="material-symbols-outlined">collections</span>
                    Xem thư viện
                </a>
                <a href="booking/index.php" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl
                                                   bg-white/10 backdrop-blur-xl border border-white/30
                                                   text-white font-bold text-lg
                                                   hover:bg-white/20 transition-all duration-300
                                                   hover:-translate-y-1">
                    <span class="material-symbols-outlined">calendar_month</span>
                    Đặt phòng ngay
                </a>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <a href="#gallery" class="w-12 h-12 rounded-full bg-white/10 backdrop-blur-xl 
                                      border border-white/20 flex items-center justify-center
                                      hover:bg-white/20 transition-all">
                <span class="material-symbols-outlined text-white">expand_more</span>
            </a>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="py-20 bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="text-center mb-12">
                <h2 class="font-heading text-3xl md:text-4xl font-bold mb-4">
                    Khám phá Aurora Hotel
                </h2>
                <p class="text-text-secondary-light dark:text-text-secondary-dark max-w-2xl mx-auto">
                    Duyệt qua bộ sưu tập hình ảnh để cảm nhận không gian sang trọng và dịch vụ đẳng cấp
                </p>
            </div>

            <!-- Filter Tabs - Liquid Glass Style -->
            <div class="flex flex-wrap justify-center gap-3 mb-12">
                <?php foreach ($categories as $key => $cat): ?>
                <a href="?category=<?php echo $key; ?>" 
                   class="group inline-flex items-center gap-2 px-5 py-3 rounded-full font-semibold text-sm
                          transition-all duration-300
                          <?php if ($current_category === $key): ?>
                          bg-accent text-white shadow-lg shadow-accent/30
                          <?php else: ?>
                          bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-gray-200 dark:border-gray-700
                          text-gray-600 dark:text-gray-300 hover:border-accent hover:text-accent
                          <?php endif; ?>">
                    <span class="material-symbols-outlined text-lg 
                                <?php echo $current_category === $key ? '' : 'group-hover:scale-110'; ?> 
                                transition-transform"><?php echo $cat['icon']; ?></span>
                    <span><?php echo $cat['name']; ?></span>
                    <span class="px-2 py-0.5 rounded-full text-xs
                                <?php echo $current_category === $key 
                                    ? 'bg-white/20' 
                                    : 'bg-gray-100 dark:bg-gray-700'; ?>">
                        <?php echo $cat['count']; ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Results Info -->
            <div class="flex items-center justify-between mb-8 px-2">
                <p class="text-text-secondary-light dark:text-text-secondary-dark">
                    Hiển thị <span class="font-semibold text-accent"><?php echo count($page_images); ?></span> 
                    trong tổng số <span class="font-semibold"><?php echo $total_images; ?></span> ảnh
                    <?php if ($current_category !== 'all'): ?>
                    - Danh mục: <span class="font-semibold text-accent"><?php echo $categories[$current_category]['name']; ?></span>
                    <?php endif; ?>
                </p>
                <div class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                    Trang <?php echo $current_page; ?>/<?php echo max(1, $total_pages); ?>
                </div>
            </div>

            <!-- Gallery Grid - Masonry Style -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="gallery-grid">
                <?php foreach ($page_images as $index => $image): ?>
                <div class="gallery-card group relative rounded-2xl overflow-hidden cursor-pointer
                            bg-white dark:bg-gray-800 shadow-lg hover:shadow-2xl
                            transform hover:-translate-y-2 transition-all duration-500"
                     data-index="<?php echo $offset + $index; ?>"
                     data-src="<?php echo htmlspecialchars($image['src']); ?>"
                     data-title="<?php echo htmlspecialchars($image['title']); ?>"
                     onclick="openLightbox(this)">
                    
                    <!-- Image Container -->
                    <div class="aspect-[4/3] overflow-hidden">
                        <img src="<?php echo htmlspecialchars($image['src']); ?>" 
                             alt="<?php echo htmlspecialchars($image['title']); ?>"
                             class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700"
                             loading="lazy">
                    </div>
                    
                    <!-- Overlay - Liquid Glass Effect -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent
                                opacity-0 group-hover:opacity-100 transition-all duration-300">
                        
                        <!-- Zoom Icon -->
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                                    w-16 h-16 rounded-full bg-white/20 backdrop-blur-xl border border-white/30
                                    flex items-center justify-center
                                    scale-0 group-hover:scale-100 transition-transform duration-300">
                            <span class="material-symbols-outlined text-white text-3xl">zoom_in</span>
                        </div>
                        
                        <!-- Info -->
                        <div class="absolute bottom-0 left-0 right-0 p-5
                                    transform translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                            <h3 class="text-white font-bold text-lg mb-1">
                                <?php echo htmlspecialchars($image['title']); ?>
                            </h3>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-accent text-sm">
                                    <?php echo $categories[$image['category']]['icon'] ?? 'image'; ?>
                                </span>
                                <span class="text-accent text-sm font-medium">
                                    <?php echo $category_names[$image['category']] ?? 'Khác'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category Badge -->
                    <div class="absolute top-4 left-4 px-3 py-1.5 rounded-full
                                bg-white/20 backdrop-blur-xl border border-white/30
                                text-white text-xs font-semibold
                                opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <?php echo $category_names[$image['category']] ?? 'Khác'; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($page_images)): ?>
            <!-- Empty State -->
            <div class="text-center py-20">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 dark:bg-gray-800 
                            flex items-center justify-center">
                    <span class="material-symbols-outlined text-5xl text-gray-400">image_not_supported</span>
                </div>
                <h3 class="text-xl font-bold mb-2">Không có ảnh nào</h3>
                <p class="text-text-secondary-light dark:text-text-secondary-dark mb-6">
                    Không tìm thấy ảnh trong danh mục này
                </p>
                <a href="?category=all" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl
                                              bg-accent text-white font-semibold hover:bg-accent/90 transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Xem tất cả ảnh
                </a>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php 
            $pages_total = intval($total_pages);
            $page_current = intval($current_page);
            if ($pages_total > 1 && $pages_total < 100): 
                $prev_page = max(1, $page_current - 1);
                $next_page = min($pages_total, $page_current + 1);
                $cat = htmlspecialchars($current_category);
            ?>
            <div class="flex items-center justify-center gap-3 mt-16">
                <!-- Previous -->
                <?php if ($page_current > 1): ?>
                <a href="?category=<?php echo $cat; ?>&page=<?php echo $prev_page; ?>" 
                   class="w-12 h-12 rounded-full flex items-center justify-center 
                          text-gray-600 hover:text-primary transition-all duration-300"
                   style="background: #f9fafb; box-shadow: 4px 4px 10px #d1d5db, -4px -4px 10px #ffffff;">
                    <span class="material-symbols-outlined">chevron_left</span>
                </a>
                <?php endif; ?>
                
                <!-- Page Numbers -->
                <?php for ($i = 1; $i <= $pages_total; $i++): ?>
                    <?php if ($i == $page_current): ?>
                    <!-- Active Page - Border style -->
                    <span class="w-12 h-12 rounded-full flex items-center justify-center font-semibold"
                          style="background: #f9fafb; 
                                 color: #cc9a2c; 
                                 border: 2px solid #d4af37;
                                 box-shadow: 4px 4px 10px #d1d5db, -4px -4px 10px #ffffff;">
                        <?php echo $i; ?>
                    </span>
                    <?php else: ?>
                    <!-- Other Pages - Neumorphism style -->
                    <a href="?category=<?php echo $cat; ?>&page=<?php echo $i; ?>" 
                       class="w-12 h-12 rounded-full flex items-center justify-center font-semibold
                              text-gray-600 dark:text-gray-300 hover:text-primary
                              transition-all duration-300"
                       style="background: #f9fafb;
                              box-shadow: 4px 4px 10px #d1d5db, -4px -4px 10px #ffffff;">
                        <?php echo $i; ?>
                    </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <!-- Next -->
                <?php if ($page_current < $pages_total): ?>
                <a href="?category=<?php echo $cat; ?>&page=<?php echo $next_page; ?>" 
                   class="w-12 h-12 rounded-full flex items-center justify-center 
                          text-gray-600 hover:text-primary transition-all duration-300"
                   style="background: #f9fafb; box-shadow: 4px 4px 10px #d1d5db, -4px -4px 10px #ffffff;">
                    <span class="material-symbols-outlined">chevron_right</span>
                </a>
                <?php endif; ?>="material-symbols-outlined">chevron_right</span>
                </a>
                <?php endif; ?>
            </div>
            
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="relative py-24 overflow-hidden">
        <div class="absolute inset-0">
            <img src="assets/img/hero-banner/aurora-hotel-bien-hoa-3.jpg" alt="Aurora Hotel" 
                 class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-black/80 to-black/60"></div>
        </div>
        
        <div class="relative z-10 max-w-4xl mx-auto px-4 text-center">
            <h2 class="font-heading text-4xl md:text-5xl font-bold text-white mb-6">
                Trải nghiệm không gian thực tế
            </h2>
            <p class="text-xl text-white/80 mb-10 max-w-2xl mx-auto">
                Đặt phòng ngay hôm nay để tận hưởng không gian sang trọng và dịch vụ đẳng cấp 5 sao
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="booking/index.php" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl
                                                   bg-accent text-white font-bold text-lg
                                                   hover:bg-accent/90 transition-all duration-300
                                                   shadow-lg shadow-accent/30 hover:-translate-y-1">
                    <span class="material-symbols-outlined">calendar_month</span>
                    Đặt phòng ngay
                </a>
                <a href="contact.php" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl
                                             bg-white/10 backdrop-blur-xl border border-white/30
                                             text-white font-bold text-lg
                                             hover:bg-white/20 transition-all duration-300
                                             hover:-translate-y-1">
                    <span class="material-symbols-outlined">call</span>
                    Liên hệ tư vấn
                </a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

<!-- Lightbox Modal - Liquid Glass -->
<div id="lightbox" class="lightbox-modal fixed inset-0 z-[9999] hidden p-4 bg-black/95 backdrop-blur-xl" style="display: none;">
    <!-- Close Button -->
    <button onclick="closeLightbox()" 
            class="absolute top-6 right-6 w-12 h-12 rounded-full
                   bg-white/10 backdrop-blur-xl border border-white/20
                   flex items-center justify-center text-white
                   hover:bg-white/20 transition-all duration-300 z-50">
        <span class="material-symbols-outlined text-2xl">close</span>
    </button>
    
    <!-- Navigation -->
    <button onclick="prevImage()" 
            class="absolute left-6 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full
                   bg-white/10 backdrop-blur-xl border border-white/20
                   flex items-center justify-center text-white
                   hover:bg-white/20 transition-all duration-300 z-50">
        <span class="material-symbols-outlined text-3xl">chevron_left</span>
    </button>
    
    <button onclick="nextImage()" 
            class="absolute right-6 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full
                   bg-white/10 backdrop-blur-xl border border-white/20
                   flex items-center justify-center text-white
                   hover:bg-white/20 transition-all duration-300 z-50">
        <span class="material-symbols-outlined text-3xl">chevron_right</span>
    </button>
    
    <!-- Image Container -->
    <div class="max-w-6xl max-h-[85vh] relative">
        <img id="lightbox-image" src="" alt="" 
             class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl">
        
        <!-- Image Info -->
        <div id="lightbox-info" class="absolute bottom-0 left-0 right-0 p-6
                                       bg-gradient-to-t from-black/80 to-transparent rounded-b-lg">
            <h3 id="lightbox-title" class="text-white text-xl font-bold mb-2"></h3>
            <p id="lightbox-counter" class="text-white/70 text-sm"></p>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
// Gallery Lightbox
const lightbox = document.getElementById('lightbox');
const lightboxImage = document.getElementById('lightbox-image');
const lightboxTitle = document.getElementById('lightbox-title');
const lightboxCounter = document.getElementById('lightbox-counter');

let currentIndex = 0;
// Chỉ lấy ảnh của trang hiện tại để lightbox hoạt động đúng
const images = <?php echo json_encode(array_map(fn($img) => ['src' => $img['src'], 'title' => $img['title']], $page_images)); ?>;



function openLightbox(element) {
    // Kiểm tra có ảnh không
    if (!images || images.length === 0) {
        console.log('No images available');
        return;
    }
    
    // Lấy index từ vị trí trong DOM (0-based cho trang hiện tại)
    const cards = Array.from(document.querySelectorAll('.gallery-card'));
    currentIndex = cards.indexOf(element);
    
    if (currentIndex === -1) currentIndex = 0;
    
    updateLightbox();
    lightbox.style.display = 'flex';
    lightbox.style.alignItems = 'center';
    lightbox.style.justifyContent = 'center';
    lightbox.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    lightbox.style.display = 'none';
    lightbox.classList.add('hidden');
    document.body.style.overflow = '';
}

function updateLightbox() {
    if (images[currentIndex]) {
        lightboxImage.src = images[currentIndex].src;
        lightboxImage.alt = images[currentIndex].title;
        lightboxTitle.textContent = images[currentIndex].title;
        // Hiển thị số thứ tự trong trang hiện tại
        lightboxCounter.textContent = `Ảnh ${currentIndex + 1} / ${images.length} (Trang <?php echo $current_page; ?>)`;
    }
}

function nextImage() {
    currentIndex = (currentIndex + 1) % images.length;
    updateLightbox();
}

function prevImage() {
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    updateLightbox();
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (!lightbox.classList.contains('hidden')) {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'ArrowLeft') prevImage();
    }
});

// Click outside to close
lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) closeLightbox();
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
</body>
</html>
