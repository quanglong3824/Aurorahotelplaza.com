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
            // Ensure height is always a float, provide a default if null or missing
            $height = isset($g['height_m']) && $g['height_m'] !== null ? (float)$g['height_m'] : 1.0; // Default height if null or missing
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

        // 5. Xác định booking type
        $bookingType = ($requestData['stay_type'] === 'inquiry') ? 'inquiry' : 'instant';

        // 6. Tạo booking code
        $bookingCode = 'AUR' . strtoupper(substr(md5(uniqid()), 0, 8));

        // 6b. Đảm bảo user_id không null (Tự động tạo user nếu là Guest)
        $userId = $requestData['user_id'];
        if (!$userId && !empty($requestData['guest_email'])) {
            $existingUser = $this->userRepo->findByEmail($requestData['guest_email']);
            if ($existingUser) {
                $userId = $existingUser['user_id'];
            } else {
                // Tạo user mới cho khách vãng lai
                $userId = $this->userRepo->create([
                    'email' => $requestData['guest_email'],
                    'full_name' => $requestData['guest_name'],
                    'phone' => $requestData['guest_phone'],
                    'password_hash' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT)
                ]);
            }
        }

        // Nếu vẫn null sau khi thử tạo/tìm (không có email), ném lỗi hoặc dùng ID mặc định
        if (!$userId) {
            throw new Exception("Vui lòng đăng nhập hoặc cung cấp email để đặt phòng.");
        }
        
        // 7. Chuẩn bị dữ liệu đầy đủ cho repository
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
            'payment_method' => $requestData['payment_method'] ?? 'cash',
            'guest_name' => $requestData['guest_name'],
            'guest_phone' => $requestData['guest_phone'],
            'guest_email' => $requestData['guest_email'],
            'special_requests' => $requestData['special_requests'] ?? null,
            // Các trường MỚI thêm vào
            'booking_type' => $bookingType,
            'inquiry_message' => $requestData['inquiry_message'] ?? null,
            'duration_type' => $requestData['duration_type'] ?? null,
            'num_adults' => $requestData['num_adults'],
            'num_children' => $requestData['num_children'] ?? 0,
            'total_nights' => $requestData['num_nights'],
            'room_price' => $pricing['room_total'] ?? 0,
            'extra_guest_fee' => $pricing['extra_guest_fee'] ?? 0,
            'extra_bed_fee' => $pricing['extra_bed_fee'] ?? 0,
            'extra_beds' => $requestData['extra_beds'] ?? 0,
            'occupancy_type' => $pricing['occupancy_type'] ?? 'standard',
            'price_type_used' => $pricing['price_type_used'] ?? 'standard',
            'cancellation_reason' => null
        ];

        // 8. Thực hiện INSERT với transaction
        try {
            $this->bookingRepo->beginTransaction();
            
            $bookingId = $this->bookingRepo->create($bookingData);
            
            // Lưu extra guests data nếu có
            if (!empty($requestData['extra_guests']) && is_array($requestData['extra_guests'])) {
                foreach ($requestData['extra_guests'] as $index => $guest) {
                    $this->bookingRepo->saveExtraGuest($bookingId, $index + 1, $guest);
                }
            }
            
            $this->bookingRepo->commit();
            
            return [
                'success' => true,
                'booking_id' => $bookingId,
                'booking_code' => $bookingCode,
                'booking_type' => $bookingType,
                'pricing' => $pricing,
                'message' => ($bookingType === 'inquiry' ? 'Yêu cầu tư vấn của bạn đã được gửi thành công!' : 'Đơn đặt phòng của bạn đã được ghi nhận thành công.')
            ];
        } catch (\Exception $e) {
            $this->bookingRepo->rollBack();
            throw $e;
        }
    }

    /**
     * Lấy thông tin chi tiết booking theo code
     */
    public function getBookingByCode(string $bookingCode): ?array {
        return $this->bookingRepo->findByCode($bookingCode);
    }

    /**
     * Xác nhận booking
     */
    public function confirmBooking(string $bookingCode, int $userId = null): array {
        try {
            $this->bookingRepo->beginTransaction();
            
            $booking = $this->bookingRepo->findByCode($bookingCode);
            
            if (!$booking) {
                throw new Exception('Không tìm thấy booking');
            }
            
            if ($booking['status'] !== 'pending') {
                throw new Exception('Booking không ở trạng thái pending');
            }
            
            // Kiểm tra ownership nếu có userId
            if ($userId !== null && $booking['user_id'] !== null && $booking['user_id'] != $userId) {
                throw new Exception('Bạn không có quyền xác nhận booking này');
            }
            
            $this->bookingRepo->updateStatus($bookingCode, 'confirmed');
            
            $this->bookingRepo->addHistory(
                $booking['booking_id'],
                'pending',
                'confirmed',
                $userId,
                'Booking confirmed by user'
            );
            
            $this->bookingRepo->commit();
            
            return ['success' => true, 'message' => 'Booking đã được xác nhận'];
        } catch (\Exception $e) {
            $this->bookingRepo->rollBack();
            throw $e;
        }
    }
}
