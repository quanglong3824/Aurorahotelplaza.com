<?php
/**
 * Aurora Hotel Plaza - Booking Detail Controller
 */

require_once '../config/database.php';
require_once '../src/Core/Repositories/BookingRepository.php';

use Aurora\Core\Repositories\BookingRepository;

class BookingDetailController {
    private BookingRepository $bookingRepo;

    public function __construct() {
        $db = getDB();
        $this->bookingRepo = new BookingRepository($db);
    }

    public function getData() {
        $booking_id = $_GET['id'] ?? null;

        if (!$booking_id) {
            header('Location: bookings.php');
            exit;
        }

        try {
            $booking = $this->bookingRepo->findWithDetails($booking_id);

            if (!$booking) {
                header('Location: bookings.php');
                exit;
            }

            $history = $this->bookingRepo->getHistory($booking_id);
            $payments = $this->bookingRepo->getPayments($booking_id);
            $services = $this->bookingRepo->getServices($booking_id);

            return [
                'booking' => $booking,
                'history' => $history,
                'payments' => $payments,
                'services' => $services,
                'booking_id' => $booking_id,
                'page_title' => 'Chi tiết đặt phòng #' . $booking['booking_code'],
                'page_subtitle' => 'Thông tin chi tiết và quản lý đơn đặt phòng'
            ];

        } catch (Exception $e) {
            error_log("Booking detail controller error: " . $e->getMessage());
            header('Location: bookings.php');
            exit;
        }
    }
}
