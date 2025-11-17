<?php
session_start();
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
        SELECT b.*, rt.name as room_type_name, rt.description as room_description,
               r.room_number, u.email as user_email
        FROM bookings b
        LEFT JOIN room_types rt ON b.room_type_id = rt.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN users u ON b.user_id = u.id
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
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="../assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-background-light dark:bg-background-dark">
<?php include '../includes/header.php'; ?>
<main class="pt-20 pb-16 px-4">
    <div class="max-w-4xl mx-auto">
        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-lg p-8">
                <h1 class="text-3xl font-bold mb-6">Xác nhận đặt phòng</h1>
                <div class="space-y-4">
                    <p><strong>Mã đặt phòng:</strong> <?php echo $booking['booking_code']; ?></p>
                    <p><strong>Loại phòng:</strong> <?php echo $booking['room_type_name']; ?></p>
                    <p><strong>Nhận phòng:</strong> <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></p>
                    <p><strong>Trả phòng:</strong> <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></p>
                    <p><strong>Tổng tiền:</strong> <?php echo number_format($booking['total_amount']); ?> VNĐ</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>
