<?php
class ReportsController {
    public function getData() {
        require_once '../config/database.php';
        
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-d');

        try {
            $db = getDB();

            // Revenue statistics
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_bookings,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_booking_value,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
                FROM bookings
                WHERE DATE(created_at) BETWEEN :date_from AND :date_to
            ");
            $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
            $revenue_stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Daily revenue chart data
            $stmt = $db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as bookings,
                    SUM(total_amount) as revenue
                FROM bookings
                WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                AND status != 'cancelled'
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
            $daily_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Room type performance
            $stmt = $db->prepare("
                SELECT 
                    rt.type_name,
                    COUNT(b.booking_id) as bookings,
                    SUM(b.total_amount) as revenue,
                    AVG(b.total_nights) as avg_nights
                FROM bookings b
                JOIN room_types rt ON b.room_type_id = rt.room_type_id
                WHERE DATE(b.created_at) BETWEEN :date_from AND :date_to
                AND b.status != 'cancelled'
                GROUP BY rt.room_type_id
                ORDER BY revenue DESC
            ");
            $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
            $room_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Top customers
            $stmt = $db->prepare("
                SELECT 
                    u.full_name,
                    u.email,
                    COUNT(b.booking_id) as total_bookings,
                    SUM(b.total_amount) as total_spent
                FROM bookings b
                JOIN users u ON b.user_id = u.user_id
                WHERE DATE(b.created_at) BETWEEN :date_from AND :date_to
                AND b.status != 'cancelled'
                GROUP BY u.user_id
                ORDER BY total_spent DESC
                LIMIT 10
            ");
            $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
            $top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Booking status distribution
            $stmt = $db->prepare("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM bookings
                WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                GROUP BY status
            ");
            $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
            $status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Payment method distribution
            $stmt = $db->prepare("
                SELECT 
                    payment_method,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM payments
                WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                AND status = 'completed'
                GROUP BY payment_method
            ");
            $stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
            $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Occupancy rate
            $stmt = $db->prepare("
                SELECT 
                    COUNT(DISTINCT r.room_id) as total_rooms,
                    COUNT(DISTINCT CASE WHEN r.status = 'occupied' THEN r.room_id END) as occupied_rooms
                FROM rooms r
            ");
            $stmt->execute();
            $occupancy = $stmt->fetch(PDO::FETCH_ASSOC);
            $occupancy_rate = $occupancy['total_rooms'] > 0 ?
                ($occupancy['occupied_rooms'] / $occupancy['total_rooms']) * 100 : 0;

        } catch (Exception $e) {
            error_log("Reports page error: " . $e->getMessage());
            $revenue_stats = ['total_bookings' => 0, 'total_revenue' => 0, 'avg_booking_value' => 0, 'cancelled_bookings' => 0];
            $daily_revenue = [];
            $room_performance = [];
            $top_customers = [];
            $status_distribution = [];
            $payment_methods = [];
            $occupancy_rate = 0;
            $occupancy = ['total_rooms' => 0, 'occupied_rooms' => 0];
        }

        return [
            'date_from' => $date_from,
            'date_to' => $date_to,
            'revenue_stats' => $revenue_stats,
            'daily_revenue' => $daily_revenue,
            'room_performance' => $room_performance,
            'top_customers' => $top_customers,
            'status_distribution' => $status_distribution,
            'payment_methods' => $payment_methods,
            'occupancy' => $occupancy,
            'occupancy_rate' => $occupancy_rate
        ];
    }
}
