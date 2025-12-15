<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/language.php';
initLanguage();

$room_slug = 'family';
$room_price = 6500000;
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT base_price FROM room_types WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$room_slug]);
    $room_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($room_data) $room_price = $room_data['base_price'];
} catch (Exception $e) {
    error_log("Apartment detail error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title><?php _e('apartment_detail.family_title'); ?></title>
<script src="../assets/js/tailwindcss-cdn.js"></script>
<link href="../assets/css/fonts.css" rel="stylesheet"/>
<script src="../assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/liquid-glass.css">
<link rel="stylesheet" href="../assets/css/room-detail.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <section class="page-header-room" data-bg-image="../assets/img/family-apartment/can-ho-family-aurora-hotel-3.jpg">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header"><?php _e('apartment_detail.badge_family'); ?></span>
            <h1 class="page-title"><?php _e('apartment_detail.family_name'); ?></h1>
            <p class="page-subtitle"><?php _e('apartment_detail.family_subtitle'); ?></p>
        </div>
    </section>

    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">
                        <?php _e('apartment_detail.family_desc'); ?>
                    </p>

                    <div class="room-specs">
                        <div class="spec-item">
                            <div class="spec-icon"><span class="material-symbols-outlined">bed</span></div>
                            <div class="spec-content">
                                <div class="spec-label"><?php _e('apartment_detail.bed_type'); ?></div>
                                <div class="spec-value"><?php _e('apartment_detail.family_bed'); ?></div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon"><span class="material-symbols-outlined">square_foot</span></div>
                            <div class="spec-content">
                                <div class="spec-label"><?php _e('apartment_detail.area'); ?></div>
                                <div class="spec-value">100 m²</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon"><span class="material-symbols-outlined">person</span></div>
                            <div class="spec-content">
                                <div class="spec-label"><?php _e('apartment_detail.capacity'); ?></div>
                                <div class="spec-value">4-6 <?php _e('apartment_detail.persons'); ?></div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon"><span class="material-symbols-outlined">countertops</span></div>
                            <div class="spec-content">
                                <div class="spec-label"><?php _e('apartment_detail.kitchen'); ?></div>
                                <div class="spec-value"><?php _e('apartment_detail.premium_kitchen'); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="amenities-section">
                        <h3 class="section-title"><?php _e('apartment_detail.amenities'); ?></h3>
                        <div class="amenities-grid">
                            <div class="amenity-item"><?php _e('apartment_detail.amenity_wifi'); ?></div><div class="amenity-item"><?php _e('apartment_detail.amenity_tv'); ?></div><div class="amenity-item"><?php _e('apartment_detail.amenity_ac'); ?></div>
                            <div class="amenity-item"><?php _e('apartment_detail.amenity_stove'); ?></div><div class="amenity-item"><?php _e('apartment_detail.amenity_fridge'); ?></div><div class="amenity-item"><?php _e('apartment_detail.amenity_washer'); ?></div>
                            <div class="amenity-item"><?php _e('apartment_detail.amenity_bathroom'); ?></div><div class="amenity-item"><?php _e('apartment_detail.amenity_cookware'); ?></div><div class="amenity-item"><?php _e('apartment_detail.amenity_toiletries'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="booking-card">
                    <div class="price-section">
                        <div class="price-label"><?php _e('apartment_detail.apartment_price'); ?></div>
                        <div><span class="price-amount"><?php echo number_format($room_price, 0, ',', '.'); ?>đ</span><span class="price-unit"><?php _e('apartment_detail.per_night'); ?></span></div>
                        <p style="font-size: 0.875rem; color: #666; margin-top: 0.5rem;"><?php _e('apartment_detail.discount_30_7days'); ?></p>
                    </div>
                    <form class="booking-form" action="../booking/index.php" method="get">
                        <input type="hidden" name="room_type" value="family">
                        <div class="form-group"><label class="form-label"><?php _e('apartment_detail.check_in_date'); ?></label><input type="date" name="check_in" class="form-input" required></div>
                        <div class="form-group"><label class="form-label"><?php _e('apartment_detail.check_out_date'); ?></label><input type="date" name="check_out" class="form-input" required></div>
                        <div class="form-group"><label class="form-label"><?php _e('apartment_detail.num_guests'); ?></label><select name="guests" class="form-input"><option value="2">2 <?php _e('apartment_detail.persons'); ?></option><option value="4">4 <?php _e('apartment_detail.persons'); ?></option><option value="6" selected>6 <?php _e('apartment_detail.persons'); ?></option></select></div>
                        <button type="submit" class="btn-book"><?php _e('apartment_detail.book_now'); ?></button>
                    </form>
                    <div class="contact-info"><div class="contact-text"><?php _e('apartment_detail.or_call'); ?></div><div class="contact-phone">(+84-251) 391.8888</div></div>
                </div>
            </div>
        </div>
    </section>
    <section class="gallery-section">
        <div class="container-custom">
            <h2 class="section-title-center"><?php _e('apartment_detail.gallery'); ?></h2>
            <div class="room-gallery">
                <div class="gallery-item"><img src="../assets/img/family-apartment/can-ho-family-aurora-hotel-3.jpg" alt="Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/family-apartment/can-ho-family-aurora-hotel-5.jpg" alt="Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/family-apartment/can-ho-family-aurora-hotel-6.jpg" alt="Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/family-apartment/can-ho-family-aurora-hotel-7.jpg" alt="Family Apartment"></div>
                <div class="gallery-item"><img src="../assets/img/family-apartment/can-ho-family-aurora-hotel-8.jpg" alt="Family Apartment"></div>
            </div>
        </div>
    </section>
    <?php 
    // Lấy thông tin căn hộ hiện tại để loại trừ khỏi danh sách related
    require_once __DIR__ . '/../helpers/room-helper.php';
    $currentRoom = getRoomBySlug('family');
    $currentRoomTypeId = $currentRoom ? $currentRoom['id'] : null;
    $sectionTitle = __('apartment_detail.other_apartments');
    include '../includes/related-rooms.php'; 
    ?>
</main>
<?php include '../includes/footer.php'; ?>
</div>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/room-detail-bg.js"></script>
</body>
</html>
