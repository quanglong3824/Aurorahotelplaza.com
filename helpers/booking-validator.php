<?php
/**
 * Aurora Hotel Plaza - Booking Spam Prevention & Overlap Detection
 * Chống spam và xử lý đặt phòng chồng chéo
 */

/**
 * Kiểm tra xem user có đang có quá nhiều booking chưa hoàn tất không
 * NỚI LỎNG: Cho phép đặt tối đa 5 booking cùng lúc để khách có thể đặt cho nhiều gia đình
 * 
 * @param int|null $user_id User ID (nếu đăng nhập)
 * @param string $guest_email Email khách (nếu không đăng nhập)
 * @param string $guest_phone SĐT khách
 * @return array ['allowed' => bool, 'message' => string, 'pending_bookings' => array]
 */
function checkBookingSpam($user_id = null, $guest_email = null, $guest_phone = null) {
    try {
        $db = getDB();
        // Chỉ chặn nếu có quá nhiều đơn 'pending' (chưa thanh toán/xác nhận)
        // Các đơn 'confirmed' hoặc 'checked_in' thì cho phép đặt thêm thoải mái
        $blocked_statuses = ['pending']; 
        $max_pending_bookings = 5; 
        
        $where_conditions = [];
        $params = [];
        
        if ($user_id) {
            $where_conditions[] = "user_id = ?";
            $params[] = $user_id;
        } else {
            $sub_conditions = [];
            if ($guest_email) {
                if (is_array($guest_email) && !empty($guest_email)) {
                    $placeholders = implode(',', array_fill(0, count($guest_email), '?'));
                    $sub_conditions[] = "guest_email IN ($placeholders)";
                    $params = array_merge($params, $guest_email);
                } else {
                    $sub_conditions[] = "guest_email = ?";
                    $params[] = $guest_email;
                }
            }
            if ($guest_phone) {
                if (is_array($guest_phone) && !empty($guest_phone)) {
                    $placeholders = implode(',', array_fill(0, count($guest_phone), '?'));
                    $sub_conditions[] = "guest_phone IN ($placeholders)";
                    $params = array_merge($params, $guest_phone);
                } else {
                    $sub_conditions[] = "guest_phone = ?";
                    $params[] = $guest_phone;
                }
            }
            if (empty($sub_conditions)) return ['allowed' => true, 'message' => '', 'pending_bookings' => []];
            $where_conditions[] = "(" . implode(' OR ', $sub_conditions) . ")";
        }
        
        $placeholders = implode(',', array_fill(0, count($blocked_statuses), '?'));
        $where_conditions[] = "status IN ($placeholders)";
        $params = array_merge($params, $blocked_statuses);
        
        $where_sql = implode(' AND ', $where_conditions);
        
        $stmt = $db->prepare("
            SELECT booking_id, booking_code, status, payment_status, created_at,
                   TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_since_creation
            FROM bookings
            WHERE $where_sql
            ORDER BY created_at DESC
        ");
        $stmt->execute($params);
        $pending_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Auto-cancel unpaid bookings older than 30 mins
        $active_count = 0;
        foreach ($pending_bookings as $booking) {
            if ($booking['payment_status'] === 'unpaid' && $booking['minutes_since_creation'] > 30) {
                autoCancelUnpaidBooking($booking['booking_id']);
            } else {
                $active_count++;
            }
        }
        
        if ($active_count < $max_pending_bookings) {
            return ['allowed' => true, 'message' => '', 'pending_bookings' => []];
        }

        // Nếu vượt quá giới hạn thì mới chặn
        $details = [];
        foreach (array_slice($pending_bookings, 0, 3) as $b) {
            $details[] = "Mã {$b['booking_code']}";
        }
        
        return [
            'allowed' => false,
            'message' => "Bạn đang có $active_count đơn hàng chờ xử lý. Vui lòng hoàn tất hoặc hủy các đơn cũ trước khi đặt thêm quá nhiều đơn mới.",
            'pending_bookings' => $pending_bookings
        ];
        
    } catch (Exception $e) {
        error_log("Booking spam check error: " . $e->getMessage());
        return ['allowed' => true, 'message' => '', 'pending_bookings' => []];
    }
}

/**
 * Kiểm tra trùng lặp đặt phòng
 * NỚI LỎNG: Chỉ cảnh báo hoặc cho phép đặt nhiều phòng cùng lúc
 */
function checkBookingOverlap($user_id = null, $email = null, $phone = null, $new_check_in, $new_check_out) {
    // Cho phép đặt trùng lặp vì khách có thể đặt nhiều phòng cho gia đình/đoàn
    return ['allowed' => true, 'message' => '', 'overlapping_bookings' => []];
}

/**
 * Tự động hủy
 */
function autoCancelUnpaidBooking($booking_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE booking_id = ? AND payment_status = 'unpaid'");
        return $stmt->execute([$booking_id]);
    } catch (Exception $e) { return false; }
}

/**
 * Rate Limit
 */
function checkRateLimit($identifier, $max_requests = 5, $time_window = 60) {
    $file = __DIR__ . '/../config/booking_rate_limits.json';
    $limits = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $now = time();
    
    foreach ($limits as $k => $v) { if ($v['reset_time'] < $now) unset($limits[$k]); }
    
    if (!isset($limits[$identifier])) {
        $limits[$identifier] = ['count' => 1, 'reset_time' => $now + $time_window];
        file_put_contents($file, json_encode($limits));
        return ['allowed' => true, 'message' => '', 'retry_after' => 0];
    }
    
    $limits[$identifier]['count']++;
    if ($limits[$identifier]['count'] > $max_requests) {
        file_put_contents($file, json_encode($limits));
        return ['allowed' => false, 'message' => "Quá nhiều yêu cầu. Thử lại sau " . ($limits[$identifier]['reset_time'] - $now) . " giây.", 'retry_after' => $limits[$identifier]['reset_time'] - $now];
    }
    
    file_put_contents($file, json_encode($limits));
    return ['allowed' => true, 'message' => '', 'retry_after' => 0];
}

function getRateLimitIdentifier() {
    if (isset($_SESSION['user_id'])) return 'user_' . $_SESSION['user_id'];
    return 'ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}
