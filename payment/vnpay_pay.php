<?php
require_once("./config.php");

// Get booking data from session or URL params
$amount = isset($_GET['amount']) ? (int)$_GET['amount'] : 10000;
$booking_code = isset($_GET['booking_code']) ? htmlspecialchars($_GET['booking_code']) : '';
$room_type = isset($_GET['room_type']) ? htmlspecialchars($_GET['room_type']) : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Thanh toán đặt phòng Aurora Hotel Plaza qua VNPAY">
    <title>Thanh toán - Aurora Hotel Plaza</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
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

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <p class="loading-text">Đang chuyển đến cổng thanh toán...</p>
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

        <!-- Payment Card -->
        <div class="liquid-glass-card payment-card">
            <form action="/payment/vnpay_create_payment.php" id="paymentForm" method="post">
                
                <!-- Card Header -->
                <div class="card-header">
                    <h2>Thanh toán đặt phòng</h2>
                    <p>Hoàn tất thanh toán để xác nhận đặt phòng của bạn</p>
                </div>

                <!-- Card Body -->
                <div class="card-body">
                    
                    <!-- Amount Display -->
                    <div class="amount-display">
                        <p class="amount-label">Số tiền thanh toán</p>
                        <p class="amount-value">
                            <span id="amountDisplay"><?php echo number_format($amount, 0, ',', '.'); ?></span>
                            <span class="amount-currency">VNĐ</span>
                        </p>
                        <?php if ($booking_code): ?>
                        <p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            Mã đặt phòng: <strong style="color: var(--accent);"><?php echo $booking_code; ?></strong>
                        </p>
                        <?php endif; ?>
                    </div>

                    <!-- Hidden Amount Input -->
                    <input type="hidden" name="amount" id="amount" value="<?php echo $amount; ?>">
                    <?php if ($booking_code): ?>
                    <input type="hidden" name="booking_code" value="<?php echo $booking_code; ?>">
                    <?php endif; ?>

                    <!-- Payment Methods -->
                    <div class="payment-methods">
                        <p class="payment-methods-title">Phương thức thanh toán</p>
                        
                        <!-- VNPAY QR -->
                        <label class="payment-option selected" data-value="">
                            <input type="radio" name="bankCode" value="" checked>
                            <span class="radio-custom"></span>
                            <div class="payment-option-content">
                                <div class="payment-option-icon">
                                    <span class="material-symbols-outlined">qr_code_2</span>
                                </div>
                                <div class="payment-option-text">
                                    <h4>Cổng thanh toán VNPAY</h4>
                                    <p>Quét mã QR hoặc chọn ngân hàng</p>
                                </div>
                            </div>
                        </label>

                        <!-- VNPAY QR App -->
                        <label class="payment-option" data-value="VNPAYQR">
                            <input type="radio" name="bankCode" value="VNPAYQR">
                            <span class="radio-custom"></span>
                            <div class="payment-option-content">
                                <div class="payment-option-icon">
                                    <span class="material-symbols-outlined">smartphone</span>
                                </div>
                                <div class="payment-option-text">
                                    <h4>Ứng dụng VNPAY-QR</h4>
                                    <p>Thanh toán bằng app ngân hàng</p>
                                </div>
                            </div>
                        </label>

                        <!-- ATM Card -->
                        <label class="payment-option" data-value="VNBANK">
                            <input type="radio" name="bankCode" value="VNBANK">
                            <span class="radio-custom"></span>
                            <div class="payment-option-content">
                                <div class="payment-option-icon">
                                    <span class="material-symbols-outlined">credit_card</span>
                                </div>
                                <div class="payment-option-text">
                                    <h4>Thẻ ATM nội địa</h4>
                                    <p>Thẻ ATM/Tài khoản ngân hàng Việt Nam</p>
                                </div>
                            </div>
                        </label>

                        <!-- International Card -->
                        <label class="payment-option" data-value="INTCARD">
                            <input type="radio" name="bankCode" value="INTCARD">
                            <span class="radio-custom"></span>
                            <div class="payment-option-content">
                                <div class="payment-option-icon">
                                    <span class="material-symbols-outlined">language</span>
                                </div>
                                <div class="payment-option-text">
                                    <h4>Thẻ quốc tế</h4>
                                    <p>Visa, MasterCard, JCB, American Express</p>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Language Selection -->
                    <div class="language-section">
                        <p class="payment-methods-title">Ngôn ngữ hiển thị</p>
                        <div class="language-options">
                            <label class="language-option selected" data-value="vn">
                                <input type="radio" name="language" value="vn" checked>
                                <span class="flag">🇻🇳</span>
                                <span>Tiếng Việt</span>
                            </label>
                            <label class="language-option" data-value="en">
                                <input type="radio" name="language" value="en">
                                <span class="flag">🇺🇸</span>
                                <span>English</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-payment" id="submitBtn">
                        <span class="material-symbols-outlined icon">lock</span>
                        <span>Thanh toán an toàn</span>
                    </button>

                </div>

                <!-- Card Footer -->
                <div class="card-footer">
                    <div class="security-badge">
                        <span class="material-symbols-outlined icon">verified_user</span>
                        <span>Giao dịch được bảo mật bởi VNPAY</span>
                    </div>
                    <div class="partner-logos">
                        <img src="/payment/assets/img/vnpay-logo.png" alt="VNPAY" onerror="this.style.display='none'">
                        <img src="/payment/assets/img/visa-logo.png" alt="Visa" onerror="this.style.display='none'">
                        <img src="/payment/assets/img/mastercard-logo.png" alt="Mastercard" onerror="this.style.display='none'">
                    </div>
                </div>

            </form>
        </div>

        <!-- Footer -->
        <div class="payment-footer">
            <p>© <?php echo date('Y'); ?> Aurora Hotel Plaza. Powered by <a href="https://vnpay.vn" target="_blank">VNPAY</a></p>
        </div>
    </div>

    <script>
        // Payment option selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Language option selection
        document.querySelectorAll('.language-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.language-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Form submission with loading
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            document.getElementById('loadingOverlay').classList.add('active');
            document.getElementById('submitBtn').disabled = true;
        });

        // Format amount on load
        function formatAmount(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount);
        }
    </script>
</body>
</html>
