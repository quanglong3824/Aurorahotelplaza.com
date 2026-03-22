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
 * - confirmed (đã xác nhận) - MỚI: Cho phép đặt tiếp sau khi xác nhận
 * 
 * @param int|null $user_id User ID (nếu đăng nhập)
 * @param string $guest_email Email khách (nếu không đăng nhập)
 * @param string $guest_phone SĐT khách
 * @return array ['allowed' => bool, 'message' => string, 'pending_bookings' => array]
 */
function checkBookingSpam($user_id = null, $guest_email = null, $guest_phone = null) {
    try {
        $db = getDB();
        
        // Debug logging
        error_log("=== BOOKING SPAM CHECK ===");
        error_log("User ID: " . ($user_id ?? 'null'));
        error_log("Guest Email: " . ($guest_email ?? 'null'));
        error_log("Guest Phone: " . ($guest_phone ?? 'null'));
        
        // Các trạng thái booking CHƯA HOÀN TẤT (không được đặt tiếp)
        // MỚI: 'confirmed' đã cho phép đặt tiếp (không cần đợi checkout)
        $blocked_statuses = ['pending', 'checked_in'];
        
        $where_conditions = [];
        $params = [];
        
        // Build WHERE clause based on available info
        if ($user_id) {
            // User đã đăng ký: check theo user_id
            $where_conditions[] = "user_id = ?";
            $params[] = $user_id;
            error_log("Checking user_id: $user_id");
        } elseif ($guest_email || $guest_phone) {
            // Guest: check theo email hoặc phone
            $where_conditions[] = "(guest_email = ? OR guest_phone = ?)";
            $params[] = $guest_email;
            $params[] = $guest_phone;
            error_log("Checking guest_email: $guest_email, guest_phone: $guest_phone");
        } else {
            // No identifier provided
            error_log("No user_id, email, or phone provided - allowing booking");
            return [
                'allowed' => true,
                'message' => '',
                'pending_bookings' => []
            ];
        }
        
        // Only check bookings that are NOT completed/cancelled/confirmed
        $placeholders = implode(',', array_fill(0, count($blocked_statuses), '?'));
        $where_conditions[] = "status IN ($placeholders)";
        $params = array_merge($params, $blocked_statuses);
        
        // Only check future bookings or current active ones
        $where_conditions[] = "(check_in_date >= CURDATE() OR status IN ('pending', 'checked_in'))";
        
        $where_sql = implode(' AND ', $where_conditions);
        
        error_log("SQL WHERE: $where_sql");
        error_log("SQL Params: " . implode(', ', $params));
        
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
        
        error_log("Found " . count($pending_bookings) . " pending bookings");
        foreach ($pending_bookings as $pb) {
            error_log("Booking: {$pb['booking_code']} - Status: {$pb['status']} - Payment: {$pb['payment_status']} - Minutes: {$pb['minutes_since_creation']}");
        }
        
        // If no pending bookings, allow new booking
        if (empty($pending_bookings)) {
            return [
                'allowed' => true,
                'message' => '',
                'pending_bookings' => []
            ];
        }
        
        // ========== AUTO-CANCEL: Cancel unpaid bookings after 30 minutes ==========
        $has_active_bookings = false;
        $bookings_to_cancel = [];
        
        foreach ($pending_bookings as $booking) {
            // Auto-cancel unpaid bookings older than 30 minutes
            if ($booking['payment_status'] === 'unpaid' && $booking['minutes_since_creation'] > 30) {
                $bookings_to_cancel[] = $booking['booking_id'];
            } else {
                $has_active_bookings = true;
            }
        }
        
        // Perform auto-cancellation
        if (!empty($bookings_to_cancel)) {
            foreach ($bookings_to_cancel as $booking_id) {
                autoCancelUnpaidBooking($booking_id);
            }
            
            // Refresh the list after auto-cancel
            $stmt->execute($params);
            $pending_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If all bookings were cancelled, allow new booking
            if (empty($pending_bookings)) {
                return [
                    'allowed' => true,
                    'message' => '',
                    'pending_bookings' => []
                ];
            }
        }
        // ========== END AUTO-CANCEL ==========
        
        // Block completely - user has incomplete bookings
        $count = count($pending_bookings);
        $status_labels = [
            'pending' => 'Chờ xác nhận',
            'checked_in' => 'Đang ở'
        ];
        
        $booking_details = [];
        foreach ($pending_bookings as $booking) {
            $status_label = $status_labels[$booking['status']] ?? $booking['status'];
            $booking_details[] = "Mã {$booking['booking_code']} ($status_label)";
        }
        
        return [
            'allowed' => false,
            'message' => "Bạn đang có $count đặt phòng chưa hoàn tất: " . implode(', ', $booking_details) . 
                         ". Vui lòng hoàn tất thanh toán và trả phòng trước khi đặt phòng mới.",
            'pending_bookings' => $pending_bookings
        ];
        
    } catch (Exception $e) {
        error_log("Booking spam check error: " . $e->getMessage());
        // If error occurs, allow booking (don't block legitimate users)
        return [
            'allowed' => true,
            'message' => '',
            'pending_bookings' => []
        ];
    }
}

/**
 * Kiểm tra trùng lặp đặt phòng (overlap detection)
 * Kiểm tra xem khoảng thời gian đặt có trùng với booking hiện có không
 * 
 * @param int $user_id User ID
 * @param string $email Email
 * @param string $phone Phone
 * @param string $new_check_in Check-in date mới
 * @param string $new_check_out Check-out date mới
 * @return array ['allowed' => bool, 'message' => string, 'overlapping_bookings' => array]
 */
