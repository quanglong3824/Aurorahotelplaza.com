<?php
class ServicesController {
    public function getData() {
        require_once '../config/database.php';
        
        $category_filter = $_GET['category'] ?? 'all';
        $status_filter = $_GET['available'] ?? 'all';
        $search = $_GET['search'] ?? '';

        $where_clauses = [];
        $params = [];

        if ($category_filter !== 'all') {
            $where_clauses[] = "category = :category";
            $params[':category'] = $category_filter;
        }

        if ($status_filter !== 'all') {
            $where_clauses[] = "available = :available";
            $params[':available'] = $status_filter;
        }

        if (!empty($search)) {
            $where_clauses[] = "(service_name LIKE :search OR description LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        try {
            $db = getDB();

            $sql = "
                SELECT s.*,
                       (SELECT COUNT(*) FROM service_bookings WHERE service_id = s.service_id) as total_bookings,
                       (SELECT SUM(total_price) FROM service_bookings WHERE service_id = s.service_id AND status = 'completed') as total_revenue
                FROM services s
                $where_sql
                ORDER BY s.sort_order ASC, s.created_at DESC
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN category = 'spa' THEN 1 ELSE 0 END) as spa,
                    SUM(CASE WHEN category = 'restaurant' THEN 1 ELSE 0 END) as restaurant,
                    SUM(CASE WHEN category = 'laundry' THEN 1 ELSE 0 END) as laundry,
                    SUM(CASE WHEN category = 'transport' THEN 1 ELSE 0 END) as transport,
                    SUM(CASE WHEN available = 1 THEN 1 ELSE 0 END) as available
                FROM services
            ");
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Services page error: " . $e->getMessage());
            $services = [];
            $counts = ['total' => 0, 'spa' => 0, 'restaurant' => 0, 'laundry' => 0, 'transport' => 0, 'available' => 0];
        }

        return [
            'services' => $services,
            'counts' => $counts,
            'category_filter' => $category_filter,
            'status_filter' => $status_filter,
            'search' => $search
        ];
    }
}
