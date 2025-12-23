<?php
/**
 * Script để kiểm tra và sửa tổng tiền booking bị sai
 * Chạy một lần để fix các booking cũ
 */

session_start();
require_once '../config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    die('Unauthorized');
}

$db = getDB();

// Lấy tất cả booking cần kiểm tra
$stmt = $db->query("
    SELECT 
        b.booking_id,
        b.booking_code,
        b.room_price,
        b.total_nights,
        b.extra_guest_fee,
        b.extra_bed_fee,
        b.extra_beds,
        b.total_amount,
        b.status
    FROM bookings b
    WHERE b.status NOT IN ('cancelled')
    ORDER BY b.created_at DESC
");

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fixed = 0;
$errors = [];

echo "<h2>Kiểm tra và sửa tổng tiền booking</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr>
    <th>Mã booking</th>
    <th>Giá phòng</th>
    <th>Số đêm</th>
    <th>Tiền phòng</th>
    <th>Phụ thu khách</th>
    <th>Phí giường phụ</th>
    <th>Tổng cũ</th>
    <th>Tổng đúng</th>
    <th>Trạng thái</th>
</tr>";

foreach ($bookings as $booking) {
    $room_subtotal = $booking['room_price'] * $booking['total_nights'];
    $extra_guest_fee = $booking['extra_guest_fee'] ?? 0;
    $extra_bed_fee = $booking['extra_bed_fee'] ?? 0;
    
    $correct_total = $room_subtotal + $extra_guest_fee + $extra_bed_fee;
    $current_total = $booking['total_amount'];
    
    $is_wrong = abs($correct_total - $current_total) > 1000;
    
    $status = $is_wrong ? '<span style="color:red">SAI</span>' : '<span style="color:green">OK</span>';
    
    echo "<tr" . ($is_wrong ? " style='background:#ffeeee'" : "") . ">";
    echo "<td>{$booking['booking_code']}</td>";
    echo "<td>" . number_format($booking['room_price']) . "</td>";
    echo "<td>{$booking['total_nights']}</td>";
    echo "<td>" . number_format($room_subtotal) . "</td>";
    echo "<td>" . number_format($extra_guest_fee) . "</td>";
    echo "<td>" . number_format($extra_bed_fee) . "</td>";
    echo "<td>" . number_format($current_total) . "</td>";
    echo "<td><strong>" . number_format($correct_total) . "</strong></td>";
    echo "<td>$status</td>";
    echo "</tr>";
    
    // Sửa nếu sai
    if ($is_wrong && isset($_GET['fix'])) {
        try {
            $update = $db->prepare("UPDATE bookings SET total_amount = ? WHERE booking_id = ?");
            $update->execute([$correct_total, $booking['booking_id']]);
            $fixed++;
        } catch (Exception $e) {
            $errors[] = "Lỗi sửa {$booking['booking_code']}: " . $e->getMessage();
        }
    }
}

echo "</table>";

if (isset($_GET['fix'])) {
    echo "<p><strong>Đã sửa $fixed booking.</strong></p>";
    if (!empty($errors)) {
        echo "<p style='color:red'>Lỗi:</p><ul>";
        foreach ($errors as $err) {
            echo "<li>$err</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p><a href='?fix=1' onclick=\"return confirm('Bạn có chắc muốn sửa các booking bị sai?')\">Nhấn để sửa các booking bị sai</a></p>";
}
?>
