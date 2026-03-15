<?php
/**
 * Aurora Hotel Plaza - FAQs Controller
 */

class FaqsController {
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
            
            // Get FAQs
            $sql = "
                SELECT *
                FROM faqs
                $where_sql
                ORDER BY sort_order ASC, created_at DESC
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get categories
            $stmt = $db->query("SELECT DISTINCT category FROM faqs WHERE category IS NOT NULL ORDER BY category");
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get counts
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(views) as total_views
                FROM faqs
            ");
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("FAQs Controller error: " . $e->getMessage());
            $faqs = [];
            $categories = [];
            $counts = ['total' => 0, 'active' => 0, 'total_views' => 0];
        }

        return [
            'faqs' => $faqs,
            'categories' => $categories,
            'counts' => $counts,
            'category_filter' => $category_filter,
            'status_filter' => $status_filter
        ];
    }
}
