<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Phòng nghỉ - Aurora Hotel Plaza</title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
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
            <h1 class="page-title">Phòng nghỉ</h1>
            <p class="page-subtitle">Trải nghiệm không gian nghỉ dưỡng sang trọng với đầy đủ tiện nghi hiện đại</p>
        </div>
    </section>

    <!-- Rooms Section -->
    <section class="section-padding">
        <div class="container-custom">
            <!-- Room Categories -->
            <div class="room-categories">
                <button class="category-btn active" data-category="all">Tất cả</button>
                <button class="category-btn" data-category="deluxe">Deluxe</button>
                <button class="category-btn" data-category="premium">Premium</button>
                <button class="category-btn" data-category="vip">VIP</button>
            </div>

            <!-- Rooms Grid -->
            <div class="rooms-grid">
                <!-- Deluxe Room -->
                <div class="room-card" data-category="deluxe">
                    <div class="room-image-wrapper">
                        <img src="assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg" alt="Phòng Deluxe" class="room-image">
                        <div class="room-badge">Phổ biến</div>
                    </div>
                    <div class="room-content">
                        <h3 class="room-title">Phòng Deluxe</h3>
                        <p class="room-description">
                            Phòng rộng 35m² với thiết kế hiện đại, giường King size thoải mái và tầm nhìn thành phố tuyệt đẹp.
                        </p>
                        <div class="room-features">
                            <span class="feature-item">
                                <span class="material-symbols-outlined">bed</span>
                                Giường King
                            </span>
                            <span class="feature-item">
                                <span class="material-symbols-outlined">square_foot</span>
                                35 m²
                            </span>
                            <span class="feature-item">
                                <span class="material-symbols-outlined">person</span>
                                2 người
                            </span>
                        </div>
                        <div class="room-amenities">
                            <h4 class="amenities-title">Tiện nghi</h4>
                            <div class="amenities-list">
                                <div class="amenity-item">WiFi miễn phí</div>
                                <div class="amenity-item">TV màn hình phẳng</div>
                                <div class="amenity-item">Điều hòa</div>
                                <div class="amenity-item">Minibar</div>
                                <div class="amenity-item">Két an toàn</div>
                                <div class="amenity-item">Phòng tắm riêng</div>
                            </div>
                        </div>
                        <div class="room-footer">
                            <div class="room-price">
                                <span class="price-label">Giá từ</span>
                                <div>
                                    <span class="price-amount">1.200.000đ</span>
                                    <span class="price-unit">/đêm</span>
                                </div>
                            </div>
                            <div class="room-actions">
                                <a href="room-details/deluxe.php" class="btn-book-now">Đặt ngay</a>
                                <a href="room-details/deluxe.php" class="btn-view-details">
                                    Xem chi tiết
                                    <span class="material-symbols-outlined">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Premium Deluxe -->
                <div class="room-card" data-category="premium">
                    <div class="room-image-wrapper">
                        <img src="assets/img/premium deluxe/PREMIUM-DELUXE-AURORA-HOTEL-1.jpg" alt="Premium Deluxe" class="room-image">
                    </div>
                    <div class="room-content">
                        <h3 class="room-title">Premium Deluxe</h3>
                        <p class="room-description">
                            Phòng cao cấp 45m² với khu vực sinh hoạt riêng biệt, tầm nhìn panorama và nội thất sang trọng.
                        </p>
                        <div class="room-features">
                            <span class="feature-item">
                                <span class="material-symbols-outlined">bed</span>
                                Giường King
                            </span>
                            <span class="feature-item">
                                <span class="material-symbols-outlined">square_foot</span>
                                45 m²
                            </span>
                            <span class="feature-item">
                                <span class="material-symbols-outlined">person</span>
                                2-3 người
                            </span>
                        </div>
                        <div class="room-amenities">
                            <h4 class="amenities-title">Tiện nghi</h4>
                            <div class="amenities-list">
                                <div class="amenity-item">WiFi cao tốc</div>
                                <div class="amenity-item">Smart TV 55"</div>
                                <div class="amenity-item">Sofa thư giãn</div>
                                <div class="amenity-item">Bàn làm việc</div>
                                <div class="amenity-item">Bồn tắm</div>
                                <div class="amenity-item">Máy pha cà phê</div>
                            </div>
                        </div>
                        <div class="room-footer">
                            <div class="room-price">
                                <span class="price-label">Giá từ</span>
                                <div>
                                    <span class="price-amount">1.800.000đ</span>
                                    <span class="price-unit">/đêm</span>
                                </div>
                            </div>
                            <div class="room-actions">
                                <a href="room-details/premium-deluxe.php" class="btn-book-now">Đặt ngay</a>
                                <a href="room-details/premium-deluxe.php" class="btn-view-details">
                                    Xem chi tiết
                                    <span class="material-symbols-outlined">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VIP Suite -->
                <div class="room-card" data-category="vip">
                    <div class="room-image-wrapper">
                        <img src="assets/img/vip /VIP-ROOM-AURORA-HOTEL-1.jpg" alt="VIP Suite" class="room-image">
                        <div class="room-badge">Cao cấp</div>
                    </div>
                    <div class="room-content">
                        <h3 class="room-title">VIP Suite</h3>
                        <p class="room-description">
                            Suite sang trọng 80m² với phòng khách riêng, ban công rộng và dịch vụ butler 24/7.
                        </p>
                        <div class="room-features">
                            <span class="feature-item">
                                <span class="material-symbols-outlined">bed</span>
                                Giường King
                            </span>
                            <span class="feature-item">
                                <span class="material-symbols-outlined">square_foot</span>
                                80 m²
                            </span>
                            <span class="feature-item">
                                <span class="material-symbols-outlined">person</span>
                                2-4 người
                            </span>
                        </div>
                        <div class="room-amenities">
                            <h4 class="amenities-title">Tiện nghi</h4>
                            <div class="amenities-list">
                                <div class="amenity-item">Phòng khách riêng</div>
                                <div class="amenity-item">Ban công rộng</div>
                                <div class="amenity-item">Bồn tắm Jacuzzi</div>
                                <div class="amenity-item">Butler 24/7</div>
                                <div class="amenity-item">Minibar cao cấp</div>
                                <div class="amenity-item">Hệ thống âm thanh</div>
                            </div>
                        </div>
                        <div class="room-footer">
                            <div class="room-price">
                                <span class="price-label">Giá từ</span>
                                <div>
                                    <span class="price-amount">3.500.000đ</span>
                                    <span class="price-unit">/đêm</span>
                                </div>
                            </div>
                            <div class="room-actions">
                                <a href="room-details/vip-suite.php" class="btn-book-now">Đặt ngay</a>
                                <a href="room-details/vip-suite.php" class="btn-view-details">
                                    Xem chi tiết
                                    <span class="material-symbols-outlined">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Premium Twin -->
                <div class="room-card" data-category="premium">
                    <div class="room-image-wrapper">
                        <img src="assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-1.jpg" alt="Premium Twin" class="room-image">
                    </div>
                    <div class="room-content">
                        <h3 class="room-title">Premium Twin</h3>
                        <p class="room-description">
                            Phòng đôi cao cấp 40m² với 2 giường đơn, phù hợp cho gia đình hoặc nhóm bạn.
                        </p>
                        <div class="room-features">
                            <span class="feature-item">
                                <span class="material-symbols-outlined">bed</span>
                                2 Giường đơn
                            </span>
                            <span class="feature-item">
                                <span class="material-symbols-outlined">square_foot</span>
                                40 m²
                            </span>
                            <span class="feature-item">
                                <span class="material-symbols-outlined">person</span>
                                2-3 người
                            </span>
                        </div>
                        <div class="room-amenities">
                            <h4 class="amenities-title">Tiện nghi</h4>
                            <div class="amenities-list">
                                <div class="amenity-item">WiFi miễn phí</div>
                                <div class="amenity-item">Smart TV</div>
                                <div class="amenity-item">Tủ lạnh mini</div>
                                <div class="amenity-item">Bàn làm việc</div>
                                <div class="amenity-item">Vòi sen massage</div>
                                <div class="amenity-item">Ấm đun nước</div>
                            </div>
                        </div>
                        <div class="room-footer">
                            <div class="room-price">
                                <span class="price-label">Giá từ</span>
                                <div>
                                    <span class="price-amount">1.600.000đ</span>
                                    <span class="price-unit">/đêm</span>
                                </div>
                            </div>
                            <div class="room-actions">
                                <a href="room-details/premium-twin.php" class="btn-book-now">Đặt ngay</a>
                                <a href="room-details/premium-twin.php" class="btn-view-details">
                                    Xem chi tiết
                                    <span class="material-symbols-outlined">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container-custom">
            <div class="cta-content">
                <h2 class="cta-title">Cần tư vấn chọn phòng?</h2>
                <p class="cta-description">
                    Liên hệ với chúng tôi để được tư vấn chi tiết về loại phòng phù hợp với nhu cầu của bạn
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
<script src="assets/js/rooms.js"></script>
</body>
</html>
