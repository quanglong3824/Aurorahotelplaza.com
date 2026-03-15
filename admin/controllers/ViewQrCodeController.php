<?php
/**
 * Aurora Hotel Plaza - View QR Code Controller
 * Handles data fetching for the QR code display page
 */

require_once '../src/Core/Repositories/BookingRepository.php';

use Aurora\Core\Repositories\BookingRepository;

function getViewQrCodeData() {
    $booking_id = $_GET['id'] ?? 0;

    if (!$booking_id) {
        return null;
    }

    try {
        $db = getDB();
        $bookingRepo = new BookingRepository($db);
        
        // Use repository to find booking with details
        $booking = $bookingRepo->findWithDetails((int)$booking_id);

        if (!$booking) {
            return null;
        }

        // QR code URL using local library
        $qr_url = '../profile/api/get-qrcode.php?booking_id=' . $booking_id;

        // Calculate nights
        $check_in = new DateTime($booking['check_in_date']);
        $check_out = new DateTime($booking['check_out_date']);
        $nights = $check_in->diff($check_out)->days;

        // Status labels and classes
        $status_config = [
            'pending' => ['label' => 'Chờ xác nhận', 'class' => 'badge-warning', 'icon' => 'schedule'],
            'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'badge-info', 'icon' => 'check_circle'],
            'checked_in' => ['label' => 'Đã nhận phòng', 'class' => 'badge-success', 'icon' => 'door_open'],
            'checked_out' => ['label' => 'Đã trả phòng', 'class' => 'badge-secondary', 'icon' => 'door_front'],
            'cancelled' => ['label' => 'Đã hủy', 'class' => 'badge-danger', 'icon' => 'cancel'],
            'no_show' => ['label' => 'Không đến', 'class' => 'badge-warning', 'icon' => 'person_off']
        ];

        $payment_status_config = [
            'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'badge-danger'],
            'partial' => ['label' => 'Thanh toán một phần', 'class' => 'badge-warning'],
            'paid' => ['label' => 'Đã thanh toán', 'class' => 'badge-success'],
            'refunded' => ['label' => 'Đã hoàn tiền', 'class' => 'badge-secondary']
        ];

        return [
            'booking_id' => $booking_id,
            'booking' => $booking,
            'qr_url' => $qr_url,
            'nights' => $nights,
            'status_config' => $status_config,
            'payment_status_config' => $payment_status_config
        ];

    } catch (Exception $e) {
        error_log("View QR Controller error: " . $e->getMessage());
        return null;
    }
}
