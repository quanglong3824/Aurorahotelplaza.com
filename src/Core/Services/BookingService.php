<?php
namespace Aurora\Core\Services;

use Aurora\Core\Repositories\RoomRepository;
use Aurora\Core\Repositories\BookingRepository;
use Aurora\Core\Services\PricingService;
use Exception;

/**
 * BookingService - Điều phối toàn bộ luồng đặt phòng (Orchestrator)
 */
class BookingService {
    private RoomRepository $roomRepo;
    private BookingRepository $bookingRepo;
    private PricingService $pricingService;

    public function __construct(RoomRepository $roomRepo, BookingRepository $bookingRepo, PricingService $pricingService) {
        $this->roomRepo = $roomRepo;
        $this->bookingRepo = $bookingRepo;
        $this->pricingService = $pricingService;
    }

    /**
     * Quy trình tạo đơn đặt phòng mới
     */
    public function createBooking(array $requestData): array {
        // 1. Kiểm tra đầu vào cơ bản
        $checkIn = new \DateTime($requestData['check_in']);
        $checkOut = new \DateTime($requestData['check_out']);
        $today = new \DateTime();
        
        $interval = $checkIn->diff($checkOut);
        $totalDays = $interval->invert ? -$interval->days : $interval->days;

        // VÁ LỖI: Chặn đặt phòng quá xa (7759 ngày) hoặc ngày quá khứ
        if ($checkIn < $today->setTime(0,0,0)) {
            throw new Exception(__("booking.err_past_date"));
        }
        if ($totalDays <= 0) {
            throw new Exception(__("booking.err_checkout_before_checkin"));
        }
        if ($totalDays > 365) {
            throw new Exception(__("booking.err_max_stay"));
        }

        // VÁ LỖI: Kiểm tra số lượng khách
        if ($requestData['num_adults'] <= 0) {
            throw new Exception(__("booking.err_invalid_adults"));
        }
        if ($requestData['num_adults'] > 20) {
            throw new Exception(__("booking.err_max_guests"));
        }

        $roomTypeId = $requestData['room_type_id'] ?? 0;
        $roomType = $this->roomRepo->findRoomTypeById($roomTypeId);
        
        if (!$roomType) {
            throw new Exception(__("booking.err_room_not_found"));
        }

        // 2. Kiểm tra phòng trống
        if (!$this->roomRepo->checkAvailability($roomTypeId, $requestData['check_in'], $requestData['check_out'])) {
            throw new Exception(__("booking.err_no_availability"));
        }

        // 3. Chuẩn bị DTO cho khách thêm
        $extraGuests = [];
        foreach (($requestData['extra_guests'] ?? []) as $g) {
            $height = isset($g['height_m']) ? (float)$g['height_m'] : 1.7; // Mặc định 1m7 nếu thiếu
            $extraGuests[] = new \Aurora\Core\DTOs\GuestDTO($height, $g['includes_breakfast'] ?? true);
        }

        // 4. Tính toán tài chính qua PricingService
        $pricing = $this->pricingService->calculateTotal(
            $roomType,
            $requestData['num_nights'],
            $requestData['num_adults'],
            $extraGuests,
            $requestData['extra_beds'] ?? 0,
            $requestData['stay_type'] ?? 'standard'
        );

        // 5. Lưu vào Database (Transaction)
        $bookingCode = 'AUR' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        $bookingId = $this->bookingRepo->create([
            'booking_code' => $bookingCode,
            'user_id' => $requestData['user_id'],
            'room_type_id' => $roomTypeId,
            'check_in_date' => $requestData['check_in'],
            'check_out_date' => $requestData['check_out'],
            'total_amount' => $pricing['total_amount'],
            'guest_name' => $requestData['guest_name'],
            'guest_phone' => $requestData['guest_phone'],
            'guest_email' => $requestData['guest_email'],
            'special_requests' => $requestData['special_requests'],
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash'
        ]);

        if (!$bookingId) {
            throw new Exception("Lỗi không thể tạo bản ghi đặt phòng trong database.");
        }

        return [
            'success' => true,
            'booking_id' => $bookingId,
            'booking_code' => $bookingCode,
            'pricing' => $pricing,
            'message' => 'Đơn đặt phòng đã được chuẩn bị thành công.'
        ];
    }
}
