<?php
/**
 * SMART STRESS TEST SEEDER (LOGIC AWARE)
 * Mô phỏng người dùng thật: Kiểm tra còn phòng mới đặt.
 * Mục tiêu: 
 * 1. Lấp đầy lịch phòng để test logic Overbooking.
 * 2. Gây áp lực lên câu lệnh SQL "Check Availability".
 */

require_once 'config/database.php';
require_once 'models/Booking.php'; // Sử dụng Model thật để check logic

// Tăng giới hạn thực thi
set_time_limit(600);
ini_set('memory_limit', '1024M');

$db = getDB();
$bookingModel = new Booking($db);

// Cấu hình test
$NUM_ATTEMPTS = 1000; // Số lần thử đặt phòng
$PEAK_START_DATE = date('Y-m-d', strtotime('+30 days')); // Cao điểm tháng sau
$PEAK_DURATION_DAYS = 14; // Kéo dài 2 tuần

echo "<h1>Smart Logic Seeder</h1>";
echo "<div style='font-family: monospace; line-height: 1.5;'>";

try {
    // 1. Lấy thông tin Room Types và SỐ LƯỢNG PHÒNG THỰC TẾ
    $sql = "
        SELECT rt.room_type_id, rt.type_name, rt.base_price, rt.category, 
               (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id AND r.status = 'available') as total_rooms
        FROM room_types rt 
        WHERE rt.status = 'active'
    ";
    $room_types = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    if (empty($room_types)) {
        die("Lỗi: Không tìm thấy loại phòng nào.");
    }

    // In ra năng lực phục vụ hiện tại
    echo "<strong>Current Inventory:</strong><br>";
    foreach ($room_types as $rt) {
        // Nếu chưa khai báo phòng cụ thể, giả lập năng lực là 5
        if ($rt['total_rooms'] == 0)
            $rt['total_rooms'] = 5;
        echo "- {$rt['type_name']} ({$rt['category']}): {$rt['total_rooms']} rooms available.<br>";
    }
    echo "<hr>";

    // 2. Chuẩn bị User ID pool
    $stmt = $db->query("SELECT user_id FROM users WHERE user_role = 'customer' LIMIT 100");
    $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Nếu chưa có user, tạo nhanh 10 user
    if (empty($user_ids)) {
        // Tạo user fallback logic ở đây nếu cần
        die("Vui lòng chạy tool cũ 1 lần để tạo user trước.");
    }

    echo "<h3>Starting Intelligent Simulation ($NUM_ATTEMPTS requests)...</h3>";

    $success_count = 0;
    $full_count = 0;
    $start_time = microtime(true);

    // Chuẩn bị User Info Map để đỡ query nhiều
    $stmt_users = $db->query("SELECT user_id, full_name, email, phone FROM users WHERE user_id IN (" . implode(',', $user_ids) . ")");
    $users_map = [];
    while ($row = $stmt_users->fetch(PDO::FETCH_ASSOC)) {
        $users_map[$row['user_id']] = $row;
    }

    for ($i = 0; $i < $NUM_ATTEMPTS; $i++) {

        // A. Random Yêu cầu đặt phòng
        $rt = $room_types[array_rand($room_types)];
        $user_id = $user_ids[array_rand($user_ids)];
        $u_info = $users_map[$user_id];

        // Random ngày trong đợt cao điểm
        $day_offset = rand(0, $PEAK_DURATION_DAYS);
        $check_in = date('Y-m-d', strtotime("$PEAK_START_DATE + $day_offset days"));

        // Logic đêm nghỉ
        if ($rt['category'] == 'apartment') {
            $nights = rand(3, 10); // Căn hộ thuê lâu hơn
        } else {
            $nights = rand(1, 3); // Phòng khách sạn thuê ngắn
        }
        $check_out = date('Y-m-d', strtotime("$check_in + $nights days"));

        // B. KIỂM TRA PHÒNG TRỐNG (LOGIC THẬT)
        // Đây là bước quan trọng nhất để làm "nóng" database
        $is_available = $bookingModel->checkAvailability($rt['room_type_id'], $check_in, $check_out);

        if ($is_available) {
            // C. Nếu còn phòng => INSERT
            // Tự động gán phòng (room_id) luôn để đúng chuẩn
            $assigned_room = $bookingModel->getAvailableRoom($rt['room_type_id'], $check_in, $check_out);
            $room_id = $assigned_room ? $assigned_room['room_id'] : null;

            // Tính giá & Status
            $total_amount = $rt['base_price'] * $nights;
            $bcode = 'SMART' . date('dm') . strtoupper(bin2hex(random_bytes(2))) . $i;

            $bookingModel->create([
                'booking_code' => $bcode,
                'booking_type' => 'instant',
                'user_id' => $user_id,
                'room_type_id' => $rt['room_type_id'],
                'room_id' => $room_id, // Gán phòng luôn
                'check_in_date' => $check_in,
                'check_out_date' => $check_out,
                'num_adults' => 2,
                'num_children' => 0,
                'total_nights' => $nights,
                'room_price' => $rt['base_price'] * $nights,
                'payment_status' => 'paid',
                'status' => 'confirmed', // Đã check availability nên auto confirm
                'total_amount' => $total_amount,
                'guest_name' => $u_info['full_name'],
                'guest_email' => $u_info['email'],
                'guest_phone' => $u_info['phone']
            ]);

            $success_count++;
            // echo "<span style='color:green'>+</span> "; 
        } else {
            // D. Hết phòng => Bỏ qua (Mô phỏng khách hàng bỏ đi hoặc chọn ngày khác)
            $full_count++;
            // echo "<span style='color:red'>x</span> ";
        }

        if ($i % 50 == 0) {
            echo ". ";
            flush();
        }
    }

    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 2);

    echo "<br><br><strong>SIMULATION RESULTS:</strong><br>";
    echo "Total Requests: $NUM_ATTEMPTS<br>";
    echo "- Success (Booked): <span style='color:green'>$success_count</span><br>";
    echo "- Rejected (Full): <span style='color:red'>$full_count</span><br>";
    echo "Time taken: {$duration}s<br>";

    if ($success_count > 0) {
        echo "Avg Booking Speed: " . round($success_count / $duration, 2) . " bookings/sec (including logic check)<br>";
    }

    echo "<i>Note: This test runs real 'Check Availability' logic against your database. High 'Rejected' count means your hotel is fully booked for the peak period.</i>";
    echo "<br><br><a href='index.php'>[Back to Home]</a>";

} catch (Exception $e) {
    echo "<br><strong style='color:red'>ERROR:</strong> " . $e->getMessage();
}
echo "</div>";
?>