<?php
require_once __DIR__ . '/../helpers/language.php';
initLanguage();
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php _e('room_detail.premium_twin_title'); ?></title>
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
    <section class="page-header-room" style="background-image: url('../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-1.jpg');">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="room-badge-header">Linh hoạt</span>
            <h1 class="page-title">Phòng Premium Twin</h1>
            <p class="page-subtitle">Hai giường đơn cao cấp, lý tưởng cho bạn bè hoặc đồng nghiệp</p>
        </div>
    </section>

    <!-- Room Info -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="room-info-section">
                <div class="room-content">
                    <p class="room-description">
                        Phòng Premium Twin rộng 42m² được thiết kế hiện đại với 2 giường đơn cao cấp, tầm nhìn thành phố đẹp. 
                        Phòng được trang bị đầy đủ tiện nghi như TV màn hình phẳng 50", minibar, két an toàn và phòng tắm riêng với 
                        vòi sen massage. Đây là lựa chọn hoàn hảo cho bạn bè, đồng nghiệp hoặc gia đình có trẻ em.
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
                            <div class="amenity-item">WiFi miễn phí tốc độ cao</div>
                            <div class="amenity-item">TV màn hình phẳng 50"</div>
                            <div class="amenity-item">Điều hòa nhiệt độ</div>
                            <div class="amenity-item">Minibar đầy đủ</div>
                            <div class="amenity-item">Két an toàn</div>
                            <div class="amenity-item">2 Bàn làm việc</div>
                            <div class="amenity-item">Phòng tắm riêng</div>
                            <div class="amenity-item">Vòi sen massage</div>
                            <div class="amenity-item">Máy sấy tóc</div>
                            <div class="amenity-item">Đồ vệ sinh cá nhân</div>
                            <div class="amenity-item">Dép đi trong phòng</div>
                            <div class="amenity-item">Ấm đun nước</div>
                        </div>
                    </div>
                </div>

                <div class="booking-card">
                    <div class="price-section">
                        <div class="price-label"><?php _e('room_detail.room_price'); ?></div>
                        <div>
                            <span class="price-amount">1.600.000đ</span>
                            <span class="price-unit">/<?php _e('room_detail.night'); ?></span>
                        </div>
                    </div>
                    <form class="booking-form">
                        <div class="form-group">
                            <label class="form-label"><?php _e('room_detail.check_in_date'); ?></label>
                            <input type="date" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php _e('room_detail.check_out_date'); ?></label>
                            <input type="date" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php _e('room_detail.num_guests'); ?></label>
                            <select class="form-input">
                                <option>1 <?php _e('room_detail.person'); ?></option>
                                <option selected>2 <?php _e('room_detail.person'); ?></option>
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
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-1.jpg" alt="Phòng Premium Twin">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-2.jpg" alt="Phòng Premium Twin">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-3.jpg" alt="Phòng Premium Twin">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-4.jpg" alt="Phòng Premium Twin">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-6.jpg" alt="Phòng Premium Twin">
                </div>
                <div class="gallery-item">
                    <img src="../assets/img/premium twin/PREMIUM-DELUXE-TWIN-AURORA-7.jpg" alt="Phòng Premium Twin">
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
</body>
</html>
