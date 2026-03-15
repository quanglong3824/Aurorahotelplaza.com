<?php
/**
 * Aurora Hotel Plaza - Apartment Inquiries Controller
 * Handles data fetching and processing for apartment inquiries management
 */

require_once '../helpers/auth-middleware.php';

function getApartmentInquiriesData() {
    // Get filter parameters from GET
    $status_filter = $_GET['status'] ?? 'all';
    $apartment_filter = $_GET['apartment'] ?? 'all';
    $duration_filter = $_GET['duration'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    // Build query
    $where_clauses = ["b.booking_type = 'inquiry'"];
    $params = [];

    if ($status_filter !== 'all') {
        $where_clauses[] = "b.status = :status";
        $params[':status'] = $status_filter;
    }

    if ($apartment_filter !== 'all') {
        $where_clauses[] = "b.room_type_id = :room_type_id";
        $params[':room_type_id'] = $apartment_filter;
    }

    if ($duration_filter !== 'all') {
        $where_clauses[] = "b.duration_type = :duration_type";
        $params[':duration_type'] = $duration_filter;
    }

    if (!empty($search)) {
        $where_clauses[] = "(b.booking_code LIKE :search OR b.guest_name LIKE :search OR b.guest_email LIKE :search OR b.guest_phone LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    $where_sql = implode(' AND ', $where_clauses);

    // Initial state
    $inquiries = [];
    $total_inquiries = 0;
    $stats = ['pending' => 0, 'contacted' => 0, 'confirmed' => 0, 'cancelled' => 0];
    $apartments = [];

    try {
        $db = getDB();

        // Get total count
        $count_stmt = $db->prepare("SELECT COUNT(*) FROM bookings b WHERE {$where_sql}");
        $count_stmt->execute($params);
        $total_inquiries = (int) $count_stmt->fetchColumn();

        // Get inquiries with pagination
        $stmt = $db->prepare("
            SELECT b.*, rt.type_name, rt.thumbnail,
                   u.full_name as user_name, u.phone as user_phone
            FROM bookings b
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN users u ON b.user_id = u.user_id
            WHERE {$where_sql}
            ORDER BY b.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get stats
        $stats_stmt = $db->query("
            SELECT status, COUNT(*) as count 
            FROM bookings 
            WHERE booking_type = 'inquiry'
            GROUP BY status
        ");
        while ($row = $stats_stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['status']] = (int) $row['count'];
        }

        // Get apartment types for filter
        $apartments_stmt = $db->query("
            SELECT room_type_id, type_name 
            FROM room_types 
            WHERE category = 'apartment' 
            ORDER BY type_name
        ");
        $apartments = $apartments_stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Apartment Inquiries Controller error: " . $e->getMessage());
    }

    $total_pages = ceil($total_inquiries / $per_page);

    // Duration labels for filter dropdown
    $duration_labels = [
        '1_month' => '1 tháng',
        '3_months' => '3 tháng',
        '6_months' => '6 tháng',
        '12_months' => '12 tháng',
        'custom' => 'Khác (theo ngày)'
    ];

    // Status labels
    $status_labels = [
        'pending' => ['label' => 'Chờ liên hệ', 'class' => 'badge-warning'],
        'contacted' => ['label' => 'Đã liên hệ', 'class' => 'badge-info'],
        'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'badge-success'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'badge-danger'],
        'checked_out' => ['label' => 'Hoàn tất', 'class' => 'badge-secondary']
    ];

    return [
        'inquiries' => $inquiries,
        'total_inquiries' => $total_inquiries,
        'total_pages' => $total_pages,
        'stats' => $stats,
        'apartments' => $apartments,
        'duration_labels' => $duration_labels,
        'status_labels' => $status_labels,
        'status_filter' => $status_filter,
        'apartment_filter' => $apartment_filter,
        'duration_filter' => $duration_filter,
        'search' => $search,
        'page' => $page
    ];
}

/**
 * Helper function to parse duration for display
 */
function parseDurationForView($duration_type)
{
    if (empty($duration_type))
        return 'N/A';

    // Check if it's a custom days format (custom_45_days)
    if (preg_match('/^custom_(\d+)_days$/', $duration_type, $matches)) {
        return (int) $matches[1] . ' ngày';
    }

    // Check if it's a month format (1_month, 3_months, etc)
    if (preg_match('/^(\d+)_month/', $duration_type, $matches)) {
        $months = (int) $matches[1];
        if ($months == 12)
            return '12 tháng (1 năm)';
        if ($months == 24)
            return '24 tháng (2 năm)';
        return $months . ' tháng';
    }

    return $duration_type;
}
