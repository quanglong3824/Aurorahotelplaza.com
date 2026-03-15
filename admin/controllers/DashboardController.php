<?php
/**
 * Aurora Hotel Plaza - Dashboard Controller
 * Handles data fetching for the admin dashboard
 */

function getDashboardData() {
    try {
        $db = getDB();
        $today = date('Y-m-d');

        // Real-time stats
        $stmt = $db->query("
            SELECT 
                (SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()) as bookings_today,
                (SELECT SUM(total_amount) FROM bookings WHERE DATE(created_at) = CURDATE() AND status != 'cancelled') as revenue_today,
                (SELECT COUNT(*) FROM bookings WHERE status = 'pending') as pending_bookings,
                (SELECT COUNT(*) FROM rooms WHERE status = 'available') as available_rooms,
                (SELECT COUNT(*) FROM rooms) as total_rooms,
                (SELECT COUNT(*) FROM bookings WHERE check_in_date = CURDATE() AND status IN ('confirmed', 'checked_in')) as checkins_today,
                (SELECT COUNT(*) FROM bookings WHERE check_out_date = CURDATE() AND status = 'checked_in') as checkouts_today,
                (SELECT COUNT(*) FROM users WHERE user_role = 'customer' AND DATE(created_at) = CURDATE()) as new_customers_today
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Revenue comparison
        $stmt = $db->query("
            SELECT 
                (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'cancelled') as revenue_this_month,
                (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND status != 'cancelled') as revenue_last_month
        ");
        $revenue_comparison = $stmt->fetch(PDO::FETCH_ASSOC);

        $revenue_growth = 0;
        if ($revenue_comparison['revenue_last_month'] > 0) {
            $revenue_growth = (($revenue_comparison['revenue_this_month'] - $revenue_comparison['revenue_last_month']) / $revenue_comparison['revenue_last_month']) * 100;
        }

        // Occupancy rate
        $occupancy_rate = ($stats['total_rooms'] > 0) ?
            (($stats['total_rooms'] - $stats['available_rooms']) / $stats['total_rooms']) * 100 : 0;

        // Recent activities
        $stmt = $db->query("
            SELECT al.*, u.full_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.created_at DESC
            LIMIT 10
        ");
        $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top room types
        $stmt = $db->query("
            SELECT rt.type_name, COUNT(b.booking_id) as bookings, SUM(b.total_amount) as revenue
            FROM room_types rt
            LEFT JOIN bookings b ON rt.room_type_id = b.room_type_id AND MONTH(b.created_at) = MONTH(CURDATE())
            WHERE b.status != 'cancelled'
            GROUP BY rt.room_type_id
            ORDER BY revenue DESC
            LIMIT 5
        ");
        $top_room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Upcoming check-ins
        $stmt = $db->query("
            SELECT b.*, u.full_name, rt.type_name, r.room_number
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE b.check_in_date = CURDATE() AND b.status = 'confirmed'
            ORDER BY b.check_in_date ASC
            LIMIT 5
        ");
        $upcoming_checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'stats' => $stats,
            'revenue_growth' => $revenue_growth,
            'occupancy_rate' => $occupancy_rate,
            'recent_activities' => $recent_activities,
            'top_room_types' => $top_room_types,
            'upcoming_checkins' => $upcoming_checkins
        ];

    } catch (Exception $e) {
        error_log("Dashboard Controller error: " . $e->getMessage());
        return [
            'stats' => ['bookings_today' => 0, 'revenue_today' => 0, 'pending_bookings' => 0, 'available_rooms' => 0, 'total_rooms' => 0, 'checkins_today' => 0, 'checkouts_today' => 0, 'new_customers_today' => 0],
            'revenue_growth' => 0,
            'occupancy_rate' => 0,
            'recent_activities' => [],
            'top_room_types' => [],
            'upcoming_checkins' => []
        ];
    }
}
