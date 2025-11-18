<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Nhà hàng - Aurora Hotel Plaza</title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/service-detail.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-service" style="background-image: url('assets/img/restaurant/NHA-HANG-AURORA-HOTEL-6.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <h1 class="page-title">Nhà hàng Aurora</h1>
            <p class="page-subtitle">Trải nghiệm ẩm thực đa dạng với không gian sang trọng và phục vụ tận tâm</p>
        </div>
    </section>

    <!-- Service Overview -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="service-overview">
                <div class="overview-content">
                    <h2 class="section-title">Ẩm thực Á - Âu đẳng cấp</h2>
                    <p class="section-description">
                        Nhà hàng Aurora tự hào mang đến thực đơn phong phú với các món ăn Á - Âu được chế biến bởi đội ngũ 
                        đầu bếp giàu kinh nghiệm. Không gian nhà hàng sang trọng, hiện đại với sức chứa lên đến 200 khách, 
                        phù hợp cho cả bữa ăn gia đình và tiệc buffet.
                    </p>
                    <div class="overview-stats">
                        <div class="stat-item">
                            <div class="stat-number">200+</div>
                            <div class="stat-label">Món ăn đa dạng</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">200</div>
                            <div class="stat-label">Chỗ ngồi</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">6-22h</div>
                            <div class="stat-label">Giờ phục vụ</div>
                        </div>
                    </div>
                </div>
                <div class="overview-image">
                    <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-4.jpg" alt="Nhà hàng Aurora">
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features-section">
        <div class="container-custom">
            <h2 class="section-title-center">Đặc sắc ẩm thực</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">restaurant</span>
                    </div>
                    <h3 class="feature-title">Món Á đa dạng</h3>
                    <p class="feature-description">Các món ăn truyền thống Việt Nam và châu Á</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">lunch_dining</span>
                    </div>
                    <h3 class="feature-title">Món Âu tinh tế</h3>
                    <p class="feature-description">Beefsteak, pasta và các món Âu cao cấp</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">set_meal</span>
                    </div>
                    <h3 class="feature-title">Buffet sáng</h3>
                    <p class="feature-description">Buffet sáng miễn phí cho khách lưu trú</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">cake</span>
                    </div>
                    <h3 class="feature-title">Bánh ngọt</h3>
                    <p class="feature-description">Bánh ngọt, tráng miệng tự làm hàng ngày</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">local_bar</span>
                    </div>
                    <h3 class="feature-title">Bar & Cocktail</h3>
                    <p class="feature-description">Đồ uống đa dạng, cocktail pha chế chuyên nghiệp</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">room_service</span>
                    </div>
                    <h3 class="feature-title">Room Service</h3>
                    <p class="feature-description">Giao đồ ăn tận phòng 24/7</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Menu Highlights -->
    <section class="section-padding">
        <div class="container-custom">
            <h2 class="section-title-center">Thực đơn nổi bật</h2>
            <div class="packages-grid">
                <div class="package-card">
                    <div class="package-header">
                        <h3 class="package-name">Set Á</h3>
                        <div class="package-price">
                            <span class="price-amount">350.000đ</span>
                            <span class="price-unit">/người</span>
                        </div>
                    </div>
                    <ul class="package-features">
                        <li>Gỏi cuốn tôm thịt</li>
                        <li>Phở bò đặc biệt</li>
                        <li>Cơm chiên Dương Châu</li>
                        <li>Canh chua cá lóc</li>
                        <li>Tráng miệng</li>
                        <li>Nước ngọt</li>
                    </ul>
                    <a href="contact.php" class="btn-package">Đặt bàn ngay</a>
                </div>

                <div class="package-card featured">
                    <div class="package-badge">Phổ biến</div>
                    <div class="package-header">
                        <h3 class="package-name">Set Âu</h3>
                        <div class="package-price">
                            <span class="price-amount">450.000đ</span>
                            <span class="price-unit">/người</span>
                        </div>
                    </div>
                    <ul class="package-features">
                        <li>Salad Caesar</li>
                        <li>Soup kem nấm</li>
                        <li>Beefsteak Úc</li>
                        <li>Pasta Carbonara</li>
                        <li>Bánh Tiramisu</li>
                        <li>Nước ép trái cây</li>
                    </ul>
                    <a href="contact.php" class="btn-package">Đặt bàn ngay</a>
                </div>

                <div class="package-card">
                    <div class="package-header">
                        <h3 class="package-name">Buffet Tối</h3>
                        <div class="package-price">
                            <span class="price-amount">550.000đ</span>
                            <span class="price-unit">/người</span>
                        </div>
                    </div>
                    <ul class="package-features">
                        <li>Hơn 50 món Á - Âu</li>
                        <li>Hải sản tươi sống</li>
                        <li>BBQ nướng tại bàn</li>
                        <li>Lẩu Thái</li>
                        <li>Tráng miệng đa dạng</li>
                        <li>Nước uống không giới hạn</li>
                    </ul>
                    <a href="contact.php" class="btn-package">Đặt bàn ngay</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery -->
    <section class="gallery-section">
        <div class="container-custom">
            <h2 class="section-title-center">Hình ảnh nhà hàng</h2>
            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-11.jpg" alt="Nhà hàng">
                </div>
                <div class="gallery-item">
                    <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-10.jpg" alt="Nhà hàng">
                </div>
                <div class="gallery-item">
                    <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-14.jpg" alt="Nhà hàng">
                </div>
                <div class="gallery-item">
                    <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-8.jpg" alt="Nhà hàng">
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container-custom">
            <div class="cta-content">
                <h2 class="cta-title">Đặt bàn ngay hôm nay</h2>
                <p class="cta-description">
                    Liên hệ với chúng tôi để đặt bàn hoặc tư vấn thực đơn
                </p>
                <a href="contact.php" class="cta-button">
                    <span class="material-symbols-outlined">phone</span>
                    Liên hệ ngay: (+84-251) 391.8888
                </a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
