<?php
/**
 * Aurora Hotel Plaza - Reviews Controller
 */

class ReviewsController {
    public function getData() {
        require_once '../config/database.php';
        
        // Get filter parameters
        $status_filter = $_GET['status'] ?? 'all';
        $rating_filter = $_GET['rating'] ?? 'all';
        $search = $_GET['search'] ?? '';

        // Build query
        $where_clauses = [];
        $params = [];

        if ($status_filter !== 'all') {
            $where_clauses[] = "r.status = :status";
            $params[':status'] = $status_filter;
        }

        if ($rating_filter !== 'all') {
            $where_clauses[] = "r.rating >= :rating";
            $params[':rating'] = $rating_filter;
        }

        if (!empty($search)) {
            $where_clauses[] = "(r.title LIKE :search OR r.comment LIKE :search OR u.full_name LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        try {
            $db = getDB();
            
            // Get reviews
            $sql = "
                SELECT r.*, u.full_name, u.email, rt.type_name,
                       (SELECT COUNT(*) FROM review_responses WHERE review_id = r.review_id) as response_count
                FROM reviews r
                JOIN users u ON r.user_id = u.user_id
                LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
                $where_sql
                ORDER BY r.created_at DESC
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get counts
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    AVG(rating) as avg_rating
                FROM reviews
            ");
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Reviews Controller error: " . $e->getMessage());
            $reviews = [];
            $counts = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'avg_rating' => 0];
        }

        return [
            'reviews' => $reviews,
            'counts' => $counts,
            'status_filter' => $status_filter,
            'rating_filter' => $rating_filter,
            'search' => $search
        ];
    }
}
