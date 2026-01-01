<?php
/**
 * DATABASE STRESS TEST SEEDER (HIGH CONCURRENCY SIMULATION)
 * Mô phỏng luồng dữ liệu lớn tập trung trong một khoảng thời gian ngắn
 * Mục tiêu: Test khả năng xử lý bookings, check availability và reporting
 */

require_once 'config/database.php';

// Tăng giới hạn thực thi
set_time_limit(600);
ini_set('memory_limit', '1024M');

$db = getDB();

// Cấu hình test
$NUM_THREADS = 200; // Số lượng "người dùng" giả lập đặt phòng cùng lúc
$DATA_PER_THREAD = 3; // Mỗi người đặt tối thiểu 3 lần
$TOTAL_BOOKINGS = $NUM_THREADS * $DATA_PER_THREAD; // Tổng 600 bookings

// Khoảng thời gian CAO ĐIỂM cần test (Ví dụ: Tết Nguyên Đán hoặc Lễ - Tháng tới)
$PEAK_START_DATE = date('Y-m-d', strtotime('+30 days')); // Bắt đầu từ 30 ngày tới
$PEAK_DURATION_DAYS = 7; // Cao điểm trong 1 tuần

echo "<h1>Aurora Stress Test Seeder</h1>";
echo "<div style='font-family: monospace; line-height: 1.5;'>";
echo "<strong>Configuration:</strong><br>";
echo "- Virtual Threads (Concurrent Users): $NUM_THREADS<br>";
echo "- Bookings per User: $DATA_PER_THREAD<br>";
echo "- Total Bookings to Generate: $TOTAL_BOOKINGS<br>";
echo "- Target Peak Period: $PEAK_START_DATE (for $PEAK_DURATION_DAYS days)<br>";
echo "<hr>";

