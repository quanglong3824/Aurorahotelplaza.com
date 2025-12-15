<?php
require_once __DIR__ . '/../helpers/language.php';
initLanguage();
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php _e('room_detail.vip_suite_title'); ?></title>
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
            <span class="room-badge-header"><?php _e('room_detail.vip'); ?></span>
            <h1 class="page-title"><?php _e('room_detail.vip_suite_name'); ?></h1>
            <p class="page-subtitle"><?php _e('room_detail.vip_suite_subtitle'); ?></p>
        </div>
    </section>

    <!-- Room Info -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">
                        <?php _e('room_detail.vip_suite_desc'); ?>
                    </p>

                    <div class="room-specs">
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">bed</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label"><?php _e('room_detail.bed_type'); ?></div>
                                <div class="spec-value"><?php _e('room_detail.bed_king'); ?> + Sofa</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">square_foot</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label"><?php _e('room_detail.area'); ?></div>
                                <div class="spec-value">80 m²</div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label"><?php _e('room_detail.capacity'); ?></div>
                                <div class="spec-value">2-4 <?php _e('room_detail.guests'); ?></div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <span class="material-symbols-outlined">visibility</span>
                            </div>
                            <div class="spec-content">
                                <div class="spec-label"><?php _e('room_detail.view'); ?></div>
                                <div class="spec-value">Panorama 360°</div>
                            </div>
                        </div>
                    </div>

                    <div class="amenities-section">
                        <h3 class="section-title"><?php _e('room_detail.amenities'); ?></h3>
                        <div class="amenities-grid">
                            <div class="amenity-item"><?php _e('room_detail.amenity_wifi'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_tv'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_ac'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_minibar'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_safe'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_desk'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_bathroom'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_shower'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_hairdryer'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_toiletries'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_slippers'); ?></div>
                            <div class="amenity-item"><?php _e('room_detail.amenity_kettle'); ?></div>
                            <div class="amenity-item">Butler 24/7</div>
                            <div class="amenity-item">Jacuzzi</div>
                            <div class="amenity-item">Balcony</div>
                            <div class="amenity-item">Dining table</div>
                        </div>
                    </div>
                </div>

                <div class="booking-card">
                    <div class="price-section">
                        <div class="price-label"><?php _e('room_detail.room_price'); ?></div>
                        <div>
                            <span class="price-amount">3.500.000đ</span>
                            <span class="price-unit">/<?php _e('room_detail.night'); ?></span>
                        </div>
                    </div>
                    <form class="booking-form" action="../booking/index.php" method="get">
                        <input type="hidden" name="room_type" value="vip-suite">
                        <div class="form-group">
                            <label class="form-label"><?php _e('room_detail.check_in_date'); ?></label>
                            <input type="date" name="check_in" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php _e('room_detail.check_out_date'); ?></label>
                            <input type="date" name="check_out" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php _e('room_detail.num_guests'); ?></label>
                            <select name="guests" class="form-input">
                                <option value="1">1 <?php _e('room_detail.person'); ?></option>
                                <option value="2" selected>2 <?php _e('room_detail.person'); ?></option>
                                <option value="3">3 <?php _e('room_detail.person'); ?></option>
                                <option value="4">4 <?php _e('room_detail.person'); ?></option>
                            </select>
                        </div>
                        <button type="submit" class="btn-book"><?php _e('room_detail.book_now'); ?></button>
                    </form>
                    <div class="contact-info">
                        <div class="contact-text"><?php _e('room_detail.or_call'); ?></div>
                        <div class="contact-phone">(+84-251) 391.8888</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery -->
    <section class="gallery-section">
        <div class="container-custom">
            <h2 class="section-title-center"><?php _e('room_detail.room_gallery'); ?></h2>
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

    <?php 
    // Lấy thông tin phòng hiện tại để loại trừ khỏi danh sách related
    require_once __DIR__ . '/../helpers/room-helper.php';
    $currentRoom = getRoomBySlug('vip-suite');
    $currentRoomTypeId = $currentRoom ? $currentRoom['id'] : null;
    $sectionTitle = __('room_detail.other_rooms');
    include '../includes/related-rooms.php'; 
    ?>
</main>

<?php include '../includes/footer.php'; ?>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
