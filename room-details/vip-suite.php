<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Phòng VIP Suite - Aurora Hotel Plaza</title>
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
    <section class="page-header-room" style="background-image: url('../assets/img/vip /VIP-ROOM-AURORA-HOTEL-1.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header">VIP</span>
            <h1 class="page-title">Phòng VIP Suite</h1>
            <p class="page-subtitle">Đẳng cấp thượng lưu với không gian rộng rãi và dịch vụ đặc biệt</p>
        </div>
    </section>

    <!-- Room Info -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">
                        Phòng VIP Suite rộng 80m² là đỉnh cao của sự sang trọng với phòng ngủ riêng biệt, phòng khách rộng rãi, 
                        giường King size đặc biệt và tầm nhìn panorama tuyệt đẹp. Phòng được trang bị đầy đủ tiện nghi cao cấp nhất 
                        như TV màn hình phẳng 65", hệ thống âm thanh cao cấp, minibar đầy đủ, két an toàn lớn và phòng tắm sang trọng 
                        với bồn tắm Jacuzzi. Dịch vụ butler 24/7 và nhiều đặc quyền VIP khác.
                    </p>

                    <div class="room-specs">
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">bed</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Loại giường</div>
                                <div class="spec-value">1 Giường King + Sofa</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">square_foot</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Diện tích</div>
                                <div class="spec-value">80 m²</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Sức chứa</div>
                                <div class="spec-value">2-4 người</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">visibility</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Tầm nhìn</div>
                                <div class="spec-value">Panorama 360°</div>
                            </div>
                        </div>
                    </div>

                    <div class="amenities-section">
                        <h3 class="section-title">Tiện nghi phòng</h3>
                        <div class="amenities-grid">
                            <div class="amenity-item">WiFi miễn phí tốc độ cao</div>
                            <div class="amenity-item">TV màn hình phẳng 65"</div>
                            <div class="amenity-item">Hệ thống âm thanh cao cấp</div>
                            <div class="amenity-item">Minibar đầy đủ</div>
                            <div class="amenity-item">Két an toàn lớn</div>
                            <div class="amenity-item">Bàn làm việc executive</div>
                            <div class="amenity-item">Phòng tắm sang trọng</div>
                            <div class="amenity-item">Bồn tắm Jacuzzi</div>
                            <div class="amenity-item">Vòi sen massage cao cấp</div>
                            <div class="amenity-item">Đồ vệ sinh thương hiệu</div>
                            <div class="amenity-item">Áo choàng tắm cao cấp</div>
                            <div class="amenity-item">Máy pha cà phê Nespresso</div>
                            <div class="amenity-item">Dịch vụ Butler 24/7</div>
                            <div class="amenity-item">Phòng khách riêng</div>
                            <div class="amenity-item">Ban công lớn</div>
                            <div class="amenity-item">Bàn ăn 4 người</div>
                        </div>
                    </div>
                </div>

                <div class="booking-card">
                    <div class="price-section">
                        <div class="price-label">Giá phòng</div>
                        <div>
                            <span class="price-amount">3.500.000đ</span>
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
                                <option>3 người</option>
                                <option>4 người</option>
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
                    <img src="../assets/img/vip /VIP-ROOM-AURORA-HOTEL-1.jpg" alt="Phòng VIP Suite">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/vip /VIP-ROOM-AURORA-HOTEL-3.jpg" alt="Phòng VIP Suite">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/vip /VIP-ROOM-AURORA-HOTEL-4.jpg" alt="Phòng VIP Suite">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/vip /VIP-ROOM-AURORA-HOTEL-5.jpg" alt="Phòng VIP Suite">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/vip /VIP-ROOM-AURORA-HOTEL-6.jpg" alt="Phòng VIP Suite">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/vip /VIP-ROOM-AURORA-HOTEL-7.jpg" alt="Phòng VIP Suite">
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
                    <img src="../assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg" alt="Deluxe" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">Deluxe</h3>
                        <div class="related-price">1.200.000đ/đêm</div>
                        <a href="deluxe.php" class="btn-view">Xem chi tiết</a>
                    </div>
                </div>
                <div class="related-card">
                    <img src="../assets/img/premium deluxe/premium-deluxe1.jpg" alt="Premium Deluxe" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">Premium Deluxe</h3>
                        <div class="related-price">1.800.000đ/đêm</div>
                        <a href="premium-deluxe.php" class="btn-view">Xem chi tiết</a>
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
