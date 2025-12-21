<?php
/**
 * Aurora Hotel Plaza - Calculate Booking Price API
 * File: booking/api/calculate_price.php
 * Date: 2025-12-21
 * Description: API tính giá đặt phòng theo nghiệp vụ lễ tân
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';
require_once '../../helpers/pricing_calculator.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $db = getDB();

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input)) {
        $input = $_POST;
    }

    // Validate required fields
    $roomTypeId = $input['room_type_id'] ?? null;
    $checkIn = $input['check_in'] ?? null;
    $checkOut = $input['check_out'] ?? null;

    if (empty($roomTypeId)) {
        throw new Exception('Vui lòng chọn loại phòng');
    }

    // Get room type information
    $stmt = $db->prepare("SELECT * FROM room_types WHERE room_type_id = ?");
    $stmt->execute([$roomTypeId]);
    $roomType = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$roomType) {
        throw new Exception('Loại phòng không tồn tại');
    }

    // Default values
    $numAdults = max(1, (int) ($input['num_adults'] ?? 2));
    $numChildren = max(0, (int) ($input['num_children'] ?? 0));
    $numRooms = max(1, (int) ($input['num_rooms'] ?? 1));
    $extraBeds = max(0, (int) ($input['extra_beds'] ?? 0));
    $bookingType = $input['booking_type'] ?? 'standard'; // 'standard', 'short_stay', 'weekly'
    $extraGuests = $input['extra_guests'] ?? []; // Array of {'height_m': float, 'includes_breakfast': bool}

    // Calculate number of nights
    if ($bookingType === 'short_stay') {
        // Short stay không cần ngày checkout, tính theo giá cố định
        $numNights = 0;
        $checkInDate = new DateTime($checkIn ?? 'now');
        $checkOutDate = clone $checkInDate;
    } else {
        if (empty($checkIn) || empty($checkOut)) {
            throw new Exception('Vui lòng chọn ngày nhận phòng và trả phòng');
        }

        $checkInDate = new DateTime($checkIn);
        $checkOutDate = new DateTime($checkOut);

        if ($checkInDate >= $checkOutDate) {
            throw new Exception('Ngày trả phòng phải sau ngày nhận phòng');
        }

        $numNights = $checkInDate->diff($checkOutDate)->days;
    }

    // Check if weekly rate should apply
    if ($roomType['category'] === 'apartment' && $numNights >= 7 && $bookingType === 'standard') {
        $bookingType = 'weekly';
    }

    // Calculate room price
    $roomPriceResult = calculateRoomPrice(
        $roomType,
        $numAdults,
        max(1, $numNights),
        $bookingType
    );

    $roomPricePerUnit = $roomPriceResult['price'];
    $priceType = $roomPriceResult['price_type'];

    // Total room price (for all rooms)
    $totalRoomPrice = $roomPricePerUnit * $numRooms;

    // Calculate extra guest fees
    $extraGuestFee = 0;
    $extraGuestDetails = [];

    foreach ($extraGuests as $guest) {
        $guestFee = calculateExtraGuestFee(
            (float) ($guest['height_m'] ?? 1.7),
            (bool) ($guest['includes_breakfast'] ?? true)
        );

        // Per night fee
        $nightMultiplier = $bookingType === 'short_stay' ? 1 : max(1, $numNights);
        $totalGuestFee = $guestFee['fee'] * $nightMultiplier;

        $extraGuestFee += $totalGuestFee;
        $extraGuestDetails[] = [
            'category' => $guestFee['category'],
            'fee_per_night' => $guestFee['fee'],
            'total_fee' => $totalGuestFee,
            'includes_breakfast' => $guestFee['includes_breakfast']
        ];
    }

    // Calculate extra bed fee
    $extraBedFee = calculateExtraBedFee(
        $extraBeds,
        max(1, $numNights),
        $roomType['category']
    );

    // Subtotal
    $subtotal = $totalRoomPrice + $extraGuestFee + $extraBedFee;

    // Tax and service fee (already included in room price per policy)
    $taxIncluded = true;
    $taxRate = 0.08; // 8% VAT
    $serviceRate = 0.05; // 5% service charge
    $tax = 0;
    $serviceFee = 0;

    // Total amount
    $totalAmount = $subtotal + $tax + $serviceFee;

    // Build response
    $response = [
        'success' => true,
        'data' => [
            // Room info
            'room_type_id' => $roomType['room_type_id'],
            'room_type_name' => $roomType['type_name'],
            'category' => $roomType['category'],
            'booking_type' => $bookingType,

            // Date info
            'check_in' => $checkInDate->format('Y-m-d'),
            'check_out' => $checkOutDate->format('Y-m-d'),
            'num_nights' => $numNights,

            // Occupancy
            'num_adults' => $numAdults,
            'num_children' => $numChildren,
            'num_rooms' => $numRooms,
            'extra_beds' => $extraBeds,

            // Pricing breakdown
            'price_type' => $priceType,
            'price_type_label' => getPriceTypeLabel($priceType, 'vi'),
            'price_per_night' => $roomPriceResult['price_per_night'] ?? $roomPricePerUnit,
            'room_price' => $roomPricePerUnit,
            'room_total' => $totalRoomPrice,

            // Extra fees
            'extra_guest_fee' => $extraGuestFee,
            'extra_guest_details' => $extraGuestDetails,
            'extra_bed_fee' => $extraBedFee,

            // Totals
            'subtotal' => $subtotal,
            'tax' => $tax,
            'service_fee' => $serviceFee,
            'tax_included' => $taxIncluded,
            'total_amount' => $totalAmount,

            // Formatted prices
            'formatted' => [
                'price_per_night' => formatCurrency($roomPriceResult['price_per_night'] ?? $roomPricePerUnit),
                'room_price' => formatCurrency($roomPricePerUnit),
                'room_total' => formatCurrency($totalRoomPrice),
                'extra_guest_fee' => formatCurrency($extraGuestFee),
                'extra_bed_fee' => formatCurrency($extraBedFee),
                'subtotal' => formatCurrency($subtotal),
                'tax' => formatCurrency($tax),
                'service_fee' => formatCurrency($serviceFee),
                'total_amount' => formatCurrency($totalAmount)
            ],

            // Room type pricing details (for display)
            'pricing' => [
                'published' => (float) ($roomType['price_published'] ?? 0),
                'single' => (float) ($roomType['price_single_occupancy'] ?? 0),
                'double' => (float) ($roomType['price_double_occupancy'] ?? 0),
                'short_stay' => (float) ($roomType['price_short_stay'] ?? 0),
                'daily_single' => (float) ($roomType['price_daily_single'] ?? 0),
                'daily_double' => (float) ($roomType['price_daily_double'] ?? 0),
                'weekly_single' => (float) ($roomType['price_weekly_single'] ?? 0),
                'weekly_double' => (float) ($roomType['price_weekly_double'] ?? 0)
            ],

            // Policies
            'policies' => [
                'short_stay_available' => $roomType['category'] === 'room' && !empty($roomType['price_short_stay']),
                'weekly_rate_available' => $roomType['category'] === 'apartment',
                'extra_bed_available' => $roomType['category'] === 'room',
                'extra_bed_price' => 650000
            ]
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
