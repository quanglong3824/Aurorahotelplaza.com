<?php
/**
 * Booking Model - Business Logic Layer
 */

class Booking {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Check room availability
     */
    public function checkAvailability($room_type_id, $check_in, $check_out) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as available_rooms
            FROM rooms r
            WHERE r.room_type_id = ? 
            AND r.status = 'available'
            AND r.id NOT IN (
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
    public function getAvailableRoom($room_type_id, $check_in, $check_out) {
        $stmt = $this->db->prepare("
            SELECT r.id 
            FROM rooms r
            WHERE r.room_type_id = ? 
            AND r.status = 'available'
            AND r.id NOT IN (
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
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO bookings (
                booking_code, user_id, room_id, room_type_id,
                check_in_date, check_out_date, num_guests, num_nights,
                room_price, total_amount,
                guest_name, guest_email, guest_phone, special_requests,
                status, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['booking_code'],
            $data['user_id'],
            $data['room_id'],
            $data['room_type_id'],
            $data['check_in_date'],
            $data['check_out_date'],
            $data['num_guests'],
            $data['num_nights'],
            $data['room_price'],
            $data['total_amount'],
            $data['guest_name'],
            $data['guest_email'],
            $data['guest_phone'],
            $data['special_requests'],
            $data['status'] ?? 'pending',
            $data['payment_status'] ?? 'unpaid'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get booking by code
     */
    public function getByCode($booking_code) {
        $stmt = $this->db->prepare("
            SELECT b.*, rt.name as room_type_name, r.room_number
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.id
            LEFT JOIN rooms r ON b.room_id = r.id
            WHERE b.booking_code = ?
        ");
        $stmt->execute([$booking_code]);
        return $stmt->fetch();
    }
    
    /**
     * Update booking status
     */
    public function updateStatus($booking_id, $status) {
        $stmt = $this->db->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $booking_id]);
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($booking_id, $payment_status, $paid_amount = null) {
        if ($paid_amount !== null) {
            $stmt = $this->db->prepare("
                UPDATE bookings 
                SET payment_status = ?, paid_amount = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$payment_status, $paid_amount, $booking_id]);
        } else {
            $stmt = $this->db->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
            return $stmt->execute([$payment_status, $booking_id]);
        }
    }
    
    /**
     * Get user bookings
     */
    public function getUserBookings($user_id, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT b.*, rt.name as room_type_name, r.room_number
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.id
            LEFT JOIN rooms r ON b.room_id = r.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Generate booking code
     */
    public static function generateBookingCode() {
        return 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    }
    
    /**
     * Calculate nights between dates
     */
    public static function calculateNights($check_in, $check_out) {
        $date1 = new DateTime($check_in);
        $date2 = new DateTime($check_out);
        $interval = $date1->diff($date2);
        return $interval->days;
    }
}
?>
