<?php
session_start();
require_once 'config/database.php';
require_once 'config/environment.php';

// Lấy thông tin user từ session và database nếu đã đăng nhập
$is_logged_in = isset($_SESSION['user_id']);
$user_name = '';
$user_email = '';
$user_phone = '';

if ($is_logged_in) {
    try {
        $db = getDB();
        if ($db) {
            $stmt = $db->prepare("SELECT full_name, email, phone FROM users WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $user_name = $user['full_name'] ?? '';
                $user_email = $user['email'] ?? '';
                $user_phone = $user['phone'] ?? '';
            }
        } else {
            // Fallback to session if DB fails
            $user_name = $_SESSION['user_name'] ?? '';
            $user_email = $_SESSION['user_email'] ?? '';
        }
    } catch (Exception $e) {
        error_log("Contact page - Get user info error: " . $e->getMessage());
        // Fallback to session data
        $user_name = $_SESSION['user_name'] ?? '';
        $user_email = $_SESSION['user_email'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Liên hệ - Aurora Hotel Plaza</title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/contact.css">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Page Header -->
    <section class="page-header-contact">
        <div class="page-header-overlay"></div>
        <div class="page-header-content">
            <span class="badge-liquid-glass mb-6">
                <span class="material-symbols-outlined text-accent">support_agent</span>
                Hỗ trợ 24/7
            </span>
            <h1 class="page-title">Liên hệ với chúng tôi</h1>
            <p class="page-subtitle">Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7</p>
            <div class="flex flex-wrap gap-4 justify-center mt-8">
                <a href="tel:+842513918888" class="btn-liquid-primary">
                    <span class="material-symbols-outlined">phone</span>
                    Gọi ngay
                </a>
                <a href="#contact-form" class="btn-liquid-glass">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    Gửi tin nhắn
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact-form" class="section-padding">
        <div class="container-custom">
            <div class="contact-wrapper">
                <!-- Contact Info -->
                <div class="contact-info-section">
                    <h2 class="section-title">Thông tin liên hệ</h2>
                    <p class="section-description">Hãy liên hệ với chúng tôi qua các kênh dưới đây hoặc điền form bên cạnh</p>
                    
                    <div class="info-list">
                        <div class="info-item">
                            <div class="info-icon">
                                <span class="material-symbols-outlined">location_on</span>
                            </div>
                            <div class="info-content">
                                <h3 class="info-title">Địa chỉ</h3>
                                <p class="info-text">Số 253, Phạm Văn Thuận, KP2<br>Phường Tam Hiệp, Tỉnh Đồng Nai</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <span class="material-symbols-outlined">phone</span>
                            </div>
                            <div class="info-content">
                                <h3 class="info-title">Điện thoại</h3>
                                <p class="info-text"><a href="tel:+842513918888">(+84-251) 391.8888</a></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <span class="material-symbols-outlined">email</span>
                            </div>
                            <div class="info-content">
                                <h3 class="info-title">Email</h3>
                                <p class="info-text">
                                    <a href="mailto:info@aurorahotelplaza.com">info@aurorahotelplaza.com</a><br>
                                    <a href="mailto:booking@aurorahotelplaza.com">booking@aurorahotelplaza.com</a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <span class="material-symbols-outlined">schedule</span>
                            </div>
                            <div class="info-content">
                                <h3 class="info-title">Giờ làm việc</h3>
                                <p class="info-text">Lễ tân: 24/7<br>Nhà hàng: 6:00 - 22:00</p>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="social-section">
                        <h3 class="social-title">Theo dõi chúng tôi</h3>
                        <div class="social-links">
                            <a href="#" class="social-link">
                                <svg class="social-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="#" class="social-link">
                                <svg class="social-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            <a href="#" class="social-link">
                                <svg class="social-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="contact-form-wrapper">
                    <h2 class="section-title">Gửi tin nhắn cho chúng tôi</h2>
                    
                    <?php if ($is_logged_in): ?>
                    <div class="logged-in-notice">
                        <span class="material-symbols-outlined">verified_user</span>
                        <span>Bạn đang đăng nhập với tài khoản <strong><?php echo htmlspecialchars($user_email); ?></strong></span>
                    </div>
                    <?php endif; ?>
                    
                    <form class="contact-form" id="contactForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Họ và tên <span class="text-red-500">*</span></label>
                                <input type="text" 
                                       name="name" 
                                       class="form-input <?php echo $is_logged_in && $user_name ? 'readonly-input' : ''; ?>" 
                                       placeholder="Nhập họ và tên" 
                                       value="<?php echo htmlspecialchars($user_name); ?>"
                                       <?php echo $is_logged_in && $user_name ? 'readonly' : ''; ?>
                                       required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email <span class="text-red-500">*</span></label>
                                <input type="email" 
                                       name="email" 
                                       class="form-input <?php echo $is_logged_in && $user_email ? 'readonly-input' : ''; ?>" 
                                       placeholder="Nhập email" 
                                       value="<?php echo htmlspecialchars($user_email); ?>"
                                       <?php echo $is_logged_in && $user_email ? 'readonly' : ''; ?>
                                       required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Số điện thoại <span class="text-red-500">*</span></label>
                                <input type="tel" 
                                       name="phone" 
                                       class="form-input <?php echo $is_logged_in && $user_phone ? 'readonly-input' : ''; ?>" 
                                       placeholder="Nhập số điện thoại" 
                                       value="<?php echo htmlspecialchars($user_phone); ?>"
                                       <?php echo $is_logged_in && $user_phone ? 'readonly' : ''; ?>
                                       required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Chủ đề</label>
                                <select name="subject" class="form-input">
                                    <option value="Đặt phòng">Đặt phòng</option>
                                    <option value="Tổ chức sự kiện">Tổ chức sự kiện</option>
                                    <option value="Dịch vụ khác">Dịch vụ khác</option>
                                    <option value="Góp ý">Góp ý</option>
                                    <option value="Khiếu nại">Khiếu nại</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Tin nhắn <span class="text-red-500">*</span></label>
                            <textarea name="message" class="form-textarea" rows="6" placeholder="Nhập nội dung tin nhắn của bạn..." required minlength="10"></textarea>
                            <p class="form-hint">Tối thiểu 10 ký tự</p>
                        </div>
                        
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <span class="btn-text">
                                <span class="material-symbols-outlined">send</span>
                                Gửi tin nhắn
                            </span>
                            <span class="btn-loading hidden">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Đang gửi...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="map-container">
            <iframe 
                src="https://maps.google.com/maps?q=10.957145,106.842134&hl=vi&z=17&output=embed"
                class="map-iframe"
                allowfullscreen=""
                loading="lazy">
            </iframe>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-24 right-4 z-50 flex flex-col gap-2"></div>

<script src="assets/js/main.js"></script>
<script src="assets/js/contact.js"></script>
</body>
</html>
