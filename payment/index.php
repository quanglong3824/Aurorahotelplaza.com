<?php
// Redirect to main payment page or show admin tools
$isAdmin = isset($_GET['admin']) && $_GET['admin'] === 'true';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Quản lý thanh toán VNPAY - Aurora Hotel Plaza">
    <title>Quản lý thanh toán - Aurora Hotel Plaza</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/payment/assets/css/payment.css" rel="stylesheet">
</head>
<body>
    <!-- Animated Background -->
    <div class="payment-background">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <!-- Main Container -->
    <div class="payment-container">
        <!-- Logo -->
        <div class="payment-logo">
            <a href="/">
                <img src="/assets/img/logo/aurora-hotel-plaza-logo.png" alt="Aurora Hotel Plaza" onerror="this.style.display='none'">
            </a>
            <h1>AURORA HOTEL PLAZA</h1>
        </div>

        <!-- Admin Tools Card -->
        <div class="liquid-glass-card payment-card">
            
            <!-- Card Header -->
            <div class="card-header">
                <h2>Cổng thanh toán VNPAY</h2>
                <p>Quản lý và kiểm tra giao dịch thanh toán</p>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                
                <!-- Main Actions -->
                <div class="payment-methods">
                    <p class="payment-methods-title">Chức năng chính</p>
                    
                    <!-- Create Payment -->
                    <a href="/payment/vnpay_pay.php" class="payment-option" style="text-decoration: none;">
                        <span class="radio-custom" style="background: var(--accent); border-color: var(--accent);">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: white;">add</span>
                        </span>
                        <div class="payment-option-content">
                            <div class="payment-option-icon">
                                <span class="material-symbols-outlined">payments</span>
                            </div>
                            <div class="payment-option-text">
                                <h4>Tạo giao dịch mới</h4>
                                <p>Tạo đơn thanh toán qua VNPAY</p>
                            </div>
                        </div>
                    </a>

                    <!-- Query Transaction -->
                    <a href="/payment/vnpay_querydr.php" class="payment-option" style="text-decoration: none;">
                        <span class="radio-custom" style="background: #6366f1; border-color: #6366f1;">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: white;">search</span>
                        </span>
                        <div class="payment-option-content">
                            <div class="payment-option-icon">
                                <span class="material-symbols-outlined">manage_search</span>
                            </div>
                            <div class="payment-option-text">
                                <h4>Truy vấn giao dịch</h4>
                                <p>Kiểm tra trạng thái thanh toán</p>
                            </div>
                        </div>
                    </a>

                    <!-- Refund -->
                    <a href="/payment/vnpay_refund.php" class="payment-option" style="text-decoration: none;">
                        <span class="radio-custom" style="background: #f59e0b; border-color: #f59e0b;">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: white;">undo</span>
                        </span>
                        <div class="payment-option-content">
                            <div class="payment-option-icon">
                                <span class="material-symbols-outlined">currency_exchange</span>
                            </div>
                            <div class="payment-option-text">
                                <h4>Hoàn tiền</h4>
                                <p>Yêu cầu hoàn tiền giao dịch</p>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Quick Links -->
                <div class="action-buttons" style="margin-top: 1.5rem;">
                    <a href="/" class="btn-secondary">
                        <span class="material-symbols-outlined">home</span>
                        <span>Trang chủ</span>
                    </a>
                    <a href="/booking/" class="btn-primary">
                        <span class="material-symbols-outlined">hotel</span>
                        <span>Đặt phòng</span>
                    </a>
                </div>

            </div>

            <!-- Card Footer -->
            <div class="card-footer">
                <div class="security-badge">
                    <span class="material-symbols-outlined icon">verified_user</span>
                    <span>Hệ thống thanh toán được bảo mật bởi VNPAY</span>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="payment-footer">
            <p>© <?php echo date('Y'); ?> Aurora Hotel Plaza. Powered by <a href="https://vnpay.vn" target="_blank">VNPAY</a></p>
        </div>
    </div>
</body>
</html>
