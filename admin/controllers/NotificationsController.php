<?php
/**
 * Aurora Hotel Plaza - Notifications Controller
 */

function getNotificationsData() {
    // Get filter
    $filter = $_GET['filter'] ?? 'all';
    $type_filter = $_GET['type'] ?? 'all';

    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];
        
        // Build query
        $where = ["user_id = :user_id"];
        $params = [':user_id' => $user_id];
        
        if ($filter === 'unread') {
            $where[] = "is_read = 0";
        } elseif ($filter === 'read') {
            $where[] = "is_read = 1";
        }
        
        if ($type_filter !== 'all') {
            $where[] = "type = :type";
            $params[':type'] = $type_filter;
        }
        
        $where_sql = implode(' AND ', $where);
        
        // Get notifications
        $stmt = $db->prepare("
            SELECT * FROM notifications
            WHERE $where_sql
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute($params);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get stats
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN type = 'booking' THEN 1 ELSE 0 END) as booking,
                SUM(CASE WHEN type = 'payment' THEN 1 ELSE 0 END) as payment,
                SUM(CASE WHEN type = 'review' THEN 1 ELSE 0 END) as review,
                SUM(CASE WHEN type = 'service' THEN 1 ELSE 0 END) as service
            FROM notifications
            WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Notifications controller error: " . $e->getMessage());
        $notifications = [];
        $stats = ['total' => 0, 'unread' => 0, 'booking' => 0, 'payment' => 0, 'review' => 0, 'service' => 0];
    }

    return [
        'notifications' => $notifications,
        'stats' => $stats,
        'filter' => $filter,
        'type_filter' => $type_filter
    ];
}

if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) return 'Vừa xong';
        if ($diff < 3600) return floor($diff / 60) . ' phút trước';
        if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
        if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
        
        return date('m/d/Y H:i', $time);
    }
}
