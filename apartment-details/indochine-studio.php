<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<title>Indochine Studio Apartment - Aurora Hotel Plaza</title>
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
    <section class="page-header-room" style="background-image: url('../assets/img/indochine studio apartment/indochine-studio-apartment-1.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header">Indochine</span>
            <h1 class="page-title">Indochine Studio Apartment</h1>
            <p class="page-subtitle">Studio phong cách Đông Dương 50m²</p>
        </div>
    </section>
    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">Studio 50m² thiết kế theo phong cách Đông Dương độc đáo. Kết hợp nét đẹp truyền thống và hiện đại. Không gian mở với bếp nhỏ, nội thất gỗ tự nhiên và họa tiết dân gian. Lý tưởng cho 1-2 người.</p>
                    <div class="room-specs">
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">bed</span></div><div class="spec-content"><div class="spec-label">Loại giường</div><div class="spec-value">Studio</div></div></div>
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">square_foot</span></div><div class="spec-content"><div class="spec-label">Diện tích</div><div class="spec-value">50 m²</div></div></div>
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">person</span></div><div class="spec-content"><div class="spec-label">Sức chứa</div><div class="spec-value">1-2 người</div></div></div>
                    </div>
                    <div class="amenities-section">
                        <h3 class="section-title">Tiện nghi căn hộ</h3>
                        <div class="amenities-grid">
                            <div class="amenity-item">WiFi cao tốc</div><div class="amenity-item">Smart TV</div><div class="amenity-item">Điều hòa</div>
                            <div class="amenity-item">Bếp nhỏ</div><div class="amenity-item">Tủ lạnh</div><div class="amenity-item">Máy giặt</div>
                        </div>
                    </div>
                </div>
                <div class="booking-card">
                    <div class="price-section"><div class="price-label">Giá căn hộ</div><div><span class="price-amount">2.800.000đ</span><span class="price-unit">/đêm</span></div></div>
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
                <div class="gallery-item"><img src="../assets/img/indochine studio apartment/indochine-studio-apartment-1.jpg" alt="Indochine Studio Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/indochine studio apartment/indochine-studio-apartment-2.jpg" alt="Indochine Studio Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/indochine studio apartment/indochine-studio-apartment-3.jpg" alt="Indochine Studio Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/indochine studio apartment/indochine-studio-apartment-4.jpg" alt="Indochine Studio Apartment"></div>
            </div>
        </div>
    </section>

        <?php 
    // Lấy thông tin căn hộ hiện tại để loại trừ khỏi danh sách related
    require_once __DIR__ . '/../helpers/room-helper.php';
    $currentRoom = getRoomBySlug('indochine-studio');
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
