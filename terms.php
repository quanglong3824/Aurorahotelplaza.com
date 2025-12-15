<?php
session_start();
require_once 'config/database.php';

$page_title = 'Điều khoản sử dụng';
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title><?php echo $page_title; ?> - Aurora Hotel Plaza</title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Hero Section -->
    <section class="policy-hero">
        <div class="policy-hero-content">
            <span class="glass-badge-accent mb-4">
                <span class="material-symbols-outlined text-accent">gavel</span>
                Quy định & Điều khoản
            </span>
            <h1 class="policy-hero-title">Điều khoản sử dụng</h1>
            <p class="policy-hero-subtitle">Quy định sử dụng dịch vụ tại Aurora Hotel Plaza</p>
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Quick Navigation -->
            <div class="glass-card-solid p-6 mb-8">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
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
                    <p class="text-lg leading-relaxed">
                        Chào mừng quý khách đến với Aurora Hotel Plaza. Bằng việc sử dụng website và dịch vụ của chúng tôi, 
                        quý khách đồng ý tuân thủ các điều khoản và điều kiện được nêu dưới đây. Vui lòng đọc kỹ trước khi 
                        sử dụng dịch vụ.
                    </p>
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-4">
                        <strong>Cập nhật lần cuối:</strong> 01/12/2025
                    </p>
                </div>

                <div id="gioi-thieu" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">1</span>
                        Giới thiệu
                    </h2>
                    <div class="policy-section-content">
                        <p>
                            Aurora Hotel Plaza là khách sạn cao cấp tọa lạc tại trung tâm thành phố Biên Hòa, tỉnh Đồng Nai. 
                            Chúng tôi cung cấp dịch vụ lưu trú, nhà hàng, hội nghị và các dịch vụ tiện ích khác.
                        </p>
                        <p>
                            Các điều khoản này áp dụng cho tất cả khách hàng sử dụng website, đặt phòng trực tuyến và 
                            sử dụng dịch vụ tại khách sạn.
                        </p>
                    </div>
                </div>

                <div id="dieu-kien" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">2</span>
                        Điều kiện sử dụng
                    </h2>
                    <div class="policy-section-content">
                        <h4>2.1. Độ tuổi</h4>
                        <ul>
                            <li>Quý khách phải từ 18 tuổi trở lên để đặt phòng</li>
                            <li>Trẻ em dưới 18 tuổi phải có người lớn đi kèm</li>
                        </ul>
                        <h4>2.2. Giấy tờ tùy thân</h4>
                        <ul>
                            <li>Khách Việt Nam: CMND/CCCD còn hiệu lực</li>
                            <li>Khách nước ngoài: Hộ chiếu và visa hợp lệ</li>
                            <li>Giấy tờ phải được xuất trình khi nhận phòng</li>
                        </ul>
                        <h4>2.3. Tài khoản người dùng</h4>
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
                    <div class="policy-section-content">
                        <h4>3.1. Thời gian nhận/trả phòng</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-4">
                            <div class="glass-card-solid p-4 text-center">
                                <span class="material-symbols-outlined text-accent text-3xl mb-2">login</span>
                                <h5 class="font-bold">Nhận phòng (Check-in)</h5>
                                <p class="text-2xl font-bold text-accent">14:00</p>
                            </div>
                            <div class="glass-card-solid p-4 text-center">
                                <span class="material-symbols-outlined text-accent text-3xl mb-2">logout</span>
                                <h5 class="font-bold">Trả phòng (Check-out)</h5>
                                <p class="text-2xl font-bold text-accent">12:00</p>
                            </div>
                        </div>
                        <ul>
                            <li>Nhận phòng sớm hoặc trả phòng muộn có thể phát sinh phụ phí</li>
                            <li>Vui lòng liên hệ lễ tân để được hỗ trợ</li>
                        </ul>
                        
                        <h4>3.2. Xác nhận đặt phòng</h4>
                        <ul>
                            <li>Đặt phòng chỉ được xác nhận sau khi nhận email/SMS xác nhận</li>
                            <li>Mã đặt phòng cần được lưu giữ để check-in</li>
                            <li>Thông tin đặt phòng có thể thay đổi tùy theo tình trạng phòng</li>
                        </ul>

                        <h4>3.3. Số lượng khách</h4>
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
                    <div class="policy-section-content">
                        <h4>4.1. Phương thức thanh toán</h4>
                        <div class="flex flex-wrap gap-3 my-4">
                            <span class="glass-badge-solid">💳 Thẻ tín dụng/ghi nợ</span>
                            <span class="glass-badge-solid">🏦 Chuyển khoản ngân hàng</span>
                            <span class="glass-badge-solid">💵 Tiền mặt</span>
                            <span class="glass-badge-solid">📱 Ví điện tử</span>
                        </div>
                        
                        <h4>4.2. Chính sách giá</h4>
                        <ul>
                            <li>Giá phòng đã bao gồm VAT 10%</li>
                            <li>Giá có thể thay đổi theo mùa và sự kiện</li>
                            <li>Các dịch vụ bổ sung sẽ được tính riêng</li>
                        </ul>

                        <h4>4.3. Đặt cọc</h4>
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
                    <div class="policy-section-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="glass-card-solid p-4">
                                <h5 class="font-bold text-green-600 mb-3 flex items-center gap-2">
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
                                <h5 class="font-bold text-red-600 mb-3 flex items-center gap-2">
                                    <span class="material-symbols-outlined">cancel</span>
                                    Không được phép
                                </h5>
                                <ul class="text-sm space-y-2">
                                    <li>✗ Hút thuốc trong phòng (phạt 500.000đ)</li>
                                    <li>✗ Mang vật nuôi (trừ phòng cho phép)</li>
                                    <li>✗ Gây ồn ào sau 22:00</li>
                                    <li>✗ Mang chất cấm, vũ khí</li>
                                </ul>
                            </div>
                        </div>
                        
                        <h4>5.1. Giờ yên tĩnh</h4>
                        <p>Từ 22:00 đến 07:00 hàng ngày. Quý khách vui lòng giữ yên lặng để không ảnh hưởng đến khách khác.</p>
                        
                        <h4>5.2. Tài sản</h4>
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
                    <div class="policy-section-content">
                        <h4>6.1. Trách nhiệm của khách sạn</h4>
                        <ul>
                            <li>Cung cấp dịch vụ đúng như mô tả</li>
                            <li>Đảm bảo an ninh, an toàn cho khách</li>
                            <li>Hỗ trợ khách hàng 24/7</li>
                            <li>Bảo mật thông tin cá nhân</li>
                        </ul>
                        
                        <h4>6.2. Trách nhiệm của khách hàng</h4>
                        <ul>
                            <li>Tuân thủ nội quy khách sạn</li>
                            <li>Cung cấp thông tin chính xác</li>
                            <li>Thanh toán đầy đủ các chi phí</li>
                            <li>Bảo quản tài sản khách sạn</li>
                        </ul>

                        <h4>6.3. Giới hạn trách nhiệm</h4>
                        <p>
                            Khách sạn không chịu trách nhiệm cho các thiệt hại gián tiếp, mất mát do sự kiện bất khả kháng 
                            (thiên tai, dịch bệnh, chiến tranh...).
                        </p>
                    </div>
                </div>

                <div id="thay-doi" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">7</span>
                        Thay đổi điều khoản
                    </h2>
                    <div class="policy-section-content">
                        <p>
                            Aurora Hotel Plaza có quyền thay đổi các điều khoản này bất cứ lúc nào. Các thay đổi sẽ có 
                            hiệu lực ngay khi được đăng tải trên website. Việc tiếp tục sử dụng dịch vụ sau khi có thay đổi 
                            đồng nghĩa với việc quý khách chấp nhận các điều khoản mới.
                        </p>
                        
                        <div class="glass-card-solid p-6 mt-6">
                            <h4 class="font-bold text-lg mb-4">Liên hệ hỗ trợ</h4>
                            <p class="mb-4">Nếu có thắc mắc về điều khoản sử dụng, vui lòng liên hệ:</p>
                            <div class="space-y-2">
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">phone</span>
                                    <a href="tel:+842513918888" class="hover:text-accent">(+84-251) 391.8888</a>
                                </p>
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">email</span>
                                    <a href="mailto:info@aurorahotelplaza.com" class="hover:text-accent">info@aurorahotelplaza.com</a>
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

