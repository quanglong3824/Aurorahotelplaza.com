<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Studio Apartment - Aurora Hotel Plaza</title>
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
    <section class="page-header-room" style="background-image: url('../assets/img/studio apartment/CAN-HO-STUDIO-AURORA-HOTEL-1.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header">Tiết kiệm</span>
            <h1 class="page-title">Studio Apartment</h1>
            <p class="page-subtitle">Không gian mở hiện đại với đầy đủ tiện nghi</p>
        </div>
    </section>

    <!-- Room Info -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">
                        Căn hộ studio 45m² với không gian mở, bếp nhỏ và đầy đủ tiện nghi. Thiết kế thông minh tối ưu hóa không gian 
                        với khu vực ngủ, khu vực sinh hoạt và bếp nấu được bố trí hợp lý. Phòng được trang bị giường King size, 
                        TV màn hình phẳng, bếp điện từ, tủ lạnh, máy giặt và đầy đủ dụng cụ nấu ăn. Lý tưởng cho 1-2 người lưu trú 
                        dài ngày hoặc khách muốn có không gian riêng tư như ở nhà.
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
                                <div class="spec-value">45 m²</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Sức chứa</div>
                                <div class="spec-value">1-2 người</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">countertops</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label">Bếp</div>
                                <div class="spec-value">Bếp nhỏ đầy đủ</div>
                            </div>
                        </div>
                    </div>

                    <div class="amenities-section">
                        <h3 class="section-title">Tiện nghi căn hộ</h3>
                        <div class="amenities-grid">
                            <div class="amenity-item">WiFi miễn phí tốc độ cao</div>
                            <div class="amenity-item">Smart TV 43"</div>
                            <div class="amenity-item">Điều hòa nhiệt độ</div>
                            <div class="amenity-item">Bếp điện từ</div>
                            <div class="amenity-item">Tủ lạnh</div>
                            <div class="amenity-item">Máy giặt</div>
                            <div class="amenity-item">Bàn làm việc</div>
                            <div class="amenity-item">Dụng cụ nấu ăn đầy đủ</div>
                            <div class="amenity-item">Bộ bát đĩa</div>
                            <div class="amenity-item">Phòng tắm riêng</div>
                            <div class="amenity-item">Máy sấy tóc</div>
                            <div class="amenity-item">Đồ vệ sinh cá nhân</div>
                        </div>
                    </div>
                </div>

                <div class="booking-card">
                    <div class="price-section">
                        <div class="price-label">Giá căn hộ</div>
                        <div>
                            <span class="price-amount">2.500.000đ</span>
                            <span class="price-unit">/đêm</span>
                        </div>
                        <p style="font-size: 0.875rem; color: #666; margin-top: 0.5rem;">Giảm 20% cho thuê từ 7 ngày</p>
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
                        <button type="submit" class="btn-book">Đặt căn hộ ngay</button>
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
            <h2 class="section-title-center">Hình ảnh căn hộ</h2>
            <div class="room-gallery">
                <div class="gallery-item">
                    <img src="../assets/img/studio apartment/CAN-HO-STUDIO-AURORA-HOTEL-1.jpg" alt="Studio Apartment">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/studio apartment/CAN-HO-STUDIO-AURORA-HOTEL-2.jpg" alt="Studio Apartment">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/studio apartment/CAN-HO-STUDIO-AURORA-HOTEL-3.jpg" alt="Studio Apartment">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/studio apartment/CAN-HO-STUDIO-AURORA-HOTEL-4.jpg" alt="Studio Apartment">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/studio apartment/CAN-HO-STUDIO-AURORA-HOTEL-5.jpg" alt="Studio Apartment">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/studio apartment/CAN-HO-STUDIO-AURORA-HOTEL-6.jpg" alt="Studio Apartment">
                </div>
            </div>
        </div>
    </section>

    <!-- Related Rooms -->
    <section class="related-section">
        <div class="container-custom">
            <h2 class="section-title-center">Căn hộ khác</h2>
            <div class="related-grid">
                <div class="related-card">
                    <img src="../assets/img/premium apartment/CAN-HO-PREMIUM-AURORA-HOTEL-1.jpg" alt="Premium Apartment" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">Premium Apartment</h3>
                        <div class="related-price">4.200.000đ/đêm</div>
                        <a href="premium.php" class="btn-view">Xem chi tiết</a>
                    </div>
                </div>
                <div class="related-card">
                    <img src="../assets/img/family apartment/CAN-HO-FAMILY-AURORA-HOTEL-3.jpg" alt="Family Apartment" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">Family Apartment</h3>
                        <div class="related-price">6.500.000đ/đêm</div>
                        <a href="family.php" class="btn-view">Xem chi tiết</a>
                    </div>
                </div>
                <div class="related-card">
                    <img src="../assets/img/modern studio apartment/modern-studio-apartment-1.jpg" alt="Modern Studio" class="related-image">
                    <div class="related-content">
                        <h3 class="related-title">Modern Studio</h3>
                        <div class="related-price">2.600.000đ/đêm</div>
                        <a href="modern-studio.php" class="btn-view">Xem chi tiết</a>
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
