<?php
/**
 * Aurora Hotel Plaza - Blog Comments Controller
 * Handles data fetching for blog comments management
 */

function getBlogCommentsData() {
    $status_filter = $_GET['status'] ?? 'pending';
    $search = $_GET['search'] ?? '';
    $post_filter = $_GET['post_id'] ?? '';

    $where_clauses = [];
    $params = [];

    if ($status_filter !== 'all') {
        $where_clauses[] = "c.status = :status";
        $params[':status'] = $status_filter;
    }

    if (!empty($search)) {
        $where_clauses[] = "(c.content LIKE :search OR c.author_name LIKE :search OR c.author_email LIKE :search OR p.title LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($post_filter)) {
        $where_clauses[] = "c.post_id = :post_id";
        $params[':post_id'] = (int)$post_filter;
    }

    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    try {
        $db = getDB();

        $sql = "
            SELECT c.*, p.title as post_title, p.slug as post_slug
            FROM blog_comments c
            LEFT JOIN blog_posts p ON c.post_id = p.post_id
            $where_sql
            ORDER BY c.created_at DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'comments' => $comments,
            'status_filter' => $status_filter,
            'search' => $search,
            'post_filter' => $post_filter
        ];
    } catch (Exception $e) {
        error_log('Admin blog comments controller error: ' . $e->getMessage());
        return [
            'comments' => [],
            'status_filter' => $status_filter,
            'search' => $search,
            'post_filter' => $post_filter
        ];
    }
}
