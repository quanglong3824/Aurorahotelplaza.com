<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Family Apartment - Aurora Hotel Plaza</title>
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
    <section class="page-header-room" style="background-image: url('../assets/img/family apartment/CAN-HO-FAMILY-AURORA-HOTEL-3.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header">Gia đình</span>
            <h1 class="page-title">Family Apartment</h1>
            <p class="page-subtitle">Căn hộ rộng rãi với 2 phòng ngủ, hoàn hảo cho gia đình</p>
        </div>
    </section>

    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">
                        Căn hộ rộng rãi 100m² với 2 phòng ngủ, phòng khách lớn và bếp đầy đủ. Thiết kế gia đình với không gian thoải mái, 
                        2 phòng ngủ riêng biệt, 2 phòng tắm, phòng khách rộng và bếp hiện đại. Phòng ngủ chính có giường King size, 
                        phòng ngủ phụ có 2 giường đơn. Hoàn hảo cho gia đình 4-6 người lưu trú dài ngày.
                    </p>

                    <div class="room-specs">
                        <div class="spec-item">
                            <div class="spec-icon"><span class="material-symbols-outlined">bed</span></div>
                            <div class="spec-content">
                                <div class="spec-label">Loại giường</div>
                                <div class="spec-value">2 phòng ngủ</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon"><span class="material-symbols-outlined">square_foot</span></div>
                            <div class="spec-content">
                                <div class="spec-label">Diện tích</div>
                                <div class="spec-value">100 m²</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon"><span class="material-symbols-outlined">person</span></div>
                            <div class="spec-content">
                                <div class="spec-label">Sức chứa</div>
                                <div class="spec-value">4-6 người</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon"><span class="material-symbols-outlined">countertops</span></div>
                            <div class="spec-content">
                                <div class="spec-label">Bếp</div>
                                <div class="spec-value">Bếp đầy đủ</div>
                            </div>
                        </div>
                    </div>
                    <div class="amenities-section">
                        <h3 class="section-title">Tiện nghi căn hộ</h3>
                        <div class="amenities-grid">
                            <div class="amenity-item">WiFi miễn phí cao tốc</div><div class="amenity-item">2 Smart TV</div><div class="amenity-item">3 Điều hòa</div>
                            <div class="amenity-item">Bếp hiện đại</div><div class="amenity-item">Tủ lạnh lớn</div><div class="amenity-item">Máy giặt sấy</div>
                            <div class="amenity-item">2 Phòng tắm</div><div class="amenity-item">Bàn ăn 6 người</div><div class="amenity-item">Ban công lớn</div>
                        </div>
                    </div>
                </div>
                <div class="booking-card">
                    <div class="price-section">
                        <div class="price-label">Giá căn hộ</div>
                        <div><span class="price-amount">6.500.000đ</span><span class="price-unit">/đêm</span></div>
                        <p style="font-size: 0.875rem; color: #666; margin-top: 0.5rem;">Giảm 30% cho thuê từ 7 ngày</p>
                    </div>
                    <form class="booking-form">
                        <div class="form-group"><label class="form-label">Ngày nhận phòng</label><input type="date" class="form-input" required></div>
                        <div class="form-group"><label class="form-label">Ngày trả phòng</label><input type="date" class="form-input" required></div>
                        <div class="form-group"><label class="form-label">Số khách</label><select class="form-input"><option>2 người</option><option>4 người</option><option selected>6 người</option></select></div>
                        <button type="submit" class="btn-book">Đặt căn hộ ngay</button>
                    </form>
                    <div class="contact-info"><div class="contact-text">Hoặc gọi đặt phòng</div><div class="contact-phone">(+84-251) 391.8888</div></div>
                </div>
            </div>
        </div>
    </section>
    <section class="gallery-section">
        <div class="container-custom">
            <h2 class="section-title-center">Hình ảnh căn hộ</h2>
            <div class="room-gallery">
                <div class="gallery-item"><img src="../assets/img/family apartment/CAN-HO-FAMILY-AURORA-HOTEL-3.jpg" alt="Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/family apartment/CAN-HO-FAMILY-AURORA-HOTEL-4.jpg" alt="Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/family apartment/CAN-HO-FAMILY-AURORA-HOTEL-5.jpg" alt="Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/family apartment/CAN-HO-FAMILY-AURORA-HOTEL-6.jpg" alt="Family Apartment"></div>
            </div>
        </div>
    </section>
    <section class="related-section">
        <div class="container-custom">
            <h2 class="section-title-center">Căn hộ khác</h2>
            <div class="related-grid">
                <div class="related-card"><img src="../assets/img/studio apartment/CAN-HO-STUDIO-AURORA-HOTEL-1.jpg" alt="Studio" class="related-image"><div class="related-content"><h3 class="related-title">Studio Apartment</h3><div class="related-price">2.500.000đ/đêm</div><a href="studio.php" class="btn-view">Xem chi tiết</a></div></div>
                <div class="related-card"><img src="../assets/img/premium apartment/CAN-HO-PREMIUM-AURORA-HOTEL-1.jpg" alt="Premium" class="related-image"><div class="related-content"><h3 class="related-title">Premium Apartment</h3><div class="related-price">4.200.000đ/đêm</div><a href="premium.php" class="btn-view">Xem chi tiết</a></div></div>
                <div class="related-card"><img src="../assets/img/classical family apartment/classical-family-apartment1.jpg" alt="Classical Family" class="related-image"><div class="related-content"><h3 class="related-title">Classical Family</h3><div class="related-price">6.800.000đ/đêm</div><a href="classical-family.php" class="btn-view">Xem chi tiết</a></div></div>
            </div>
        </div>
    </section>
</main>
<?php include '../includes/footer.php'; ?>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
