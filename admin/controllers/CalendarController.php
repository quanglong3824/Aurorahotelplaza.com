<?php
/**
 * Aurora Hotel Plaza - Calendar Controller
 * Handles data fetching for the bookings calendar page
 */

function getCalendarData() {
    // Get current month/year or from query
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

    // Calculate first and last day of month
    $first_day = mktime(0, 0, 0, $month, 1, $year);
    $days_in_month = date('t', $first_day);
    $month_name = date('F Y', $first_day);

    try {
        $db = getDB();
        
        $month_start = date('Y-m-01', $first_day);
        $month_end = date('Y-m-t', $first_day);

        $stmt = $db->prepare("
            SELECT b.*, u.full_name, rt.type_name, r.room_number
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE (
                (YEAR(check_in_date) = :year AND MONTH(check_in_date) = :month)
                OR (YEAR(check_out_date) = :year AND MONTH(check_out_date) = :month)
                OR (check_in_date <= :month_end AND check_out_date >= :month_start)
            )
            AND status NOT IN ('cancelled', 'no_show')
            ORDER BY check_in_date ASC
        ");
        
        $stmt->execute([
            ':year' => $year,
            ':month' => $month,
            ':month_start' => $month_start,
            ':month_end' => $month_end
        ]);
        
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize bookings by date
        $bookings_by_date = [];
        foreach ($bookings as $booking) {
            $check_in = strtotime($booking['check_in_date']);
            $check_out = strtotime($booking['check_out_date']);
            
            for ($date = $check_in; $date <= $check_out; $date = strtotime('+1 day', $date)) {
                $date_key = date('Y-m-d', $date);
                if (!isset($bookings_by_date[$date_key])) {
                    $bookings_by_date[$date_key] = [];
                }
                $bookings_by_date[$date_key][] = $booking;
            }
        }
        
    } catch (Exception $e) {
        error_log("Calendar Controller error: " . $e->getMessage());
        $bookings = [];
        $bookings_by_date = [];
    }

    // Navigation
    $prev_month = $month - 1;
    $prev_year = $year;
    if ($prev_month < 1) {
        $prev_month = 12;
        $prev_year--;
    }

    $next_month = $month + 1;
    $next_year = $year;
    if ($next_month > 12) {
        $next_month = 1;
        $next_year++;
    }

    return [
        'month' => $month,
        'year' => $year,
        'first_day' => $first_day,
        'days_in_month' => $days_in_month,
        'month_name' => $month_name,
        'bookings' => $bookings,
        'bookings_by_date' => $bookings_by_date,
        'prev_month' => $prev_month,
        'prev_year' => $prev_year,
        'next_month' => $next_month,
        'next_year' => $next_year
    ];
}
