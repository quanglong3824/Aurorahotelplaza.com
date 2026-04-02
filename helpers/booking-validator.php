<?php
/**
 * Aurora Hotel Plaza - Booking Spam Prevention & Overlap Detection
 * Chống spam và xử lý đặt phòng chồng chéo
 */

/**
 * Kiểm tra xem user có đang có booking chưa hoàn tất không
 * SIẾT CHẶT: Chỉ cho đặt tiếp khi TẤT CẢ booking cũ đã:
 * - checked_out (đã trả phòng)
 * - cancelled (đã hủy)
 * - confirmed (đã xác nhận) - SIẾT CHẶT: Chặn cả confirmed
 * 
 * @param int|null $user_id User ID (nếu đăng nhập)
 * @param string $guest_email Email khách (nếu không đăng nhập)
 * @param string $guest_phone SĐT khách
 * @return array ['allowed' => bool, 'message' => string, 'pending_bookings' => array]
 */
function checkBookingSpam($user_id = null, $guest_email = null, $guest_phone = null) {
    try {
        $db = getDB();
        $blocked_statuses = ['pending', 'confirmed', 'checked_in'];
        $where_conditions = [];
        $params = [];
        
        if ($user_id) {
            $where_conditions[] = "user_id = ?";
            $params[] = $user_id;
        } else {
            $sub_conditions = [];
            
            // Xử lý Email (có thể là chuỗi hoặc mảng)
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
            
            // Xử lý Phone (có thể là chuỗi hoặc mảng)
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
        $where_conditions[] = "(check_in_date >= CURDATE() OR status IN ('pending', 'checked_in'))";
        
        $where_sql = implode(' AND ', $where_conditions);
        
        $stmt = $db->prepare("
            SELECT booking_id, booking_code, status, payment_status, check_in_date, check_out_date, 
                   total_amount, guest_name, guest_email, guest_phone, created_at,
                   TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_since_creation
            FROM bookings
            WHERE $where_sql
            ORDER BY created_at DESC
        ");
        $stmt->execute($params);
        $pending_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($pending_bookings)) {
            return ['allowed' => true, 'message' => '', 'pending_bookings' => []];
        }
        
        // Auto-cancel unpaid
        $has_active = false;
        foreach ($pending_bookings as $booking) {
            if ($booking['payment_status'] === 'unpaid' && $booking['minutes_since_creation'] > 30) {
                autoCancelUnpaidBooking($booking['booking_id']);
            } else {
                $has_active = true;
            }
        }
        
        if (!$has_active) {
            return ['allowed' => true, 'message' => '', 'pending_bookings' => []];
        }

        // Re-fetch
        $stmt->execute($params);
        $pending_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($pending_bookings)) {
            return ['allowed' => true, 'message' => '', 'pending_bookings' => []];
        }

        $count = count($pending_bookings);
        $status_labels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đang ở'
        ];
        
        $details = [];
        foreach ($pending_bookings as $b) {
            $lbl = $status_labels[$b['status']] ?? $b['status'];
            $details[] = "Mã {$b['booking_code']} ($lbl)";
        }
        
        return [
            'allowed' => false,
            'message' => "Bạn đang có $count đặt phòng chưa hoàn tất: " . implode(', ', $details) . ". Vui lòng hoàn tất thanh toán và trả phòng trước khi đặt phòng mới.",
            'pending_bookings' => $pending_bookings
        ];
        
    } catch (Exception $e) {
        error_log("Booking spam check error: " . $e->getMessage());
        return ['allowed' => true, 'message' => '', 'pending_bookings' => []];
    }
}

/**
 * Kiểm tra trùng lặp đặt phòng
 */
function checkBookingOverlap($user_id = null, $email = null, $phone = null, $new_check_in, $new_check_out) {
    try {
        $db = getDB();
        $pending_statuses = ['pending', 'confirmed', 'checked_in'];
        $where_conditions = [];
        $params = [];
        
        if ($user_id) {
            $where_conditions[] = "(user_id = ? OR guest_email = ? OR guest_phone = ?)";
            $params[] = $user_id; $params[] = $email; $params[] = $phone;
        } else {
            $where_conditions[] = "(guest_email = ? OR guest_phone = ?)";
            $params[] = $email; $params[] = $phone;
        }
        
        $placeholders = implode(',', array_fill(0, count($pending_statuses), '?'));
        $where_conditions[] = "status IN ($placeholders)";
        $params = array_merge($params, $pending_statuses);
        $where_conditions[] = "check_in_date < ? AND check_out_date > ?";
        $params[] = $new_check_out; $params[] = $new_check_in;
        
        $where_sql = implode(' AND ', $where_conditions);
        $stmt = $db->prepare("SELECT booking_id, booking_code, check_in_date, check_out_date FROM bookings WHERE $where_sql");
        $stmt->execute($params);
        $overlapping = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($overlapping)) {
            return [
                'allowed' => false,
                'message' => "Bạn đã có đặt phòng trùng khoảng thời gian này.",
                'overlapping_bookings' => $overlapping
            ];
        }
        return ['allowed' => true, 'message' => '', 'overlapping_bookings' => []];
    } catch (Exception $e) {
        return ['allowed' => true, 'message' => '', 'overlapping_bookings' => []];
    }
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
