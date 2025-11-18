<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<title>Classical Premium Apartment - Aurora Hotel Plaza</title>
<script src="../assets/js/tailwindcss-cdn.js"></script>
<link href="../assets/css/fonts.css" rel="stylesheet"/>
<script src="../assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/room-detail.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body">
<div class="relative flex min-h-screen w-full flex-col">
<?php include '../includes/header.php'; ?>
<main class="flex h-full grow flex-col">
    <section class="page-header-room" style="background-image: url('../assets/img/classical premium apartment/classical-premium-apartment-1.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header">Classical</span>
            <h1 class="page-title">Classical Premium Apartment</h1>
            <p class="page-subtitle">Căn hộ cao cấp phong cách cổ điển 75m²</p>
        </div>
    </section>
    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">Căn hộ cao cấp 75m² với 1 phòng ngủ thiết kế theo phong cách Classical Châu Âu. Nội thất gỗ cao cấp, họa tiết trang trí tinh tế. Phòng khách rộng, bếp đầy đủ tiện nghi. Lý tưởng cho 2-4 người.</p>
                    <div class="room-specs">
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">bed</span></div><div class="spec-content"><div class="spec-label">Loại giường</div><div class="spec-value">1 phòng ngủ</div></div></div>
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">square_foot</span></div><div class="spec-content"><div class="spec-label">Diện tích</div><div class="spec-value">75 m²</div></div></div>
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">person</span></div><div class="spec-content"><div class="spec-label">Sức chứa</div><div class="spec-value">2-4 người</div></div></div>
                    </div>
                    <div class="amenities-section">
                        <h3 class="section-title">Tiện nghi căn hộ</h3>
                        <div class="amenities-grid">
                            <div class="amenity-item">WiFi cao tốc</div><div class="amenity-item">Smart TV 55"</div><div class="amenity-item">Điều hòa</div>
                            <div class="amenity-item">Bếp hiện đại</div><div class="amenity-item">Tủ lạnh</div><div class="amenity-item">Máy giặt sấy</div>
                        </div>
                    </div>
                </div>
                <div class="booking-card">
                    <div class="price-section"><div class="price-label">Giá căn hộ</div><div><span class="price-amount">4.800.000đ</span><span class="price-unit">/đêm</span></div></div>
                    <form class="booking-form">
                        <div class="form-group"><label class="form-label">Ngày nhận phòng</label><input type="date" class="form-input" required></div>
                        <div class="form-group"><label class="form-label">Ngày trả phòng</label><input type="date" class="form-input" required></div>
                        <button type="submit" class="btn-book">Đặt căn hộ ngay</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Gallery -->
    <section class="gallery-section">
        <div class="container-custom">
            <h2 class="section-title-center">Hình ảnh căn hộ</h2>
            <div class="room-gallery">
                <div class="gallery-item"><img src="../assets/img/classical premium apartment/classical-premium-apartment-1.jpg" alt="Classical Premium Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/classical premium apartment/classical-premium-apartment-2.jpg" alt="Classical Premium Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/classical premium apartment/classical-premium-apartment-3.jpg.jpg" alt="Classical Premium Apartment"></div>
            </div>
        </div>
    </section>

    <!-- Related Apartments -->
    <section class="related-section">
        <div class="container-custom">
            <h2 class="section-title-center">Căn hộ khác</h2>
            <div class="related-grid">
                <div class="related-card"><img src="../assets/img/classical family apartment/classical-family-apartment1.jpg" alt="Classical Family" class="related-image"><div class="related-content"><h3 class="related-title">Classical Family</h3><div class="related-price">6.800.000đ/đêm</div><a href="classical-family.php" class="btn-view">Xem chi tiết</a></div></div>
                <div class="related-card"><img src="../assets/img/premium apartment/CAN-HO-PREMIUM-AURORA-HOTEL-1.jpg" alt="Premium Apartment" class="related-image"><div class="related-content"><h3 class="related-title">Premium Apartment</h3><div class="related-price">4.200.000đ/đêm</div><a href="premium.php" class="btn-view">Xem chi tiết</a></div></div>
                <div class="related-card"><img src="../assets/img/modern premium apartment/modern-premium-apartment-1.jpg" alt="Modern Premium" class="related-image"><div class="related-content"><h3 class="related-title">Modern Premium</h3><div class="related-price">5.200.000đ/đêm</div><a href="modern-premium.php" class="btn-view">Xem chi tiết</a></div></div>
            </div>
        </div>
    </section>
</main>
<?php include '../includes/footer.php'; ?>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
