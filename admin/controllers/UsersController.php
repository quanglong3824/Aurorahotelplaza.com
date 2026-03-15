<?php
class UsersController {
    public function getData() {
        require_once '../config/database.php';
        
        // Get filter parameters
        $role_filter = $_GET['role'] ?? 'all';
        $status_filter = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';

        // Build query
        $where_clauses = ["user_role != 'customer'"];
        $params = [];

        if ($role_filter !== 'all') {
            $where_clauses[] = "user_role = :role";
            $params[':role'] = $role_filter;
        }

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

            // Get users
            $sql = "
                SELECT u.*,
                       (SELECT COUNT(*) FROM bookings WHERE checked_in_by = u.user_id OR cancelled_by = u.user_id) as actions_count
                FROM users u
                $where_sql
                ORDER BY u.created_at DESC
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get counts
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN user_role = 'admin' THEN 1 ELSE 0 END) as admin,
                    SUM(CASE WHEN user_role = 'sale' THEN 1 ELSE 0 END) as sale,
                    SUM(CASE WHEN user_role = 'receptionist' THEN 1 ELSE 0 END) as receptionist,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
                FROM users
                WHERE user_role != 'customer'
            ");
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Users page error: " . $e->getMessage());
            $users = [];
            $counts = ['total' => 0, 'admin' => 0, 'sale' => 0, 'receptionist' => 0, 'active' => 0];
        }

        return [
            'users' => $users,
            'counts' => $counts,
            'role_filter' => $role_filter,
            'status_filter' => $status_filter,
            'search' => $search
        ];
    }
}
