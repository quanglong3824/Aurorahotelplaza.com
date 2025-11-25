<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<title>Indochine Family Apartment - Aurora Hotel Plaza</title>
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
    <section class="page-header-room" style="background-image: url('../assets/img/indochine family apartment/indochine-family-apartment-1.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header">Indochine</span>
            <h1 class="page-title">Indochine Family Apartment</h1>
            <p class="page-subtitle">Căn hộ gia đình phong cách Đông Dương 105m²</p>
        </div>
    </section>
    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">Căn hộ gia đình 105m² với 2 phòng ngủ thiết kế theo phong cách Đông Dương độc đáo. Kết hợp giữa nét đẹp truyền thống Việt Nam và sự tinh tế của Pháp. Không gian ấm cúng với nội thất gỗ tự nhiên, họa tiết dân gian và màu sắc hài hòa.</p>
                    <div class="room-specs">
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">bed</span></div><div class="spec-content"><div class="spec-label">Loại giường</div><div class="spec-value">2 phòng ngủ</div></div></div>
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">square_foot</span></div><div class="spec-content"><div class="spec-label">Diện tích</div><div class="spec-value">105 m²</div></div></div>
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">person</span></div><div class="spec-content"><div class="spec-label">Sức chứa</div><div class="spec-value">4-6 người</div></div></div>
                    </div>
                    <div class="amenities-section">
                        <h3 class="section-title">Tiện nghi căn hộ</h3>
                        <div class="amenities-grid">
                            <div class="amenity-item">WiFi cao tốc</div><div class="amenity-item">2 Smart TV</div><div class="amenity-item">3 Điều hòa</div>
                            <div class="amenity-item">Bếp hiện đại</div><div class="amenity-item">2 Phòng tắm</div><div class="amenity-item">Máy giặt sấy</div>
                        </div>
                    </div>
                </div>
                <div class="booking-card">
                    <div class="price-section"><div class="price-label">Giá căn hộ</div><div><span class="price-amount">7.200.000đ</span><span class="price-unit">/đêm</span></div></div>
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
                <div class="gallery-item"><img src="../assets/img/indochine family apartment/indochine-family-apartment-1.jpg" alt="Indochine Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/indochine family apartment/indochine-family-apartment-2.jpg" alt="Indochine Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/indochine family apartment/indochine-family-apartment-3.jpg" alt="Indochine Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/indochine family apartment/indochine-family-apartment-4.jpg" alt="Indochine Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/indochine family apartment/indochine-family-apartment-5.jpg" alt="Indochine Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/indochine family apartment/indochine-family-apartment-6.jpg" alt="Indochine Family Apartment"></div>
            </div>
        </div>
    </section>

        <?php 
    // Lấy thông tin căn hộ hiện tại để loại trừ khỏi danh sách related
    require_once __DIR__ . '/../helpers/room-helper.php';
    $currentRoom = getRoomBySlug('indochine-family');
    $currentRoomTypeId = $currentRoom ? $currentRoom['id'] : null;
    $sectionTitle = 'Căn hộ khác';
    include '../includes/related-rooms.php'; 
    ?>
</main>
<?php include '../includes/footer.php'; ?>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
