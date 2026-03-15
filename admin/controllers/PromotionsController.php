<?php
class PromotionsController {
    public function getData() {
        require_once '../config/database.php';
        
        $status_filter = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';

        $where_clauses = [];
        $params = [];

        if ($status_filter !== 'all') {
            $where_clauses[] = "status = :status";
            $params[':status'] = $status_filter;
        }

        if (!empty($search)) {
            $where_clauses[] = "(promotion_code LIKE :search OR promotion_name LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        try {
            $db = getDB();

            $sql = "
                SELECT p.*,
                       (SELECT COUNT(*) FROM promotion_usage WHERE promotion_id = p.promotion_id) as usage_count
                FROM promotions p
                $where_sql
                ORDER BY p.created_at DESC
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
                FROM promotions
            ");
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Promotions page error: " . $e->getMessage());
            $promotions = [];
            $counts = ['total' => 0, 'active' => 0, 'inactive' => 0, 'expired' => 0];
        }

        return [
            'promotions' => $promotions,
            'counts' => $counts,
            'status_filter' => $status_filter,
            'search' => $search
        ];
    }
}
