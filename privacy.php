<?php
session_start();
require_once 'config/database.php';

$page_title = 'Chính sách bảo mật';
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
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
        <div class="policy-hero-overlay"></div>
        <div class="policy-hero-content">
            <span class="glass-badge-accent mb-4">
                <span class="material-symbols-outlined text-accent">security</span>
                Bảo mật thông tin
            </span>
            <h1 class="policy-hero-title">Chính sách bảo mật</h1>
            <p class="policy-hero-subtitle">Cam kết bảo vệ thông tin cá nhân của quý khách</p>
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
                    <a href="#thu-thap" class="policy-nav-link">1. Thu thập thông tin</a>
                    <a href="#su-dung" class="policy-nav-link">2. Sử dụng thông tin</a>
                    <a href="#bao-mat" class="policy-nav-link">3. Bảo mật thông tin</a>
                    <a href="#chia-se" class="policy-nav-link">4. Chia sẻ thông tin</a>
                    <a href="#cookie" class="policy-nav-link">5. Cookie và công nghệ</a>
                    <a href="#quyen-loi" class="policy-nav-link">6. Quyền của khách hàng</a>
                    <a href="#lien-he" class="policy-nav-link">7. Liên hệ</a>
                </div>
            </div>

            <!-- Policy Content -->
            <div class="policy-content">
                <div class="policy-intro glass-card-solid p-6 mb-8">
                    <p class="text-lg leading-relaxed">
                        Aurora Hotel Plaza cam kết bảo vệ quyền riêng tư và thông tin cá nhân của quý khách. 
                        Chính sách bảo mật này giải thích cách chúng tôi thu thập, sử dụng và bảo vệ thông tin 
                        của quý khách khi sử dụng dịch vụ tại khách sạn và website của chúng tôi.
                    </p>
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-4">
                        <strong>Cập nhật lần cuối:</strong> 01/12/2025
                    </p>
                </div>

                <div id="thu-thap" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">1</span>
                        Thu thập thông tin
                    </h2>
                    <div class="policy-section-content">
                        <p>Chúng tôi thu thập các loại thông tin sau:</p>
                        <h4>1.1. Thông tin cá nhân</h4>
                        <ul>
                            <li>Họ và tên đầy đủ</li>
                            <li>Số điện thoại liên hệ</li>
                            <li>Địa chỉ email</li>
                            <li>Số CMND/CCCD/Hộ chiếu</li>
                            <li>Địa chỉ thường trú</li>
                            <li>Ngày sinh</li>
                        </ul>
                        <h4>1.2. Thông tin đặt phòng</h4>
                        <ul>
                            <li>Ngày nhận phòng và trả phòng</li>
                            <li>Loại phòng và dịch vụ yêu cầu</li>
                            <li>Số lượng khách lưu trú</li>
                            <li>Yêu cầu đặc biệt (nếu có)</li>
                        </ul>
                        <h4>1.3. Thông tin thanh toán</h4>
                        <ul>
                            <li>Thông tin thẻ tín dụng/ghi nợ (được mã hóa)</li>
                            <li>Lịch sử giao dịch</li>
                            <li>Hóa đơn và biên lai</li>
                        </ul>
                    </div>
                </div>

                <div id="su-dung" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">2</span>
                        Sử dụng thông tin
                    </h2>
                    <div class="policy-section-content">
                        <p>Thông tin của quý khách được sử dụng cho các mục đích:</p>
                        <ul>
                            <li><strong>Xử lý đặt phòng:</strong> Xác nhận và quản lý đặt phòng của quý khách</li>
                            <li><strong>Cung cấp dịch vụ:</strong> Đảm bảo trải nghiệm lưu trú tốt nhất</li>
                            <li><strong>Liên lạc:</strong> Gửi xác nhận, thông báo và hỗ trợ khách hàng</li>
                            <li><strong>Cải thiện dịch vụ:</strong> Phân tích và nâng cao chất lượng phục vụ</li>
                            <li><strong>Marketing:</strong> Gửi ưu đãi và khuyến mãi (với sự đồng ý của quý khách)</li>
                            <li><strong>Tuân thủ pháp luật:</strong> Đáp ứng yêu cầu của cơ quan chức năng</li>
                        </ul>
                    </div>
                </div>

                <div id="bao-mat" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">3</span>
                        Bảo mật thông tin
                    </h2>
                    <div class="policy-section-content">
                        <p>Chúng tôi áp dụng các biện pháp bảo mật nghiêm ngặt:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="glass-card-solid p-4">
                                <span class="material-symbols-outlined text-accent text-2xl mb-2">encrypted</span>
                                <h4 class="font-bold mb-1">Mã hóa SSL/TLS</h4>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Tất cả dữ liệu được mã hóa khi truyền tải</p>
                            </div>
                            <div class="glass-card-solid p-4">
                                <span class="material-symbols-outlined text-accent text-2xl mb-2">shield</span>
                                <h4 class="font-bold mb-1">Firewall bảo vệ</h4>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Hệ thống tường lửa chống xâm nhập</p>
                            </div>
                            <div class="glass-card-solid p-4">
                                <span class="material-symbols-outlined text-accent text-2xl mb-2">lock</span>
                                <h4 class="font-bold mb-1">Kiểm soát truy cập</h4>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Chỉ nhân viên được ủy quyền mới truy cập</p>
                            </div>
                            <div class="glass-card-solid p-4">
                                <span class="material-symbols-outlined text-accent text-2xl mb-2">backup</span>
                                <h4 class="font-bold mb-1">Sao lưu định kỳ</h4>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Dữ liệu được sao lưu an toàn hàng ngày</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="chia-se" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">4</span>
                        Chia sẻ thông tin
                    </h2>
                    <div class="policy-section-content">
                        <p>Chúng tôi <strong>không bán</strong> thông tin cá nhân của quý khách. Thông tin chỉ được chia sẻ trong các trường hợp:</p>
                        <ul>
                            <li>Đối tác cung cấp dịch vụ (thanh toán, vận chuyển) với cam kết bảo mật</li>
                            <li>Cơ quan chức năng theo yêu cầu pháp luật</li>
                            <li>Với sự đồng ý rõ ràng của quý khách</li>
                        </ul>
                    </div>
                </div>

                <div id="cookie" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">5</span>
                        Cookie và công nghệ theo dõi
                    </h2>
                    <div class="policy-section-content">
                        <p>Website của chúng tôi sử dụng cookie để:</p>
                        <ul>
                            <li>Ghi nhớ thông tin đăng nhập và tùy chọn của quý khách</li>
                            <li>Phân tích lưu lượng truy cập và hành vi người dùng</li>
                            <li>Cải thiện trải nghiệm sử dụng website</li>
                            <li>Hiển thị nội dung phù hợp</li>
                        </ul>
                        <p class="mt-4">Quý khách có thể tắt cookie trong cài đặt trình duyệt, tuy nhiên một số tính năng có thể bị ảnh hưởng.</p>
                    </div>
                </div>

                <div id="quyen-loi" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">6</span>
                        Quyền của khách hàng
                    </h2>
                    <div class="policy-section-content">
                        <p>Quý khách có các quyền sau đối với thông tin cá nhân:</p>
                        <ul>
                            <li><strong>Quyền truy cập:</strong> Yêu cầu xem thông tin chúng tôi lưu trữ</li>
                            <li><strong>Quyền chỉnh sửa:</strong> Cập nhật thông tin không chính xác</li>
                            <li><strong>Quyền xóa:</strong> Yêu cầu xóa thông tin (trong phạm vi pháp luật cho phép)</li>
                            <li><strong>Quyền từ chối:</strong> Hủy đăng ký nhận email marketing</li>
                            <li><strong>Quyền khiếu nại:</strong> Liên hệ chúng tôi nếu có thắc mắc</li>
                        </ul>
                    </div>
                </div>

                <div id="lien-he" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">7</span>
                        Liên hệ
                    </h2>
                    <div class="policy-section-content">
                        <p>Nếu quý khách có bất kỳ câu hỏi nào về chính sách bảo mật, vui lòng liên hệ:</p>
                        <div class="glass-card-solid p-6 mt-4">
                            <h4 class="font-bold text-lg mb-4">Aurora Hotel Plaza</h4>
                            <div class="space-y-3">
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">location_on</span>
                                    Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, TP. Biên Hòa, Đồng Nai
                                </p>
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">phone</span>
                                    <a href="tel:+842513918888" class="hover:text-accent">(+84-251) 391.8888</a>
                                </p>
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">email</span>
                                    <a href="mailto:privacy@aurorahotelplaza.com" class="hover:text-accent">privacy@aurorahotelplaza.com</a>
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

.policy-hero-overlay {
    display: none;
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
