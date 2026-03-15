<!DOCTYPE html>
<html translate="no" class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('apartment_detail.classical_family_title'); ?></title>
    <link href="../assets/css/tailwind-output.css" rel="stylesheet" />
    <link href="../assets/css/fonts.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="../assets/css/room-detail.css">
</head>

<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include '../includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <section class="page-header-room"
                data-bg-image="../assets/img/classical-family-apartment/classical-family-apartment1.jpg">
                <div class="page-header-overlay"></div>
                <div class="page-header-content">
                    <span class="room-badge-header"><?php _e('apartment_detail.badge_classical'); ?></span>
                    <h1 class="page-title"><?php _e('apartment_detail.classical_family_name'); ?></h1>
                    <p class="page-subtitle"><?php _e('apartment_detail.classical_family_subtitle'); ?></p>
                </div>
            </section>

            <section class="section-padding">
                <div class="container-custom">
                    <div class="room-info-section">
                        <div class="room-content">
                            <p class="room-description">
                                <?php _e('apartment_detail.classical_family_desc'); ?>
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
                                    <div class="spec-icon"><span class="material-symbols-outlined">square_foot</span>
                                    </div>
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
                            </div>
                            <div class="amenities-section">
                                <h3 class="section-title"><?php _e('apartment_detail.amenities'); ?></h3>
                                <div class="amenities-grid">
                                    <div class="amenity-item"><?php _e('apartment_detail.amenity_wifi'); ?></div>
                                    <div class="amenity-item"><?php _e('apartment_detail.amenity_tv'); ?></div>
                                    <div class="amenity-item"><?php _e('apartment_detail.amenity_ac'); ?></div>
                                    <div class="amenity-item"><?php _e('apartment_detail.amenity_stove'); ?></div>
                                    <div class="amenity-item"><?php _e('apartment_detail.amenity_bathroom'); ?></div>
                                    <div class="amenity-item"><?php _e('apartment_detail.amenity_washer'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="booking-card">
                            <div class="price-section">
                                <div class="price-label"><?php _e('apartment_detail.apartment_price'); ?></div>
                                <div><span
                                        class="price-amount"><?php echo number_format($room_price, 0, ',', '.'); ?>VND</span><span
                                        class="price-unit"><?php _e('apartment_detail.per_night'); ?></span></div>
                            </div>
                            <form class="booking-form" action="../booking/index.php" method="get">
                                <input type="hidden" name="room_type" value="<?php echo htmlspecialchars($room_slug); ?>">
                                <div class="form-group"><label
                                        class="form-label"><?php _e('apartment_detail.check_in_date'); ?></label><input
                                        type="date" name="check_in" class="form-input" required></div>
                                <div class="form-group"><label
                                        class="form-label"><?php _e('apartment_detail.check_out_date'); ?></label><input
                                        type="date" name="check_out" class="form-input" required></div>
                                <button type="submit" class="btn-book"><?php _e('inquiry.contact_btn'); ?></button>
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
                        <div class="gallery-item"><img
                                src="../assets/img/classical-family-apartment/classical-family-apartment1.jpg"
                                alt="Classical Family Apartment"></div>
                        <div class="gallery-item"><img
                                src="../assets/img/classical-family-apartment/classical-family-apartment2.jpg"
                                alt="Classical Family Apartment"></div>
                        <div class="gallery-item"><img
                                src="../assets/img/classical-family-apartment/classical-family-apartment3.jpg"
                                alt="Classical Family Apartment"></div>
                        <div class="gallery-item"><img
                                src="../assets/img/classical-family-apartment/classical-family-apartment4.jpg"
                                alt="Classical Family Apartment"></div>
                        <div class="gallery-item"><img
                                src="../assets/img/classical-family-apartment/classical-family-apartment5.jpg"
                                alt="Classical Family Apartment"></div>
                        <div class="gallery-item"><img
                                src="../assets/img/classical-family-apartment/classical-family-apartment6.jpg"
                                alt="Classical Family Apartment"></div>
                    </div>
                </div>
            </section>

            <?php
            // Lấy thông tin căn hộ hiện tại để loại trừ khỏi danh sách related
            require_once __DIR__ . '/../../helpers/room-helper.php';
            $currentRoom = getRoomBySlug($room_slug);
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
