<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Tổ chức hội nghị - Aurora Hotel Plaza</title>
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
    <section class="page-header-service" style="background-image: url('assets/img/src/ui/horizontal/hoi-nghi-khach-san-o-bien-hoa.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <h1 class="page-title">Tổ chức hội nghị</h1>
            <p class="page-subtitle">Không gian chuyên nghiệp với đầy đủ trang thiết bị hiện đại cho mọi sự kiện doanh nghiệp</p>
        </div>
    </section>

    <!-- Service Overview -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="service-overview">
                <div class="overview-content">
                    <h2 class="section-title">Phòng hội nghị đẳng cấp</h2>
                    <p class="section-description">
                        Aurora Hotel Plaza cung cấp các phòng hội nghị hiện đại với đầy đủ trang thiết bị công nghệ cao, 
                        phù hợp cho mọi quy mô từ họp nhóm nhỏ đến hội nghị lớn. Đội ngũ kỹ thuật chuyên nghiệp luôn sẵn sàng 
                        hỗ trợ để đảm bảo sự kiện của bạn diễn ra thành công tốt đẹp.
                    </p>
                    <div class="overview-stats">
                        <div class="stat-item">
                            <div class="stat-number">5</div>
                            <div class="stat-label">Phòng hội nghị</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">300</div>
                            <div class="stat-label">Người tối đa</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Hỗ trợ kỹ thuật</div>
                        </div>
                    </div>
                </div>
                <div class="overview-image">
                    <img src="assets/img/src/ui/horizontal/Hoi-nghi-aurora-8.jpg" alt="Hội nghị Aurora">
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features-section">
        <div class="container-custom">
            <h2 class="section-title-center">Tiện nghi hội nghị</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">tv</span>
                    </div>
                    <h3 class="feature-title">Màn hình LED</h3>
                    <p class="feature-description">Màn hình LED lớn, projector độ phân giải cao</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">mic</span>
                    </div>
                    <h3 class="feature-title">Âm thanh chuyên nghiệp</h3>
                    <p class="feature-description">Hệ thống micro không dây, loa chất lượng cao</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">wifi</span>
                    </div>
                    <h3 class="feature-title">WiFi tốc độ cao</h3>
                    <p class="feature-description">Internet tốc độ cao, ổn định cho mọi thiết bị</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">coffee</span>
                    </div>
                    <h3 class="feature-title">Coffee Break</h3>
                    <p class="feature-description">Dịch vụ trà, cà phê và đồ ăn nhẹ</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">ac_unit</span>
                    </div>
                    <h3 class="feature-title">Điều hòa trung tâm</h3>
                    <p class="feature-description">Hệ thống điều hòa hiện đại, mát mẻ</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">support_agent</span>
                    </div>
                    <h3 class="feature-title">Hỗ trợ kỹ thuật</h3>
                    <p class="feature-description">Đội ngũ kỹ thuật viên hỗ trợ suốt sự kiện</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages -->
    <section class="section-padding">
        <div class="container-custom">
            <h2 class="section-title-center">Gói dịch vụ hội nghị</h2>
            <div class="packages-grid">
                <div class="package-card">
                    <div class="package-header">
                        <h3 class="package-name">Gói Nửa ngày</h3>
                        <div class="package-price">
                            <span class="price-amount">5.000.000đ</span>
                            <span class="price-unit">/4 giờ</span>
                        </div>
                    </div>
                    <ul class="package-features">
                        <li>Phòng họp 50-100 người</li>
                        <li>Projector + Màn hình</li>
                        <li>Micro không dây</li>
                        <li>WiFi miễn phí</li>
                        <li>Coffee break 1 lần</li>
                        <li>Bảng Flipchart</li>
                    </ul>
                    <a href="contact.php" class="btn-package">Liên hệ đặt phòng</a>
                </div>

                <div class="package-card featured">
                    <div class="package-badge">Phổ biến</div>
                    <div class="package-header">
                        <h3 class="package-name">Gói Cả ngày</h3>
                        <div class="package-price">
                            <span class="price-amount">9.000.000đ</span>
                            <span class="price-unit">/8 giờ</span>
                        </div>
                    </div>
                    <ul class="package-features">
                        <li>Phòng họp 100-200 người</li>
                        <li>Màn hình LED lớn</li>
                        <li>Hệ thống âm thanh cao cấp</li>
                        <li>WiFi tốc độ cao</li>
                        <li>Coffee break 2 lần</li>
                        <li>Buffet trưa</li>
                        <li>Bảng Flipchart + Bút</li>
                        <li>Hỗ trợ kỹ thuật</li>
                    </ul>
                    <a href="contact.php" class="btn-package">Liên hệ đặt phòng</a>
                </div>

                <div class="package-card">
                    <div class="package-header">
                        <h3 class="package-name">Gói VIP</h3>
                        <div class="package-price">
                            <span class="price-amount">15.000.000đ</span>
                            <span class="price-unit">/ngày</span>
                        </div>
                    </div>
                    <ul class="package-features">
                        <li>Phòng hội nghị 200-300 người</li>
                        <li>Màn hình LED 3D</li>
                        <li>Âm thanh chuyên nghiệp</li>
                        <li>WiFi doanh nghiệp</li>
                        <li>Coffee break 3 lần</li>
                        <li>Buffet trưa + tối</li>
                        <li>Phòng VIP cho ban tổ chức</li>
                        <li>Hỗ trợ kỹ thuật 24/7</li>
                        <li>Ghi hình chuyên nghiệp</li>
                    </ul>
                    <a href="contact.php" class="btn-package">Liên hệ đặt phòng</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container-custom">
            <div class="cta-content">
                <h2 class="cta-title">Đặt phòng hội nghị ngay</h2>
                <p class="cta-description">
                    Liên hệ với chúng tôi để được tư vấn và nhận báo giá chi tiết
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
