<?php
/**
 * Aurora Hotel Plaza - Pricing Calculator Helper
 * File: helpers/pricing_calculator.php
 * Date: 2025-12-21
 * Description: Hàm tính giá phòng theo nghiệp vụ lễ tân
 */

/**
 * Tính giá phòng khách sạn
 * 
 * @param array $roomType Thông tin loại phòng từ room_types
 * @param int $numAdults Số người lớn
 * @param int $numNights Số đêm (0 cho short stay)
 * @param string $bookingType 'standard', 'short_stay', 'weekly'
 * @return array ['price' => giá, 'price_type' => loại giá áp dụng]
 */
function calculateRoomPrice($roomType, $numAdults = 2, $numNights = 1, $bookingType = 'standard')
{
    $price = 0;
    $priceType = 'double';

    $category = $roomType['category'] ?? 'room';

    if ($category === 'room') {
        // Phòng khách sạn
        if ($bookingType === 'short_stay' && !empty($roomType['price_short_stay'])) {
            // Giá nghỉ ngắn hạn (không nhân số đêm)
            $price = (float) $roomType['price_short_stay'];
            $priceType = 'short_stay';
        } else {
            // Giá theo số người
            if ($numAdults == 1 && !empty($roomType['price_single_occupancy'])) {
                $price = (float) $roomType['price_single_occupancy'] * $numNights;
                $priceType = 'single';
            } else {
                // Mặc định giá 2 người
                $price = !empty($roomType['price_double_occupancy'])
                    ? (float) $roomType['price_double_occupancy'] * $numNights
                    : (float) $roomType['base_price'] * $numNights;
                $priceType = 'double';
            }
        }
    } else {
        // Căn hộ
        if ($bookingType === 'weekly' && $numNights >= 7) {
            // Giá tuần (dùng giá trung bình/đêm)
            if ($numAdults == 1 && !empty($roomType['price_avg_weekly_single'])) {
                $price = (float) $roomType['price_avg_weekly_single'] * $numNights;
                $priceType = 'weekly';
            } elseif (!empty($roomType['price_avg_weekly_double'])) {
                $price = (float) $roomType['price_avg_weekly_double'] * $numNights;
                $priceType = 'weekly';
            } else {
                // Fallback to weekly total price
                if ($numAdults == 1 && !empty($roomType['price_weekly_single'])) {
                    $weeks = floor($numNights / 7);
                    $extraDays = $numNights % 7;
                    $price = ((float) $roomType['price_weekly_single'] * $weeks) +
                        ((float) ($roomType['price_daily_single'] ?? $roomType['base_price']) * $extraDays);
                    $priceType = 'weekly';
                } elseif (!empty($roomType['price_weekly_double'])) {
                    $weeks = floor($numNights / 7);
                    $extraDays = $numNights % 7;
                    $price = ((float) $roomType['price_weekly_double'] * $weeks) +
                        ((float) ($roomType['price_daily_double'] ?? $roomType['base_price']) * $extraDays);
                    $priceType = 'weekly';
                }
            }
        } else {
            // Giá ngày
            if ($numAdults == 1 && !empty($roomType['price_daily_single'])) {
                $price = (float) $roomType['price_daily_single'] * $numNights;
                $priceType = 'daily';
            } elseif (!empty($roomType['price_daily_double'])) {
                $price = (float) $roomType['price_daily_double'] * $numNights;
                $priceType = 'daily';
            } else {
                $price = (float) $roomType['base_price'] * $numNights;
                $priceType = 'daily';
            }
        }
    }

    return [
        'price' => $price,
        'price_type' => $priceType,
        'price_per_night' => $numNights > 0 ? $price / $numNights : $price
    ];
}

/**
 * Tính phí khách thêm dựa trên chiều cao
 * Phí này là PHÍ/ĐÊM - sẽ được nhân với số đêm ở nơi gọi hàm
 * 
 * @param float $heightM Chiều cao tính bằng mét
 * @param bool $includeBreakfast Có bao gồm bữa sáng không (mặc định có)
 * @return array ['fee' => phí/đêm, 'category' => phân loại]
 */
