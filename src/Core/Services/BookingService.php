<?php
namespace Aurora\Core\Services;

use Aurora\Core\Repositories\RoomRepository;
use Aurora\Core\Services\PricingService;
use Exception;

/**
 * BookingService - Điều phối toàn bộ luồng đặt phòng (Orchestrator)
 */
class BookingService {
    private RoomRepository $roomRepo;
    private PricingService $pricingService;

    public function __construct(RoomRepository $roomRepo, PricingService $pricingService) {
        $this->roomRepo = $roomRepo;
        $this->pricingService = $pricingService;
    }

    /**
     * Quy trình tạo đơn đặt phòng mới
     */
    public function createBooking(array $requestData): array {
        // 1. Kiểm tra đầu vào cơ bản
        $roomTypeId = $requestData['room_type_id'] ?? 0;
        $roomType = $this->roomRepo->findRoomTypeById($roomTypeId);
        
        if (!$roomType) {
            throw new Exception("Loại phòng không tồn tại.");
        }

        // 2. Kiểm tra phòng trống
        if (!$this->roomRepo->checkAvailability($roomTypeId, $requestData['check_in'], $requestData['check_out'])) {
            throw new Exception("Loại phòng này đã hết trong thời gian bạn chọn.");
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

        // 5. Lưu vào Database (Transaction)
        // [Logic lưu DB sẽ được thực hiện tại đây qua BookingRepository]

        return [
            'success' => true,
            'pricing' => $pricing,
            'message' => 'Đơn đặt phòng đã được chuẩn bị thành công.'
        ];
    }
}
