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

        /* Tối ưu hiệu suất cực hạn cho Gallery */
        .gallery-card {
            background: #1e293b !important; /* Màu đặc, không dùng alpha hay blur để tối ưu cuộn */
            border: 1px solid rgba(255, 255, 255, 0.1);
            transform: translateZ(0); /* Ép tăng tốc phần cứng */
            backface-visibility: hidden;
        }

        .gallery-card:hover {
            border-color: #d4af37;
            background: #2d3748 !important;
        }

        /* Gỡ bỏ blur ở các phần tử lặp lại nhiều lần */
        .glass-badge-pill, .hero-glass-card, .glass-pagination-item {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            background: rgba(30, 41, 59, 0.8) !important;
        }

        .gallery-pagination-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
        }

        .gallery-pagination-dot.active {
            background: #d4af37;
        }

        /* Đảm bảo Footer hiển thị */
        footer {
            margin-top: 5rem;
            background: #0f172a !important;
            width: 100%;
            position: relative;
            z-index: 50;
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
                    <?php echo ($lang === 'vi' ? 'Bộ sưu tập' : 'Collection'); ?>
                </div>

                <h1 class="hero-title-glass">
                    <?php _e('gallery_page.page_title'); ?>
                </h1>

                <p class="hero-subtitle-glass">
                    <?php 
                        $subtitle = ($lang === 'vi') 
                            ? "Khám phá không gian sang trọng qua $total_images hình ảnh" 
                            : "Explore our luxury space through $total_images images";
                        echo $subtitle;
                    ?>
                </p>

                <!-- Filter Tabs -->
                <div class="flex flex-wrap justify-center gap-3 mt-8">
                    <?php foreach ($categories as $key => $cat): ?>
                        <a href="?category=<?php echo $key; ?>#gallery" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full font-semibold text-sm
                          transition-all duration-300 border
                          <?php if ($current_category === $key): ?>
                          bg-accent border-accent text-white
                          <?php else: ?>
                          bg-white/10 border-white/20 text-white/70 hover:bg-white/20 hover:text-white
                          <?php endif; ?>">
                            <span class="material-symbols-outlined text-lg"><?php echo $cat['icon']; ?></span>
                            <span><?php echo $cat['name']; ?></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-white/10">
                                <?php echo $cat['count']; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section id="gallery" class="py-16 relative z-10">
            <div class="max-w-7xl mx-auto px-4">

                <!-- Results Info - ĐÃ SỬA LỖI HIỂN THỊ -->
                <div class="flex flex-col md:flex-row items-center justify-between mb-10 gap-4 px-2">
                    <div class="glass-badge-pill">
                        <span class="material-symbols-outlined text-xs">info</span>
                        <span class="text-sm opacity-90">
                            <?php 
                                if ($lang === 'vi') {
                                    echo "Đang hiển thị <strong class='text-accent'>" . count($page_images) . "</strong> trên tổng số <strong>$total_images</strong> ảnh";
                                } else {
                                    echo "Showing <strong class='text-accent'>" . count($page_images) . "</strong> of <strong>$total_images</strong> images";
                                }
                            ?>
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-white/60">
                        <span class="material-symbols-outlined text-sm">pages</span>
                        <span class="font-bold text-white">
                            <?php 
                                $page_text = ($lang === 'vi' ? 'Trang' : 'Page');
                                echo "$page_text $current_page / $total_pages"; 
                            ?>
                        </span>
                    </div>
                </div>

                <!-- Gallery Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($page_images as $index => $image): ?>
                        <div class="gallery-card group relative rounded-2xl overflow-hidden cursor-pointer"
                            onclick="openLightbox(<?php echo $index; ?>)">

                            <div class="aspect-[4/3] overflow-hidden bg-gray-900">
                                <img src="<?php echo htmlspecialchars(imgUrl($image['src'])); ?>"
                                    alt="<?php echo htmlspecialchars($image['title']); ?>"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    loading="lazy">
                            </div>

                            <!-- Overlay tinh giản -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="absolute bottom-0 left-0 right-0 p-5">
                                    <h3 class="text-white font-bold text-sm mb-1"><?php echo htmlspecialchars($image['title']); ?></h3>
                                    <p class="text-accent text-[10px] uppercase font-bold"><?php echo $category_names[$image['category']] ?? ''; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-20 flex flex-col items-center gap-6">
                        <div class="flex items-center gap-2">
                            <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                                <div class="gallery-pagination-dot <?php echo $i == $current_page ? 'active' : ''; ?>"></div>
                            <?php endfor; ?>
                        </div>

                        <div class="flex items-center justify-center gap-2">
                            <?php if ($current_page > 1): ?>
                                <a href="?category=<?php echo $current_category; ?>&page=<?php echo $current_page - 1; ?>#gallery"
                                    class="w-10 h-10 rounded-lg glass-pagination-item">
                                    <span class="material-symbols-outlined">chevron_left</span>
                                </a>
                            <?php endif; ?>

                            <?php
                            $range = 1;
                            for ($i = 1; $i <= $total_pages; $i++):
                                if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)):
                                    $active_class = ($i == $current_page) ? 'active' : '';
                                    ?>
                                    <a href="?category=<?php echo $current_category; ?>&page=<?php echo $i; ?>#gallery"
                                        class="w-10 h-10 rounded-lg glass-pagination-item <?php echo $active_class; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1): ?>
                                    <span class="w-10 h-10 flex items-center justify-center text-white/30">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?category=<?php echo $current_category; ?>&page=<?php echo $current_page + 1; ?>#gallery"
                                    class="w-10 h-10 rounded-lg glass-pagination-item">
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 relative z-10">
            <div class="max-w-7xl mx-auto px-4">
                <div class="glass-cta-box">
                    <h2 class="hero-title-glass" style="font-size: 2rem; margin-bottom: 1rem;">
                        <?php echo ($lang === 'vi' ? 'Sẵn sàng trải nghiệm?' : 'Ready to experience?'); ?>
                    </h2>
                    <div class="flex flex-wrap gap-4 justify-center">
                        <a href="booking/index.php" class="btn-glass-gold px-8 py-3"><?php _e('gallery_page.book_now'); ?></a>
                        <a href="contact.php" class="btn-glass-outline px-8 py-3"><?php echo ($lang === 'vi' ? 'Liên hệ' : 'Contact'); ?></a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="fixed inset-0 z-[9999] hidden bg-black/95 flex items-center justify-center p-4">
        <button onclick="closeLightbox()" class="absolute top-6 right-6 text-white"><span class="material-symbols-outlined text-4xl">close</span></button>
        <button onclick="prevImage()" class="absolute left-6 text-white"><span class="material-symbols-outlined text-5xl">chevron_left</span></button>
        <button onclick="nextImage()" class="absolute right-6 text-white"><span class="material-symbols-outlined text-5xl">chevron_right</span></button>
        <div class="max-w-5xl max-h-full">
            <img id="lightbox-image" src="" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl">
            <div class="mt-4 text-center">
                <h3 id="lightbox-title" class="text-white text-lg font-bold"></h3>
                <p id="lightbox-counter" class="text-white/60 text-sm"></p>
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

        function openLightbox(index) {
            currentIndex = index;
            updateLightbox();
            lightbox.classList.remove('hidden');
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.classList.add('hidden');
            lightbox.style.display = 'none';
            document.body.style.overflow = '';
        }

        function updateLightbox() {
            if (images[currentIndex]) {
                lightboxImage.src = images[currentIndex].src;
                lightboxTitle.textContent = images[currentIndex].title;
                lightboxCounter.textContent = `<?php echo ($lang === 'vi' ? 'Ảnh' : 'Image'); ?> ${currentIndex + 1} / ${images.length}`;
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
    </script>
</body>

</html>
