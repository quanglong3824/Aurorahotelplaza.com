<?php
/**
 * Aurora Hotel Plaza - Room Types Controller
 * Handles data fetching and processing for room types management
 */

function getRoomTypesData() {
    // Get filter parameters from GET
    $category_filter = $_GET['category'] ?? 'all';
    $status_filter = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';

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

    if (!empty($search)) {
        $where_clauses[] = "(type_name LIKE :search OR description LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    try {
        $db = getDB();

        // Get room types
        $sql = "
            SELECT rt.*,
                   (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id) as total_rooms,
                   (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id AND r.status = 'available') as available_rooms
        FROM room_types rt
        $where_sql
        ORDER BY rt.sort_order ASC, rt.created_at DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get counts
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN category = 'room' THEN 1 ELSE 0 END) as rooms,
                SUM(CASE WHEN category = 'apartment' THEN 1 ELSE 0 END) as apartments,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
            FROM room_types
        ");
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'room_types' => $room_types,
            'counts' => $counts,
            'category_filter' => $category_filter,
            'status_filter' => $status_filter,
            'search' => $search
        ];

    } catch (Exception $e) {
        error_log("Room Types Controller error: " . $e->getMessage());
        return [
            'room_types' => [],
            'counts' => ['total' => 0, 'rooms' => 0, 'apartments' => 0, 'active' => 0, 'inactive' => 0],
            'category_filter' => $category_filter,
            'status_filter' => $status_filter,
            'search' => $search
        ];
    }
}
