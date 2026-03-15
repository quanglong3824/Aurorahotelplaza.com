<?php
/**
 * Aurora Hotel Plaza - Logs Controller
 */

function getLogsData() {
    // Check admin role
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }

    // Get filter parameters
    $action_filter = $_GET['action'] ?? 'all';
    $user_filter = $_GET['user_id'] ?? 'all';
    $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 50;
    $offset = ($page - 1) * $per_page;

    // Build query
    $where_clauses = [];
    $params = [];

    if ($action_filter !== 'all') {
        $where_clauses[] = "al.action = :action";
        $params[':action'] = $action_filter;
    }

    if ($user_filter !== 'all') {
        $where_clauses[] = "al.user_id = :user_id";
        $params[':user_id'] = $user_filter;
    }

    if (!empty($date_from)) {
        $where_clauses[] = "DATE(al.created_at) >= :date_from";
        $params[':date_from'] = $date_from;
    }

    if (!empty($date_to)) {
        $where_clauses[] = "DATE(al.created_at) <= :date_to";
        $params[':date_to'] = $date_to;
    }

    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    try {
        $db = getDB();
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM activity_logs al $where_sql";
        $stmt = $db->prepare($count_sql);
        $stmt->execute($params);
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_records / $per_page);
        
        // Get logs
        $sql = "
            SELECT al.*, u.full_name, u.email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            $where_sql
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get unique actions for filter
        $stmt = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
        $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get users for filter
        $stmt = $db->query("
            SELECT DISTINCT u.user_id, u.full_name 
            FROM users u
            INNER JOIN activity_logs al ON u.user_id = al.user_id
            WHERE u.user_role != 'customer'
            ORDER BY u.full_name
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get stats
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT DATE(created_at)) as unique_days
            FROM activity_logs
            WHERE DATE(created_at) BETWEEN :date_from AND :date_to
        ");
        $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Logs controller error: " . $e->getMessage());
        $logs = [];
        $actions = [];
        $users = [];
        $total_records = 0;
        $total_pages = 0;
        $stats = ['total' => 0, 'unique_users' => 0, 'unique_days' => 0];
    }

    return [
        'logs' => $logs,
        'actions' => $actions,
        'users' => $users,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'stats' => $stats,
        'action_filter' => $action_filter,
        'user_filter' => $user_filter,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'page' => $page,
        'per_page' => $per_page
    ];
}
