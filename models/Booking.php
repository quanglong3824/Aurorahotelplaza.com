<?php
/**
 * Booking Model - Business Logic Layer
 */

class Booking
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Check room availability
     */
    public function checkAvailability($room_type_id, $check_in, $check_out)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as available_rooms
            FROM rooms r
            WHERE r.room_type_id = ? 
            AND r.status = 'available'
            AND r.room_id NOT IN (
                SELECT room_id 
                FROM bookings 
                WHERE room_id IS NOT NULL
                AND status NOT IN ('cancelled', 'checked_out')
                AND (
                    (check_in_date <= ? AND check_out_date > ?)
                    OR (check_in_date < ? AND check_out_date >= ?)
                    OR (check_in_date >= ? AND check_out_date <= ?)
                )
            )
        ");

        $stmt->execute([
            $room_type_id,
            $check_in, $check_in,
            $check_out, $check_out,
            $check_in, $check_out
        ]);

        $result = $stmt->fetch();
        return $result['available_rooms'] > 0;
    }

    /**
     * Get available room
     */
    public function getAvailableRoom($room_type_id, $check_in, $check_out)
    {
        $stmt = $this->db->prepare("
            SELECT r.room_id 
            FROM rooms r
            WHERE r.room_type_id = ? 
            AND r.status = 'available'
            AND r.room_id NOT IN (
                SELECT room_id 
                FROM bookings 
                WHERE room_id IS NOT NULL
                AND status NOT IN ('cancelled', 'checked_out')
                AND (
                    (check_in_date <= ? AND check_out_date > ?)
                    OR (check_in_date < ? AND check_out_date >= ?)
                    OR (check_in_date >= ? AND check_out_date <= ?)
                )
            )
            LIMIT 1
        ");

        $stmt->execute([
            $room_type_id,
            $check_in, $check_in,
            $check_out, $check_out,
            $check_in, $check_out
        ]);

        return $stmt->fetch();
    }

    /**
     * Create new booking
     */
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO bookings (
                booking_code, booking_type, user_id, room_id, room_type_id,
                check_in_date, check_out_date, num_adults, num_children, total_nights,
                room_price, extra_guest_fee, extra_bed_fee, extra_beds, total_amount,
                guest_name, guest_email, guest_phone, special_requests,
                occupancy_type, price_type_used,
                status, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['booking_code'],
            $data['booking_type'] ?? 'instant',
            $data['user_id'],
            $data['room_id'],
            $data['room_type_id'],
            $data['check_in_date'],
            $data['check_out_date'],
            $data['num_adults'] ?? $data['num_guests'] ?? 1,
            $data['num_children'] ?? 0,
            $data['total_nights'] ?? $data['num_nights'],
            $data['room_price'],
            $data['extra_guest_fee'] ?? 0,
            $data['extra_bed_fee'] ?? 0,
            $data['extra_beds'] ?? 0,
            $data['total_amount'],
            $data['guest_name'],
            $data['guest_email'],
            $data['guest_phone'],
            $data['special_requests'] ?? null,
            $data['occupancy_type'] ?? 'double',
            $data['price_type_used'] ?? 'double',
            $data['status'] ?? 'pending',
            $data['payment_status'] ?? 'unpaid'
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Get booking by code with full details
     */
    public function getByCode($booking_code)
    {
        $stmt = $this->db->prepare("
            SELECT b.*, 
                   b.extra_guest_fee, b.extra_bed_fee, b.extra_beds,
                   b.occupancy_type, b.price_type_used,
                   rt.type_name, rt.type_name_en, rt.category, rt.description, rt.amenities, rt.thumbnail, rt.bed_type, rt.size_sqm,
                   r.room_number, r.floor, r.building,
                   p.payment_method, p.transaction_id, p.paid_at, p.status as payment_status_payment, p.amount as paid_amount,
                   u.full_name as user_name, u.email as user_email
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            LEFT JOIN users u ON b.user_id = u.user_id
            WHERE b.booking_code = ?
        ");
        $stmt->execute([$booking_code]);
        return $stmt->fetch();
    }

    /**
     * Get booking by ID
     */
    public function getById($booking_id)
    {
        $stmt = $this->db->prepare("
            SELECT b.*, 
                   b.extra_guest_fee, b.extra_bed_fee, b.extra_beds,
                   b.occupancy_type, b.price_type_used,
                   rt.type_name, rt.type_name_en, rt.category, rt.description, rt.amenities, rt.thumbnail, rt.bed_type, rt.size_sqm,
                   r.room_number, r.floor, r.building,
                   p.payment_method, p.transaction_id, p.paid_at, p.status as payment_status_payment, p.amount as paid_amount
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$booking_id]);
        return $stmt->fetch();
    }

    /**
     * Update booking status
     */
    public function updateStatus($booking_id, $status)
    {
        $stmt = $this->db->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        return $stmt->execute([$status, $booking_id]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($booking_id, $payment_status, $paid_amount = null)
    {
        if ($paid_amount !== null) {
            $stmt = $this->db->prepare("
                UPDATE bookings 
                SET payment_status = ? 
                WHERE booking_id = ?
            ");
            return $stmt->execute([$payment_status, $booking_id]);
        } else {
            $stmt = $this->db->prepare("UPDATE bookings SET payment_status = ? WHERE booking_id = ?");
            return $stmt->execute([$payment_status, $booking_id]);
        }
    }

    /**
     * Get user bookings with filters and pagination
     * Đọc đầy đủ dữ liệu booking bao gồm phụ thu, giường phụ, loại giá
     */
    public function getUserBookings($user_id, $filters = [], $page = 1, $per_page = 10)
    {
        $where_conditions = ['b.user_id = ?'];
        $params = [$user_id];

        // Apply filters
        if (!empty($filters['status'])) {
            $where_conditions[] = 'b.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['payment_status'])) {
            $where_conditions[] = '(p.status = ? OR (p.status IS NULL AND b.payment_status = ?))';
            $params[] = $filters['payment_status'];
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'b.check_in_date >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'b.check_in_date <= ?';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            require_once __DIR__ . '/../helpers/booking-helper.php';
            $possible_codes = \BookingHelper::parseSmartCode($filters['search']);

            $search_conditions = [];
            foreach ($possible_codes as $code) {
                if (strpos($code, '%') !== false) {
                    $search_conditions[] = 'b.booking_code LIKE ?';
                } else {
                    $search_conditions[] = 'b.booking_code = ?';
                }
                $params[] = $code;
            }

            $search_param = '%' . $filters['search'] . '%';
            $search_conditions[] = 'b.guest_name LIKE ?';
            $params[] = $search_param;
            $search_conditions[] = 'rt.type_name LIKE ?';
            $params[] = $search_param;

            $where_conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        // Get total count
        $count_sql = "
            SELECT COUNT(*) as total
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            $where_clause
        ";
        $stmt = $this->db->prepare($count_sql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Get bookings - đọc đầy đủ các trường
        $offset = (int) (($page - 1) * $per_page);
        $per_page = (int) $per_page;

        $sql = "
            SELECT 
                b.booking_id, b.booking_code, b.booking_type, b.user_id, b.room_type_id, b.room_id,
                b.check_in_date, b.check_out_date, b.num_adults, b.num_children, b.num_rooms,
                b.total_nights, b.room_price, b.service_charges, b.discount_amount, b.points_used,
                b.total_amount, b.special_requests, b.inquiry_message, b.duration_type,
                b.guest_name, b.guest_email, b.guest_phone, b.guest_id_number,
                b.status, b.payment_status AS booking_payment_status, b.qr_code, b.confirmation_sent,
                b.checked_in_at, b.checked_out_at, b.cancelled_at, b.cancellation_reason,
                b.created_at, b.updated_at,
                b.occupancy_type, b.extra_guest_fee, b.extra_bed_fee, b.extra_beds,
                b.short_stay_hours, b.expected_checkin_time, b.expected_checkout_time, b.price_type_used,
                rt.type_name, rt.type_name_en, rt.category, rt.thumbnail, rt.bed_type, rt.size_sqm,
                r.room_number, r.floor, r.building,
                COALESCE(p.status, b.payment_status) as payment_status, 
                p.payment_method, p.paid_at, p.amount as paid_amount, p.transaction_id
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            $where_clause
            ORDER BY b.created_at DESC
            LIMIT $per_page OFFSET $offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();

        return [
            'bookings' => $bookings,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }

    /**
     * Get user booking statistics
     */
    public function getUserStatistics($user_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
                COUNT(CASE WHEN status = 'checked_in' THEN 1 END) as checked_in_bookings,
                COUNT(CASE WHEN status = 'checked_out' THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
                COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_bookings,
                SUM(CASE WHEN status IN ('confirmed', 'checked_in', 'checked_out') THEN total_amount ELSE 0 END) as total_spent,
                SUM(CASE WHEN status IN ('confirmed', 'checked_in', 'checked_out') THEN total_nights ELSE 0 END) as total_nights
            FROM bookings 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    /**
     * Generate booking code
     */
    public static function generateBookingCode()
    {
        return 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Calculate nights between dates
     */
    public static function calculateNights($check_in, $check_out)
    {
        $date1 = new DateTime($check_in);
        $date2 = new DateTime($check_out);
        $interval = $date1->diff($date2);
        return $interval->days;
    }

    /**
     * Cancel booking
     */
    public function cancelBooking($booking_id, $user_id, $reason = null)
    {
        $booking = $this->getById($booking_id);

        if (!$booking) {
            return ['success' => false, 'message' => 'Không tìm thấy đặt phòng'];
        }

        if ($booking['user_id'] != $user_id) {
            return ['success' => false, 'message' => 'Bạn không có quyền hủy đặt phòng này'];
        }

        if (!in_array($booking['status'], ['pending', 'confirmed'])) {
            return ['success' => false, 'message' => 'Không thể hủy đặt phòng ở trạng thái hiện tại'];
        }

        $check_in = new DateTime($booking['check_in_date']);
        $now = new DateTime();
        $hours_until_checkin = ($check_in->getTimestamp() - $now->getTimestamp()) / 3600;

        if ($hours_until_checkin < 24) {
            return ['success' => false, 'message' => 'Không thể hủy đặt phòng trong vòng 24 giờ trước khi nhận phòng'];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE bookings 
                SET status = 'cancelled', 
                    cancelled_at = NOW(), 
                    cancelled_by = ?,
                    cancellation_reason = ?
                WHERE booking_id = ?
            ");
            $stmt->execute([$user_id, $reason, $booking_id]);

            $this->addHistory($booking_id, $booking['status'], 'cancelled', $user_id, $reason);

            if ($booking['payment_status'] === 'paid') {
                $stmt = $this->db->prepare("
                    UPDATE payments 
                    SET status = 'refunded', refunded_at = NOW()
                    WHERE booking_id = ?
                ");
                $stmt->execute([$booking_id]);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã hủy đặt phòng thành công'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Cancel booking error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi hủy đặt phòng'];
        }
    }

    /**
     * Add booking history record
     */
    public function addHistory($booking_id, $old_status, $new_status, $changed_by = null, $notes = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$booking_id, $old_status, $new_status, $changed_by, $notes]);
    }

    /**
     * Get booking history
     */
    public function getHistory($booking_id)
    {
        $stmt = $this->db->prepare("
            SELECT bh.*, u.full_name as changed_by_name
            FROM booking_history bh
            LEFT JOIN users u ON bh.changed_by = u.user_id
            WHERE bh.booking_id = ?
            ORDER BY bh.created_at ASC
        ");
        $stmt->execute([$booking_id]);
        return $stmt->fetchAll();
    }

    /**
     * Check if booking can be cancelled
     */
    public function canBeCancelled($booking_id)
    {
        $booking = $this->getById($booking_id);

        if (!$booking) {
            return false;
        }

        if (!in_array($booking['status'], ['pending', 'confirmed'])) {
            return false;
        }

        $check_in = new DateTime($booking['check_in_date']);
        $now = new DateTime();
        $hours_until_checkin = ($check_in->getTimestamp() - $now->getTimestamp()) / 3600;

        return $hours_until_checkin >= 24;
    }

    /**
     * Export bookings to array for CSV/Excel
     */
    public function exportUserBookings($user_id, $filters = [])
    {
        $where_conditions = ['b.user_id = ?'];
        $params = [$user_id];

        if (!empty($filters['status'])) {
            $where_conditions[] = 'b.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'b.check_in_date >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'b.check_in_date <= ?';
            $params[] = $filters['date_to'];
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        $stmt = $this->db->prepare("
            SELECT 
                b.booking_code,
                b.created_at as booking_date,
                rt.type_name as room_type,
                b.check_in_date,
                b.check_out_date,
                b.total_nights,
                b.num_adults,
                b.num_children,
                b.room_price,
                b.extra_guest_fee,
                b.extra_bed_fee,
                b.total_amount,
                b.status,
                b.occupancy_type,
                b.price_type_used,
                COALESCE(p.status, b.payment_status) as payment_status,
                p.payment_method,
                b.guest_name,
                b.guest_phone,
                b.guest_email
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            $where_clause
            ORDER BY b.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
