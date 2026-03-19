<?php
namespace Aurora\Core\Repositories;

use PDO;

/**
 * BookingRepository - Đóng gói nghiệp vụ lưu trữ Đặt phòng
 */
class BookingRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Tạo mới một booking
     */
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO bookings (
                booking_code, user_id, room_id, room_type_id, 
                check_in_date, check_out_date, total_amount, 
                status, payment_status, payment_method, 
                guest_name, guest_phone, guest_email, special_requests
            ) VALUES (
                :booking_code, :user_id, :room_id, :room_type_id, 
                :check_in_date, :check_out_date, :total_amount, 
                :status, :payment_status, :payment_method, 
                :guest_name, :guest_phone, :guest_email, :special_requests
            )
        ");
        
        $stmt->execute([
            ':booking_code' => $data['booking_code'],
            ':user_id' => $data['user_id'] ?? null,
            ':room_id' => $data['room_id'] ?? null,
            ':room_type_id' => $data['room_type_id'],
            ':check_in_date' => $data['check_in_date'],
            ':check_out_date' => $data['check_out_date'],
            ':total_amount' => $data['total_amount'],
            ':status' => $data['status'] ?? 'pending',
            ':payment_status' => $data['payment_status'] ?? 'unpaid',
            ':payment_method' => $data['payment_method'] ?? 'cash',
            ':guest_name' => $data['guest_name'],
            ':guest_phone' => $data['guest_phone'],
            ':guest_email' => $data['guest_email'],
            ':special_requests' => $data['special_requests'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Cập nhật trạng thái thanh toán
     */
    public function updatePaymentStatus(string $bookingCode, string $status): bool {
        $stmt = $this->db->prepare("UPDATE bookings SET payment_status = ? WHERE booking_code = ?");
        return $stmt->execute([$status, $bookingCode]);
    }

    public function findByCode(string $bookingCode): ?array {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE booking_code = ?");
        $stmt->execute([$bookingCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Tìm booking với đầy đủ thông tin chi tiết (JOIN với users, room_types, rooms)
     */
    public function findWithDetails(int $bookingId): ?array {
        $stmt = $this->db->prepare("
            SELECT b.*, b.booking_type, b.inquiry_message, b.duration_type,
                   u.full_name as user_name, u.email as user_email, u.phone as user_phone,
                   rt.type_name, rt.category, rt.bed_type, rt.max_occupancy,
                   r.room_number, r.floor, r.building
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE b.booking_id = :booking_id
        ");
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Lấy lịch sử thay đổi của booking
     */
    public function getHistory(int $bookingId): array {
        $stmt = $this->db->prepare("
            SELECT bh.*, u.full_name as changed_by_name
            FROM booking_history bh
            LEFT JOIN users u ON bh.changed_by = u.user_id
            WHERE bh.booking_id = :booking_id
            ORDER BY bh.created_at DESC
        ");
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách thanh toán của booking
     */
    public function getPayments(int $bookingId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM payments
            WHERE booking_id = :booking_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách booking của user với filter và phân trang
     */
    public function getUserBookings(int $userId, array $filters = [], int $page = 1, int $perPage = 10): array {
        $where = ["b.user_id = ?"];
        $params = [$userId];

        if (!empty($filters['status'])) {
            $where[] = "b.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['payment_status'])) {
            $where[] = "b.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "b.check_in_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "b.check_in_date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $search = "%{$filters['search']}%";
            $where[] = "(b.booking_code LIKE ? OR rt.type_name LIKE ? OR b.guest_name LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = implode(" AND ", $where);

        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM bookings b 
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            WHERE $whereClause
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get bookings
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT b.*, rt.type_name, rt.category, rt.thumbnail, r.room_number, r.floor, r.building
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE $whereClause
            ORDER BY b.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'bookings' => $bookings,
            'total' => $total,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Lấy các booking gần đây của user
     */
    public function getRecentBookings(int $userId, int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT b.*, rt.type_name 
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            WHERE b.user_id = ? 
            ORDER BY b.created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thống kê booking của user
     */
    public function getUserStats(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total, 
                SUM(CASE WHEN status IN ('confirmed','checked_in') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'checked_out' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as spent
            FROM bookings 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Map to expected format if needed
        return [
            'total_bookings' => $stats['total'],
            'active_bookings' => $stats['active'],
            'pending_bookings' => $stats['pending'],
            'completed_bookings' => $stats['completed'],
            'cancelled_bookings' => $stats['cancelled'],
            'total_spent' => $stats['spent'] ?? 0,
            'total' => $stats['total'],
            'active' => $stats['active'],
            'spent' => $stats['spent'] ?? 0
        ];
    }

    /**
     * Hủy booking
     */
    public function cancelBooking(int $bookingId, int $userId, string $reason): array {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
            $stmt->execute([$bookingId, $userId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                return ['success' => false, 'message' => 'Booking not found or access denied'];
            }

            if (!in_array($booking['status'], ['pending', 'confirmed'])) {
                return ['success' => false, 'message' => 'Booking cannot be cancelled in current status'];
            }

            $stmt = $this->db->prepare("
                UPDATE bookings 
                SET status = 'cancelled', 
                    cancellation_reason = ?, 
                    cancelled_at = NOW() 
                WHERE booking_id = ?
            ");
            $stmt->execute([$reason, $bookingId]);

            // Add to history
            $stmt = $this->db->prepare("
                INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes, created_at)
                VALUES (?, ?, 'cancelled', ?, ?, NOW())
            ");
            $stmt->execute([$bookingId, $booking['status'], $userId, $reason]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Booking cancelled successfully'];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
