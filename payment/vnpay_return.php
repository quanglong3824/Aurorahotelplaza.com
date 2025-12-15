<?php
require_once("./config.php");

// Verify VNPAY response
$vnp_SecureHash = isset($_GET['vnp_SecureHash']) ? $_GET['vnp_SecureHash'] : '';
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

// Determine result status
$isValidSignature = ($secureHash == $vnp_SecureHash);
$isSuccess = $isValidSignature && (isset($_GET['vnp_ResponseCode']) && $_GET['vnp_ResponseCode'] == '00');

// Get transaction details
$txnRef = isset($_GET['vnp_TxnRef']) ? htmlspecialchars($_GET['vnp_TxnRef']) : 'N/A';
$amount = isset($_GET['vnp_Amount']) ? (int)$_GET['vnp_Amount'] / 100 : 0;
$orderInfo = isset($_GET['vnp_OrderInfo']) ? htmlspecialchars(urldecode($_GET['vnp_OrderInfo'])) : 'N/A';
$responseCode = isset($_GET['vnp_ResponseCode']) ? htmlspecialchars($_GET['vnp_ResponseCode']) : 'N/A';
$transactionNo = isset($_GET['vnp_TransactionNo']) ? htmlspecialchars($_GET['vnp_TransactionNo']) : 'N/A';
$bankCode = isset($_GET['vnp_BankCode']) ? htmlspecialchars($_GET['vnp_BankCode']) : 'N/A';
$payDate = isset($_GET['vnp_PayDate']) ? htmlspecialchars($_GET['vnp_PayDate']) : 'N/A';

// Format pay date
if ($payDate !== 'N/A' && strlen($payDate) >= 14) {
    $formattedDate = substr($payDate, 6, 2) . '/' . substr($payDate, 4, 2) . '/' . substr($payDate, 0, 4) . ' ' . 
                     substr($payDate, 8, 2) . ':' . substr($payDate, 10, 2) . ':' . substr($payDate, 12, 2);
} else {
    $formattedDate = $payDate;
}

// Response code messages
$responseMessages = [
    '00' => 'Giao dịch thành công',
    '07' => 'Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường)',
    '09' => 'Giao dịch không thành công: Thẻ/Tài khoản chưa đăng ký dịch vụ InternetBanking',
    '10' => 'Giao dịch không thành công: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
    '11' => 'Giao dịch không thành công: Đã hết hạn chờ thanh toán',
    '12' => 'Giao dịch không thành công: Thẻ/Tài khoản bị khóa',
    '13' => 'Giao dịch không thành công: Nhập sai mật khẩu xác thực giao dịch (OTP)',
    '24' => 'Giao dịch không thành công: Khách hàng hủy giao dịch',
    '51' => 'Giao dịch không thành công: Tài khoản không đủ số dư',
    '65' => 'Giao dịch không thành công: Tài khoản đã vượt quá hạn mức giao dịch trong ngày',
    '75' => 'Ngân hàng thanh toán đang bảo trì',
    '79' => 'Giao dịch không thành công: Nhập sai mật khẩu thanh toán quá số lần quy định',
    '99' => 'Các lỗi khác'
];

