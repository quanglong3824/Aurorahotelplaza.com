<?php
require_once __DIR__ . '/../helpers/language.php';
initLanguage();
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<title><?php _e('apartment_detail.classical_premium_title'); ?></title>
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
    <section class="page-header-room" style="background-image: url('../assets/img/classical-premium-apartment/classical-premium-apartment-1.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header"><?php _e('apartment_detail.badge_classical'); ?></span>
            <h1 class="page-title"><?php _e('apartment_detail.classical_premium_name'); ?></h1>
            <p class="page-subtitle"><?php _e('apartment_detail.classical_premium_subtitle'); ?></p>
        </div>
    </section>
    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description"><?php _e('apartment_detail.classical_premium_desc'); ?></p>
                    <div class="room-specs">
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">bed</span></div><div class="spec-content"><div class="spec-label"><?php _e('apartment_detail.bed_type'); ?></div><div class="spec-value"><?php _e('apartment_detail.one_bedroom'); ?></div></div></div>
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">square_foot</span></div><div class="spec-content"><div class="spec-label"><?php _e('apartment_detail.area'); ?></div><div class="spec-value">75 m²</div></div></div>
                        <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">person</span></div><div class="spec-content"><div class="spec-label"><?php _e('apartment_detail.capacity'); ?></div><div class="spec-value">2-4 <?php _e('apartment_detail.persons'); ?></div></div></div>
                    </div>
                    <div class="amenities-section">
                        <h3 class="section-title"><?php _e('apartment_detail.amenities'); ?></h3>
                        <div class="amenities-grid">
                            <div class="amenity-item"><?php _e('apartment_detail.amenity_wifi'); ?></div><div class="amenity-item"><?php _e('apartment_detail.amenity_tv'); ?></div><div class="amenity-item"><?php _e('apartment_detail.amenity_ac'); ?></div>
                            <div class="amenity-item"><?php _e('apartment_detail.amenity_stove'); ?></div><div class="amenity-item"><?php _e('apartment_detail.amenity_fridge'); ?></div><div class="amenity-item"><?php _e('apartment_detail.amenity_washer'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="booking-card">
                    <div class="price-section"><div class="price-label"><?php _e('apartment_detail.apartment_price'); ?></div><div><span class="price-amount">4.800.000đ</span><span class="price-unit"><?php _e('apartment_detail.per_night'); ?></span></div></div>
                    <form class="booking-form">
                        <div class="form-group"><label class="form-label"><?php _e('apartment_detail.check_in_date'); ?></label><input type="date" class="form-input" required></div>
                        <div class="form-group"><label class="form-label"><?php _e('apartment_detail.check_out_date'); ?></label><input type="date" class="form-input" required></div>
                        <button type="submit" class="btn-book"><?php _e('apartment_detail.book_now'); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Gallery -->
    <section class="gallery-section">
        <div class="container-custom">
            <h2 class="section-title-center"><?php _e('apartment_detail.gallery'); ?></h2>
            <div class="room-gallery">
                <div class="gallery-item"><img src="../assets/img/classical-premium-apartment/classical-premium-apartment-1.jpg" alt="Classical Premium Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/classical-premium-apartment/classical-premium-apartment-2.jpg" alt="Classical Premium Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/classical-premium-apartment/classical-premium-apartment-3.jpg.jpg" alt="Classical Premium Apartment"></div>
            </div>
        </div>
    </section>

        <?php 
    // Lấy thông tin căn hộ hiện tại để loại trừ khỏi danh sách related
    require_once __DIR__ . '/../helpers/room-helper.php';
    $currentRoom = getRoomBySlug('classical-premium');
    $currentRoomTypeId = $currentRoom ? $currentRoom['id'] : null;
    $sectionTitle = __('apartment_detail.other_apartments');
    include '../includes/related-rooms.php'; 
    ?>
</main>
<?php include '../includes/footer.php'; ?>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
