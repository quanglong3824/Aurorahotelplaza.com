<?php
/**
 * ULTIMATE SEEDER (USER GEN + SMART BOOKING)
 * Tự động tạo user nếu thiếu và đặt phòng thông minh.
 */

require_once 'config/database.php';
require_once 'models/Booking.php';

set_time_limit(1200);
ini_set('memory_limit', '1024M');

$db = getDB();
$bookingModel = new Booking($db); // Model xử lý logic check phòng

// Cấu hình
$NUM_USERS_TO_CREATE = 200;   // Tăng user lên để đa dạng
$NUM_ATTEMPTS = 9000;         // Tăng số lượt bắn data lên 9000
$PEAK_START_DATE = date('Y-m-d', strtotime('-30 days')); // Bắn từ quá khứ đến tương lai
$PEAK_DURATION_DAYS = 90;     // Kéo dài 3 tháng để dàn trải data

echo "<h1>Ultimate Data Seeder</h1>";
echo "<div style='font-family: monospace; line-height: 1.5;'>";

try {
    // ---------------------------------------------------------
    // BƯỚC 1: KIỂM TRA & TẠO USER (Nếu thiếu)
    // ---------------------------------------------------------
    echo "<h3>1. Checking User Base...</h3>";

    // Đếm số user hiện có
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE user_role = 'customer'");
    $current_users = $stmt->fetchColumn();

    $user_ids = [];

    if ($current_users < $NUM_USERS_TO_CREATE) {
        echo "Found only $current_users users. Generating more...<br>";

        $faker_first = ['Nguyen', 'Tran', 'Le', 'Pham', 'Hoang', 'Huynh', 'Phan', 'Vu', 'Vo', 'Dang'];
        $faker_last = ['An', 'Binh', 'Cuong', 'Dung', 'Giang', 'Hieu', 'Hung', 'Khanh', 'Long', 'Minh', 'Nam'];

        $insert_user = $db->prepare("
            INSERT IGNORE INTO users (email, password_hash, full_name, phone, user_role, status, email_verified, created_at) 
            VALUES (?, ?, ?, ?, 'customer', 'active', 1, NOW())
        ");

        $db->beginTransaction();
        for ($i = 0; $i < $NUM_USERS_TO_CREATE; $i++) {
            $name = $faker_first[array_rand($faker_first)] . ' ' . $faker_last[array_rand($faker_last)];
            $email = 'user_' . uniqid() . '@example.com';
            $phone = '09' . rand(10000000, 99999999);
            $pass = password_hash('123456', PASSWORD_DEFAULT);

            $insert_user->execute([$email, $pass, $name, $phone]);
        }
        $db->commit();
        echo "Created $NUM_USERS_TO_CREATE new users.<br>";
    } else {
        echo "User base is sufficient ($current_users users).<br>";
    }

    // Lấy danh sách ID user để dùng
    $stmt = $db->query("SELECT user_id FROM users WHERE user_role = 'customer' LIMIT 100");
    $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Load thông tin user vào RAM để dùng dần
    $stmt_users = $db->query("SELECT user_id, full_name, email, phone FROM users WHERE user_id IN (" . implode(',', $user_ids) . ")");
    $users_map = [];
    while ($row = $stmt_users->fetch(PDO::FETCH_ASSOC)) {
        $users_map[$row['user_id']] = $row;
    }

    // ---------------------------------------------------------
    // BƯỚC 2: CHUẨN BỊ INVENTORY (KHO PHÒNG)
    // ---------------------------------------------------------
    echo "<h3>2. Analyzing Inventory...</h3>";
    $sql = "
        SELECT rt.room_type_id, rt.type_name, rt.base_price, rt.category, 
               (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id AND r.status = 'available') as total_rooms
        FROM room_types rt 
        WHERE rt.status = 'active'
    ";
    $room_types = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    if (empty($room_types))
        die("Error: No room types found.");

    foreach ($room_types as $rt) {
        $qty = $rt['total_rooms'] > 0 ? $rt['total_rooms'] : 5; // Fallback 5 nếu chưa khai báo phòng
        echo "- {$rt['type_name']}: $qty rooms.<br>";
    }

    // ---------------------------------------------------------
    // BƯỚC 3: SMART BOOKING SIMULATION
    // ---------------------------------------------------------
    echo "<h3>3. Starting Simulation ($NUM_ATTEMPTS requests)...</h3>";

    $success = 0;
    $rejected = 0;
    $start_time = microtime(true);

    for ($i = 0; $i < $NUM_ATTEMPTS; $i++) {
        // Random dữ liệu đầu vào
        $rt = $room_types[array_rand($room_types)];
        $uid = $user_ids[array_rand($user_ids)];
        $u_info = $users_map[$uid];

        $day_offset = rand(0, $PEAK_DURATION_DAYS);
        $check_in = date('Y-m-d', strtotime("$PEAK_START_DATE + $day_offset days"));

        $nights = ($rt['category'] == 'apartment') ? rand(2, 7) : rand(1, 3);
        $check_out = date('Y-m-d', strtotime("$check_in + $nights days"));

        // *** CORE LOGIC ***
        $is_available = $bookingModel->checkAvailability($rt['room_type_id'], $check_in, $check_out);

        if ($is_available) {
            // Lấy phòng trống cụ thể để gán
            $assigned_room = $bookingModel->getAvailableRoom($rt['room_type_id'], $check_in, $check_out);
            $room_id = $assigned_room ? $assigned_room['room_id'] : null;

            $bookingModel->create([
                'booking_code' => 'AUTO' . date('dm') . strtoupper(bin2hex(random_bytes(2))) . $i,
                'booking_type' => 'instant',
                'user_id' => $uid,
                'room_type_id' => $rt['room_type_id'],
                'room_id' => $room_id,
                'check_in_date' => $check_in,
                'check_out_date' => $check_out,
                'num_adults' => 2,
                'total_nights' => $nights,
                'room_price' => $rt['base_price'] * $nights,
                'total_amount' => $rt['base_price'] * $nights,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'guest_name' => $u_info['full_name'],
                'guest_email' => $u_info['email'],
                'guest_phone' => $u_info['phone']
            ]);
            $success++;
        } else {
            $rejected++;
        }

        if ($i % 50 == 0) {
            echo ". ";
            flush();
        }
    }

    $duration = round(microtime(true) - $start_time, 2);

    echo "<br><hr>";
    echo "<strong>RESULT:</strong><br>";
    echo "User Base: " . count($user_ids) . " users.<br>";
    echo "Total Requests: $NUM_ATTEMPTS<br>";
    echo "Successful Bookings: <strong style='color:green'>$success</strong><br>";
    echo "Rejected (Full): <strong style='color:red'>$rejected</strong><br>";
    echo "Time: {$duration}s (" . round($success / $duration) . " bookings/sec)<br>";
    echo "<br><a href='index.php'>[Go Home]</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
echo "</div>";
?>