$statusMessage = isset($responseMessages[$responseCode]) ? $responseMessages[$responseCode] : 'Lỗi không xác định';
if (!$isValidSignature) {
    $statusMessage = 'Chữ ký không hợp lệ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kết quả thanh toán - Aurora Hotel Plaza">
    <title><?php echo $isSuccess ? 'Thanh toán thành công' : 'Thanh toán thất bại'; ?> - Aurora Hotel Plaza</title>
    
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

        <!-- Result Card -->
        <div class="liquid-glass-card result-card">
            
            <!-- Card Header with Result -->
            <div class="card-header">
                <!-- Result Icon -->
                <div class="result-icon <?php echo $isSuccess ? 'success' : 'error'; ?>">
                    <span class="material-symbols-outlined">
                        <?php echo $isSuccess ? 'check_circle' : 'cancel'; ?>
                    </span>
                </div>
                
                <h2 class="result-title <?php echo $isSuccess ? 'success' : 'error'; ?>">
                    <?php echo $isSuccess ? 'Thanh toán thành công!' : 'Thanh toán thất bại'; ?>
                </h2>
                <p class="result-message"><?php echo $statusMessage; ?></p>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                
                <!-- Amount Display -->
                <div class="amount-display" style="<?php echo $isSuccess ? '' : 'opacity: 0.6;'; ?>">
                    <p class="amount-label">Số tiền thanh toán</p>
                    <p class="amount-value">
                        <span><?php echo number_format($amount, 0, ',', '.'); ?></span>
                        <span class="amount-currency">VNĐ</span>
                    </p>
                </div>

                <!-- Transaction Details -->
                <div class="transaction-details">
                    <div class="detail-row">
                        <span class="detail-label">Mã giao dịch</span>
                        <span class="detail-value"><?php echo $txnRef; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Mã GD VNPAY</span>
                        <span class="detail-value"><?php echo $transactionNo; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Ngân hàng</span>
                        <span class="detail-value"><?php echo $bankCode; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Thời gian</span>
                        <span class="detail-value"><?php echo $formattedDate; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Nội dung</span>
                        <span class="detail-value" style="max-width: 200px; text-align: right;"><?php echo $orderInfo; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Mã phản hồi</span>
                        <span class="detail-value" style="color: <?php echo $isSuccess ? 'var(--success)' : 'var(--error)'; ?>;">
                            <?php echo $responseCode; ?>
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if ($isSuccess): ?>
                    <a href="/profile/bookings.php" class="btn-secondary">
                        <span class="material-symbols-outlined">receipt_long</span>
                        <span>Xem đặt phòng</span>
                    </a>
                    <a href="/" class="btn-primary">
                        <span class="material-symbols-outlined">home</span>
                        <span>Về trang chủ</span>
                    </a>
                    <?php else: ?>
                    <a href="/booking/" class="btn-secondary">
                        <span class="material-symbols-outlined">refresh</span>
                        <span>Thử lại</span>
                    </a>
                    <a href="/contact.php" class="btn-primary">
                        <span class="material-symbols-outlined">support_agent</span>
                        <span>Liên hệ hỗ trợ</span>
                    </a>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Card Footer -->
            <div class="card-footer">
                <div class="security-badge">
                    <span class="material-symbols-outlined icon">verified_user</span>
                    <span>Giao dịch được xác thực bởi VNPAY</span>
                </div>
                <?php if ($isSuccess): ?>
                <p style="margin-top: 1rem; font-size: 0.8125rem; color: var(--text-secondary);">
                    Email xác nhận đã được gửi đến địa chỉ email của bạn
                </p>
                <?php endif; ?>
            </div>

        </div>

        <!-- Footer -->
        <div class="payment-footer">
            <p>© <?php echo date('Y'); ?> Aurora Hotel Plaza. Powered by <a href="https://vnpay.vn" target="_blank">VNPAY</a></p>
        </div>
    </div>

    <?php if ($isSuccess): ?>
    <script>
        // Confetti animation for successful payment
        function createConfetti() {
            const colors = ['#d4af37', '#10b981', '#6366f1', '#f59e0b', '#ec4899'];
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.style.cssText = `
                    position: fixed;
                    width: 10px;
                    height: 10px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    left: ${Math.random() * 100}vw;
                    top: -10px;
                    border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
                    animation: confettiFall ${2 + Math.random() * 3}s linear forwards;
                    opacity: ${0.7 + Math.random() * 0.3};
                    z-index: 1000;
                `;
                document.body.appendChild(confetti);
                setTimeout(() => confetti.remove(), 5000);
            }
        }
        
        // Add confetti animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes confettiFall {
                to {
                    transform: translateY(100vh) rotate(720deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Trigger confetti on load
        setTimeout(createConfetti, 500);
    </script>
    <?php endif; ?>
</body>
</html>