function checkBookingOverlap($user_id = null, $email = null, $phone = null, $new_check_in, $new_check_out) {
    try {
        $db = getDB();
        
        $pending_statuses = ['pending', 'confirmed', 'checked_in'];
        
        $where_conditions = [];
        $params = [];
        
        // Identify user
        if ($user_id) {
            $where_conditions[] = "(user_id = ? OR guest_email = ? OR guest_phone = ?)";
            $params[] = $user_id;
            $params[] = $email;
            $params[] = $phone;
        } else {
            $where_conditions[] = "(guest_email = ? OR guest_phone = ?)";
            $params[] = $email;
            $params[] = $phone;
        }
        
        // Only check active bookings
        $placeholders = implode(',', array_fill(0, count($pending_statuses), '?'));
        $where_conditions[] = "status IN ($placeholders)";
        $params = array_merge($params, $pending_statuses);
        
        // Check for date overlap
        // Overlap occurs when: new_check_in < existing_check_out AND new_check_out > existing_check_in
        $where_conditions[] = "check_in_date < ? AND check_out_date > ?";
        $params[] = $new_check_out;
        $params[] = $new_check_in;
        
        $where_sql = implode(' AND ', $where_conditions);
        
        $stmt = $db->prepare("
            SELECT booking_id, booking_code, check_in_date, check_out_date, status, guest_name
            FROM bookings
            WHERE $where_sql
        ");
        $stmt->execute($params);
        $overlapping_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($overlapping_bookings)) {
            $dates = [];
            foreach ($overlapping_bookings as $booking) {
                $dates[] = "Mã {$booking['booking_code']}: {$booking['check_in_date']} đến {$booking['check_out_date']}";
            }
            
            return [
                'allowed' => false,
                'message' => "Bạn đã có đặt phòng trùng khoảng thời gian này: " . implode(', ', $dates),
                'overlapping_bookings' => $overlapping_bookings
            ];
        }
        
        return [
            'allowed' => true,
            'message' => '',
            'overlapping_bookings' => []
        ];
        
    } catch (Exception $e) {
        error_log("Booking overlap check error: " . $e->getMessage());
        return [
            'allowed' => true,
            'message' => '',
            'overlapping_bookings' => []
        ];
    }
}

/**
 * Tự động hủy booking chưa thanh toán sau 30 phút
 * 
 * @param int $booking_id Booking ID
 * @return bool Success
 */
function autoCancelUnpaidBooking($booking_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            UPDATE bookings 
            SET status = 'cancelled', 
                payment_status = 'unpaid',
                cancellation_reason = 'Auto-cancelled: Unpaid after 30 minutes',
                updated_at = NOW()
            WHERE booking_id = ? AND payment_status = 'unpaid'
        ");
        $stmt->execute([$booking_id]);
        
        error_log("Auto-cancelled unpaid booking: $booking_id");
        
        return true;
    } catch (Exception $e) {
        error_log("Auto-cancel booking error: " . $e->getMessage());
        return false;
    }
}

/**
 * Kiểm tra rate limiting (chống spam request)
 * Sử dụng file-based rate limiting đơn giản
 * 
 * @param string $identifier User identifier (email/phone/IP)
 * @param int $max_requests Số request tối đa
 * @param int $time_window Thời gian (giây)
 * @return array ['allowed' => bool, 'message' => string, 'retry_after' => int]
 */
function checkRateLimit($identifier, $max_requests = 5, $time_window = 60) {
    $rate_limit_file = __DIR__ . '/../config/booking_rate_limits.json';
    
    // Load existing limits
    $limits = [];
    if (file_exists($rate_limit_file)) {
        $content = file_get_contents($rate_limit_file);
        $limits = json_decode($content, true) ?: [];
    }
    
    $now = time();
    
    // Clean old entries
    foreach ($limits as $key => $data) {
        if ($data['reset_time'] < $now) {
            unset($limits[$key]);
        }
    }
    
    // Check current identifier
    if (!isset($limits[$identifier])) {
        $limits[$identifier] = [
            'count' => 1,
            'reset_time' => $now + $time_window
        ];
        saveRateLimits($rate_limit_file, $limits);
        return ['allowed' => true, 'message' => '', 'retry_after' => 0];
    }
    
    $limits[$identifier]['count']++;
    
    if ($limits[$identifier]['count'] > $max_requests) {
        saveRateLimits($rate_limit_file, $limits);
        $retry_after = $limits[$identifier]['reset_time'] - $now;
        return [
            'allowed' => false,
            'message' => "Bạn đã thực hiện quá nhiều yêu cầu. Vui lòng thử lại sau $retry_after giây.",
            'retry_after' => $retry_after
        ];
    }
    
    saveRateLimits($rate_limit_file, $limits);
    return ['allowed' => true, 'message' => '', 'retry_after' => 0];
}

/**
 * Save rate limits to file
 */
function saveRateLimits($file, $limits) {
    try {
        file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));
    } catch (Exception $e) {
        error_log("Cannot save rate limits: " . $e->getMessage());
    }
}

/**
 * Generate rate limit identifier from request
 */
function getRateLimitIdentifier() {
    $identifiers = [];
    
    if (isset($_SESSION['user_id'])) {
        $identifiers[] = 'user_' . $_SESSION['user_id'];
    }
    
    if (isset($_POST['guest_email'])) {
        $identifiers[] = 'email_' . $_POST['guest_email'];
    }
    
    if (isset($_POST['guest_phone'])) {
        $identifiers[] = 'phone_' . $_POST['guest_phone'];
    }
    
    // Always include IP
    $identifiers[] = 'ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    return implode('|', $identifiers);
}
