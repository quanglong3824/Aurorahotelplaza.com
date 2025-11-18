<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

$promo_code = trim($_POST['promo_code'] ?? '');
$total_amount = floatval($_POST['total_amount'] ?? 0);
$room_type_id = $_POST['room_type_id'] ?? null;

if (empty($promo_code) || $total_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
    exit;
}

try {
    $db = getDB();
    
    // Get promotion
    $stmt = $db->prepare("
        SELECT * FROM promotions
        WHERE code = :code
        AND status = 'active'
        AND start_date <= NOW()
        AND end_date >= NOW()
        LIMIT 1
    ");
    
    $stmt->execute([':code' => strtoupper($promo_code)]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn']);
        exit;
    }
    
    // Check usage limit
    if ($promo['max_uses'] > 0) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as usage_count
            FROM bookings
            WHERE promotion_code = :code
        ");
        $stmt->execute([':code' => $promo['code']]);
        $usage = $stmt->fetch(PDO::FETCH_ASSOC)['usage_count'];
        
        if ($usage >= $promo['max_uses']) {
            echo json_encode(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng']);
            exit;
        }
    }
    
    // Check minimum amount
    if ($promo['min_booking_amount'] > 0 && $total_amount < $promo['min_booking_amount']) {
        echo json_encode([
            'success' => false,
            'message' => 'Giá trị đơn hàng tối thiểu: ' . number_format($promo['min_booking_amount'], 0, ',', '.') . 'đ'
        ]);
        exit;
    }
    
    // Check user usage limit
    if (isset($_SESSION['user_id']) && $promo['max_uses_per_user'] > 0) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as user_usage
            FROM bookings
            WHERE promotion_code = :code AND user_id = :user_id
        ");
        $stmt->execute([
            ':code' => $promo['code'],
            ':user_id' => $_SESSION['user_id']
        ]);
        $user_usage = $stmt->fetch(PDO::FETCH_ASSOC)['user_usage'];
        
        if ($user_usage >= $promo['max_uses_per_user']) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã sử dụng hết lượt áp dụng mã này']);
            exit;
        }
    }
    
    // Calculate discount
    $discount_amount = 0;
    
    if ($promo['discount_type'] === 'percentage') {
        $discount_amount = ($total_amount * $promo['discount_value']) / 100;
        
        // Check max discount
        if ($promo['max_discount_amount'] > 0 && $discount_amount > $promo['max_discount_amount']) {
            $discount_amount = $promo['max_discount_amount'];
        }
    } else {
        // Fixed amount
        $discount_amount = $promo['discount_value'];
        
        // Discount cannot exceed total
        if ($discount_amount > $total_amount) {
            $discount_amount = $total_amount;
        }
    }
    
    $final_amount = $total_amount - $discount_amount;
    
    echo json_encode([
        'success' => true,
        'promotion' => [
            'code' => $promo['code'],
            'name' => $promo['promo_name'],
            'discount_type' => $promo['discount_type'],
            'discount_value' => $promo['discount_value']
        ],
        'original_amount' => $total_amount,
        'discount_amount' => $discount_amount,
        'final_amount' => $final_amount,
        'message' => 'Áp dụng mã giảm giá thành công!'
    ]);
    
} catch (Exception $e) {
    error_log("Apply promotion error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
