<?php
/**
 * Aurora Hotel Plaza - Gallery Controller
 */

class GalleryController {
    public function getData() {
        require_once '../config/database.php';
        
        // Get filter parameters
        $category_filter = $_GET['category'] ?? 'all';
        $status_filter = $_GET['status'] ?? 'all';

        // Build query
        $where_clauses = [];
        $params = [];

        if ($category_filter !== 'all') {
            $where_clauses[] = "category = :category";
            $params[':category'] = $category_filter;
        }

        if ($status_filter !== 'all') {
            $where_clauses[] = "status = :status";
            $params[':status'] = $status_filter;
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        try {
            $db = getDB();
            
            // Get gallery images
            $sql = "
                SELECT g.*, u.full_name as uploaded_by_name
                FROM gallery g
                LEFT JOIN users u ON g.uploaded_by = u.user_id
                $where_sql
                ORDER BY g.sort_order ASC, g.created_at DESC
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get categories
            $stmt = $db->query("SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL ORDER BY category");
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get counts
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
                FROM gallery
            ");
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Gallery Controller error: " . $e->getMessage());
            $images = [];
            $categories = [];
            $counts = ['total' => 0, 'active' => 0, 'inactive' => 0];
        }

        return [
            'images' => $images,
            'categories' => $categories,
            'counts' => $counts,
            'category_filter' => $category_filter,
            'status_filter' => $status_filter
        ];
    }
}
