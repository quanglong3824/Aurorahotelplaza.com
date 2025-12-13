<?php
session_start();
require_once 'config/database.php';

$page_title = 'Chính sách hủy phòng';
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
        <div class="policy-hero-content">
            <span class="glass-badge-accent mb-4">
                <span class="material-symbols-outlined text-accent">event_busy</span>
                Chính sách hủy & hoàn tiền
            </span>
            <h1 class="policy-hero-title">Chính sách hủy phòng</h1>
            <p class="policy-hero-subtitle">Quy định về hủy đặt phòng và hoàn tiền tại Aurora Hotel Plaza</p>
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Important Notice -->
            <div class="glass-card-accent p-6 mb-8 border-l-4 border-accent">
                <div class="flex items-start gap-4">
                    <span class="material-symbols-outlined text-accent text-3xl">info</span>
                    <div>
                        <h3 class="font-bold text-lg mb-2">Lưu ý quan trọng</h3>
                        <p class="text-text-secondary-light dark:text-text-secondary-dark">
                            Chính sách hủy phòng có thể khác nhau tùy theo loại phòng, chương trình khuyến mãi và thời điểm đặt phòng. 
                            Vui lòng kiểm tra kỹ điều kiện hủy phòng trước khi xác nhận đặt phòng.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Cancellation Timeline -->
            <div class="mb-12">
                <h2 class="font-display text-2xl font-bold mb-6 text-center">Biểu đồ hoàn tiền</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="cancellation-card cancellation-full">
                        <div class="cancellation-icon">
                            <span class="material-symbols-outlined">sentiment_very_satisfied</span>
                        </div>
                        <div class="cancellation-time">≥ 7 ngày</div>
                        <div class="cancellation-percent">100%</div>
                        <div class="cancellation-label">Hoàn tiền đầy đủ</div>
                    </div>
                    <div class="cancellation-card cancellation-high">
                        <div class="cancellation-icon">
                            <span class="material-symbols-outlined">sentiment_satisfied</span>
                        </div>
                        <div class="cancellation-time">3-6 ngày</div>
                        <div class="cancellation-percent">70%</div>
                        <div class="cancellation-label">Hoàn 70% tiền cọc</div>
                    </div>
                    <div class="cancellation-card cancellation-medium">
                        <div class="cancellation-icon">
                            <span class="material-symbols-outlined">sentiment_neutral</span>
                        </div>
                        <div class="cancellation-time">1-2 ngày</div>
                        <div class="cancellation-percent">50%</div>
                        <div class="cancellation-label">Hoàn 50% tiền cọc</div>
                    </div>
                    <div class="cancellation-card cancellation-none">
                        <div class="cancellation-icon">
                            <span class="material-symbols-outlined">sentiment_dissatisfied</span>
                        </div>
                        <div class="cancellation-time">< 24 giờ</div>
                        <div class="cancellation-percent">0%</div>
                        <div class="cancellation-label">Không hoàn tiền</div>
                    </div>
                </div>
            </div>

            <!-- Policy Content -->
            <div class="policy-content">
                <div id="chinh-sach-chung" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">1</span>
                        Chính sách hủy phòng tiêu chuẩn
                    </h2>
                    <div class="policy-section-content">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-accent/10">
                                        <th class="p-4 text-left font-bold border-b-2 border-accent/30">Thời gian hủy</th>
                                        <th class="p-4 text-left font-bold border-b-2 border-accent/30">Phí hủy</th>
                                        <th class="p-4 text-left font-bold border-b-2 border-accent/30">Hoàn tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="p-4">Trước 7 ngày hoặc hơn</td>
                                        <td class="p-4 text-green-600 font-semibold">Miễn phí</td>
                                        <td class="p-4">100% tiền cọc</td>
                                    </tr>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="p-4">Trước 3-6 ngày</td>
                                        <td class="p-4 text-yellow-600 font-semibold">30% tiền cọc</td>
                                        <td class="p-4">70% tiền cọc</td>
                                    </tr>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="p-4">Trước 1-2 ngày</td>
                                        <td class="p-4 text-orange-600 font-semibold">50% tiền cọc</td>
                                        <td class="p-4">50% tiền cọc</td>
                                    </tr>
                                    <tr>
                                        <td class="p-4">Trong vòng 24 giờ / No-show</td>
                                        <td class="p-4 text-red-600 font-semibold">100% tiền cọc</td>
                                        <td class="p-4">Không hoàn tiền</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="loai-gia" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">2</span>
                        Chính sách theo loại giá
                    </h2>
                    <div class="policy-section-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="glass-card-solid p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="material-symbols-outlined text-green-500 text-2xl">check_circle</span>
                                    <h4 class="font-bold text-lg">Giá linh hoạt (Flexible Rate)</h4>
                                </div>
                                <ul class="space-y-2 text-sm">
                                    <li class="flex items-start gap-2">
                                        <span class="text-green-500">✓</span>
                                        Hủy miễn phí trước 24 giờ
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-green-500">✓</span>
                                        Thay đổi ngày linh hoạt
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-green-500">✓</span>
                                        Hoàn tiền đầy đủ nếu hủy đúng hạn
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="glass-card-solid p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="material-symbols-outlined text-red-500 text-2xl">lock</span>
                                    <h4 class="font-bold text-lg">Giá không hoàn tiền (Non-refundable)</h4>
                                </div>
                                <ul class="space-y-2 text-sm">
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-500">✗</span>
                                        Không được hủy hoặc thay đổi
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-500">✗</span>
                                        Không hoàn tiền trong mọi trường hợp
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-green-500">✓</span>
                                        Giá ưu đãi hơn 15-20%
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="mua-cao-diem" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">3</span>
                        Chính sách mùa cao điểm
                    </h2>
                    <div class="policy-section-content">
                        <div class="glass-card-accent p-6 mb-4">
                            <h4 class="font-bold mb-3">Các dịp cao điểm áp dụng chính sách đặc biệt:</h4>
                            <div class="flex flex-wrap gap-2">
                                <span class="glass-badge-solid">Giáng sinh (20-26/12)</span>
                                <span class="glass-badge-solid">Tết Dương lịch (30/12-2/1)</span>
                                <span class="glass-badge-solid">Tết Nguyên đán</span>
                                <span class="glass-badge-solid">Lễ 30/4 - 1/5</span>
                                <span class="glass-badge-solid">🇻Quốc khánh 2/9</span>
                            </div>
                        </div>
                        <p><strong>Trong mùa cao điểm:</strong></p>
                        <ul>
                            <li>Yêu cầu đặt cọc 100% khi đặt phòng</li>
                            <li>Hủy trước 14 ngày: Hoàn 100%</li>
                            <li>Hủy trước 7-13 ngày: Hoàn 50%</li>
                            <li>Hủy trong vòng 7 ngày: Không hoàn tiền</li>
                        </ul>
                    </div>
                </div>

                <div id="thay-doi" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">4</span>
                        Thay đổi đặt phòng
                    </h2>
                    <div class="policy-section-content">
                        <h4>4.1. Thay đổi ngày lưu trú</h4>
                        <ul>
                            <li>Miễn phí thay đổi nếu thông báo trước 48 giờ</li>
                            <li>Phụ thuộc vào tình trạng phòng trống</li>
                            <li>Chênh lệch giá (nếu có) sẽ được tính thêm hoặc hoàn lại</li>
                        </ul>
                        
                        <h4>4.2. Thay đổi loại phòng</h4>
                        <ul>
                            <li>Nâng cấp phòng: Thanh toán phần chênh lệch</li>
                            <li>Hạ cấp phòng: Hoàn lại phần chênh lệch (trừ phí xử lý 5%)</li>
                        </ul>

                        <h4>4.3. Rút ngắn thời gian lưu trú</h4>
                        <ul>
                            <li>Thông báo trước 24 giờ: Hoàn tiền các đêm không sử dụng</li>
                            <li>Không thông báo: Tính phí 1 đêm cho mỗi đêm hủy</li>
                        </ul>
                    </div>
                </div>

                <div id="hoan-tien" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">5</span>
                        Quy trình hoàn tiền
                    </h2>
                    <div class="policy-section-content">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="glass-card-solid p-4 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-accent/20 flex items-center justify-center">
                                    <span class="text-accent font-bold text-xl">1</span>
                                </div>
                                <h5 class="font-bold mb-2">Gửi yêu cầu</h5>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Liên hệ qua email hoặc hotline</p>
                            </div>
                            <div class="glass-card-solid p-4 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-accent/20 flex items-center justify-center">
                                    <span class="text-accent font-bold text-xl">2</span>
                                </div>
                                <h5 class="font-bold mb-2">Xác nhận</h5>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Nhận email xác nhận trong 24h</p>
                            </div>
                            <div class="glass-card-solid p-4 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-accent/20 flex items-center justify-center">
                                    <span class="text-accent font-bold text-xl">3</span>
                                </div>
                                <h5 class="font-bold mb-2">Hoàn tiền</h5>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">5-10 ngày làm việc</p>
                            </div>
                        </div>
                        
                        <h4>Phương thức hoàn tiền:</h4>
                        <ul>
                          <li><strong>Thẻ tín dụng/ghi nợ:</strong> Hoàn về thẻ gốc trong 5-10 ngày việc</li>
                            <li><strong>Chuyển khoản:</strong> Hoàn về tài khoản trong 3-5 ngày làm việc</li>
                            <li><strong>Tiền mặt:</strong> Nhận tại quầy lễ tân hoặc chuyển khoản</li>
                        </ul            </div>
                </div>

                <div id="bat-kha-khang" class="policy-section">
                    <h2 class="policy-section-title">
                        <span class="policy-section-number">6</span>
                        Trường hợp bất khả kháng
                    </h2>
         <div class="policy-section-content">
                        <p>Trong các trường hợp bất khả kháng sau, khách sạn sẽ hoàn tiền 100% hoặc cho phép đổi ngày miễn phí:</p>
                <ul>
                     <li>Thiên tai (bão, lũ lụt, động đất...)</li>
                            <li>Dịch bệnh được công bố bởi cơ quan y tế</li>
                            <li>Hạn chế di chuyển do chính phủ ban hành</li>
                            <li>Sự cố nghiêm trọng tại khách sạn</li>
                        </ul>
                        <p class="mt-4">
                            <strong>Lưu ý:</strong> Quý khách cần cung cấp bằng chứng liên quan (vé máy bay bị hủy, giấy xác nhận y tế...) 
                            để được xem xét hoàn tiền theo chính sách bất khả kháng.
                        </p>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="glass-card-solid p-6 mt-8">
                    <h3 class="font-bold text-xl mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-accent">support_agent</span>
                        Liên hệ hỗ trợ hủy phòng
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="mb-4">Để hủy hoặc thay đổi đặt phòng, vui lòng liên hệ:</p>
                            <div class="space-y-3">
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">phone</span>
                                    <a href="tel:+842513918888" class="hover:text-accent font-semibold">(+84-251) 391.8888</a>
                                </p>
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">email</span>
                                    <a href="mailto:booking@aurorahotelplaza.com" class="hover:text-accent">booking@aurorahotelplaza.com</a>
                                </p>
                                <p class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-accent">schedule</span>
                                    <span>Hỗ trợ 24/7</span>
                                </p>
                            </div>
                        </div>
                        <div>
                            <p class="mb-4">Thông tin cần cung cấp khi hủy phòng:</p>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-center gap-2">
                                    <span class="text-accent">•</span>
                                    Mã đặt phòng (Booking ID)
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-accent">•</span>
                                    Họ tên người đặt phòng
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-accent">•</span>
                                    Số điện thoại/Email đăng ký
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-accent">•</span>
                                    Lý do hủy phòng
                                </li>
                            </ul>
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

/* Cancellation Cards */
.cancellation-card {
    text-align: center;
    padding: 24px 16px;
    border-radius: 16px;
    transition: all 0.3s ease;
}

.cancellation-card:hover {
    transform: translateY(-8px);
}

.cancellation-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.cancellation-time {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    opacity: 0.9;
}

.cancellation-percent {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    font-weight: 900;
    margin-bottom: 4px;
}

.cancellation-label {
    font-size: 13px;
    opacity: 0.8;
}

.cancellation-full {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.cancellation-high {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.cancellation-medium {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.cancellation-none {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
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
    
    .cancellation-percent {
        font-size: 28px;
    }
}
</style>

</body>
</html>
