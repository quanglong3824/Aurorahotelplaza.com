<?php
/**
 * Aurora Hotel Plaza - Rooms Controller
 * Handles data fetching and processing for rooms management
 */

function getRoomsData() {
    // Get filter parameters from GET
    $type_filter = $_GET['type_id'] ?? 'all';
    $status_filter = $_GET['status'] ?? 'all';
    $floor_filter = $_GET['floor'] ?? 'all';
    $search = $_GET['search'] ?? '';

    // Build query
    $where_clauses = [];
    $params = [];

    if ($type_filter !== 'all') {
        $where_clauses[] = "r.room_type_id = :type_id";
        $params[':type_id'] = $type_filter;
    }

    if ($status_filter !== 'all') {
        $where_clauses[] = "r.status = :status";
        $params[':status'] = $status_filter;
    }

    if ($floor_filter !== 'all') {
        $where_clauses[] = "r.floor = :floor";
        $params[':floor'] = $floor_filter;
    }

    if (!empty($search)) {
        $where_clauses[] = "(r.room_number LIKE :search OR r.building LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    try {
        $db = getDB();
        
        // Get rooms
        $sql = "
            SELECT r.*, rt.type_name, rt.category
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.room_type_id
            $where_sql
            ORDER BY r.floor ASC, r.room_number ASC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get room types for filter
        $stmt = $db->query("SELECT room_type_id, type_name FROM room_types ORDER BY type_name");
        $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get floors for filter
        $stmt = $db->query("SELECT DISTINCT floor FROM rooms WHERE floor IS NOT NULL ORDER BY floor");
        $floors = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get status counts
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                SUM(CASE WHEN status = 'cleaning' THEN 1 ELSE 0 END) as cleaning
            FROM rooms
        ");
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'rooms' => $rooms,
            'room_types' => $room_types,
            'floors' => $floors,
            'counts' => $counts,
            'type_filter' => $type_filter,
            'status_filter' => $status_filter,
            'floor_filter' => $floor_filter,
            'search' => $search
        ];
        
    } catch (Exception $e) {
        error_log("Rooms Controller error: " . $e->getMessage());
        return [
            'rooms' => [],
            'room_types' => [],
            'floors' => [],
            'counts' => ['total' => 0, 'available' => 0, 'occupied' => 0, 'maintenance' => 0, 'cleaning' => 0],
            'type_filter' => $type_filter,
            'status_filter' => $status_filter,
            'floor_filter' => $floor_filter,
            'search' => $search
        ];
    }
}