<style>
.policy-hero {
    position: relative;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.9), rgba(17, 24, 39, 0.7)), url('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg');
    background-size: cover;
    background-position: center;
    padding: 160px 20px 80px;
    text-align: center;
    color: white;
    min-height: 350px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.policy-hero-content {
    position: relative;
    z-index: 1;
    max-width: 800px;
    margin: 0 auto;
}

.policy-hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 16px;
    text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
}

.policy-hero-subtitle {
    font-size: 18px;
    opacity: 0.9;
}

.policy-nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba(212, 175, 55, 0.1);
    border-radius: 8px;
    color: var(--text-primary-light);
    font-size: 14px;
    transition: all 0.2s ease;
}

.policy-nav-link:hover {
    background: rgba(212, 175, 55, 0.2);
    color: #cc9a2c;
}

.dark .policy-nav-link {
    color: var(--text-primary-dark);
}

.policy-section {
    margin-bottom: 40px;
}

.policy-section-title {
    display: flex;
    align-items: center;
    gap: 16px;
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid rgba(212, 175, 55, 0.3);
}

.policy-section-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: white;
    border-radius: 50%;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 18px;
    font-weight: 700;
}

.policy-section-content {
    padding-left: 56px;
}

.policy-section-content p {
    margin-bottom: 16px;
    line-height: 1.8;
}

.policy-section-content h4 {
    font-weight: 700;
    margin: 20px 0 12px;
    color: #cc9a2c;
}

.policy-section-content h5 {
    font-weight: 600;
}

.policy-section-content ul {
    list-style: none;
    padding: 0;
    margin: 16px 0;
}

.policy-section-content ul li {
    position: relative;
    padding-left: 28px;
    margin-bottom: 12px;
    line-height: 1.6;
}

.policy-section-content ul li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #cc9a2c;
    font-weight: 700;
}

@media (max-width: 768px) {
    .policy-hero-title {
        font-size: 32px;
    }
    
    .policy-section-content {
        padding-left: 0;
    }
    
    .policy-section-title {
        font-size: 20px;
    }
}
</style>

</body>
</html>
