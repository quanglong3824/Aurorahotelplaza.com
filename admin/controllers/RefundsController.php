<?php
/**
 * Aurora Hotel Plaza - Refunds Controller
 * Handles refund management logic
 */

function getRefundsData($filters = []) {
    try {
        $db = getDB();
        
        $status_filter = $filters['status'] ?? '';
        $search = $filters['search'] ?? '';
        $page = max(1, intval($filters['page'] ?? 1));
        $per_page = 20;

        // Build query
        $where_conditions = ['1=1'];
        $params = [];
        
        if ($status_filter) {
            $where_conditions[] = 'r.refund_status = ?';
            $params[] = $status_filter;
        }
        
        if ($search) {
            $where_conditions[] = '(b.booking_code LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
            $search_param = '%' . $search . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Get total count
        $count_sql = "
            SELECT COUNT(*) as total
            FROM refunds r
            JOIN bookings b ON r.booking_id = b.booking_id
            LEFT JOIN users u ON r.requested_by = u.user_id
            $where_clause
        ";
        $stmt = $db->prepare($count_sql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];
        $total_pages = ceil($total / $per_page);
        
        // Get refunds
        $offset = ($page - 1) * $per_page;
        $sql = "
            SELECT r.*, b.booking_code, b.total_amount as booking_amount,
                   u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
                   req_user.full_name as requested_by_name,
                   app_user.full_name as approved_by_name,
                   proc_user.full_name as processed_by_name
            FROM refunds r
            JOIN bookings b ON r.booking_id = b.booking_id
            LEFT JOIN users u ON r.requested_by = u.user_id
            LEFT JOIN users req_user ON r.requested_by = req_user.user_id
            LEFT JOIN users app_user ON r.approved_by = app_user.user_id
            LEFT JOIN users proc_user ON r.processed_by = proc_user.user_id
            $where_clause
            ORDER BY r.requested_at DESC
            LIMIT $per_page OFFSET $offset
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $stats_sql = "
            SELECT 
                COUNT(*) as total_refunds,
                SUM(CASE WHEN refund_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN refund_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN refund_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN refund_status = 'pending' THEN refund_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN refund_status = 'completed' THEN refund_amount ELSE 0 END) as completed_amount
            FROM refunds
        ";
        $stmt = $db->query($stats_sql);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'refunds' => $refunds,
            'stats' => $stats,
            'total' => $total,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
            'status_filter' => $status_filter,
            'search' => $search
        ];
        
    } catch (Exception $e) {
        error_log("Refunds Controller error: " . $e->getMessage());
        return [
            'refunds' => [],
            'stats' => [
                'total_refunds' => 0,
                'pending_count' => 0,
                'approved_count' => 0,
                'completed_count' => 0,
                'pending_amount' => 0,
                'completed_amount' => 0
            ],
            'total' => 0,
            'total_pages' => 0,
            'page' => 1,
            'per_page' => 20,
            'status_filter' => '',
            'search' => ''
        ];
    }
}
