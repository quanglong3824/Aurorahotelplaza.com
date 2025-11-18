<?php
/**
 * API Endpoint: Export Bookings to CSV
 * Allows users to export their booking history
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../models/Booking.php';

// Get filter parameters
$filters = [
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

try {
    $db = getDB();
    $bookingModel = new Booking($db);
    
    // Get bookings for export
    $bookings = $bookingModel->exportUserBookings($_SESSION['user_id'], $filters);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lich-su-dat-phong-' . date('Y-m-d') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add CSV headers
    fputcsv($output, [
        'Mã đặt phòng',
        'Ngày đặt',
        'Loại phòng',
        'Ngày nhận phòng',
        'Ngày trả phòng',
        'Số đêm',
        'Số người lớn',
        'Số trẻ em',
        'Tổng tiền (VNĐ)',
        'Trạng thái',
        'Trạng thái thanh toán',
        'Phương thức thanh toán',
        'Tên khách',
        'Số điện thoại',
        'Email'
    ]);
    
    // Status translations
    $status_labels = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'checked_in' => 'Đã nhận phòng',
        'checked_out' => 'Đã trả phòng',
        'cancelled' => 'Đã hủy',
        'no_show' => 'Không đến'
    ];
    
    $payment_labels = [
        'unpaid' => 'Chưa thanh toán',
        'partial' => 'Thanh toán một phần',
        'paid' => 'Đã thanh toán',
        'completed' => 'Đã thanh toán',
        'refunded' => 'Đã hoàn tiền'
    ];
    
    $payment_methods = [
        'vnpay' => 'VNPay',
        'cash' => 'Tiền mặt',
        'bank_transfer' => 'Chuyển khoản',
        'credit_card' => 'Thẻ tín dụng'
    ];
    
    // Add data rows
    foreach ($bookings as $booking) {
        fputcsv($output, [
            $booking['booking_code'],
            date('d/m/Y H:i', strtotime($booking['booking_date'])),
            $booking['room_type'],
            date('d/m/Y', strtotime($booking['check_in_date'])),
            date('d/m/Y', strtotime($booking['check_out_date'])),
            $booking['total_nights'],
            $booking['num_adults'],
            $booking['num_children'],
            number_format($booking['total_amount'], 0, ',', '.'),
            $status_labels[$booking['status']] ?? $booking['status'],
            $payment_labels[$booking['payment_status']] ?? $booking['payment_status'] ?? 'N/A',
            $payment_methods[$booking['payment_method']] ?? $booking['payment_method'] ?? 'N/A',
            $booking['guest_name'],
            $booking['guest_phone'],
            $booking['guest_email']
        ]);
    }
    
    fclose($output);
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, entity_type, description, ip_address)
        VALUES (?, 'export_bookings', 'booking', ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        'Xuất lịch sử đặt phòng (' . count($bookings) . ' bản ghi)',
        $_SERVER['REMOTE_ADDR']
    ]);
    
} catch (Exception $e) {
    error_log("Export bookings error: " . $e->getMessage());
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Có lỗi xảy ra khi xuất dữ liệu</h1>';
    echo '<p>Vui lòng thử lại sau.</p>';
    echo '<a href="../bookings.php">Quay lại</a>';
}
?>
