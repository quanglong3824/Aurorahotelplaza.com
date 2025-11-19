<?php
/**
 * Activity Logger Helper
 * Tự động log mọi thao tác của user và admin
 */

class ActivityLogger {
    private static $db = null;
    
    /**
     * Initialize database connection
     */
    private static function getDB() {
        if (self::$db === null) {
            require_once __DIR__ . '/../config/database.php';
            self::$db = getDB();
        }
        return self::$db;
    }
    
    /**
     * Log any activity
     * 
     * @param int $user_id User ID (null for guest)
     * @param string $action Action name
     * @param string $entity_type Entity type (booking, user, payment, etc.)
     * @param int $entity_id Entity ID
     * @param string $description Description
     * @param array $metadata Additional metadata (optional)
     */
    public static function log($user_id, $action, $entity_type, $entity_id, $description, $metadata = []) {
        try {
            $db = self::getDB();
            
            require_once __DIR__ . '/security.php';
            
            $stmt = $db->prepare("
                INSERT INTO activity_logs (
                    user_id, action, entity_type, entity_id,
                    description, ip_address, user_agent, created_at
                ) VALUES (
                    :user_id, :action, :entity_type, :entity_id,
                    :description, :ip_address, :user_agent, NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $user_id,
                ':action' => $action,
                ':entity_type' => $entity_type,
                ':entity_id' => $entity_id,
                ':description' => $description,
                ':ip_address' => Security::getClientIP(),
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log user login
     */
    public static function logLogin($user_id, $email, $success = true) {
        $action = $success ? 'login_success' : 'login_failed';
        $description = $success ? 
            "Đăng nhập thành công: {$email}" : 
            "Đăng nhập thất bại: {$email}";
            
        return self::log($user_id, $action, 'user', $user_id, $description);
    }
    
    /**
     * Log user logout
     */
    public static function logLogout($user_id, $email) {
        return self::log($user_id, 'logout', 'user', $user_id, "Đăng xuất: {$email}");
    }
    
    /**
     * Log booking creation
     */
    public static function logBookingCreate($user_id, $booking_id, $booking_code, $total_amount) {
        $description = sprintf(
            "Tạo đơn đặt phòng %s. Tổng tiền: %s VND",
            $booking_code,
            number_format($total_amount)
        );
        return self::log($user_id, 'create_booking', 'booking', $booking_id, $description);
    }
    
    /**
     * Log booking status change
     */
    public static function logBookingStatusChange($user_id, $booking_id, $booking_code, $old_status, $new_status) {
        $description = sprintf(
            "Thay đổi trạng thái đơn %s: %s → %s",
            $booking_code,
            $old_status,
            $new_status
        );
        return self::log($user_id, 'update_booking_status', 'booking', $booking_id, $description);
    }
    
    /**
     * Log booking cancellation
     */
    public static function logBookingCancel($user_id, $booking_id, $booking_code, $reason) {
        $description = sprintf(
            "Hủy đơn %s. Lý do: %s",
            $booking_code,
            $reason
        );
        return self::log($user_id, 'cancel_booking', 'booking', $booking_id, $description);
    }
    
    /**
     * Log payment confirmation
     */
    public static function logPaymentConfirm($user_id, $booking_id, $booking_code, $amount, $points_earned) {
        $description = sprintf(
            "Xác nhận thanh toán đơn %s: %s VND. Cộng %s điểm",
            $booking_code,
            number_format($amount),
            number_format($points_earned)
        );
        return self::log($user_id, 'confirm_payment', 'booking', $booking_id, $description);
    }
    
    /**
     * Log points adjustment
     */
    public static function logPointsAdjust($admin_id, $user_id, $points, $type, $reason) {
        $description = sprintf(
            "%s %s điểm cho user ID %s. Lý do: %s",
            $type === 'add' ? 'Cộng' : 'Trừ',
            number_format($points),
            $user_id,
            $reason
        );
        return self::log($admin_id, 'adjust_points', 'user', $user_id, $description);
    }
    
    /**
     * Log profile update
     */
    public static function logProfileUpdate($user_id, $changes) {
        $description = "Cập nhật thông tin cá nhân: " . implode(', ', $changes);
        return self::log($user_id, 'update_profile', 'user', $user_id, $description);
    }
    
    /**
     * Log password change
     */
    public static function logPasswordChange($user_id) {
        return self::log($user_id, 'change_password', 'user', $user_id, "Đổi mật khẩu");
    }
    
    /**
     * Log review submission
     */
    public static function logReviewSubmit($user_id, $review_id, $booking_code, $rating) {
        $description = sprintf(
            "Đánh giá đơn %s: %s sao",
            $booking_code,
            $rating
        );
        return self::log($user_id, 'submit_review', 'review', $review_id, $description);
    }
    
    /**
     * Log promotion usage
     */
    public static function logPromotionUse($user_id, $booking_id, $promo_code, $discount) {
        $description = sprintf(
            "Sử dụng mã %s cho đơn. Giảm: %s VND",
            $promo_code,
            number_format($discount)
        );
        return self::log($user_id, 'use_promotion', 'booking', $booking_id, $description);
    }
    
    /**
     * Log room assignment
     */
    public static function logRoomAssign($admin_id, $booking_id, $booking_code, $room_number) {
        $description = sprintf(
            "Phân phòng %s cho đơn %s",
            $room_number,
            $booking_code
        );
        return self::log($admin_id, 'assign_room', 'booking', $booking_id, $description);
    }
    
    /**
     * Log check-in
     */
    public static function logCheckIn($admin_id, $booking_id, $booking_code, $guest_name) {
        $description = sprintf(
            "Check-in đơn %s - Khách: %s",
            $booking_code,
            $guest_name
        );
        return self::log($admin_id, 'checkin', 'booking', $booking_id, $description);
    }
    
    /**
     * Log check-out
     */
    public static function logCheckOut($admin_id, $booking_id, $booking_code, $guest_name) {
        $description = sprintf(
            "Check-out đơn %s - Khách: %s",
            $booking_code,
            $guest_name
        );
        return self::log($admin_id, 'checkout', 'booking', $booking_id, $description);
    }
    
    /**
     * Log service booking
     */
    public static function logServiceBooking($user_id, $service_id, $service_name, $quantity) {
        $description = sprintf(
            "Đặt dịch vụ: %s (x%s)",
            $service_name,
            $quantity
        );
        return self::log($user_id, 'book_service', 'service', $service_id, $description);
    }
    
    /**
     * Log admin CRUD operations
     */
    public static function logAdminCRUD($admin_id, $action, $entity_type, $entity_id, $entity_name) {
        $actions = [
            'create' => 'Tạo mới',
            'update' => 'Cập nhật',
            'delete' => 'Xóa'
        ];
        
        $description = sprintf(
            "%s %s: %s (ID: %s)",
            $actions[$action] ?? $action,
            $entity_type,
            $entity_name,
            $entity_id
        );
        
        return self::log($admin_id, "{$action}_{$entity_type}", $entity_type, $entity_id, $description);
    }
    
    /**
     * Get recent activities
     * 
     * @param int $limit Number of records
     * @param int $user_id Filter by user (optional)
     * @param string $entity_type Filter by entity type (optional)
     * @return array Activities
     */
    public static function getRecentActivities($limit = 50, $user_id = null, $entity_type = null) {
        try {
            $db = self::getDB();
            
            $where = [];
            $params = [];
            
            if ($user_id) {
                $where[] = "al.user_id = :user_id";
                $params[':user_id'] = $user_id;
            }
            
            if ($entity_type) {
                $where[] = "al.entity_type = :entity_type";
                $params[':entity_type'] = $entity_type;
            }
            
            $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $stmt = $db->prepare("
                SELECT 
                    al.*,
                    u.full_name,
                    u.email,
                    u.user_role
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.user_id
                $where_sql
                ORDER BY al.created_at DESC
                LIMIT :limit
            ");
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get activities error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activity statistics
     */
    public static function getStatistics($days = 7) {
        try {
            $db = self::getDB();
            
            $stmt = $db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(CASE WHEN action LIKE '%booking%' THEN 1 END) as booking_actions,
                    COUNT(CASE WHEN action LIKE '%payment%' THEN 1 END) as payment_actions,
                    COUNT(CASE WHEN action LIKE '%login%' THEN 1 END) as login_actions
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([':days' => $days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get statistics error: " . $e->getMessage());
            return [];
        }
    }
}
