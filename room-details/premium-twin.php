<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/language.php';
require_once __DIR__ . '/../helpers/image-helper.php';
initLanguage();

$room_slug = 'premium-twin';
$room_price = 1600000;
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT base_price FROM room_types WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$room_slug]);
    $room_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($room_data)
        $room_price = $room_data['base_price'];
} catch (Exception $e) {
    error_log("Room detail error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('room_detail.premium_twin_title'); ?></title>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <link href="../assets/css/fonts.css" rel="stylesheet" />
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="../assets/css/room-detail.css">
</head>

<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include '../includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
            <!-- Page Header -->
            <section class="page-header-room"
                data-bg-image="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-1.jpg'); ?>">
                <div class="page-header-overlay"></div>
                <div class="page-header-content">
                    <span class="room-badge-header"><?php _e('room_detail.premium_twin_badge'); ?></span>
                    <h1 class="page-title"><?php _e('room_detail.premium_twin_name'); ?></h1>
                    <p class="page-subtitle"><?php _e('room_detail.premium_twin_subtitle'); ?></p>
                </div>
            </section>

            <!-- Room Info -->
            <section class="section-padding">
                <div class="container-custom">
                    <div class="room-info-section">
                        <div class="room-content">
                            <p class="room-description">
                                <?php _e('room_detail.premium_twin_desc'); ?>
                            </p>

                            <div class="room-specs">
                                <div class="spec-item">
                                    <div class="spec-icon">
                                        <span class="material-symbols-outlined">bed</span>
                                    </div>
                                    <div class="spec-content">
                                        <div class="spec-label"><?php _e('room_detail.bed_type'); ?></div>
                                        <div class="spec-value"><?php _e('room_detail.bed_twin'); ?></div>
                                    </div>
                                </div>
                                <div class="spec-item">
                                    <div class="spec-icon">
                                        <span class="material-symbols-outlined">square_foot</span>
                                    </div>
                                    <div class="spec-content">
                                        <div class="spec-label"><?php _e('room_detail.area'); ?></div>
                                        <div class="spec-value">42 m²</div>
                                    </div>
                                </div>
                                <div class="spec-item">
                                    <div class="spec-icon">
                                        <span class="material-symbols-outlined">person</span>
                                    </div>
                                    <div class="spec-content">
                                        <div class="spec-label"><?php _e('room_detail.capacity'); ?></div>
                                        <div class="spec-value">2 <?php _e('room_detail.guests'); ?></div>
                                    </div>
                                </div>
                                <div class="spec-item">
                                    <div class="spec-icon">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </div>
                                    <div class="spec-content">
                                        <div class="spec-label"><?php _e('room_detail.view'); ?></div>
                                        <div class="spec-value"><?php _e('room_detail.city_view'); ?></div>
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
                                </div>
                            </div>
                        </div>

                        <div class="booking-card">
                            <div class="price-section">
                                <div class="price-label"><?php _e('room_detail.room_price'); ?></div>
                                <div>
                                    <span
                                        class="price-amount"><?php echo number_format($room_price, 0, ',', '.'); ?>đ</span>
                                    <span class="price-unit">/<?php _e('room_detail.night'); ?></span>
                                </div>
                            </div>
                            <form class="booking-form" action="../booking/index.php" method="get">
                                <input type="hidden" name="room_type" value="premium-twin">
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
                            <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-1.jpg'); ?>"
                                alt="Phòng Premium Twin">
                        </div>
                        <div class="gallery-item">
                            <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-2.jpg'); ?>"
                                alt="Phòng Premium Twin">
                        </div>
                        <div class="gallery-item">
                            <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-3.jpg'); ?>"
                                alt="Phòng Premium Twin">
                        </div>
                        <div class="gallery-item">
                            <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-4.jpg'); ?>"
                                alt="Phòng Premium Twin">
                        </div>
                        <div class="gallery-item">
                            <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-6.jpg'); ?>"
                                alt="Phòng Premium Twin">
                        </div>
                        <div class="gallery-item">
                            <img src="<?php echo imgUrl('assets/img/premium-twin/premium-deluxe-twin-aurora-7.jpg'); ?>"
                                alt="Phòng Premium Twin">
                        </div>
                    </div>
                </div>
            </section>

            <?php
            // Lấy thông tin phòng hiện tại để loại trừ khỏi danh sách related
            require_once __DIR__ . '/../helpers/room-helper.php';
            $currentRoom = getRoomBySlug('premium-twin');
            $currentRoomTypeId = $currentRoom ? $currentRoom['id'] : null;
            $sectionTitle = __('room_detail.other_rooms');
            include '../includes/related-rooms.php';
            ?>
        </main>

        <?php include '../includes/footer.php'; ?>
    </div>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/room-detail-bg.js"></script>
</body>

</html>