<?php
/**
 * Aurora Hotel Plaza - Bookings Controller
 * Handles data fetching and processing for bookings management
 */

require_once '../helpers/booking-helper.php';

function getBookingsData() {
    // Get filter parameters from GET
    $status_filter = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    // Build query
    $where_clauses = [];
    $params = [];

    if ($status_filter !== 'all') {
        $where_clauses[] = "b.status = :status";
        $params[':status'] = $status_filter;
    }

    if (!empty($search)) {
        // Smart search - hỗ trợ mã ngắn
        $possible_codes = BookingHelper::parseSmartCode($search);

        $search_conditions = [];
        foreach ($possible_codes as $index => $code) {
            if (strpos($code, '%') !== false) {
                $search_conditions[] = "b.booking_code LIKE :code{$index}";
                $params[":code{$index}"] = $code;
            } else {
                $search_conditions[] = "b.booking_code = :code{$index}";
                $params[":code{$index}"] = $code;
            }
        }

        // Thêm tìm kiếm theo tên, email, SĐT
        $search_conditions[] = "b.guest_name LIKE :search_text";
        $search_conditions[] = "b.guest_email LIKE :search_text";
        $search_conditions[] = "b.guest_phone LIKE :search_text";
        $params[':search_text'] = "%$search%";

        $where_clauses[] = "(" . implode(' OR ', $search_conditions) . ")";
    }

    if (!empty($date_from)) {
        $where_clauses[] = "b.check_in_date >= :date_from";
        $params[':date_from'] = $date_from;
    }

    if (!empty($date_to)) {
        $where_clauses[] = "b.check_in_date <= :date_to";
        $params[':date_to'] = $date_to;
    }

    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    try {
        $db = getDB();

        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM bookings b $where_sql";
        $stmt = $db->prepare($count_sql);
        $stmt->execute($params);
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_records / $per_page);

        // Get bookings
        $sql = "
            SELECT b.*, u.full_name as user_name, rt.type_name, r.room_number
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            $where_sql
            ORDER BY b.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get status counts
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in,
                SUM(CASE WHEN status = 'checked_out' THEN 1 ELSE 0 END) as checked_out,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM bookings
        ");
        $status_counts = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'bookings' => $bookings,
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'status_counts' => $status_counts,
            'status_filter' => $status_filter,
            'search' => $search,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'page' => $page
        ];

    } catch (Exception $e) {
        error_log("Bookings Controller error: " . $e->getMessage());
        return [
            'bookings' => [],
            'total_records' => 0,
            'total_pages' => 0,
            'status_counts' => ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'checked_in' => 0, 'checked_out' => 0, 'cancelled' => 0],
            'status_filter' => $status_filter,
            'search' => $search,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'page' => $page
        ];
    }
}
