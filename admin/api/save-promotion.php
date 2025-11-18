<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$promotion_id = $_POST['promotion_id'] ?? null;
$promotion_code = strtoupper(trim($_POST['promotion_code'] ?? ''));
$promotion_name = trim($_POST['promotion_name'] ?? '');
$discount_type = $_POST['discount_type'] ?? '';
$discount_value = $_POST['discount_value'] ?? 0;
$min_booking_amount = $_POST['min_booking_amount'] ?? null;
$max_discount = $_POST['max_discount'] ?? null;
$usage_limit = $_POST['usage_limit'] ?? null;
$usage_per_user = $_POST['usage_per_user'] ?? 1;
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$applicable_to = $_POST['applicable_to'] ?? 'all';
$status = $_POST['status'] ?? 'active';
$description = trim($_POST['description'] ?? '');

if (empty($promotion_code) || empty($promotion_name) || empty($discount_type) || $discount_value <= 0) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

try {
    $db = getDB();
    
    // Check duplicate code
    if ($promotion_id) {
        $stmt = $db->prepare("SELECT promotion_id FROM promotions WHERE promotion_code = :code AND promotion_id != :id");
        $stmt->execute([':code' => $promotion_code, ':id' => $promotion_id]);
    } else {
        $stmt = $db->prepare("SELECT promotion_id FROM promotions WHERE promotion_code = :code");
        $stmt->execute([':code' => $promotion_code]);
    }
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi đã tồn tại']);
        exit;
    }
    
    if ($promotion_id) {
        // Update
        $stmt = $db->prepare("
            UPDATE promotions SET
                promotion_code = :code,
                promotion_name = :name,
                description = :description,
                discount_type = :discount_type,
                discount_value = :discount_value,
                min_booking_amount = :min_booking_amount,
                max_discount = :max_discount,
                usage_limit = :usage_limit,
                usage_per_user = :usage_per_user,
                applicable_to = :applicable_to,
                start_date = :start_date,
                end_date = :end_date,
                status = :status,
                updated_at = NOW()
            WHERE promotion_id = :promotion_id
        ");
        
        $stmt->execute([
            ':code' => $promotion_code,
            ':name' => $promotion_name,
            ':description' => $description,
            ':discount_type' => $discount_type,
            ':discount_value' => $discount_value,
            ':min_booking_amount' => $min_booking_amount,
            ':max_discount' => $max_discount,
            ':usage_limit' => $usage_limit,
            ':usage_per_user' => $usage_per_user,
            ':applicable_to' => $applicable_to,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':status' => $status,
            ':promotion_id' => $promotion_id
        ]);
        
        $message = 'Cập nhật khuyến mãi thành công';
        
    } else {
        // Insert
        $stmt = $db->prepare("
            INSERT INTO promotions (
                promotion_code, promotion_name, description, discount_type, discount_value,
                min_booking_amount, max_discount, usage_limit, usage_per_user,
                applicable_to, start_date, end_date, status, created_by, created_at
            ) VALUES (
                :code, :name, :description, :discount_type, :discount_value,
                :min_booking_amount, :max_discount, :usage_limit, :usage_per_user,
                :applicable_to, :start_date, :end_date, :status, :created_by, NOW()
            )
        ");
        
        $stmt->execute([
            ':code' => $promotion_code,
            ':name' => $promotion_name,
            ':description' => $description,
            ':discount_type' => $discount_type,
            ':discount_value' => $discount_value,
            ':min_booking_amount' => $min_booking_amount,
            ':max_discount' => $max_discount,
            ':usage_limit' => $usage_limit,
            ':usage_per_user' => $usage_per_user,
            ':applicable_to' => $applicable_to,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':status' => $status,
            ':created_by' => $_SESSION['user_id']
        ]);
        
        $promotion_id = $db->lastInsertId();
        $message = 'Thêm khuyến mãi thành công';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'promotion_id' => $promotion_id
    ]);
    
} catch (Exception $e) {
    error_log("Save promotion error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
