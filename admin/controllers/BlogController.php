<?php
/**
 * Aurora Hotel Plaza - Blog Controller
 */

class BlogController {
    public function getData() {
        require_once '../config/database.php';
        
        // Filters
        $status_filter = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';

        $where_clauses = [];
        $params = [];

        if ($status_filter !== 'all') {
            $where_clauses[] = "status = :status";
            $params[':status'] = $status_filter;
        }

        if (!empty($search)) {
            $where_clauses[] = "(title LIKE :search OR content LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        try {
            $db = getDB();
            
            $sql = "
                SELECT bp.*, u.full_name as author_name,
                       (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.post_id) as comment_count
                FROM blog_posts bp
                LEFT JOIN users u ON bp.author_id = u.user_id
                $where_sql
                ORDER BY bp.created_at DESC
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Blog Controller error: " . $e->getMessage());
            $posts = [];
        }

        return [
            'posts' => $posts,
            'status_filter' => $status_filter,
            'search' => $search
        ];
    }
}
