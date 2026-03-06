<?php
session_start();
require_once 'config/database.php';

$page_title = 'Điều khoản sử dụng';
?>
<!DOCTYPE html>
<html translate="no" class="light" lang="vi">

<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php echo $page_title; ?> - Aurora Hotel Plaza</title>
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet" />
    <script src="assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/liquid-glass.css">
    <link rel="stylesheet" href="assets/css/pages-glass.css">
    <link rel="stylesheet" href="assets/css/policy.css">
</head>

<body class="glass-page font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include 'includes/header.php'; ?>

        <main class="flex h-full grow flex-col">
                <!-- Hero Section - Using Policy Hero Class -->
                <section class="policy-hero-glass">
                    <div class="hero-glass-card">
                        <div class="glass-badge-pill mb-4 justify-center mx-auto">
                            <span class="material-symbols-outlined text-sm">gavel</span>
                            Quy định & Điều khoản
                        </div>
                        <h1 class="hero-title-glass">Điều khoản sử dụng</h1>
                        <p class="hero-subtitle-glass">Quy định sử dụng dịch vụ tại Aurora Hotel Plaza</p>
                    </div>
                </section>

                <!-- Content Section -->
                <section class="py-16">
                    <div class="max-w-4xl mx-auto px-4">
                        <!-- Quick Navigation -->
                        <div class="glass-card-solid p-6 mb-8">
                            <h3 class="font-bold text-lg mb-4 flex items-center gap-2 text-white">
                                <span class="material-symbols-outlined text-accent">menu_book</span>
                                Mục lục
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <a href="#gioi-thieu" class="policy-nav-link">1. Giới thiệu</a>
                                <a href="#dieu-kien" class="policy-nav-link">2. Điều kiện sử dụng</a>
                                <a href="#dat-phong" class="policy-nav-link">3. Quy định đặt phòng</a>
                                <a href="#thanh-toan" class="policy-nav-link">4. Thanh toán</a>
                                <a href="#noi-quy" class="policy-nav-link">5. Nội quy khách sạn</a>
                                <a href="#trach-nhiem" class="policy-nav-link">6. Trách nhiệm</a>
                                <a href="#thay-doi" class="policy-nav-link">7. Thay đổi điều khoản</a>
                            </div>
                        </div>

                        <!-- Policy Content -->
                        <div class="policy-content">
                            <div class="policy-intro glass-card-solid p-6 mb-8">
                                <p class="text-lg leading-relaxed text-white/90">
                                    Chào mừng quý khách đến với Aurora Hotel Plaza. Bằng việc sử dụng website và dịch vụ
                                    của chúng tôi,
                                    quý khách đồng ý tuân thủ các điều khoản và điều kiện được nêu dưới đây. Vui lòng
                                   đọc kỹ trước khi
                                    sử dụng dịch vụ.
                                </p>
                                <p class="text-sm text-white/60 mt-4">
                                    <strong>Cập nhật lần cuối:</strong> 12/01/2025
                                </p>
                            </div>

                            <div id="gioi-thieu" class="policy-section">
                                <h2 class="policy-section-title">
                                    <span class="policy-section-number">1</span>
                                    Giới thiệu
                                </h2>
                                <div class="policy-section-content text-white/80">
                                    <p>
                                        Aurora Hotel Plaza là khách sạn cao cấp tọa lạc tại trung tâm thành phố Biên
                                        Hòa, tỉnh Đồng Nai.
                                        Chúng tôi cung cấp dịch vụ lưu trú, nhà hàng, hội nghị và các dịch vụ tiện ích
                                        khác.
                                    </p>
                                    <p>
                                        Các điều khoản này áp dụng cho tất cả khách hàng sử dụng website, đặt phòng trực
                                        tuyến và
                                        sử dụng dịch vụ tại khách sạn.
                                    </p>
                                </div>
                            </div>

                            <div id="dieu-kien" class="policy-section">
                                <h2 class="policy-section-title">
                                    <span class="policy-section-number">2</span>
                                    Điều kiện sử dụng
                                </h2>
                                <div class="policy-section-content text-white/80">
                                    <h4 class="text-accent">2.1. Độ tuổi</h4>
                                    <ul>
                                        <li>Quý khách phải từ 18 tuổi trở lên để đặt phòng</li>
                                        <li>Trẻ em dưới 18 tuổi phải có người lớn đi kèm</li>
                                    </ul>
                                    <h4 class="text-accent">2.2. Giấy tờ tùy thân</h4>
                                    <ul>
                                        <li>Khách Việt Nam: CMND/CCCD còn hiệu lực</li>
                                        <li>Khách nước ngoài: Hộ chiếu và visa hợp lệ</li>
                                        <li>Giấy tờ phải được xuất trình khi nhận phòng</li>
                                    </ul>
                                    <h4 class="text-accent">2.3. Tài khoản người dùng</h4>
                                    <ul>
                                        <li>Thông tin đăng ký phải chính xác và đầy đủ</li>
                                        <li>Quý khách chịu trách nhiệm bảo mật tài khoản</li>
                                        <li>Không được chia sẻ tài khoản cho người khác</li>
                                    </ul>
                                </div>
                            </div>

                            <div id="dat-phong" class="policy-section">
                                <h2 class="policy-section-title">
                                    <span class="policy-section-number">3</span>
                                    Quy định đặt phòng
                                </h2>
                                <div class="policy-section-content text-white/80">
                                    <h4 class="text-accent">3.1. Thời gian nhận/trả phòng</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-4">
                                        <div class="glass-card-solid p-4 text-center">
                                            <span
                                                class="material-symbols-outlined text-accent text-3xl mb-2">login</span>
                                            <h5 class="font-bold text-white">Nhận phòng (Check-in)</h5>
                                            <p class="text-2xl font-bold text-accent">14:00</p>
                                        </div>
                                        <div class="glass-card-solid p-4 text-center">
                                            <span
                                                class="material-symbols-outlined text-accent text-3xl mb-2">logout</span>
                                            <h5 class="font-bold text-white">Trả phòng (Check-out)</h5>
                                            <p class="text-2xl font-bold text-accent">12:00</p>
                                        </div>
                                    </div>
                                    <ul>
                                        <li>Nhận phòng sớm hoặc trả phòng muộn có thể phát sinh phụ phí</li>
                                        <li>Vui lòng liên hệ lễ tân để được hỗ trợ</li>
                                    </ul>

                                    <h4 class="text-accent">3.2. Xác nhận đặt phòng</h4>
                                    <ul>
                                        <li>Đặt phòng chỉ được xác nhận sau khi nhận email/SMS xác nhận</li>
                                        <li>Mã đặt phòng cần được lưu giữ để check-in</li>
                                        <li>Thông tin đặt phòng có thể thay đổi tùy theo tình trạng phòng</li>
                                    </ul>

                                    <h4 class="text-accent">3.3. Số lượng khách</h4>
                                    <ul>
                                        <li>Số khách lưu trú không được vượt quá sức chứa của phòng</li>
                                        <li>Khách thêm có thể phát sinh phụ phí</li>
                                        <li>Trẻ em dưới 6 tuổi được miễn phí (ngủ chung giường với bố mẹ)</li>
                                    </ul>
                                </div>
                            </div>

                            <div id="thanh-toan" class="policy-section">
                                <h2 class="policy-section-title">
                                    <span class="policy-section-number">4</span>
                                    Thanh toán
                                </h2>
                                <div class="policy-section-content text-white/80">
                                    <h4 class="text-accent">4.1. Phương thức thanh toán</h4>
                                    <div class="flex flex-wrap gap-3 my-4">
                                        <span class="glass-badge-solid">💳 Thẻ tín dụng/ghi nợ</span>
                                        <span class="glass-badge-solid">🏦 Chuyển khoản ngân hàng</span>
                                        <span class="glass-badge-solid">💵 Tiền mặt</span>
                                        <span class="glass-badge-solid">📱 Ví điện tử</span>
                                    </div>

                                    <h4 class="text-accent">4.2. Chính sách giá</h4>
                                    <ul>
                                        <li>Giá phòng đã bao gồm VAT 10%</li>
                                        <li>Giá có thể thay đổi theo mùa và sự kiện</li>
                                        <li>Các dịch vụ bổ sung sẽ được tính riêng</li>
                                    </ul>

                                    <h4 class="text-accent">4.3. Đặt cọc</h4>
                                    <ul>
                                        <li>Đặt cọc 50% khi đặt phòng online</li>
                                        <li>Thanh toán phần còn lại khi check-in</li>
                                        <li>Đặt cọc sẽ được hoàn trả theo chính sách hủy phòng</li>
                                    </ul>
                                </div>
                            </div>

                            <div id="noi-quy" class="policy-section">
                                <h2 class="policy-section-title">
                                    <span class="policy-section-number">5</span>
                                    Nội quy khách sạn
                                </h2>
                                <div class="policy-section-content text-white/80">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="glass-card-solid p-4">
                                            <h5 class="font-bold text-green-400 mb-3 flex items-center gap-2">
                                                <span class="material-symbols-outlined">check_circle</span>
                                                Được phép
                                            </h5>
                                            <ul class="text-sm space-y-2">
                                                <li>✓ Sử dụng các tiện ích của khách sạn</li>
                                                <li>✓ Yêu cầu dịch vụ phòng 24/7</li>
                                                <li>✓ Mời khách đến thăm (đăng ký tại lễ tân)</li>
                                                <li>✓ Sử dụng WiFi miễn phí</li>
                                            </ul>
                                        </div>
                                        <div class="glass-card-solid p-4">
                                            <h5 class="font-bold text-red-400 mb-3 flex items-center gap-2">
                                                <span class="material-symbols-outlined">cancel</span>
                                                Không được phép
                                            </h5>
                                            <ul class="text-sm space-y-2">
                                                <li>✗ Hút thuốc trong phòng (phạt 500.000 VND)</li>
                                                <li>✗ Mang vật nuôi (trừ phòng cho phép)</li>
                                                <li>✗ Gây ồn ào sau 22:00</li>
                                                <li>✗ Mang chất cấm, vũ khí</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <h4 class="text-accent">5.1. Giờ yên tĩnh</h4>
                                    <p>Từ 22:00 đến 07:00 hàng ngày. Quý khách vui lòng giữ yên lặng để không ảnh hưởng
                                       đến khách khác.</p>

                                    <h4 class="text-accent">5.2. Tài sản</h4>
                                    <ul>
                                        <li>Khách sạn không chịu trách nhiệm với tài sản không gửi két an toàn</li>
                                        <li>Hư hỏng tài sản khách sạn sẽ được bồi thường theo giá trị</li>
                                    </ul>
                                </div>
                            </div>

                            <div id="trach-nhiem" class="policy-section">
                                <h2 class="policy-section-title">
                                    <span class="policy-section-number">6</span>
                                    Trách nhiệm
                                </h2>
                                <div class="policy-section-content text-white/80">
                                    <h4 class="text-accent">6.1. Trách nhiệm của khách sạn</h4>
                                    <ul>
                                        <li>Cung cấp dịch vụ đúng như mô tả</li>
                                        <li>Đảm bảo an ninh, an toàn cho khách</li>
                                        <li>Hỗ trợ khách hàng 24/7</li>
                                        <li>Bảo mật thông tin cá nhân</li>
                                    </ul>

                                    <h4 class="text-accent">6.2. Trách nhiệm của khách hàng</h4>
                                    <ul>
                                        <li>Tuân thủ nội quy khách sạn</li>
                                        <li>Cung cấp thông tin chính xác</li>
                                        <li>Thanh toán đầy đủ các chi phí</li>
                                        <li>Bảo quản tài sản khách sạn</li>
                                    </ul>

                                    <h4 class="text-accent">6.3. Giới hạn trách nhiệm</h4>
                                    <p>
                                        Khách sạn không chịu trách nhiệm cho các thiệt hại gián tiếp, mất mát do sự kiện
                                        bất khả kháng
                                        (thiên tai, dịch bệnh, chiến tranh...).
                                    </p>
                                </div>
                            </div>

                            <div id="thay-doi" class="policy-section">
                                <h2 class="policy-section-title">
                                    <span class="policy-section-number">7</span>
                                    Thay đổi điều khoản
                                </h2>
                                <div class="policy-section-content text-white/80">
                                    <p>
                                        Aurora Hotel Plaza có quyền thay đổi các điều khoản này bất cứ lúc nào. Các thay
                                       đổi sẽ có
                                        hiệu lực ngay khi được đăng tải trên website. Việc tiếp tục sử dụng dịch vụ sau
                                        khi có thay đổi
                                       đồng nghĩa với việc quý khách chấp nhận các điều khoản mới.
                                    </p>

                                    <div class="glass-card-solid p-6 mt-6">
                                        <h4 class="font-bold text-lg mb-4 text-white">Liên hệ hỗ trợ</h4>
                                        <p class="mb-4">Nếu có thắc mắc về điều khoản sử dụng, vui lòng liên hệ:</p>
                                        <div class="space-y-2">
                                            <p class="flex items-center gap-3">
                                                <span class="material-symbols-outlined text-accent">phone</span>
                                                <a href="tel:+842513918888"
                                                    class="hover:text-accent font-semibold text-white">(+84-251)
                                                    391.8888</a>
                                            </p>
                                            <p class="flex items-center gap-3">
                                                <span class="material-symbols-outlined text-accent">email</span>
                                                <a href="mailto:info@aurorahotelplaza.com"
                                                    class="hover:text-accent text-white">info@aurorahotelplaza.com</a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

</body>

</html>