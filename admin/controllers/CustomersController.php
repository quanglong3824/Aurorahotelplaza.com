<?php
/**
 * Aurora Hotel Plaza - Customers Controller
 * Handles data fetching and processing for customers management
 */

function getCustomersData() {
    // Get filter parameters from GET
    $status_filter = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    // Build query
    $where_clauses = ["user_role = 'customer'"];
    $params = [];

    if ($status_filter !== 'all') {
        $where_clauses[] = "status = :status";
        $params[':status'] = $status_filter;
    }

    if (!empty($search)) {
        $where_clauses[] = "(full_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

    try {
        $db = getDB();

        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM users $where_sql";
        $stmt = $db->prepare($count_sql);
        $stmt->execute($params);
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_records / $per_page);

        // Get customers
        $sql = "
            SELECT u.*,
                   ul.current_points, ul.lifetime_points,
                   mt.tier_name, mt.color_code,
                   (SELECT COUNT(*) FROM bookings WHERE user_id = u.user_id) as total_bookings,
                   (SELECT SUM(total_amount) FROM bookings WHERE user_id = u.user_id AND status != 'cancelled') as total_spent
            FROM users u
            LEFT JOIN user_loyalty ul ON u.user_id = ul.user_id
            LEFT JOIN membership_tiers mt ON ul.tier_id = mt.tier_id
            $where_sql
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get status counts
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned
            FROM users
            WHERE user_role = 'customer'
        ");
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'customers' => $customers,
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'counts' => $counts,
            'status_filter' => $status_filter,
            'search' => $search,
            'page' => $page
        ];

    } catch (Exception $e) {
        error_log("Customers Controller error: " . $e->getMessage());
        return [
            'customers' => [],
            'total_records' => 0,
            'total_pages' => 0,
            'counts' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'banned' => 0],
            'status_filter' => $status_filter,
            'search' => $search,
            'page' => $page
        ];
    }
}