function calculateExtraGuestFee($heightM, $includeBreakfast = true)
{
    // Theo bảng giá lễ tân - PHÍ/ĐÊM (bao gồm ăn sáng)
    $fee = 0;
    $category = 'adult';

    if ($heightM < 1.0) {
        // Trẻ em dưới 1m - Miễn phí (bao gồm ăn sáng)
        $fee = 0;
        $category = 'child_under_1m';
    } elseif ($heightM >= 1.0 && $heightM < 1.3) {
        // Trẻ em 1m - 1m3 - 200,000 VND/đêm (bao gồm ăn sáng)
        $fee = 200000;
        $category = 'child_1m_1m3';
    } else {
        // Người lớn và trẻ trên 1m3 - 400,000 VND/đêm (bao gồm ăn sáng)
        $fee = 400000;
        $category = 'adult';
    }

    return [
        'fee' => $fee,           // Phí/đêm
        'category' => $category,
        'includes_breakfast' => $includeBreakfast
    ];
}

/**
 * Tính phí giường phụ
 * 
 * @param int $numExtraBeds Số giường phụ
 * @param int $numNights Số đêm
 * @param string $category 'room' hoặc 'apartment'
 * @return float Phí giường phụ
 */
function calculateExtraBedFee($numExtraBeds, $numNights = 1, $category = 'room')
{
    // Giường phụ không áp dụng cho căn hộ
    if ($category === 'apartment') {
        return 0;
    }

    $extraBedPrice = 650000; // VND per night
    return $numExtraBeds * $extraBedPrice * $numNights;
}

/**
 * Tính tổng tiền đặt phòng
 * 
 * @param array $params [
 *   'room_type' => array thông tin loại phòng,
 *   'check_in' => 'Y-m-d' hoặc DateTime,
 *   'check_out' => 'Y-m-d' hoặc DateTime,
 *   'num_adults' => int,
 *   'num_children' => int,
 *   'extra_beds' => int,
 *   'booking_type' => 'standard', 'short_stay', 'weekly',
 *   'extra_guests' => array [['height_m' => float, 'includes_breakfast' => bool], ...]
 * ]
 * @return array Chi tiết giá
 */
function calculateBookingTotal($params)
{
    $roomType = $params['room_type'];
    $checkIn = $params['check_in'] instanceof DateTime ? $params['check_in'] : new DateTime($params['check_in']);
    $checkOut = $params['check_out'] instanceof DateTime ? $params['check_out'] : new DateTime($params['check_out']);
    $numAdults = $params['num_adults'] ?? 2;
    $numChildren = $params['num_children'] ?? 0;
    $extraBeds = $params['extra_beds'] ?? 0;
    $bookingType = $params['booking_type'] ?? 'standard';
    $extraGuests = $params['extra_guests'] ?? [];

    // Tính số đêm
    $numNights = max(1, $checkIn->diff($checkOut)->days);
    if ($bookingType === 'short_stay') {
        $numNights = 0; // Short stay không tính theo đêm
    }

    // Tính giá phòng
    $roomPriceResult = calculateRoomPrice($roomType, $numAdults, max(1, $numNights), $bookingType);
    $roomPrice = $roomPriceResult['price'];
    $priceType = $roomPriceResult['price_type'];

    // Tính phí khách thêm
    $extraGuestFee = 0;
    $extraGuestDetails = [];
    foreach ($extraGuests as $guest) {
        $guestFee = calculateExtraGuestFee(
            $guest['height_m'] ?? 1.7,
            $guest['includes_breakfast'] ?? true
        );
        $extraGuestFee += $guestFee['fee'] * ($bookingType === 'short_stay' ? 1 : max(1, $numNights));
        $extraGuestDetails[] = $guestFee;
    }

    // Tính phí giường phụ
    $category = $roomType['category'] ?? 'room';
    $extraBedFee = calculateExtraBedFee($extraBeds, max(1, $numNights), $category);

    // Tính tổng tiền trước thuế
    $subtotal = $roomPrice + $extraGuestFee + $extraBedFee;

    // Thuế và phí dịch vụ (đã bao gồm trong giá niêm yết theo bảng giá lễ tân)
    // Nếu muốn tách ra: $taxRate = 0.08 (8% VAT), $serviceFeeRate = 0.05 (5% service)
    $taxIncluded = true;
    $tax = 0;
    $serviceFee = 0;

    // Tổng tiền
    $totalAmount = $subtotal + $tax + $serviceFee;

    return [
        'room_price' => $roomPrice,
        'price_type' => $priceType,
        'price_per_night' => $roomPriceResult['price_per_night'],
        'num_nights' => $bookingType === 'short_stay' ? 0 : $numNights,
        'extra_guest_fee' => $extraGuestFee,
        'extra_guest_details' => $extraGuestDetails,
        'extra_bed_fee' => $extraBedFee,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'service_fee' => $serviceFee,
        'tax_included' => $taxIncluded,
        'total_amount' => $totalAmount,
        'currency' => 'VNĐ'
    ];
}

