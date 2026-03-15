<?php
namespace Aurora\Core\Services;

use Aurora\Core\DTOs\GuestDTO;

/**
 * PricingService - Lõi tính toán tài chính của khách sạn (OOP N+)
 * Đảm bảo tính chính xác tuyệt đối và dễ mở rộng (Clean Code)
 */
class PricingService {
    private const EXTRA_BED_PRICE = 650000;
    private const CHILD_FEE_1M_1M3 = 200000;
    private const ADULT_ADDITIONAL_FEE = 400000;

    /**
     * Tính tổng chi phí đặt phòng
     * 
     * @param array $roomType Dữ liệu từ DB
     * @param int $numNights Số đêm (0 nếu nghỉ giờ)
     * @param int $numAdults Số người lớn mặc định
     * @param array $extraGuests Danh sách GuestDTO
     * @param int $extraBeds Số giường phụ
     * @param string $stayType 'standard', 'short_stay', 'weekly'
     * @return array Kết quả chi tiết tài chính
     */
    public function calculateTotal(
        array $roomType, 
        int $numNights, 
        int $numAdults, 
        array $extraGuests = [], 
        int $extraBeds = 0, 
        string $stayType = 'standard'
    ): array {
        
        $numNights = max($stayType === 'short_stay' ? 0 : 1, $numNights);

        // 1. Tính giá gốc của phòng
        $basePrice = $this->calculateBaseRoomPrice($roomType, $numNights, $numAdults, $stayType);

        // 2. Tính phí khách thêm (Dựa trên chiều cao)
        $extraGuestFee = $this->calculateExtraGuestFees($extraGuests, $numNights, $stayType);

        // 3. Tính phí giường phụ (Chỉ cho Hotel Room)
        $extraBedFee = 0;
        if (($roomType['category'] ?? 'room') === 'room') {
            $extraBedFee = $extraBeds * self::EXTRA_BED_PRICE * max(1, $numNights);
        }

        $subtotal = $basePrice + $extraGuestFee + $extraBedFee;
        
        return [
            'base_room_price' => $basePrice,
            'extra_guest_total' => $extraGuestFee,
            'extra_bed_total' => $extraBedFee,
            'subtotal' => $subtotal,
            'tax' => 0, // VAT có thể tách ra đây nếu cần
            'total_amount' => $subtotal,
            'currency' => 'VND'
        ];
    }

    private function calculateBaseRoomPrice($roomType, $numNights, $numAdults, $stayType): float {
        if ($stayType === 'short_stay') {
            return (float)($roomType['price_short_stay'] ?? $roomType['base_price']);
        }

        // Ưu tiên giá Single nếu chỉ có 1 người lớn
        if ($numAdults === 1 && !empty($roomType['price_single_occupancy'])) {
            $dailyPrice = (float)$roomType['price_single_occupancy'];
        } else {
            $dailyPrice = (float)($roomType['price_double_occupancy'] ?? $roomType['base_price']);
        }

        // Logic cho căn hộ (giá tuần)
        if ($stayType === 'weekly' && $numNights >= 7) {
            $weeklyPrice = ($numAdults === 1) ? 
                ($roomType['price_avg_weekly_single'] ?? $dailyPrice) : 
                ($roomType['price_avg_weekly_double'] ?? $dailyPrice);
            return (float)$weeklyPrice * $numNights;
        }

        return $dailyPrice * $numNights;
    }

    private function calculateExtraGuestFees(array $guests, int $numNights, string $stayType): float {
        $total = 0;
        foreach ($guests as $guest) {
            if (!($guest instanceof GuestDTO)) continue;

            $feePerNight = 0;
            switch ($guest->category) {
                case 'child_1m_1m3':
                    $feePerNight = self::CHILD_FEE_1M_1M3;
                    break;
                case 'adult':
                    $feePerNight = self::ADULT_ADDITIONAL_FEE;
                    break;
                case 'child_under_1m':
                default:
                    $feePerNight = 0;
                    break;
            }
            
            $multiplier = ($stayType === 'short_stay') ? 1 : max(1, $numNights);
            $total += $feePerNight * $multiplier;
        }
        return $total;
    }
}
