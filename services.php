<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Dịch vụ - Aurora Hotel Plaza</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/services.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-services">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <h1 class="page-title">Dịch vụ của chúng tôi</h1>
            <p class="page-subtitle">Trải nghiệm đẳng cấp với các dịch vụ chuyên nghiệp và tiện nghi hiện đại</p>
        </div>
    </section>

    <!-- Services Grid -->
    <section class="section-padding">
        <div class="container-custom">
            <div class="services-grid">
                <!-- Wedding Service -->
                <div class="service-card">
                    <div class="service-image-wrapper">
                        <img src="assets/img/post/wedding/Tiec-cuoi-tai-aurora-5.jpg" alt="Tổ chức tiệc cưới" class="service-image">
                        <div class="service-overlay">
                            <a href="wedding.php" class="service-link">
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                    <div class="service-content">
                        <div class="service-icon">
                            <span class="material-symbols-outlined">celebration</span>
                        </div>
                        <h3 class="service-title">Tổ chức tiệc cưới</h3>
                        <p class="service-description">
                            Không gian sang trọng, lãng mạn cùng đội ngũ chuyên nghiệp sẽ biến ngày trọng đại của bạn thành hiện thực.
                        </p>
                        <ul class="service-features">
                            <li>Sảnh tiệc rộng 500m²</li>
                            <li>Trang trí theo yêu cầu</li>
                            <li>Menu đa dạng</li>
                            <li>Âm thanh ánh sáng hiện đại</li>
                        </ul>
                        <a href="wedding.php" class="service-button">Xem chi tiết</a>
                    </div>
                </div>

                <!-- Conference Service -->
                <div class="service-card">
                    <div class="service-image-wrapper">
                        <img src="assets/img/src/ui/horizontal/hoi-nghi-khach-san-o-bien-hoa.jpg" alt="Tổ chức hội nghị" class="service-image">
                        <div class="service-overlay">
                            <a href="conference.php" class="service-link">
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                    <div class="service-content">
                        <div class="service-icon">
                            <span class="material-symbols-outlined">business_center</span>
                        </div>
                        <h3 class="service-title">Tổ chức hội nghị</h3>
                        <p class="service-description">
                            Phòng họp hiện đại với đầy đủ trang thiết bị, phù hợp cho mọi quy mô sự kiện doanh nghiệp.
                        </p>
                        <ul class="service-features">
                            <li>Phòng họp 50-300 người</li>
                            <li>Thiết bị hội nghị hiện đại</li>
                            <li>Dịch vụ coffee break</li>
                            <li>Hỗ trợ kỹ thuật chuyên nghiệp</li>
                        </ul>
                        <a href="conference.php" class="service-button">Xem chi tiết</a>
                    </div>
                </div>

                <!-- Restaurant Service -->
                <div class="service-card">
                    <div class="service-image-wrapper">
                        <img src="assets/img/restaurant/NHA-HANG-AURORA-HOTEL-4.jpg" alt="Nhà hàng" class="service-image">
                        <div class="service-overlay">
                            <a href="restaurant.php" class="service-link">
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                    <div class="service-content">
                        <div class="service-icon">
                            <span class="material-symbols-outlined">restaurant</span>
                        </div>
                        <h3 class="service-title">Nhà hàng</h3>
                        <p class="service-description">
                            Thưởng thức ẩm thực Á - Âu đa dạng với đầu bếp giàu kinh nghiệm trong không gian sang trọng.
                        </p>
                        <ul class="service-features">
                            <li>Buffet sáng miễn phí</li>
                            <li>Menu Á - Âu đa dạng</li>
                            <li>Không gian sang trọng</li>
                            <li>Phục vụ 6:00 - 22:00</li>
                        </ul>
                        <a href="restaurant.php" class="service-button">Xem chi tiết</a>
                    </div>
                </div>

                <!-- Office Rental -->
                <div class="service-card">
                    <div class="service-image-wrapper">
                        <img src="assets/img/src/ui/horizontal/phong-studio-khach-san-aurora-bien-hoa.jpg" alt="Văn phòng cho thuê" class="service-image">
                        <div class="service-overlay">
                            <a href="office.php" class="service-link">
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                    <div class="service-content">
                        <div class="service-icon">
                            <span class="material-symbols-outlined">apartment</span>
                        </div>
                        <h3 class="service-title">Văn phòng cho thuê</h3>
                        <p class="service-description">
                            Không gian làm việc chuyên nghiệp, tiện nghi hiện đại phù hợp cho doanh nghiệp và startup.
                        </p>
                        <ul class="service-features">
                            <li>Diện tích linh hoạt</li>
                            <li>Đầy đủ tiện nghi</li>
                            <li>Bảo mật 24/7</li>
                            <li>Giá cả cạnh tranh</li>
                        </ul>
                        <a href="office.php" class="service-button">Xem chi tiết</a>
                    </div>
                </div>

                <!-- Spa Service -->
                <div class="service-card">
                    <div class="service-image-wrapper">
                        <img src="assets/img/deluxe/DELUXE-ROOM-AURORA-1.jpg" alt="Spa & Massage" class="service-image">
                        <div class="service-overlay">
                            <a href="#" class="service-link">
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                    <div class="service-content">
                        <div class="service-icon">
                            <span class="material-symbols-outlined">spa</span>
                        </div>
                        <h3 class="service-title">Spa & Massage</h3>
                        <p class="service-description">
                            Thư giãn và phục hồi năng lượng với các liệu trình spa chuyên nghiệp từ đội ngũ kỹ thuật viên giàu kinh nghiệm.
                        </p>
                        <ul class="service-features">
                            <li>Massage body truyền thống</li>
                            <li>Chăm sóc da mặt</li>
                            <li>Liệu trình thư giãn</li>
                            <li>Sản phẩm cao cấp</li>
                        </ul>
                        <a href="#" class="service-button">Xem chi tiết</a>
                    </div>
                </div>

                <!-- Pool & Gym -->
                <div class="service-card">
                    <div class="service-image-wrapper">
                        <img src="assets/img/deluxe/DELUXE-ROOM-AURORA-3.jpg" alt="Hồ bơi & Gym" class="service-image">
                        <div class="service-overlay">
                            <a href="#" class="service-link">
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                    <div class="service-content">
                        <div class="service-icon">
                            <span class="material-symbols-outlined">pool</span>
                        </div>
                        <h3 class="service-title">Hồ bơi & Gym</h3>
                        <p class="service-description">
                            Duy trì sức khỏe với hồ bơi ngoài trời và phòng gym hiện đại với đầy đủ thiết bị tập luyện.
                        </p>
                        <ul class="service-features">
                            <li>Hồ bơi ngoài trời</li>
                            <li>Gym hiện đại</li>
                            <li>Huấn luyện viên chuyên nghiệp</li>
                            <li>Mở cửa 6:00 - 22:00</li>
                        </ul>
                        <a href="#" class="service-button">Xem chi tiết</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container-custom">
            <div class="cta-content">
                <h2 class="cta-title">Cần tư vấn thêm?</h2>
                <p class="cta-description">
                    Liên hệ với chúng tôi để được tư vấn chi tiết về các dịch vụ phù hợp với nhu cầu của bạn
                </p>
                <a href="contact.php" class="cta-button">
                    <span class="material-symbols-outlined">phone</span>
                    Liên hệ ngay
                </a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
