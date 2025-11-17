<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Căn hộ - Aurora Hotel Plaza</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
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
            <div class="new-badge">
                <span class="material-symbols-outlined">new_releases</span>
                Mới ra mắt
            </div>
            <h1 class="page-title">Căn hộ dịch vụ</h1>
            <p class="page-subtitle">Không gian sống hiện đại với đầy đủ tiện nghi như ở nhà, phù hợp cho lưu trú dài ngày</p>
        </div>
    </section>

    <!-- Apartments Section -->
    <section class="section-padding">
        <div class="container-custom">
            <!-- Intro -->
            <div class="intro-section">
                <h2 class="intro-title">Căn hộ dịch vụ cao cấp</h2>
                <p class="intro-description">
                    Aurora Hotel Plaza tự hào giới thiệu dòng căn hộ dịch vụ cao cấp với không gian rộng rãi, 
                    thiết kế hiện đại và đầy đủ tiện nghi. Phù hợp cho gia đình, nhóm bạn hoặc khách lưu trú dài ngày.
                </p>
            </div>

            <!-- New Apartments Section -->
            <div class="new-apartments-section">
                <div class="section-header">
                    <span class="section-badge">
                        <span class="material-symbols-outlined">new_releases</span>
                        Mới ra mắt
                    </span>
                    <h2 class="section-title-main">Bộ sưu tập căn hộ mới</h2>
                    <p class="section-description">Khám phá 6 phong cách thiết kế độc đáo với 3 concept: Classical, Indochine và Modern</p>
                </div>

                <div class="new-apartments-grid">
                    <!-- Classical Family Apartment -->
                    <div class="new-apartment-card">
                        <div class="new-apartment-image-wrapper">
                            <img src="assets/img/classical family apartment/classical-family-apartment1.jpg" alt="Classical Family Apartment" class="new-apartment-image">
                            <div class="new-apartment-badge">Classical</div>
                        </div>
                        <div class="new-apartment-content">
                            <h3 class="new-apartment-title">Classical Family Apartment</h3>
                            <p class="new-apartment-description">Căn hộ gia đình phong cách cổ điển sang trọng 100m²</p>
                            <div class="new-apartment-specs">
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">bed</span>
                                    2 phòng ngủ
                                </span>
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">square_foot</span>
                                    100 m²
                                </span>
                            </div>
                            <div class="new-apartment-footer">
                                <div class="new-price">
                                    <span class="new-price-amount">6.800.000đ</span>
                                    <span class="new-price-unit">/đêm</span>
                                </div>
                                <a href="apartment-details/classical-family.php" class="btn-new-book">Đặt ngay</a>
                            </div>
                        </div>
                    </div>

                    <!-- Classical Premium Apartment -->
                    <div class="new-apartment-card">
                        <div class="new-apartment-image-wrapper">
                            <img src="assets/img/classical premium apartment/classical-premium-apartment-1.jpg" alt="Classical Premium Apartment" class="new-apartment-image">
                            <div class="new-apartment-badge">Classical</div>
                        </div>
                        <div class="new-apartment-content">
                            <h3 class="new-apartment-title">Classical Premium Apartment</h3>
                            <p class="new-apartment-description">Căn hộ cao cấp phong cách cổ điển 75m²</p>
                            <div class="new-apartment-specs">
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">bed</span>
                                    1 phòng ngủ
                                </span>
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">square_foot</span>
                                    75 m²
                                </span>
                            </div>
                            <div class="new-apartment-footer">
                                <div class="new-price">
                                    <span class="new-price-amount">4.800.000đ</span>
                                    <span class="new-price-unit">/đêm</span>
                                </div>
                                <a href="apartment-details/classical-premium.php" class="btn-new-book">Đặt ngay</a>
                            </div>
                        </div>
                    </div>

                    <!-- Indochine Family Apartment -->
                    <div class="new-apartment-card">
                        <div class="new-apartment-image-wrapper">
                            <img src="assets/img/indochine family apartment/indochine-family-apartment-1.jpg" alt="Indochine Family Apartment" class="new-apartment-image">
                            <div class="new-apartment-badge">Indochine</div>
                        </div>
                        <div class="new-apartment-content">
                            <h3 class="new-apartment-title">Indochine Family Apartment</h3>
                            <p class="new-apartment-description">Căn hộ gia đình phong cách Đông Dương 105m²</p>
                            <div class="new-apartment-specs">
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">bed</span>
                                    2 phòng ngủ
                                </span>
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">square_foot</span>
                                    105 m²
                                </span>
                            </div>
                            <div class="new-apartment-footer">
                                <div class="new-price">
                                    <span class="new-price-amount">7.200.000đ</span>
                                    <span class="new-price-unit">/đêm</span>
                                </div>
                                <a href="apartment-details/indochine-family.php" class="btn-new-book">Đặt ngay</a>
                            </div>
                        </div>
                    </div>

                    <!-- Indochine Studio Apartment -->
                    <div class="new-apartment-card">
                        <div class="new-apartment-image-wrapper">
                            <img src="assets/img/indochine studio apartment/indochine-studio-apartment-1.jpg" alt="Indochine Studio Apartment" class="new-apartment-image">
                            <div class="new-apartment-badge">Indochine</div>
                        </div>
                        <div class="new-apartment-content">
                            <h3 class="new-apartment-title">Indochine Studio Apartment</h3>
                            <p class="new-apartment-description">Studio phong cách Đông Dương 50m²</p>
                            <div class="new-apartment-specs">
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">bed</span>
                                    Studio
                                </span>
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">square_foot</span>
                                    50 m²
                                </span>
                            </div>
                            <div class="new-apartment-footer">
                                <div class="new-price">
                                    <span class="new-price-amount">2.800.000đ</span>
                                    <span class="new-price-unit">/đêm</span>
                                </div>
                                <a href="apartment-details/indochine-studio.php" class="btn-new-book">Đặt ngay</a>
                            </div>
                        </div>
                    </div>

                    <!-- Modern Premium Apartment -->
                    <div class="new-apartment-card">
                        <div class="new-apartment-image-wrapper">
                            <img src="assets/img/modern premium apartment/modern-premium-apartment-1.jpg" alt="Modern Premium Apartment" class="new-apartment-image">
                            <div class="new-apartment-badge">Modern</div>
                        </div>
                        <div class="new-apartment-content">
                            <h3 class="new-apartment-title">Modern Premium Apartment</h3>
                            <p class="new-apartment-description">Căn hộ cao cấp phong cách hiện đại 80m²</p>
                            <div class="new-apartment-specs">
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">bed</span>
                                    1 phòng ngủ
                                </span>
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">square_foot</span>
                                    80 m²
                                </span>
                            </div>
                            <div class="new-apartment-footer">
                                <div class="new-price">
                                    <span class="new-price-amount">5.200.000đ</span>
                                    <span class="new-price-unit">/đêm</span>
                                </div>
                                <a href="apartment-details/modern-premium.php" class="btn-new-book">Đặt ngay</a>
                            </div>
                        </div>
                    </div>

                    <!-- Modern Studio Apartment -->
                    <div class="new-apartment-card">
                        <div class="new-apartment-image-wrapper">
                            <img src="assets/img/modern studio apartment/modern-studio-apartment-1.jpg" alt="Modern Studio Apartment" class="new-apartment-image">
                            <div class="new-apartment-badge">Modern</div>
                        </div>
                        <div class="new-apartment-content">
                            <h3 class="new-apartment-title">Modern Studio Apartment</h3>
                            <p class="new-apartment-description">Studio phong cách hiện đại tối giản 48m²</p>
                            <div class="new-apartment-specs">
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">bed</span>
                                    Studio
                                </span>
                                <span class="spec-item">
                                    <span class="material-symbols-outlined">square_foot</span>
                                    48 m²
                                </span>
                            </div>
                            <div class="new-apartment-footer">
                                <div class="new-price">
                                    <span class="new-price-amount">2.600.000đ</span>
                                    <span class="new-price-unit">/đêm</span>
                                </div>
                                <a href="apartment-details/modern-studio.php" class="btn-new-book">Đặt ngay</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Classic Apartments Section -->
            <div class="classic-apartments-section">
                <div class="section-header">
                    <h2 class="section-title-main">Căn hộ tiêu chuẩn</h2>
                    <p class="section-description">Các căn hộ dịch vụ với tiện nghi đầy đủ, phù hợp cho mọi nhu cầu lưu trú</p>
                </div>

            <!-- Apartments Grid -->
            <div class="apartments-grid">
                <!-- Studio Apartment -->
                <div class="apartment-card">
                    <div class="apartment-image-wrapper">
                        <img src="assets/img/studio apartment/CAN-HO-STUDIO-AURORA-HOTEL-1.jpg" alt="Studio Apartment" class="apartment-image">
                        <div class="apartment-badge">Tiết kiệm</div>
                    </div>
                    <div class="apartment-content">
                        <h3 class="apartment-title">Studio Apartment</h3>
                        <p class="apartment-description">
                            Căn hộ studio 45m² với không gian mở, bếp nhỏ và đầy đủ tiện nghi. Lý tưởng cho 1-2 người.
                        </p>
                        <div class="apartment-features">
                            <div class="feature-item">
                                <span class="material-symbols-outlined">bed</span>
                                <div>
                                    <div class="feature-label">Phòng ngủ</div>
                                    <div class="feature-value">1 giường King</div>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="material-symbols-outlined">square_foot</span>
                                <div>
                                    <div class="feature-label">Diện tích</div>
                                    <div class="feature-value">45 m²</div>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="material-symbols-outlined">person</span>
                                <div>
                                    <div class="feature-label">Sức chứa</div>
                                    <div class="feature-value">1-2 người</div>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="material-symbols-outlined">countertops</span>
                                <div>
                                    <div class="feature-label">Bếp</div>
                                    <div class="feature-value">Bếp nhỏ</div>
                                </div>
                            </div>
                        </div>
                        <div class="apartment-amenities">
                            <h4 class="amenities-title">Tiện nghi đầy đủ</h4>
                            <div class="amenities-grid">
                                <div class="amenity-item">Bếp điện từ</div>
                                <div class="amenity-item">Tủ lạnh</div>
                                <div class="amenity-item">Máy giặt</div>
                                <div class="amenity-item">Smart TV</div>
                                <div class="amenity-item">WiFi cao tốc</div>
                                <div class="amenity-item">Điều hòa</div>
                                <div class="amenity-item">Bàn làm việc</div>
                                <div class="amenity-item">Dụng cụ nấu ăn</div>
                            </div>
                        </div>
                        <div class="apartment-footer">
                            <div class="apartment-price">
                                <span class="price-label">Giá từ</span>
                                <div>
                                    <span class="price-amount">2.500.000đ</span>
                                    <span class="price-unit">/đêm</span>
                                </div>
                                <span class="price-note">Giảm 20% cho thuê từ 7 ngày</span>
                            </div>
                            <a href="apartment-details/studio.php" class="btn-book-now">
                                <span class="material-symbols-outlined">calendar_month</span>
                                Đặt ngay
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Premium Apartment -->
                <div class="apartment-card featured">
                    <div class="apartment-image-wrapper">
                        <img src="assets/img/premium apartment/CAN-HO-PREMIUM-AURORA-HOTEL-1.jpg" alt="Premium Apartment" class="apartment-image">
                        <div class="apartment-badge">Phổ biến</div>
                    </div>
                    <div class="apartment-content">
                        <h3 class="apartment-title">Premium Apartment</h3>
                        <p class="apartment-description">
                            Căn hộ cao cấp 70m² với 1 phòng ngủ riêng, phòng khách rộng rãi và bếp đầy đủ tiện nghi.
                        </p>
                        <div class="apartment-features">
                            <div class="feature-item">
                                <span class="material-symbols-outlined">bed</span>
                                <div>
                                    <div class="feature-label">Phòng ngủ</div>
                                    <div class="feature-value">1 phòng + Sofa bed</div>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="material-symbols-outlined">square_foot</span>
                                <div>
                                    <div class="feature-label">Diện tích</div>
                                    <div class="feature-value">70 m²</div>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="material-symbols-outlined">person</span>
                                <div>
                                    <div class="feature-label">Sức chứa</div>
                                    <div class="feature-value">2-4 người</div>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="material-symbols-outlined">countertops</span>
                                <div>
                                    <div class="feature-label">Bếp</div>
                                    <div class="feature-value">Bếp đầy đủ</div>
                                </div>
                            </div>
                        </div>
                        <div class="apartment-amenities">
                            <h4 class="amenities-title">Tiện nghi cao cấp</h4>
                            <div class="amenities-grid">
                                <div class="amenity-item">Bếp hiện đại</div>
                                <div class="amenity-item">Tủ lạnh lớn</div>
                                <div class="amenity-item">Máy giặt sấy</div>
                                <div class="amenity-item">Smart TV 55"</div>
                                <div class="amenity-item">WiFi 100Mbps</div>
                                <div class="amenity-item">2 điều hòa</div>
                                <div class="amenity-item">Bàn ăn 4 người</div>
                                <div class="amenity-item">Ban công riêng</div>
                            </div>
                        </div>
                        <div class="apartment-footer">
                            <div class="apartment-price">
                                <span class="price-label">Giá từ</span>
                                <div>
                                    <span class="price-amount">4.200.000đ</span>
                                    <span class="price-unit">/đêm</span>
                                </div>
                                <span class="price-note">Giảm 25% cho thuê từ 7 ngày</span>
                            </div>
                            <a href="apartment-details/premium.php" class="btn-book-now">
                                <span class="material-symbols-outlined">calendar_month</span>
                                Đặt ngay
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Family Apartment -->
                <div class="apartment-card">
                    <div class="apartment-image-wrapper">
                        <img src="assets/img/family apartment/CAN-HO-FAMILY-AURORA-HOTEL-3.jpg" alt="Family Apartment" class="apartment-image">
                        <div class="apartment-badge">Gia đình</div>
                    </div>
                    <div class="apartment-content">
                        <h3 class="apartment-title">Family Apartment</h3>
                        <p class="apartment-description">
                            Căn hộ rộng rãi 100m² với 2 phòng ngủ, phòng khách lớn và bếp đầy đủ. Hoàn hảo cho gia đình.
                        </p>
                        <div class="apartment-features">
                            <div class="feature-item">
                                <span class="material-symbols-outlined">bed</span>
                                <div>
                                    <div class="feature-label">Phòng ngủ</div>
                                    <div class="feature-value">2 phòng ngủ</div>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="material-symbols-outlined">square_foot</span>
                                <div>
                                    <div class="feature-label">Diện tích</div>
                                    <div class="feature-value">100 m²</div>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="material-symbols-outlined">person</span>
                                <div>
                                    <div class="feature-label">Sức chứa</div>
                                    <div class="feature-value">4-6 người</div>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="material-symbols-outlined">countertops</span>
                                <div>
                                    <div class="feature-label">Bếp</div>
                                    <div class="feature-value">Bếp đầy đủ</div>
                                </div>
                            </div>
                        </div>
                        <div class="apartment-amenities">
                            <h4 class="amenities-title">Tiện nghi gia đình</h4>
                            <div class="amenities-grid">
                                <div class="amenity-item">Bếp hiện đại</div>
                                <div class="amenity-item">2 phòng tắm</div>
                                <div class="amenity-item">Máy giặt sấy</div>
                                <div class="amenity-item">2 Smart TV</div>
                                <div class="amenity-item">WiFi cao tốc</div>
                                <div class="amenity-item">3 điều hòa</div>
                                <div class="amenity-item">Bàn ăn 6 người</div>
                                <div class="amenity-item">Ban công lớn</div>
                            </div>
                        </div>
                        <div class="apartment-footer">
                            <div class="apartment-price">
                                <span class="price-label">Giá từ</span>
                                <div>
                                    <span class="price-amount">6.500.000đ</span>
                                    <span class="price-unit">/đêm</span>
                                </div>
                                <span class="price-note">Giảm 30% cho thuê từ 7 ngày</span>
                            </div>
                            <a href="apartment-details/family.php" class="btn-book-now">
                                <span class="material-symbols-outlined">calendar_month</span>
                                Đặt ngay
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits-section">
        <div class="container-custom">
            <h2 class="section-title">Ưu điểm căn hộ dịch vụ</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <span class="material-symbols-outlined">home</span>
                    </div>
                    <h3 class="benefit-title">Như ở nhà</h3>
                    <p class="benefit-description">Không gian riêng tư với đầy đủ tiện nghi sinh hoạt hàng ngày</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <span class="material-symbols-outlined">savings</span>
                    </div>
                    <h3 class="benefit-title">Tiết kiệm</h3>
                    <p class="benefit-description">Giá ưu đãi cho thuê dài ngày, tiết kiệm hơn khách sạn</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <span class="material-symbols-outlined">restaurant</span>
                    </div>
                    <h3 class="benefit-title">Tự nấu ăn</h3>
                    <p class="benefit-description">Bếp đầy đủ tiện nghi để bạn tự do nấu nướng</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <span class="material-symbols-outlined">cleaning_services</span>
                    </div>
                    <h3 class="benefit-title">Dọn phòng</h3>
                    <p class="benefit-description">Dịch vụ dọn phòng định kỳ như khách sạn</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container-custom">
            <div class="cta-content">
                <h2 class="cta-title">Cần tư vấn chọn căn hộ?</h2>
                <p class="cta-description">
                    Liên hệ với chúng tôi để được tư vấn chi tiết về loại căn hộ phù hợp với nhu cầu của bạn
                </p>
                <a href="contact.php" class="cta-button">
                    <span class="material-symbols-outlined">phone</span>
                    Liên hệ ngay
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
