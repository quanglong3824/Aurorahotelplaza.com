<?php
/**
 * Aurora Hotel Plaza - Service Bookings Controller
 * Handles data fetching and processing for service bookings management
 */

function getServiceBookingsData() {
    $status_filter = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';

    $where_clauses = [];
    $params = [];

    if ($status_filter !== 'all') {
        $where_clauses[] = "sb.status = :status";
        $params[':status'] = $status_filter;
    }

    if (!empty($search)) {
        $where_clauses[] = "(u.full_name LIKE :search OR s.service_name LIKE :search OR sb.booking_code LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    try {
        $db = getDB();

        $sql = "
            SELECT sb.*, u.full_name, s.service_name, s.price as service_price
            FROM service_bookings sb
            LEFT JOIN users u ON sb.user_id = u.user_id
            LEFT JOIN services s ON sb.service_id = s.service_id
            $where_sql
            ORDER BY sb.created_at DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $service_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END) as total_revenue
            FROM service_bookings
            WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'service_bookings' => $service_bookings,
            'stats' => $stats,
            'status_filter' => $status_filter,
            'search' => $search
        ];

    } catch (Exception $e) {
        error_log("Service bookings controller error: " . $e->getMessage());
        return [
            'service_bookings' => [],
            'stats' => ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'total_revenue' => 0],
            'status_filter' => $status_filter,
            'search' => $search
        ];
    }
}
