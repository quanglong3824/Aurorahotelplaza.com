<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Phòng Deluxe - Aurora Hotel Plaza</title>
<script src="../assets/js/tailwindcss-cdn.js"></script>
<link href="../assets/css/fonts.css" rel="stylesheet"/>
<script src="../assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/room-detail.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-room" style="background-image: url('../assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header">Phổ biến</span>
            <h1 class="page-title">Phòng Deluxe</h1>
            <p class="page-subtitle">Không gian sang trọng với đầy đủ tiện nghi hiện đại</p>
        </div>
    </section>

    <!-- Room Info -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">
                        Phòng Deluxe rộng 35m² được thiết kế hiện đại với giường King size cao cấp, tầm nhìn thành phố tuyệt đẹp. 
                        Phòng được trang bị đầy đủ tiện nghi như TV màn hình phẳng, minibar, két an toàn và phòng tắm riêng với 
                        vòi sen massage. Đây là lựa chọn hoàn hảo cho cặp đôi hoặc khách công tác.
                    </p>

                    <div class="room-specs">
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">bed</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Loại giường</div>
                                <div class="spec-value">1 Giường King</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">square_foot</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Diện tích</div>
                                <div class="spec-value">35 m²</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Sức chứa</div>
                                <div class="spec-value">2 người</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">visibility</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Tầm nhìn</div>
                                <div class="spec-value">Thành phố</div>
                            </div>
                        </div>
                    </div>

                    <div class="amenities-section">
                        <h3 class="section-title">Tiện nghi phòng</h3>
                        <div class="amenities-grid">
                            <div class="amenity-item">WiFi miễn phí tốc độ cao</div>
                            <div class="amenity-item">TV màn hình phẳng 43"</div>
                            <div class="amenity-item">Điều hòa nhiệt độ</div>
                            <div class="amenity-item">Minibar đầy đủ</div>
                            <div class="amenity-item">Két an toàn</div>
                            <div class="amenity-item">Bàn làm việc</div>
                            <div class="amenity-item">Phòng tắm riêng</div>
                            <div class="amenity-item">Vòi sen massage</div>
                            <div class="amenity-item">Máy sấy tóc</div>
                            <div class="amenity-item">Đồ vệ sinh cá nhân</div>
                            <div class="amenity-item">Dép đi trong phòng</div>
                            <div class="amenity-item">Ấm đun nước</div>
                        </div>
                    </div>
                </div>

                <div class="booking-card">
                    <div class="price-section">
                        <div class="price-label">Giá phòng</div>
                        <div>
                            <span class="price-amount">1.200.000đ</span>
                            <span class="price-unit">/đêm</span>
                        </div>
                    </div>
                    <form class="booking-form">
                        <div class="form-group">
                            <label class="form-label">Ngày nhận phòng</label>
                            <input type="date" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ngày trả phòng</label>
                            <input type="date" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Số khách</label>
                            <select class="form-input">
                                <option>1 người</option>
                                <option selected>2 người</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-book">Đặt phòng ngay</button>
                    </form>
                    <div class="contact-info">
                        <div class="contact-text">Hoặc gọi đặt phòng</div>
                        <div class="contact-phone">(+84-251) 391.8888</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery -->
    <section class="gallery-section">
        <div class="container-custom">
            <h2 class="section-title-center">Hình ảnh phòng</h2>
            <div class="room-gallery">
                <div class="gallery-item">
                    <img src="../assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg" alt="Phòng Deluxe">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/deluxe/DELUXE-ROOM-AURORA-3.jpg" alt="Phòng Deluxe">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/deluxe/DELUXE-ROOM-AURORA-5.jpg" alt="Phòng Deluxe">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/deluxe/DELUXE-ROOM-AURORA-7.jpg" alt="Phòng Deluxe">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/deluxe/DELUXE-ROOM-AURORA-9.jpg" alt="Phòng Deluxe">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/deluxe/DELUXE-ROOM-AURORA-10.jpg" alt="Phòng Deluxe">
                </div>
            </div>
        </div>
    </section>

    <!-- Related Rooms -->
    <section class="related-section">
        <div class="container-custom">
            <h2 class="section-title-center">Phòng khác</h2>
            <div class="related-grid">
                <div class="related-card">
                    <img src="../assets/img/premium deluxe/PREMIUM-DELUXE-AURORA-HOTEL-1.jpg" alt="Premium Deluxe" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">Premium Deluxe</h3>
                        <div class="related-price">1.800.000đ/đêm</div>
                        <a href="premium-deluxe.php" class="btn-view">Xem chi tiết</a>
                    </div>
                </div>
                <div class="related-card">
                    <img src="../assets/img/vip /VIP-ROOM-AURORA-HOTEL-1.jpg" alt="VIP Suite" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">VIP Suite</h3>
                        <div class="related-price">3.500.000đ/đêm</div>
                        <a href="vip-suite.php" class="btn-view">Xem chi tiết</a>
                    </div>
                </div>
                <div class="related-card">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-1.jpg" alt="Premium Twin" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">Premium Twin</h3>
                        <div class="related-price">1.600.000đ/đêm</div>
                        <a href="premium-twin.php" class="btn-view">Xem chi tiết</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
