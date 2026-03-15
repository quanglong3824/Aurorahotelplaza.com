<?php
/**
 * Aurora Hotel Plaza - Activity Logs Controller
 * Handles data fetching and processing for activity logs
 */

require_once '../helpers/activity-logger.php';

function getActivityLogsData() {
    // Get filters from GET
    $user_filter = $_GET['user'] ?? 'all';
    $action_filter = $_GET['action'] ?? 'all';
    $entity_filter = $_GET['entity'] ?? 'all';
    $date_filter = $_GET['date'] ?? 'today';

    try {
        $db = getDB();
        
        // Build query
        $where = [];
        $params = [];
        
        if ($user_filter !== 'all') {
            if ($user_filter === 'customers') {
                $where[] = "u.user_role = 'customer'";
            } elseif ($user_filter === 'staff') {
                $where[] = "u.user_role IN ('admin', 'receptionist', 'sale')";
            } else {
                $where[] = "al.user_id = :user_id";
                $params[':user_id'] = $user_filter;
            }
        }
        
        if ($action_filter !== 'all') {
            $where[] = "al.action LIKE :action";
            $params[':action'] = "%{$action_filter}%";
        }
        
        if ($entity_filter !== 'all') {
            $where[] = "al.entity_type = :entity_type";
            $params[':entity_type'] = $entity_filter;
        }
        
        // Date filter
        switch ($date_filter) {
            case 'today':
                $where[] = "DATE(al.created_at) = CURDATE()";
                break;
            case 'yesterday':
                $where[] = "DATE(al.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $where[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
        
        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get activities
        $stmt = $db->prepare("
            SELECT 
                al.*,
                u.full_name,
                u.email,
                u.user_role
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            $where_sql
            ORDER BY al.created_at DESC
            LIMIT 200
        ");
        
        $stmt->execute($params);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $stats = ActivityLogger::getStatistics(7);
        
        // Get unique users for filter
        $stmt = $db->query("
            SELECT DISTINCT u.user_id, u.full_name, u.email, u.user_role
            FROM activity_logs al
            JOIN users u ON al.user_id = u.user_id
            ORDER BY u.full_name
            LIMIT 100
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count today's activities
        $stmt = $db->query("SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()");
        $today_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return [
            'activities' => $activities,
            'stats' => $stats,
            'users' => $users,
            'today_count' => $today_count,
            'user_filter' => $user_filter,
            'action_filter' => $action_filter,
            'entity_filter' => $entity_filter,
            'date_filter' => $date_filter
        ];
        
    } catch (Exception $e) {
        error_log("Activity logs controller error: " . $e->getMessage());
        return [
            'activities' => [],
            'stats' => [],
            'users' => [],
            'today_count' => 0,
            'user_filter' => $user_filter,
            'action_filter' => $action_filter,
            'entity_filter' => $entity_filter,
            'date_filter' => $date_filter
        ];
    }
}