try {
    // 1. Chuẩn bị dữ liệu Room Types & Pricing
    $stmt = $db->query("SELECT room_type_id, base_price, category FROM room_types WHERE status = 'active'");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($room_types)) {
        die("Lỗi: Không tìm thấy loại phòng nào. Vui lòng thêm loại phòng trước.");
    }

    // 2. Tạo 200 Users giả ("Threads")
    echo "Creating $NUM_THREADS concurrent users... ";
    $faker_names = ['Nguyen', 'Tran', 'Le', 'Pham', 'Hoang', 'Huynh', 'Phan', 'Vu', 'Vo', 'Dang', 'Bui', 'Do', 'Ho', 'Ngo', 'Duong', 'Ly'];
    $faker_mids = ['Van', 'Thi', 'Minh', 'Duc', 'Quoc', 'Tuan', 'Thanh', 'Ngoc', 'Hai', 'Xuan'];
    $faker_lasts = ['An', 'Binh', 'Cuong', 'Dung', 'Giang', 'Hieu', 'Hung', 'Khanh', 'Long', 'Minh', 'Nam', 'Phuc', 'Quan', 'Son', 'Thang', 'Tung'];

    $user_ids = [];
    $insert_user = $db->prepare("
        INSERT IGNORE INTO users (email, password_hash, full_name, phone, user_role, status, email_verified, created_at) 
        VALUES (:email, :pass, :name, :phone, 'customer', 'active', 1, NOW())
    ");

    $db->beginTransaction();
    for ($i = 0; $i < $NUM_THREADS; $i++) {
        $name = $faker_names[array_rand($faker_names)] . ' ' . $faker_mids[array_rand($faker_mids)] . ' ' . $faker_lasts[array_rand($faker_lasts)];
        $email = 'perf_test_' . uniqid() . $i . '@test.com'; // Unique email
        $phone = '09' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);

        $insert_user->execute([
            ':email' => $email,
            ':pass' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: password
            ':name' => $name,
            ':phone' => $phone
        ]);

        // Lấy ID vừa tạo hoặc ID của email đã tồn tại (nếu duplicate)
        $uid = $db->lastInsertId();
        if ($uid == 0) {
            $stmt_uid = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt_uid->execute([$email]);
            $uid = $stmt_uid->fetchColumn();
        }
        $user_ids[] = $uid;
    }
    $db->commit();
    echo "Done.<br>";

    // 3. Bắt đầu xả data (600 Bookings tập trung)
    echo "<h3>Injecting $TOTAL_BOOKINGS bookings into peak period...</h3>";

    $insert_booking = $db->prepare("
        INSERT INTO bookings (
            booking_code, booking_type, user_id, room_id, room_type_id,
            check_in_date, check_out_date, num_adults, num_children, num_rooms, total_nights,
            room_price, extra_guest_fee, extra_bed_fee, extra_beds, total_amount,
            guest_name, guest_email, guest_phone,
            occupancy_type, price_type_used, status, payment_status, created_at
        ) VALUES (
            :code, :type, :uid, :rid, :rtid,
            :cin, :cout, :adults, :kids, 1, :nights,
            :price, 0, :bed_fee, :beds, :total,
            :gname, :gemail, :gphone,
            'double', 'double', :status, :pay_status, :created
        )
    ");

    // Lấy thông tin chi tiết user để map vào booking
    $stmt_users = $db->query("SELECT user_id, full_name, email, phone FROM users WHERE email LIKE 'perf_test_%'");
    $users_map = [];
    while ($row = $stmt_users->fetch(PDO::FETCH_ASSOC)) {
        $users_map[$row['user_id']] = $row;
    }

    $count = 0;
    $collisions_expected = 0;
    $start_time = microtime(true);

    $db->beginTransaction();

    foreach ($user_ids as $uid) {
        $u_info = $users_map[$uid] ?? ['full_name' => 'Tester', 'email' => 'test@test.com', 'phone' => '0000000000'];

        // Mỗi user thử đặt 3 lần trong khoảng thời gian cao điểm
        for ($k = 0; $k < $DATA_PER_THREAD; $k++) {

            // Random ngày trong tuần cao điểm => Tỉ lệ trùng lặp cao
            $random_day_offset = rand(0, $PEAK_DURATION_DAYS);
            $check_in = date('Y-m-d', strtotime("$PEAK_START_DATE + $random_day_offset days"));
            $nights = rand(1, 3);
            $check_out = date('Y-m-d', strtotime("$check_in + $nights days"));

            $room_type = $room_types[array_rand($room_types)];

            // Tính giá
            $base_price = $room_type['base_price'];
            $room_total = $base_price * $nights;
            $extra_beds = (rand(0, 100) > 80) ? 1 : 0; // 20% cần giường phụ
            $extra_bed_fee = $extra_beds * 650000 * $nights;
            $total = $room_total + $extra_bed_fee;

            // STATUS: Mô phỏng hỗn loạn
            // 70% Pending (chờ admin duyệt vì quá tải), 20% Confirmed, 10% Cancelled
            $rand_status = rand(0, 100);
            if ($rand_status < 70) {
                $status = 'pending';
                $pay_status = 'unpaid';
            } elseif ($rand_status < 90) {
                $status = 'confirmed';
                $pay_status = 'paid';
            } else {
                $status = 'cancelled';
                $pay_status = 'refunded';
            }

            // Booking Code: BK + TIMESTAMP + RANDOM (Mô phỏng request cùng giây)
            // Để test collision, ta không sleep() ở đây.
            $bcode = 'STRESS' . date('dm') . strtoupper(bin2hex(random_bytes(3)));

            // Created At: Dồn vào thời điểm hiện tại hoặc rải rác trong 1 giờ qua
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(0, 60) . ' minutes'));

            $insert_booking->execute([
                ':code' => $bcode,
                ':type' => 'instant',
                ':uid' => $uid,
                ':rid' => null, // Chưa xếp phòng (Overbooking scenario)
                ':rtid' => $room_type['room_type_id'],
                ':cin' => $check_in,
                ':cout' => $check_out,
                ':adults' => 2,
                ':kids' => 0,
                ':nights' => $nights,
                ':price' => $base_price,
                ':bed_fee' => $extra_bed_fee,
                ':beds' => $extra_beds,
                ':total' => $total,
                ':gname' => $u_info['full_name'],
                ':gemail' => $u_info['email'],
                ':gphone' => $u_info['phone'],
                ':status' => $status,
                ':pay_status' => $pay_status,
                ':created' => $created_at
            ]);

            $count++;
            if ($count % 100 == 0)
                echo ". ";
        }
    }

    $db->commit();
    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 4);

    echo "<br><br><strong>SUCCESS!</strong><br>";
    echo "Generated $count bookings in $duration seconds.<br>";
    echo "Average insertion speed: " . round($count / $duration) . " bookings/sec.<br>";
    echo "Data is focused between $PEAK_START_DATE and " . date('Y-m-d', strtotime("$PEAK_START_DATE + $PEAK_DURATION_DAYS days")) . ".<br>";
    echo "<a href='index.php'>[Back to Home]</a>";

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo "<br><strong style='color:red'>ERROR:</strong> " . $e->getMessage();
}
echo "</div>";
?>