/**
 * Format tiền tệ VND
 * 
 * @param float $amount Số tiền
 * @param bool $includeSymbol Có thêm ký hiệu tiền tệ không
 * @return string
 */
function formatCurrency($amount, $includeSymbol = true)
{
    $formatted = number_format($amount, 0, ',', '.');
    return $includeSymbol ? $formatted . ' VNĐ' : $formatted;
}

/**
 * Lấy mô tả loại giá
 * 
 * @param string $priceType 'published', 'single', 'double', 'short_stay', 'daily', 'weekly'
 * @param string $lang 'vi' hoặc 'en'
 * @return string
 */
function getPriceTypeLabel($priceType, $lang = 'vi')
{
    $labels = [
        'vi' => [
            'published' => 'Giá công bố',
            'single' => 'Giá phòng đơn (1 người)',
            'double' => 'Giá phòng đôi (2 người)',
            'short_stay' => 'Giá nghỉ ngắn hạn',
            'daily' => 'Giá theo ngày',
            'weekly' => 'Giá theo tuần'
        ],
        'en' => [
            'published' => 'Published Rate',
            'single' => 'Single Occupancy',
            'double' => 'Double Occupancy',
            'short_stay' => 'Short Stay Rate',
            'daily' => 'Daily Rate',
            'weekly' => 'Weekly Rate'
        ]
    ];

    return $labels[$lang][$priceType] ?? $priceType;
}

/**
 * Lấy tất cả chính sách giá từ database
 * 
 * @param PDO $pdo
 * @return array
 */
function getPricingPolicies($pdo)
{
    $sql = "SELECT * FROM pricing_policies WHERE is_active = 1 ORDER BY sort_order";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Kiểm tra xem có áp dụng giá tuần không
 * 
 * @param int $numNights Số đêm
 * @return bool
 */
function shouldApplyWeeklyRate($numNights)
{
    return $numNights >= 7;
}

/**
 * Tính giá hiển thị cho danh sách phòng
 * 
 * @param array $roomType Thông tin loại phòng
 * @return array ['display_price' => giá hiển thị, 'original_price' => giá gốc, 'discount' => giảm giá %]
 */
function getDisplayPrice($roomType)
{
    $category = $roomType['category'] ?? 'room';

    if ($category === 'room') {
        // Hiển thị giá giảm (2 người) và giá gốc (công bố)
        $displayPrice = $roomType['price_double_occupancy'] ?? $roomType['base_price'];
        $originalPrice = $roomType['price_published'] ?? $displayPrice;
        $discount = $originalPrice > $displayPrice
            ? round((($originalPrice - $displayPrice) / $originalPrice) * 100)
            : 0;
    } else {
        // Căn hộ: Hiển thị giá từ (1 người/ngày)
        $displayPrice = $roomType['price_daily_single'] ?? $roomType['base_price'];
        $originalPrice = $displayPrice;
        $discount = 0;
    }

    return [
        'display_price' => (float) $displayPrice,
        'original_price' => (float) $originalPrice,
        'discount' => $discount,
        'from_price' => $category === 'apartment' // Hiển thị "Từ" cho căn hộ
    ];
}

/**
 * Lấy giá theo loại phòng và loại đặt phòng
 * 
 * @param array $roomType Thông tin loại phòng
 * @param string $bookingType 'standard', 'short_stay'
 * @param int $occupancy 1 hoặc 2
 * @return float
 */
function getRoomRate($roomType, $bookingType = 'standard', $occupancy = 2)
{
    $category = $roomType['category'] ?? 'room';

    if ($bookingType === 'short_stay' && !empty($roomType['price_short_stay'])) {
        return (float) $roomType['price_short_stay'];
    }

    if ($category === 'room') {
        if ($occupancy == 1 && !empty($roomType['price_single_occupancy'])) {
            return (float) $roomType['price_single_occupancy'];
        }
        return (float) ($roomType['price_double_occupancy'] ?? $roomType['base_price']);
    } else {
        // Căn hộ
        if ($occupancy == 1 && !empty($roomType['price_daily_single'])) {
            return (float) $roomType['price_daily_single'];
        }
        return (float) ($roomType['price_daily_double'] ?? $roomType['base_price']);
    }
}
