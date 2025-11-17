<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Văn phòng cho thuê - Aurora Hotel Plaza</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/service-detail.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-service" style="background-image: url('assets/img/src/ui/horizontal/phong-studio-khach-san-aurora-bien-hoa.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <h1 class="page-title">Văn phòng cho thuê</h1>
            <p class="page-subtitle">Không gian làm việc chuyên nghiệp với đầy đủ tiện nghi hiện đại</p>
        </div>
    </section>

    <!-- Service Overview -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="service-overview">
                <div class="overview-content">
                    <h2 class="section-title">Văn phòng hiện đại</h2>
                    <p class="section-description">
                        Aurora Hotel Plaza cung cấp dịch vụ cho thuê văn phòng với diện tích linh hoạt từ 20m² đến 100m², 
                        phù hợp cho startup, doanh nghiệp vừa và nhỏ. Vị trí đắc địa tại trung tâm Biên Hòa, đầy đủ tiện nghi 
                        và dịch vụ hỗ trợ chuyên nghiệp.
                    </p>
                    <div class="overview-stats">
                        <div class="stat-item">
                            <div class="stat-number">20+</div>
                            <div class="stat-label">Văn phòng</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Bảo mật</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">Tiện nghi</div>
                        </div>
                    </div>
                </div>
                <div class="overview-image">
                    <img src="assets/img/deluxe/DELUXE-ROOM-AURORA-2.jpg" alt="Văn phòng">
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features-section">
        <div class="container-custom">
            <h2 class="section-title-center">Tiện ích văn phòng</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">wifi</span>
                    </div>
                    <h3 class="feature-title">Internet tốc độ cao</h3>
                    <p class="feature-description">WiFi và cáp quang 100Mbps ổn định</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">ac_unit</span>
                    </div>
                    <h3 class="feature-title">Điều hòa 24/7</h3>
                    <p class="feature-description">Hệ thống điều hòa trung tâm hiện đại</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">security</span>
                    </div>
                    <h3 class="feature-title">Bảo mật 24/7</h3>
                    <p class="feature-description">Camera an ninh và bảo vệ chuyên nghiệp</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">local_parking</span>
                    </div>
                    <h3 class="feature-title">Bãi đỗ xe</h3>
                    <p class="feature-description">Bãi đỗ xe rộng rãi, an toàn</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">meeting_room</span>
                    </div>
                    <h3 class="feature-title">Phòng họp</h3>
                    <p class="feature-description">Phòng họp chung miễn phí theo giờ</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">cleaning_services</span>
                    </div>
                    <h3 class="feature-title">Vệ sinh</h3>
                    <p class="feature-description">Dịch vụ vệ sinh hàng ngày</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages -->
    <section class="section-padding">
        <div class="container-custom">
            <h2 class="section-title-center">Gói cho thuê văn phòng</h2>
            <div class="packages-grid">
                <div class="package-card">
                    <div class="package-header">
                        <h3 class="package-name">Văn phòng nhỏ</h3>
                        <div class="package-price">
                            <span class="price-amount">8.000.000đ</span>
                            <span class="price-unit">/tháng</span>
                        </div>
                    </div>
                    <ul class="package-features">
                        <li>Diện tích 20-30m²</li>
                        <li>Phù hợp 3-5 người</li>
                        <li>Bàn ghế cơ bản</li>
                        <li>WiFi + Điện + Nước</li>
                        <li>Điều hòa</li>
                        <li>Vệ sinh hàng ngày</li>
                    </ul>
                    <a href="contact.php" class="btn-package">Liên hệ thuê</a>
                </div>

                <div class="package-card featured">
                    <div class="package-badge">Phổ biến</div>
                    <div class="package-header">
                        <h3 class="package-name">Văn phòng vừa</h3>
                        <div class="package-price">
                            <span class="price-amount">15.000.000đ</span>
                            <span class="price-unit">/tháng</span>
                        </div>
                    </div>
                    <ul class="package-features">
                        <li>Diện tích 40-60m²</li>
                        <li>Phù hợp 8-12 người</li>
                        <li>Bàn ghế cao cấp</li>
                        <li>WiFi + Điện + Nước</li>
                        <li>2 Điều hòa</li>
                        <li>Phòng họp riêng</li>
                        <li>Vệ sinh 2 lần/ngày</li>
                        <li>Chỗ đỗ xe ưu tiên</li>
                    </ul>
                    <a href="contact.php" class="btn-package">Liên hệ thuê</a>
                </div>

                <div class="package-card">
                    <div class="package-header">
                        <h3 class="package-name">Văn phòng lớn</h3>
                        <div class="package-price">
                            <span class="price-amount">25.000.000đ</span>
                            <span class="price-unit">/tháng</span>
                        </div>
                    </div>
                    <ul class="package-features">
                        <li>Diện tích 80-100m²</li>
                        <li>Phù hợp 15-20 người</li>
                        <li>Nội thất cao cấp</li>
                        <li>WiFi doanh nghiệp</li>
                        <li>3 Điều hòa</li>
                        <li>2 Phòng họp riêng</li>
                        <li>Pantry riêng</li>
                        <li>Vệ sinh 3 lần/ngày</li>
                        <li>Bãi đỗ xe riêng</li>
                    </ul>
                    <a href="contact.php" class="btn-package">Liên hệ thuê</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container-custom">
            <div class="cta-content">
                <h2 class="cta-title">Đăng ký xem văn phòng</h2>
                <p class="cta-description">
                    Liên hệ với chúng tôi để được tư vấn và xem văn phòng trực tiếp
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
