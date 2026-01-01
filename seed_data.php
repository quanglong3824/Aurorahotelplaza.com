<?php
/**
 * EXTREME SEEDER (ALL-IN-ONE)
 * 1. Tự tạo User.
 * 2. Bắn 20k request theo Batch.
 */

require_once 'config/database.php';
require_once 'models/Booking.php';

set_time_limit(3600);
ini_set('memory_limit', '2048M');

$db = getDB();
$bookingModel = new Booking($db);

$NUM_USERS = 200;
$NUM_REQUESTS = 20000;
$BATCH_SIZE = 500;
$PEAK_START_DATE = date('Y-m-d', strtotime('-60 days'));
$PEAK_DURATION_DAYS = 120;

echo "<h1>Extreme Data Seeder (Full Package)</h1>";
echo "<div style='font-family: monospace; line-height: 1.5;'>";

try {
    // ----------------------------------------------------------------
    // 1. TẠO USER (NẾU CHƯA CÓ)
    // ----------------------------------------------------------------
    $stmt = $db->query("SELECT count(*) FROM users WHERE user_role = 'customer'");
    if ($stmt->fetchColumn() < $NUM_USERS) {
        echo "Creating $NUM_USERS users... ";
        $faker_first = ['Nguyen', 'Tran', 'Le', 'Pham', 'Hoang', 'Huynh', 'Phan', 'Vu', 'Vo', 'Dang'];
        $insert = $db->prepare("INSERT IGNORE INTO users (email, password_hash, full_name, phone, user_role, status, email_verified, created_at) VALUES (?, ?, ?, ?, 'customer', 'active', 1, NOW())");

        $db->beginTransaction();
        for ($i = 0; $i < $NUM_USERS; $i++) {
            $email = 'u' . uniqid() . $i . '@test.com';
            $name = $faker_first[array_rand($faker_first)] . ' User ' . $i;
            $insert->execute([$email, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', $name, '0123456789']);
        }
        $db->commit();
        echo "Done.<br>";
    }

    $user_ids = $db->query("SELECT user_id FROM users WHERE user_role='customer'")->fetchAll(PDO::FETCH_COLUMN);
    $room_types = $db->query("SELECT room_type_id, base_price, category FROM room_types")->fetchAll(PDO::FETCH_ASSOC);

    // ----------------------------------------------------------------
    // 2. BẮN DATA (BATCH MODE)
    // ----------------------------------------------------------------
    echo "<h3>Starting 20,000 Booking Requests...</h3>";
    $success = 0;
    $rejected = 0;
    $start_time = microtime(true);

    $db->beginTransaction(); // Batch đầu tiên

    for ($i = 0; $i < $NUM_REQUESTS; $i++) {
        $rt = $room_types[array_rand($room_types)];
        $uid = $user_ids[array_rand($user_ids)];

        $check_in = date('Y-m-d', strtotime("$PEAK_START_DATE + " . rand(0, $PEAK_DURATION_DAYS) . " days"));
        $nights = ($rt['category'] == 'apartment') ? rand(2, 5) : rand(1, 2);
        // Date object cho check_out
        $d = new DateTime($check_in);
        $d->modify("+$nights days");
        $check_out = $d->format('Y-m-d');

        // Check availability
        if ($bookingModel->checkAvailability($rt['room_type_id'], $check_in, $check_out)) {
            $assigned = $bookingModel->getAvailableRoom($rt['room_type_id'], $check_in, $check_out);
            $rid = $assigned ? $assigned['room_id'] : null;

            // Raw Insert
            $stmt = $db->prepare("INSERT INTO bookings (booking_code, booking_type, user_id, room_id, room_type_id, check_in_date, check_out_date, num_adults, total_nights, room_price, total_amount, status, payment_status, guest_name, guest_email, guest_phone, created_at) VALUES (?, 'instant', ?, ?, ?, ?, ?, 2, ?, ?, ?, 'confirmed', 'paid', ?, ?, ?, NOW())");

            $stmt->execute([
                'X' . $i . strtoupper(bin2hex(random_bytes(2))),
                $uid,
                $rid,
                $rt['room_type_id'],
                $check_in,
                $check_out,
                $nights,
                $rt['base_price'] * $nights,
                $rt['base_price'] * $nights,
                'Stress Tester',
                'test@test.com',
                '0987654321'
            ]);
            $success++;
        } else {
            $rejected++;
        }

        // Batch Commit
        if (($i + 1) % $BATCH_SIZE == 0) {
            $db->commit();
            if (function_exists('gc_collect_cycles'))
                gc_collect_cycles();
            $db->beginTransaction(); // Batch mới
            echo ". ";
            flush();
            if (($i + 1) % ($BATCH_SIZE * 20) == 0)
                echo ($i + 1) . "<br>";
        }
    }
    $db->commit(); // Commit cuối cùng

    $duration = round(microtime(true) - $start_time, 2);
    echo "<br><hr><strong>DONE!</strong> Time: {$duration}s. Success: <strong style='color:green'>$success</strong>. Full: <strong style='color:red'>$rejected</strong>.<br>";
    echo "<a href='index.php'>[Home]</a>";

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo "Error: " . $e->getMessage();
}
echo "</div>";
?>