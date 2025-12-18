<?php
/**
 * ApartmentInquiry Model
 * Handles apartment consultation/inquiry requests
 * 
 * Aurora Hotel Plaza
 */

class ApartmentInquiry
{
    private $db;
    private $table = 'apartment_inquiries';

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Generate unique inquiry code
     */
    public function generateInquiryCode()
    {
        do {
            $code = 'INQ' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE inquiry_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetchColumn() > 0);

        return $code;
    }

    /**
     * Create new apartment inquiry
     */
    public function create($data)
    {
        $inquiry_code = $this->generateInquiryCode();

        $sql = "INSERT INTO {$this->table} (
            inquiry_code, user_id, room_type_id,
            guest_name, guest_email, guest_phone,
            preferred_check_in, preferred_check_out, duration_type,
            num_adults, num_children, message, special_requests,
            status, priority
        ) VALUES (
            :inquiry_code, :user_id, :room_type_id,
            :guest_name, :guest_email, :guest_phone,
            :preferred_check_in, :preferred_check_out, :duration_type,
            :num_adults, :num_children, :message, :special_requests,
            'new', 'normal'
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'inquiry_code' => $inquiry_code,
            'user_id' => $data['user_id'] ?? null,
            'room_type_id' => $data['room_type_id'],
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'],
            'guest_phone' => $data['guest_phone'],
            'preferred_check_in' => $data['preferred_check_in'] ?? null,
            'preferred_check_out' => $data['preferred_check_out'] ?? null,
            'duration_type' => $data['duration_type'] ?? 'short_term',
            'num_adults' => $data['num_adults'] ?? 1,
            'num_children' => $data['num_children'] ?? 0,
            'message' => $data['message'] ?? null,
            'special_requests' => $data['special_requests'] ?? null
        ]);

        $inquiry_id = $this->db->lastInsertId();

        // Add to history
        $this->addHistory($inquiry_id, null, 'new', null, 'New inquiry created');

        return [
            'inquiry_id' => $inquiry_id,
            'inquiry_code' => $inquiry_code
        ];
    }

    /**
     * Get inquiry by ID
     */
    public function getById($inquiry_id)
    {
        $sql = "SELECT ai.*, rt.type_name, rt.slug, rt.thumbnail, rt.category
                FROM {$this->table} ai
                LEFT JOIN room_types rt ON ai.room_type_id = rt.room_type_id
                WHERE ai.inquiry_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$inquiry_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get inquiry by code
     */
    public function getByCode($inquiry_code)
    {
        $sql = "SELECT ai.*, rt.type_name, rt.slug, rt.thumbnail, rt.category
                FROM {$this->table} ai
                LEFT JOIN room_types rt ON ai.room_type_id = rt.room_type_id
                WHERE ai.inquiry_code = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$inquiry_code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get inquiries by user ID
     */
    public function getByUserId($user_id, $limit = 10, $offset = 0)
    {
        $sql = "SELECT ai.*, rt.type_name, rt.slug, rt.thumbnail
                FROM {$this->table} ai
                LEFT JOIN room_types rt ON ai.room_type_id = rt.room_type_id
                WHERE ai.user_id = ?
                ORDER BY ai.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all inquiries (for admin)
     */
    public function getAll($filters = [], $limit = 20, $offset = 0)
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "ai.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where[] = "ai.priority = :priority";
            $params['priority'] = $filters['priority'];
        }

        if (!empty($filters['room_type_id'])) {
            $where[] = "ai.room_type_id = :room_type_id";
            $params['room_type_id'] = $filters['room_type_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = "ai.assigned_to = :assigned_to";
            $params['assigned_to'] = $filters['assigned_to'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(ai.guest_name LIKE :search OR ai.guest_email LIKE :search OR ai.guest_phone LIKE :search OR ai.inquiry_code LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT ai.*, rt.type_name, rt.slug, rt.thumbnail,
                       u.full_name as assigned_staff_name
                FROM {$this->table} ai
                LEFT JOIN room_types rt ON ai.room_type_id = rt.room_type_id
                LEFT JOIN users u ON ai.assigned_to = u.user_id
                {$whereClause}
                ORDER BY 
                    CASE ai.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'normal' THEN 3 
                        WHEN 'low' THEN 4 
                    END,
                    ai.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count inquiries
     */
    public function count($filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params['status'] = $filters['status'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Update inquiry status
     */
    public function updateStatus($inquiry_id, $new_status, $changed_by = null, $notes = null)
    {
        // Get old status
        $stmt = $this->db->prepare("SELECT status FROM {$this->table} WHERE inquiry_id = ?");
        $stmt->execute([$inquiry_id]);
        $old_status = $stmt->fetchColumn();

        // Update status
        $sql = "UPDATE {$this->table} SET status = :status";

        if ($new_status === 'contacted') {
            $sql .= ", contacted_at = NOW()";
        } elseif (in_array($new_status, ['closed', 'cancelled'])) {
            $sql .= ", closed_at = NOW()";
        }

        $sql .= " WHERE inquiry_id = :inquiry_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => $new_status,
            'inquiry_id' => $inquiry_id
        ]);

        // Add to history
        $this->addHistory($inquiry_id, $old_status, $new_status, $changed_by, $notes);

        return true;
    }

    /**
     * Assign inquiry to staff
     */
    public function assign($inquiry_id, $staff_id, $changed_by = null)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET assigned_to = ? WHERE inquiry_id = ?");
        $stmt->execute([$staff_id, $inquiry_id]);

        $this->addHistory($inquiry_id, null, null, $changed_by, "Assigned to staff ID: {$staff_id}");

        return true;
    }

    /**
     * Update priority
     */
    public function updatePriority($inquiry_id, $priority)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET priority = ? WHERE inquiry_id = ?");
        return $stmt->execute([$priority, $inquiry_id]);
    }

    /**
     * Add admin notes
     */
    public function addAdminNotes($inquiry_id, $notes)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n', ?) WHERE inquiry_id = ?");
        return $stmt->execute([$notes, $inquiry_id]);
    }

    /**
     * Convert inquiry to booking
     */
    public function convertToBooking($inquiry_id, $booking_id, $changed_by = null)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} 
                                   SET status = 'converted', 
                                       converted_booking_id = ?, 
                                       conversion_date = NOW() 
                                   WHERE inquiry_id = ?");
        $stmt->execute([$booking_id, $inquiry_id]);

        $this->addHistory($inquiry_id, 'in_progress', 'converted', $changed_by, "Converted to booking ID: {$booking_id}");

        return true;
    }

    /**
     * Add history record
     */
    public function addHistory($inquiry_id, $old_status, $new_status, $changed_by = null, $notes = null)
    {
        $sql = "INSERT INTO apartment_inquiry_history (inquiry_id, old_status, new_status, changed_by, notes)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$inquiry_id, $old_status, $new_status, $changed_by, $notes]);
    }

    /**
     * Get inquiry history
     */
    public function getHistory($inquiry_id)
    {
        $sql = "SELECT aih.*, u.full_name as changed_by_name
                FROM apartment_inquiry_history aih
                LEFT JOIN users u ON aih.changed_by = u.user_id
                WHERE aih.inquiry_id = ?
                ORDER BY aih.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$inquiry_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get statistics
     */
    public function getStatistics()
    {
        $stats = [];

        // Count by status
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $stmt = $this->db->query($sql);
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Count new today
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE DATE(created_at) = CURDATE()";
        $stats['new_today'] = $this->db->query($sql)->fetchColumn();

        // Conversion rate
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted
                FROM {$this->table}";
        $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        $stats['conversion_rate'] = $result['total'] > 0
            ? round(($result['converted'] / $result['total']) * 100, 2)
            : 0;

        return $stats;
    }
}
