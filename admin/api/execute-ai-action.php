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
    $affected = 0;

    // Switch on the recognized intent from Gemini
    if ($action === 'CREATE_PROMOTION' && $table === 'promotions') {
        $stmt = $db->prepare("
            INSERT INTO promotions (code, title, discount_type, discount_value, min_booking_amount, start_date, end_date)
            VALUES (:code, :title, :type, :val, :min, :sd, :ed)
        ");
        $stmt->execute([
            'code' => $data['code'],
            'title' => $data['title'] ?? 'AI Generated Promo',
            'type' => $data['discount_type'] ?? 'percentage',
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
