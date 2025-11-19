<?php
session_start();
require_once '../config/environment.php';
require_once '../config/database.php';

$booking_code = $_GET['booking_code'] ?? '';

if (!$booking_code) {
    header('Location: ./index.php');
    exit;
}

try {
    $db = getDB();
    
    // Get booking details
    $stmt = $db->prepare("
        SELECT b.*, rt.type_name as room_type_name, rt.description as room_description,
               r.room_number, u.email as user_email
        FROM bookings b
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_code = ?
    ");
    $stmt->execute([$booking_code]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        throw new Exception('Không tìm thấy đơn đặt phòng');
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Xác nhận đặt phòng - Aurora Hotel Plaza</title>
<script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>?v=<?php echo time(); ?>"></script>
<link href="<?php echo asset('css/fonts.css'); ?>?v=<?php echo time(); ?>" rel="stylesheet"/>
<script src="<?php echo asset('js/tailwind-config.js'); ?>?v=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-background-light dark:bg-background-dark">
<?php include '../includes/header.php'; ?>
<main class="pt-32 pb-16 px-4">
    <div class="max-w-4xl mx-auto">
        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-lg p-8">
                <h1 class="text-3xl font-bold mb-6">Xác nhận đặt phòng</h1>
                
                <div id="confirmationMessage" class="hidden mb-6 p-4 rounded-lg"></div>
                
                <div class="space-y-4 mb-8">
                    <p><strong>Mã đặt phòng:</strong> <?php echo $booking['booking_code']; ?></p>
                    <p><strong>Loại phòng:</strong> <?php echo $booking['room_type_name']; ?></p>
                    <p><strong>Nhận phòng:</strong> <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></p>
                    <p><strong>Trả phòng:</strong> <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></p>
                    <p><strong>Tổng tiền:</strong> <?php echo number_format($booking['total_amount']); ?> VNĐ</p>
                    <p><strong>Trạng thái:</strong> 
                        <span id="bookingStatus" class="px-3 py-1 rounded-full text-sm font-semibold
                            <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo $booking['status'] === 'confirmed' ? 'Đã xác nhận' : 'Chờ xác nhận'; ?>
                        </span>
                    </p>
                </div>
                
                <?php if ($booking['status'] === 'pending'): ?>
                <div class="grid grid-cols-2 gap-3">
                    <button id="confirmBookingBtn" 
                            class="bg-gradient-to-r from-primary to-purple-600 text-white px-4 py-2.5 rounded-lg font-medium hover:opacity-90 transition-all flex items-center justify-center gap-2">
                        <span class="material-icons text-lg">check_circle</span>
                        <span>Xác nhận</span>
                    </button>
                    <a href="../profile/bookings.php" 
                       class="bg-gray-200 text-gray-700 px-4 py-2.5 rounded-lg font-medium hover:bg-gray-300 transition-all flex items-center justify-center">
                        Quay lại
                    </a>
                </div>
                <?php else: ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <p class="text-green-800 flex items-center gap-2">
                        <span class="material-icons">check_circle</span>
                        <span>Đặt phòng của bạn đã được xác nhận. Email xác nhận đã được gửi đến địa chỉ email của bạn.</span>
                    </p>
                </div>
                <a href="../profile/bookings.php" 
                   class="block w-full bg-gradient-to-r from-primary to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:opacity-90 transition-all text-center">
                    Xem danh sách đặt phòng
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmBookingBtn');
    const messageDiv = document.getElementById('confirmationMessage');
    const statusSpan = document.getElementById('bookingStatus');
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function() {
            // Disable button and show loading
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="material-icons animate-spin text-lg">refresh</span><span>Đang xử lý...</span>';
            
            try {
                const response = await fetch('./api/confirm-booking-user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        booking_code: '<?php echo $booking['booking_code']; ?>'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    messageDiv.className = 'mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800';
                    messageDiv.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span class="material-icons">check_circle</span>
                            <span>${result.message}</span>
                        </div>
                    `;
                    messageDiv.classList.remove('hidden');
                    
                    // Update status badge
                    statusSpan.className = 'px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800';
                    statusSpan.textContent = 'Đã xác nhận';
                    
                    // Hide button and show success state
                    setTimeout(() => {
                        confirmBtn.parentElement.innerHTML = `
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <p class="text-green-800 flex items-center gap-2">
                                    <span class="material-icons">check_circle</span>
                                    <span>Đặt phòng của bạn đã được xác nhận. Email xác nhận đã được gửi đến địa chỉ email của bạn.</span>
                                </p>
                            </div>
                            <a href="../profile/bookings.php" 
                               class="block w-full bg-gradient-to-r from-primary to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:opacity-90 transition-all text-center">
                                Xem danh sách đặt phòng
                            </a>
                        `;
                    }, 1500);
                } else {
                    throw new Error(result.message || 'Có lỗi xảy ra');
                }
            } catch (error) {
                // Show error message
                messageDiv.className = 'mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800';
                messageDiv.innerHTML = `
                    <div class="flex items-center gap-2">
                        <span class="material-icons">error</span>
                        <span>${error.message}</span>
                    </div>
                `;
                messageDiv.classList.remove('hidden');
                
                // Re-enable button
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<span class="material-icons text-lg">check_circle</span><span>Xác nhận</span>';
            }
        });
    }
});
</script>

</body>
</html>
