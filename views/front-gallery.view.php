<!DOCTYPE html>
<html translate="no" class="light" lang="<?php echo $lang; ?>">

<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('gallery_page.title'); ?></title>
    <meta name="description" content="<?php _e('gallery_page.page_subtitle', ['count' => $total_images]); ?>">
    <link href="assets/css/tailwind-output.css" rel="stylesheet" />
    <link href="assets/css/fonts.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/pages-glass.css">
    <style>
        body.glass-page::before {
            background-image: url('<?php echo imgUrl('assets/img/hero-banner/aurora-hotel-bien-hoa-2.jpg'); ?>');
        }

        /* Tối ưu hiệu suất: Giảm bớt blur và shadow phức tạp */
        .gallery-pagination-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .gallery-pagination-dot.active {
            background: #d4af37;
            transform: scale(1.2);
        }

        /* Gallery Card: Tối ưu để lướt mượt hơn */
        .gallery-card {
            background: rgba(30, 41, 59, 0.5) !important;
            border: 1px solid rgba(255, 255, 255, 0.08);
            /* Bỏ backdrop-filter ở đây để cuộn mượt */
            will-change: transform;
        }

        .gallery-card:hover {
            border-color: rgba(212, 175, 55, 0.3);
            background: rgba(30, 41, 59, 0.7) !important;
        }

        /* Pagination items */
        .glass-pagination-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .glass-pagination-item:hover:not(.active) {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-color: rgba(212, 175, 55, 0.4);
        }

        .glass-pagination-item.active {
            background: #d4af37;
            color: white;
            border-color: #d4af37;
        }

        /* Fix Footer padding */
        footer {
            margin-top: auto;
            position: relative;
            z-index: 20;
        }
    </style>
</head>

