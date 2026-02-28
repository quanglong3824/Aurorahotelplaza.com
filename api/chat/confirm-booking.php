<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để đặt phòng!']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$slug = $input['slug'] ?? '';
$check_in = $input['check_in'] ?? '';
$check_out = $input['check_out'] ?? '';
$message_id = $input['message_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$slug || !$check_in || !$check_out) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đặt phòng.']);
    exit;
}

try {
    $db = getDB();

    $msgRow = null;
    if ($message_id > 0) {
        $stmtCheck = $db->prepare("SELECT message FROM chat_messages WHERE message_id = ?");
        $stmtCheck->execute([$message_id]);
        $msgRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($msgRow && strpos($msgRow['message'], '[BOOK_NOW_BTN_SUCCESS:') !== false) {
            throw new Exception("Quý khách đã xác nhận đặt phòng này rồi!");
        }
    }

    // Convert DD/MM/YYYY to YYYY-MM-DD if needed, assuming Gemini might output format like 15/05/2026
    $dCheckIn = DateTime::createFromFormat('d/m/Y', $check_in);
    if ($dCheckIn) {
        $check_in_date = $dCheckIn->format('Y-m-d');
    } else {
        $check_in_date = date('Y-m-d', strtotime(str_replace('/', '-', $check_in)));
    }

    $dCheckOut = DateTime::createFromFormat('d/m/Y', $check_out);
    if ($dCheckOut) {
        $check_out_date = $dCheckOut->format('Y-m-d');
    } else {
        $check_out_date = date('Y-m-d', strtotime(str_replace('/', '-', $check_out)));
    }

    if (!$check_in_date || !$check_out_date) {
        throw new Exception("Định dạng ngày không hợp lệ.");
    }

    // Lấy thông tin User
    $stmtUser = $db->prepare("SELECT full_name, email, phone FROM users WHERE user_id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Lấy thông tin Room Type
    $stmtRoom = $db->prepare("SELECT room_type_id, base_price, price_double_occupancy, booking_type FROM room_types WHERE slug = ? AND status='active'");
    $stmtRoom->execute([$slug]);
    $roomType = $stmtRoom->fetch(PDO::FETCH_ASSOC);

    if (!$roomType) {
        throw new Exception("Không tìm thấy loại phòng này.");
    }

    $room_type_id = $roomType['room_type_id'];

    // Tính số đêm
    $ci = new DateTime($check_in_date);
    $co = new DateTime($check_out_date);
    $nights = $ci->diff($co)->days;
    if ($nights < 1)
        $nights = 1;

    // Giá
    $price = !empty($roomType['price_double_occupancy']) ? $roomType['price_double_occupancy'] : $roomType['base_price'];
    $total_amount = $price * $nights;

    // Tìm 1 phòng trống
    $stmtFindRoom = $db->prepare("
        SELECT r.room_id 
        FROM rooms r
        WHERE r.room_type_id = ? 
        AND r.status = 'available'
        AND r.room_id NOT IN (
            SELECT room_id FROM bookings WHERE room_id IS NOT NULL AND status NOT IN ('cancelled', 'checked_out')
            AND ((check_in_date <= ? AND check_out_date > ?) OR (check_in_date < ? AND check_out_date >= ?) OR (check_in_date >= ? AND check_out_date <= ?))
        ) LIMIT 1
    ");
    $stmtFindRoom->execute([$room_type_id, $check_in_date, $check_in_date, $check_out_date, $check_out_date, $check_in_date, $check_out_date]);
    $availRoom = $stmtFindRoom->fetch(PDO::FETCH_ASSOC);
    $room_id = $availRoom ? $availRoom['room_id'] : null;

    // Tạo mã Booking
    $booking_code = 'BKA' . date('ymd') . strtoupper(substr(uniqid(), -4));

    $stmtCreate = $db->prepare("
        INSERT INTO bookings (
            booking_code, booking_type, user_id, room_id, room_type_id,
            check_in_date, check_out_date, num_adults, num_children, num_rooms, total_nights,
            room_price, total_amount, guest_name, guest_email, guest_phone,
            status, payment_status, special_requests
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', 'AI Quick Booking Offline')
    ");

    $guest_name = $user['full_name'] ?? 'Khách';
    $guest_email = $user['email'] ?? '';
    $guest_phone = $user['phone'] ?? '';

    $stmtCreate->execute([
        $booking_code,
        $bType,
        $user_id,
        $room_id,
        $room_type_id,
        $check_in_date,
        $check_out_date,
        2,
        0,
        1,
        $nights,
        $total_amount,
        $total_amount,
        $guest_name,
        $guest_email,
        $guest_phone
    ]);

    $booking_id = $db->lastInsertId();

    if ($room_id && $bType === 'instant') {
        $db->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?")->execute([$room_id]);
    }

    if ($message_id > 0 && $msgRow) {
        $newMessage = preg_replace(
            '/\[BOOK_NOW_BTN:\s*slug=[^,\]]+,\s*name=[^,\]]+,\s*cin=[^,\]]+,\s*cout=[^\]]+\]/i',
            '[BOOK_NOW_BTN_SUCCESS: booking_code=' . $booking_code . ', booking_id=' . $booking_id . ']',
            $msgRow['message']
        );
        $db->prepare("UPDATE chat_messages SET message = ? WHERE message_id = ?")->execute([$newMessage, $message_id]);
    }

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'booking_code' => $booking_code
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
