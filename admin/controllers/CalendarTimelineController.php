<?php
/**
 * Aurora Hotel Plaza - Calendar Timeline Controller
 * Handles data fetching for the bookings timeline page
 */

function getCalendarTimelineData() {
    // Get date range
    $start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
    $days_to_show = 14; // Show 2 weeks

    try {
        $db = getDB();
        
        // Get all rooms with their types
        $stmt = $db->query("
            SELECT r.*, rt.type_name, rt.category
            FROM rooms r
            LEFT JOIN room_types rt ON r.room_type_id = rt.room_type_id
            ORDER BY rt.category, r.room_number
        ");
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get bookings for the date range
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $days_to_show . ' days'));
        
        $stmt = $db->prepare("
            SELECT b.*, u.full_name, r.room_number, rt.type_name
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
            WHERE b.status NOT IN ('cancelled', 'no_show')
            AND (
                (b.check_in_date <= :end_date AND b.check_out_date >= :start_date)
            )
            ORDER BY b.check_in_date ASC
        ");
        
        $stmt->execute([
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ]);
        
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize bookings by room
        $bookings_by_room = [];
        foreach ($bookings as $booking) {
            if ($booking['room_id']) {
                if (!isset($bookings_by_room[$booking['room_id']])) {
                    $bookings_by_room[$booking['room_id']] = [];
                }
                $bookings_by_room[$booking['room_id']][] = $booking;
            }
        }
        
    } catch (Exception $e) {
        error_log("Calendar timeline Controller error: " . $e->getMessage());
        $rooms = [];
        $bookings = [];
        $bookings_by_room = [];
    }

    // Status colors
    $status_colors = [
        'pending' => ['bg' => '#fbbf24', 'text' => '#92400e', 'label' => 'Chờ duyệt'],
        'confirmed' => ['bg' => '#3b82f6', 'text' => '#1e3a8a', 'label' => 'Đã xác nhận'],
        'checked_in' => ['bg' => '#10b981', 'text' => '#065f46', 'label' => 'Đang ở'],
        'checked_out' => ['bg' => '#6b7280', 'text' => '#1f2937', 'label' => 'Đã trả'],
        'completed' => ['bg' => '#8b5cf6', 'text' => '#4c1d95', 'label' => 'Hoàn thành']
    ];

    // Stats calculations
    $occupied = 0;
    foreach ($rooms as $room) {
        if (isset($bookings_by_room[$room['room_id']])) {
            foreach ($bookings_by_room[$room['room_id']] as $booking) {
                if ($booking['check_in_date'] <= date('Y-m-d') && $booking['check_out_date'] >= date('Y-m-d')) {
                    $occupied++;
                    break;
                }
            }
        }
    }
    $occupancy_rate = count($rooms) > 0 ? ($occupied / count($rooms)) * 100 : 0;

    return [
        'start_date' => $start_date,
        'days_to_show' => $days_to_show,
        'rooms' => $rooms,
        'bookings' => $bookings,
        'bookings_by_room' => $bookings_by_room,
        'status_colors' => $status_colors,
        'occupied' => $occupied,
        'occupancy_rate' => $occupancy_rate
    ];
}
