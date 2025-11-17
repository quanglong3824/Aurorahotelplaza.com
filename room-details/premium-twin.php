<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Phòng Premium Twin - Aurora Hotel Plaza</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="../assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/room-detail.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-room" style="background-image: url('../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-1.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header">Linh hoạt</span>
            <h1 class="page-title">Phòng Premium Twin</h1>
            <p class="page-subtitle">Hai giường đơn cao cấp, lý tưởng cho bạn bè hoặc đồng nghiệp</p>
        </div>
    </section>

    <!-- Room Info -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">
                        Phòng Premium Twin rộng 42m² được thiết kế hiện đại với 2 giường đơn cao cấp, tầm nhìn thành phố đẹp. 
                        Phòng được trang bị đầy đủ tiện nghi như TV màn hình phẳng 50", minibar, két an toàn và phòng tắm riêng với 
                        vòi sen massage. Đây là lựa chọn hoàn hảo cho bạn bè, đồng nghiệp hoặc gia đình có trẻ em.
                    </p>

                    <div class="room-specs">
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">bed</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Loại giường</div>
                                <div class="spec-value">2 Giường đơn</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">square_foot</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Diện tích</div>
                                <div class="spec-value">42 m²</div>
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
                            <div class="amenity-item">TV màn hình phẳng 50"</div>
                            <div class="amenity-item">Điều hòa nhiệt độ</div>
                            <div class="amenity-item">Minibar đầy đủ</div>
                            <div class="amenity-item">Két an toàn</div>
                            <div class="amenity-item">2 Bàn làm việc</div>
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
                            <span class="price-amount">1.600.000đ</span>
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
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-1.jpg" alt="Phòng Premium Twin">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-2.jpg" alt="Phòng Premium Twin">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-3.jpg" alt="Phòng Premium Twin">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-4.jpg" alt="Phòng Premium Twin">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-6.jpg" alt="Phòng Premium Twin">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-7.jpg" alt="Phòng Premium Twin">
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
                    <img src="../assets/img/vip /VIP-ROOM-AURORA-HOTEL-1.jpg" alt="VIP Suite" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">VIP Suite</h3>
                        <div class="related-price">3.500.000đ/đêm</div>
                        <a href="vip-suite.php" class="btn-view">Xem chi tiết</a>
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
