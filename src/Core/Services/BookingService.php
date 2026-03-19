<?php
namespace Aurora\Core\Services;

use Aurora\Core\Repositories\RoomRepository;
use Aurora\Core\Repositories\BookingRepository;
use Aurora\Core\Repositories\UserRepository;
use Aurora\Core\Services\PricingService;
use Exception;

/**
 * BookingService - Điều phối toàn bộ luồng đặt phòng (Orchestrator)
 */
class BookingService {
    private RoomRepository $roomRepo;
    private BookingRepository $bookingRepo;
    private UserRepository $userRepo;
    private PricingService $pricingService;

    public function __construct(RoomRepository $roomRepo, BookingRepository $bookingRepo, UserRepository $userRepo, PricingService $pricingService) {
        $this->roomRepo = $roomRepo;
        $this->bookingRepo = $bookingRepo;
        $this->userRepo = $userRepo;
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
            $extraGuests[] = new \Aurora\Core\DTOs\GuestDTO($g['height_m'], $g['includes_breakfast'] ?? true);
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

        // 5. Chuẩn bị dữ liệu lưu database
        $bookingCode = 'AUR' . strtoupper(substr(md5(uniqid()), 0, 8));

        // Tự động tạo user cho khách vãng lai nếu cần
        $userId = $requestData['user_id'];
        if (!$userId && !empty($requestData['guest_email'])) {
            $existingUser = $this->userRepo->findByEmail($requestData['guest_email']);
            if ($existingUser) {
                $userId = $existingUser['user_id'];
            } else {
                $userId = $this->userRepo->create([
                    'email' => $requestData['guest_email'],
                    'full_name' => $requestData['guest_name'],
                    'phone' => $requestData['guest_phone'],
                    'password_hash' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT)
                ]);
            }
        }

        if (!$userId) {
            throw new Exception("Vui lòng cung cấp thông tin liên hệ để đặt phòng.");
        }

        $bookingData = [
            'booking_code' => $bookingCode,
            'user_id' => $userId,
            'room_id' => null,
            'room_type_id' => $roomTypeId,
            'check_in_date' => $requestData['check_in'],
            'check_out_date' => $requestData['check_out'],
            'total_amount' => $pricing['total_amount'],
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'guest_name' => $requestData['guest_name'],
            'guest_phone' => $requestData['guest_phone'],
            'guest_email' => $requestData['guest_email'],
            'special_requests' => $requestData['special_requests'] ?? null
        ];

        try {
            $bookingId = $this->bookingRepo->create($bookingData);
            
            return [
                'success' => true,
                'booking_id' => $bookingId,
                'booking_code' => $bookingCode,
                'pricing' => $pricing,
                'message' => 'Đơn đặt phòng của bạn đã được ghi nhận thành công.'
            ];
        } catch (\Exception $e) {
            throw new Exception("Lỗi khi lưu đơn đặt phòng: " . $e->getMessage());
        }
    }
}
