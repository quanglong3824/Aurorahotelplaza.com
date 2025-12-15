<?php
session_start();
require_once '../config/environment.php';
require_once '../config/database.php';
require_once '../payment/config.php';
require_once '../helpers/logger.php';

// Get VNPay response
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
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

$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
$vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
$vnp_Amount = $_GET['vnp_Amount'] ?? 0;
$vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
$vnp_BankCode = $_GET['vnp_BankCode'] ?? '';

$payment_success = false;
$message = '';

try {
    $db = getDB();
    
    // Verify secure hash
    if ($secureHash == $vnp_SecureHash) {
        // Get booking
        $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_code = ?");
        $stmt->execute([$vnp_TxnRef]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            if ($vnp_ResponseCode == '00') {
                // Payment successful
                $payment_success = true;
                
                // Update booking status
                $stmt = $db->prepare("
                    UPDATE bookings 
                    SET status = 'confirmed', 
                        payment_status = 'paid'
                    WHERE booking_id = ?
                ");
                $stmt->execute([$booking['booking_id']]);
                
                // Create payment record
                $stmt = $db->prepare("
                    INSERT INTO payments (
                        booking_id, payment_method, amount, currency,
                        transaction_id, vnpay_response, status, paid_at
                    ) VALUES (?, 'vnpay', ?, 'VND', ?, ?, 'completed', NOW())
                ");
                $stmt->execute([
                    $booking['booking_id'],
                    $vnp_Amount / 100,
                    $vnp_TransactionNo,
                    json_encode($_GET)
                ]);
                
                // Calculate loyalty points (1 point per 10,000 VND)
                $points_earned = floor($booking['total_amount'] / 10000);
                
                // Update or create loyalty record
                $stmt = $db->prepare("
                    INSERT INTO user_loyalty (user_id, current_points, lifetime_points)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        current_points = current_points + ?,
                        lifetime_points = lifetime_points + ?
                ");
                $stmt->execute([
                    $booking['user_id'],
                    $points_earned,
                    $points_earned,
                    $points_earned,
                    $points_earned
                ]);
                
                // Add points transaction
                $stmt = $db->prepare("
                    INSERT INTO points_transactions (user_id, points, transaction_type, reference_type, reference_id, description)
                    VALUES (?, ?, 'earn', 'booking', ?, ?)
                ");
                $stmt->execute([
                    $booking['user_id'],
                    $points_earned,
                    $booking['booking_id'],
                    'Tích điểm từ đặt phòng ' . $vnp_TxnRef
                ]);
                
                $message = 'Thanh toán thành công! Bạn đã nhận được ' . $points_earned . ' điểm thưởng.';
                
                // Send payment confirmation email
                try {
                    require_once '../helpers/email.php';
                    
                    // Get complete booking data
                    $stmt = $db->prepare("
                        SELECT b.*, rt.type_name, rt.category 
                        FROM bookings b 
                        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                        WHERE b.booking_id = ?
                    ");
                    $stmt->execute([$booking['booking_id']]);
                    $booking_data = $stmt->fetch();
                    
                    if ($booking_data) {
                        $payment_data = [
                            'payment_method' => 'vnpay',
                            'transaction_id' => $vnp_TransactionNo,
                            'amount' => $vnp_Amount / 100,
                            'paid_at' => date('Y-m-d H:i:s')
                        ];
                        
                        $emailHelper = getEmailHelper();
                        $emailHelper->sendPaymentConfirmation($booking_data, $payment_data);
                    }
                } catch (Exception $emailError) {
                    error_log("Payment email error: " . $emailError->getMessage());
                }
                
                // Log payment success
                $logger = getLogger();
                $logger->logPaymentSuccess($vnp_TransactionNo, [
                    'booking_id' => $booking['booking_id'],
                    'booking_code' => $vnp_TxnRef,
                    'amount' => $vnp_Amount / 100,
                    'currency' => 'VND',
                    'transaction_id' => $vnp_TransactionNo,
                    'bank_code' => $vnp_BankCode,
                    'response_code' => $vnp_ResponseCode,
                    'points_earned' => $points_earned,
                    'payment_method' => 'vnpay'
                ], $booking['user_id']);
                
            } else {
                // Payment failed
                $message = 'Thanh toán không thành công. Mã lỗi: ' . $vnp_ResponseCode;
                
                // Update booking status
                $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
                $stmt->execute([$booking['booking_id']]);
            }
        } else {
            $message = 'Không tìm thấy đơn đặt phòng';
        }
    } else {
        $message = 'Chữ ký không hợp lệ';
    }
    
} catch (Exception $e) {
    $message = 'Có lỗi xảy ra: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo $payment_success ? 'Thanh toán thành công' : 'Thanh toán thất bại'; ?> - Aurora Hotel Plaza</title>

<script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
<link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet"/>
<script src="<?php echo asset('js/tailwind-config.js'); ?>?v=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo asset('css/liquid-glass.css'); ?>?v=<?php echo time(); ?>">
</head>
<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">

<?php include '../includes/header.php'; ?>

<main class="flex h-full grow flex-col items-center justify-center py-20 px-4">
    <div class="max-w-2xl w-full bg-surface-light dark:bg-surface-dark rounded-xl shadow-lg p-8 text-center">
        
        <?php if ($payment_success): ?>
            <div class="mb-6">
                <div class="w-20 h-20 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-5xl text-green-600 dark:text-green-400">check_circle</span>
                </div>
                <h1 class="text-3xl font-bold text-green-600 dark:text-green-400 mb-2">Thanh toán thành công!</h1>
                <p class="text-lg text-text-secondary-light dark:text-text-secondary-dark"><?php echo $message; ?></p>
            </div>
            
            <div class="bg-primary-light/20 dark:bg-gray-700 rounded-lg p-6 mb-6 text-left">
                <h3 class="font-bold text-lg mb-4">Thông tin đặt phòng</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>Mã đặt phòng:</span>
                        <span class="font-bold"><?php echo $vnp_TxnRef; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Mã giao dịch:</span>
                        <span class="font-bold"><?php echo $vnp_TransactionNo; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Ngân hàng:</span>
                        <span class="font-bold"><?php echo $vnp_BankCode; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Số tiền:</span>
                        <span class="font-bold text-accent"><?php echo number_format($vnp_Amount / 100); ?> VNĐ</span>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-4 justify-center">
                <a href="<?php echo url('booking/confirmation.php?booking_code=' . $vnp_TxnRef); ?>" class="btn-primary">
                    Xem chi tiết đặt phòng
                </a>
                <a href="<?php echo url('index.php'); ?>" class="btn-secondary">
                    Về trang chủ
                </a>
            </div>
            
        <?php else: ?>
            <div class="mb-6">
                <div class="w-20 h-20 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-5xl text-red-600 dark:text-red-400">cancel</span>
                </div>
                <h1 class="text-3xl font-bold text-red-600 dark:text-red-400 mb-2">Thanh toán thất bại</h1>
                <p class="text-lg text-text-secondary-light dark:text-text-secondary-dark"><?php echo $message; ?></p>
            </div>
            
            <div class="flex gap-4 justify-center">
                <a href="<?php echo url('booking/index.php'); ?>" class="btn-primary">
                    Đặt phòng lại
                </a>
                <a href="<?php echo url('contact.php'); ?>" class="btn-secondary">
                    Liên hệ hỗ trợ
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</div>

<script src="<?php echo asset('js/main.js'); ?>?v=<?php echo time(); ?>"></script>

<style>
.btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: #d4af37;
    color: white;
    font-weight: 600;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #b8941f;
    transform: translateY(-1px);
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    background: transparent;
    color: #6b7280;
    font-weight: 600;
    border: 2px solid #d1d5db;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    border-color: #d4af37;
    color: #d4af37;
}
</style>

</body>
</html>
