<?php
/**
 * api/chat/get-session-bookings.php
 * ─────────────────────────────────
 * Lấy danh sách đặt phòng dựa trên session hiện tại (Logged-in user hoặc guest cookie).
 */

session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
$recent_bookings_cookie = $_COOKIE['aurora_recent_bookings'] ?? '';
$recent_booking_codes = !empty($recent_bookings_cookie) ? explode(',', $recent_bookings_cookie) : [];

// Nếu không có thông tin gì, trả về trống
if (!$user_id && empty($recent_booking_codes)) {
    echo json_encode(['success' => true, 'bookings' => []]);
    exit;
}

try {
    $db = getDB();
    
    // Xây dựng câu truy vấn linh hoạt
    $conditions = [];
    $params = [];
    
    // Nếu có user_id (đã đăng nhập)
    if ($user_id) {
        $conditions[] = "b.user_id = :user_id";
        $params[':user_id'] = $user_id;
    }
    
    // Nếu có mã booking từ cookie (hỗ trợ khách vãng lai/thiết bị này)
    if (!empty($recent_booking_codes)) {
        $placeholders = [];
        foreach ($recent_booking_codes as $index => $code) {
            $p_name = ":code_" . $index;
            $placeholders[] = $p_name;
            $params[$p_name] = trim($code);
        }
        $conditions[] = "b.booking_code IN (" . implode(',', $placeholders) . ")";
    }
    
    if (empty($conditions)) {
        echo json_encode(['success' => true, 'bookings' => []]);
        exit;
    }
    
    $where_clause = implode(' OR ', $conditions);
    
    $sql = "
        SELECT 
            b.booking_id,
            b.booking_code,
            b.status,
            b.payment_status,
            b.check_in_date,
            b.check_out_date,
            b.total_amount,
            b.created_at,
            rt.type_name as room_type_name,
            rt.category
        FROM bookings b
        JOIN room_types rt ON b.room_type_id = rt.room_type_id
        WHERE {$where_clause}
        ORDER BY b.created_at DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format lại dữ liệu cho UI
    $formatted_bookings = [];
    foreach ($bookings as $b) {
        $status_label = '';
        $status_color = '';
        
        switch ($b['status']) {
            case 'pending': 
                $status_label = 'Chờ xác nhận'; 
                $status_color = '#fbbf24'; // Yellow
                break;
            case 'confirmed': 
                $status_label = 'Đã xác nhận'; 
                $status_color = '#10b981'; // Green
                break;
            case 'checked_in': 
                $status_label = 'Đang ở'; 
                $status_color = '#3b82f6'; // Blue
                break;
            case 'checked_out': 
                $status_label = 'Đã trả phòng'; 
                $status_color = '#6b7280'; // Gray
                break;
            case 'cancelled': 
                $status_label = 'Đã hủy'; 
                $status_color = '#ef4444'; // Red
                break;
            default:
                $status_label = $b['status'];
                $status_color = '#94a3b8';
        }
        
        $formatted_bookings[] = [
            'id' => $b['booking_id'],
            'code' => $b['booking_code'],
            'short_code' => substr($b['booking_code'], -6),
            'room' => $b['room_type_name'],
            'category' => $b['category'],
            'check_in' => date('d/m/Y', strtotime($b['check_in_date'])),
            'status' => $b['status'],
            'status_label' => $status_label,
            'status_color' => $status_color,
            'payment' => $b['payment_status'],
            'total' => number_format($b['total_amount']) . ' VND',
            'created' => $b['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true, 
        'bookings' => $formatted_bookings,
        'count' => count($formatted_bookings)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage()
    ]);
}
