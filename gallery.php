<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Thư viện ảnh - Aurora Hotel Plaza</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/gallery.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-gallery">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <h1 class="page-title">Thư viện ảnh</h1>
            <p class="page-subtitle">Khám phá không gian sang trọng và tiện nghi tại Aurora Hotel Plaza</p>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="section-padding">
        <div class="container-custom">
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">Tất cả</button>
                <button class="filter-tab" data-filter="rooms">Phòng nghỉ</button>
                <button class="filter-tab" data-filter="restaurant">Nhà hàng</button>
                <button class="filter-tab" data-filter="facilities">Tiện nghi</button>
                <button class="filter-tab" data-filter="events">Sự kiện</button>
            </div>

            <!-- Gallery Grid -->
            <div class="gallery-grid">
                <!-- Rooms -->
                <div class="gallery-item" data-category="rooms">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg" alt="Phòng Deluxe" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Phòng Deluxe</h3>
                            <p class="gallery-category">Phòng nghỉ</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="rooms">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/deluxe/DELUXE-ROOM-AURORA-3.jpg" alt="Phòng Deluxe" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Phòng Deluxe - View 2</h3>
                            <p class="gallery-category">Phòng nghỉ</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="rooms">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/deluxe/DELUXE-ROOM-AURORA-5.jpg" alt="Phòng Deluxe" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Phòng Deluxe - Phòng tắm</h3>
                            <p class="gallery-category">Phòng nghỉ</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <!-- Restaurant -->
                <div class="gallery-item" data-category="restaurant">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-4.jpg" alt="Nhà hàng" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Nhà hàng Aurora</h3>
                            <p class="gallery-category">Nhà hàng</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="restaurant">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-6.jpg" alt="Nhà hàng" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Không gian nhà hàng</h3>
                            <p class="gallery-category">Nhà hàng</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="restaurant">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-11.jpg" alt="Nhà hàng" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Khu vực buffet</h3>
                            <p class="gallery-category">Nhà hàng</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <!-- Facilities -->
                <div class="gallery-item" data-category="facilities">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/src/ui/horizontal/Le_tan_Aurora.jpg" alt="Lễ tân" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Lễ tân</h3>
                            <p class="gallery-category">Tiện nghi</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="facilities">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/src/ui/horizontal/sanh-khach-san-aurora.jpg" alt="Sảnh khách sạn" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Sảnh khách sạn</h3>
                            <p class="gallery-category">Tiện nghi</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="facilities">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/src/ui/horizontal/phong-studio-khach-san-aurora-bien-hoa.jpg" alt="Studio" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Phòng Studio</h3>
                            <p class="gallery-category">Tiện nghi</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <!-- Events -->
                <div class="gallery-item" data-category="events">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/post/wedding/Tiec-cuoi-tai-aurora-5.jpg" alt="Tiệc cưới" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Tiệc cưới</h3>
                            <p class="gallery-category">Sự kiện</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="events">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/src/ui/horizontal/hoi-nghi-khach-san-o-bien-hoa.jpg" alt="Hội nghị" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Phòng hội nghị</h3>
                            <p class="gallery-category">Sự kiện</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="events">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/src/ui/horizontal/Hoi-nghi-aurora-8.jpg" alt="Hội nghị" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Sự kiện hội nghị</h3>
                            <p class="gallery-category">Sự kiện</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>

                <!-- More rooms -->
                <div class="gallery-item" data-category="rooms">
                    <div class="gallery-image-wrapper">
                        <img src="assets/img/deluxe/DELUXE-ROOM-AURORA-7.jpg" alt="Phòng Deluxe" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title">Phòng Deluxe - Giường đôi</h3>
                            <p class="gallery-category">Phòng nghỉ</p>
                        </div>
                        <div class="gallery-zoom-icon">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Load More Button -->
            <div class="load-more-section">
                <button class="btn-load-more">
                    <span class="material-symbols-outlined">expand_more</span>
                    Xem thêm
                </button>
            </div>
        </div>
    </section>
</main>

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
    <div class="lightbox-content">
        <div class="lightbox-close">
            <span class="material-symbols-outlined">close</span>
        </div>
        <img id="lightboxImage" src="" alt="Gallery Image" class="lightbox-image">
        <div class="lightbox-nav lightbox-prev">
            <span class="material-symbols-outlined">chevron_left</span>
        </div>
        <div class="lightbox-nav lightbox-next">
            <span class="material-symbols-outlined">chevron_right</span>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</div>
<script src="assets/js/main.js"></script>
<script src="assets/js/gallery.js"></script>
</body>
</html>