<body class="glass-page font-body text-white flex flex-col min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="flex-grow">
        <!-- Hero Section -->
        <section class="page-hero-glass">
            <div class="hero-glass-card animate-fade-in-up">
                <div class="glass-badge-pill mb-4">
                    <span class="material-symbols-outlined text-sm">photo_library</span>
                    <?php _e('gallery_page.collection'); ?>
                </div>

                <h1 class="hero-title-glass">
                    <?php _e('gallery_page.page_title'); ?>
                </h1>

                <p class="hero-subtitle-glass">
                    <?php _e('gallery_page.page_subtitle', ['count' => $total_images]); ?>
                </p>

                <!-- Filter Tabs -->
                <div class="flex flex-wrap justify-center gap-3 mt-8">
                    <?php foreach ($categories as $key => $cat): ?>
                        <a href="?category=<?php echo $key; ?>#gallery" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full font-semibold text-sm
                          transition-all duration-300 border
                          <?php if ($current_category === $key): ?>
                          bg-accent border-accent text-white shadow-lg
                          <?php else: ?>
                          bg-white/5 border-white/10 text-white/70 hover:bg-white/10 hover:border-white/20 hover:text-white
                          <?php endif; ?>">
                            <span class="material-symbols-outlined text-lg"><?php echo $cat['icon']; ?></span>
                            <span><?php echo $cat['name']; ?></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] 
                                <?php echo $current_category === $key ? 'bg-white/20' : 'bg-white/10'; ?>">
                                <?php echo $cat['count']; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section id="gallery" class="py-16 relative z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Results Info - FIXED PAGINATION DISPLAY -->
                <div class="flex flex-col md:flex-row items-center justify-between mb-10 gap-4 px-2">
                    <div class="glass-badge-pill bg-white/5 border-white/10">
                        <span class="material-symbols-outlined text-xs">info</span>
                        <span class="text-sm opacity-90">
                            <?php _e('gallery_page.showing'); ?>
                            <strong class="text-accent"><?php echo count($page_images); ?></strong>
                            <?php _e('gallery_page.of_total'); ?>
                            <strong><?php echo $total_images; ?></strong>
                            <?php _e('gallery_page.images'); ?>
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-white/60">
                        <span class="material-symbols-outlined text-sm">pages</span>
                        <?php echo __('gallery_page.page') . ' ' . $current_page . ' / ' . $total_pages; ?>
                    </div>
                </div>

                <!-- Gallery Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="gallery-grid">
                    <?php foreach ($page_images as $index => $image): ?>
                        <div class="gallery-card group relative rounded-2xl overflow-hidden cursor-pointer
                            transform hover:-translate-y-2 transition-all duration-500"
                            data-index="<?php echo $offset + $index; ?>" onclick="openLightbox(this)">

                            <!-- Image Container -->
                            <div class="aspect-[4/3] overflow-hidden bg-gray-900">
                                <img src="<?php echo htmlspecialchars(imgUrl($image['src'])); ?>"
                                    alt="<?php echo htmlspecialchars($image['title']); ?>"
                                    class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700 opacity-90 group-hover:opacity-100"
                                    loading="lazy">
                            </div>

                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent
                                opacity-0 group-hover:opacity-100 transition-all duration-300">
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                                    w-14 h-14 rounded-full bg-white/10 backdrop-blur-md border border-white/20
                                    flex items-center justify-center scale-0 group-hover:scale-100 transition-transform duration-300">
                                    <span class="material-symbols-outlined text-white text-2xl">zoom_in</span>
                                </div>

                                <div class="absolute bottom-0 left-0 right-0 p-5 transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                                    <h3 class="text-white font-bold text-base mb-1">
                                        <?php echo htmlspecialchars($image['title']); ?>
                                    </h3>
                                    <div class="flex items-center gap-2 opacity-70">
                                        <span class="material-symbols-outlined text-accent text-xs">
                                            <?php echo $categories[$image['category']]['icon'] ?? 'image'; ?>
                                        </span>
                                        <span class="text-accent text-xs font-medium uppercase tracking-wider">
                                            <?php echo $category_names[$image['category']] ?? 'Khác'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($page_images)): ?>
                    <div class="text-center py-20 bg-white/5 rounded-3xl border border-white/10">
                        <span class="material-symbols-outlined text-6xl text-white/20 mb-4">image_not_supported</span>
                        <h3 class="text-xl font-bold mb-2"><?php _e('gallery_page.no_images'); ?></h3>
                        <p class="text-white/60 mb-8 max-w-sm mx-auto">
                            <?php _e('gallery_page.no_images_desc'); ?>
                        </p>
                        <a href="?category=all#gallery" class="btn-glass-gold">
                            <span class="material-symbols-outlined">arrow_back</span>
                            <?php _e('gallery_page.view_all_images'); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Improved Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-20 flex flex-col items-center gap-6">
                        <!-- Dots progression bar -->
                        <div class="flex items-center gap-2">
                            <?php 
                            $dot_limit = min($total_pages, 15);
                            for ($i = 1; $i <= $dot_limit; $i++): ?>
                                <div class="gallery-pagination-dot <?php echo $i == $current_page ? 'active' : ''; ?>"></div>
                            <?php endfor; ?>
                            <?php if ($total_pages > 15): ?>
                                <span class="text-white/30 text-[10px]">...</span>
                            <?php endif; ?>
                        </div>

                        <!-- Numeric Links -->
                        <div class="flex items-center justify-center gap-2">
                            <?php if ($current_page > 1): ?>
                                <a href="?category=<?php echo $current_category; ?>&page=<?php echo $current_page - 1; ?>#gallery"
                                    class="w-10 h-10 rounded-lg glass-pagination-item hover:scale-105 active:scale-95">
                                    <span class="material-symbols-outlined text-lg">chevron_left</span>
                                </a>
                            <?php endif; ?>

                            <?php
                            $range = 1;
                            for ($i = 1; $i <= $total_pages; $i++):
                                if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)):
                                    $active_class = ($i == $current_page) ? 'active' : '';
                                    ?>
                                    <a href="?category=<?php echo $current_category; ?>&page=<?php echo $i; ?>#gallery"
                                        class="w-10 h-10 rounded-lg font-bold text-sm glass-pagination-item <?php echo $active_class; ?> hover:scale-105 active:scale-95">
                                        <?php echo $i; ?>
                                    </a>
                                <?php elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1): ?>
                                    <span class="w-10 h-10 flex items-center justify-center text-white/30">
                                        <span class="material-symbols-outlined text-xs">more_horiz</span>
                                    </span>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?category=<?php echo $current_category; ?>&page=<?php echo $current_page + 1; ?>#gallery"
                                    class="w-10 h-10 rounded-lg glass-pagination-item hover:scale-105 active:scale-95">
                                    <span class="material-symbols-outlined text-lg">chevron_right</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 relative overflow-hidden z-10">
            <div class="max-w-7xl mx-auto px-4">
                <div class="glass-cta-box">
                    <h2 class="hero-title-glass" style="font-size: 2.5rem; margin-bottom: 1rem;">
                        <?php _e('gallery_page.experience_real'); ?>
                    </h2>
                    <p class="hero-subtitle-glass mb-8">
                        <?php _e('gallery_page.experience_desc'); ?>
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center relative z-10">
                        <a href="booking/index.php" class="btn-glass-gold px-8 py-3">
                            <span class="material-symbols-outlined">calendar_month</span>
                            <?php _e('gallery_page.book_now'); ?>
                        </a>
                        <a href="contact.php" class="btn-glass-outline px-8 py-3">
                            <span class="material-symbols-outlined">phone</span>
                            <?php _e('gallery_page.contact_consult'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="fixed inset-0 z-[9999] hidden bg-black/95 backdrop-blur-sm" style="display: none;">
        <button onclick="closeLightbox()" class="absolute top-6 right-6 w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 z-50">
            <span class="material-symbols-outlined">close</span>
        </button>
        <button onclick="prevImage()" class="absolute left-6 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 z-50">
            <span class="material-symbols-outlined text-2xl">chevron_left</span>
        </button>
        <button onclick="nextImage()" class="absolute right-6 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 z-50">
            <span class="material-symbols-outlined text-2xl">chevron_right</span>
        </button>
        <div class="w-full h-full flex items-center justify-center p-4">
            <div class="relative max-w-full max-h-full">
                <img id="lightbox-image" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl">
                <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black to-transparent rounded-b-lg">
                    <h3 id="lightbox-title" class="text-white text-lg font-bold mb-1"></h3>
                    <p id="lightbox-counter" class="text-white/60 text-xs font-medium"></p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/glass-pages.js"></script>
    <script>
        const lightbox = document.getElementById('lightbox');
        const lightboxImage = document.getElementById('lightbox-image');
        const lightboxTitle = document.getElementById('lightbox-title');
        const lightboxCounter = document.getElementById('lightbox-counter');

        let currentIndex = 0;
        const images = <?php echo json_encode(array_map(function ($img) {
            return ['src' => imgUrl($img['src']), 'title' => $img['title']];
        }, $page_images)); ?>;

        function openLightbox(element) {
            if (!images || images.length === 0) return;
            const cards = Array.from(document.querySelectorAll('.gallery-card'));
            currentIndex = cards.indexOf(element);
            updateLightbox();
            lightbox.style.display = 'block';
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
                lightboxTitle.textContent = images[currentIndex].title;
                lightboxCounter.textContent = `Ảnh ${currentIndex + 1} / ${images.length}`;
            }
        }

        function nextImage() { currentIndex = (currentIndex + 1) % images.length; updateLightbox(); }
        function prevImage() { currentIndex = (currentIndex - 1 + images.length) % images.length; updateLightbox(); }

        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('hidden')) {
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowRight') nextImage();
                if (e.key === 'ArrowLeft') prevImage();
            }
        });
        lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });
    </script>
</body>

</html>
