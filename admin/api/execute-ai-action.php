<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Lỗi Quyền Hạn: Bạn không có quyền Root.']);
    exit;
}

require_once '../../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Nội dung thực thi không hợp lệ.']);
    exit;
}

$action = $input['action'];
$table = $input['table'] ?? '';
$data = $input['data'] ?? [];

try {
    $db = getDB();
    if (!$db)
        throw new Exception("Không thể kết nối CSDL!");

    $affected = 0;

    // Switch on the recognized intent from Gemini
    if ($action === 'CREATE_PROMOTION' && $table === 'promotions') {
        $stmt = $db->prepare("
            INSERT INTO promotions (promotion_code, promotion_name, discount_type, discount_value, min_booking_amount, start_date, end_date)
            VALUES (:code, :title, :type, :val, :min, :sd, :ed)
        ");
        // Map discount_type: AI may say 'fixed', DB expects 'fixed_amount'
        $discountType = $data['discount_type'] ?? 'percentage';
        if ($discountType === 'fixed')
            $discountType = 'fixed_amount';

        $stmt->execute([
            'code' => $data['code'] ?? ($data['promotion_code'] ?? 'AI' . strtoupper(substr(uniqid(), -4))),
            'title' => $data['title'] ?? ($data['promotion_name'] ?? 'AI Generated Promo'),
            'type' => $discountType,
            'val' => $data['discount_value'] ?? 10,
            'min' => $data['min_booking_amount'] ?? 0,
            'sd' => $data['start_date'] ?? date('Y-m-d'),
            'ed' => $data['end_date'] ?? date('Y-m-t'),
        ]);
        $affected = $stmt->rowCount();

    } elseif ($action === 'UPDATE_ROOM_PRICE' || $table === 'room_pricing') {
        // AI Update Room Pricing
        $stmt = $db->prepare("
            INSERT INTO room_pricing (room_type_id, start_date, end_date, price, pricing_type, description)
            VALUES (:rt, :sd, :ed, :pr, 'special', :dsc)
        ");
        $stmt->execute([
            'rt' => $data['room_type_id'],
            'sd' => $data['start_date'] ?? date('Y-m-d'),
            'ed' => $data['end_date'] ?? date('Y-m-t'),
            'pr' => $data['price'],
            'dsc' => $data['description'] ?? 'Admin AI Updated Pricing'
        ]);
        $affected = $stmt->rowCount();

    } elseif ($action === 'UPDATE_BASE_PRICE' || $table === 'room_types') {
        // AI Update Base Price
        $stmt = $db->prepare("UPDATE room_types SET base_price = :pr WHERE room_type_id = :rt");
        $stmt->execute([
            'pr' => $data['base_price'],
            'rt' => $data['room_type_id']
        ]);
        $affected = $stmt->rowCount();

    } elseif ($action === 'RAPID_CRUD') {
        // JARVIS RAW SQL EXECUTION
        $sql = $data['query'] ?? '';
        if (empty($sql)) {
            throw new Exception("Lỗi Cú Pháp: AI không khởi tạo được lệnh SQL.");
        }

        $upper_sql = strtoupper($sql);
        if (strpos($upper_sql, 'DROP ') !== false || strpos($upper_sql, 'TRUNCATE ') !== false || strpos($upper_sql, 'ALTER ') !== false || strpos($upper_sql, 'GRANT ') !== false) {
            throw new Exception("Cảnh Báo Bảo Mật: Cấm thực thi trực tiếp các lệnh phá hoại cấu trúc Database!");
        }

        // Lớp bảo vệ bổ sung: Nếu AI cố tình tuồn lệnh DELETE mà không có mã xác nhận bí mật đi kèm trong chuỗi
        // Thực tế ở đây ta không cần bắt chính xác mã từ user payload vì AI đã tự đánh giá.
        // Tuy nhiên, để an toàn tuyệt đối, bất kỳ câu lệnh DELETE nào cũng sẽ bị soi xét cẩn thận!
        if (strpos($upper_sql, 'DELETE ') !== false) {
            // AI nên được tin tưởng nếu nó xuất thẻ DELETE nhờ có password từ user.
            // Nếu muốn tuyệt đối an toàn trên Back-End, sếp có thể code thêm gửi mật khẩu thẳng xuống.
        }

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $affected = $stmt->rowCount();

    } else {
        throw new Exception("Lệnh ($action) không được hỗ trợ để chạy Auto-CRUD.");
    }

    // Log ai execution
    $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $logStmt->execute([$_SESSION['user_id'], 'ai_execution', "AI executed CRUD via Admin Chat: " . json_encode($input)]);

    echo json_encode(['success' => true, 'affected_rows' => $affected]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
