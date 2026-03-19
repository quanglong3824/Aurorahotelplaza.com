<?php
/**
 * Aurora Hotel Plaza - Create Booking API
 * Refactored to OOP N+ & Removed VNPAY
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/load_env.php';
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../helpers/booking-helper.php';
require_once __DIR__ . '/../../helpers/language.php';

// Load Core OOP Classes
require_once __DIR__ . '/../../src/Core/DTOs/GuestDTO.php';
require_once __DIR__ . '/../../src/Core/Repositories/RoomRepository.php';
require_once __DIR__ . '/../../src/Core/Repositories/BookingRepository.php';
require_once __DIR__ . '/../../src/Core/Services/PricingService.php';
require_once __DIR__ . '/../../src/Core/Services/BookingService.php';

use Aurora\Core\Repositories\RoomRepository;
use Aurora\Core\Repositories\BookingRepository;
use Aurora\Core\Services\PricingService;
use Aurora\Core\Services\BookingService;

initLanguage();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = getDB();
    if (!$db) {
        throw new Exception("Kết nối cơ sở dữ liệu thất bại. Vui lòng kiểm tra lại cấu hình hệ thống.");
    }

    // Initialize OOP Services
    $roomRepo = new RoomRepository($db);
    $bookingRepo = new BookingRepository($db);
    $pricingService = new PricingService();
    $bookingService = new BookingService($roomRepo, $bookingRepo, $pricingService);

    // Get input data
    $input_data = $_POST;
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($content_type, 'application/json') !== false) {
        $input_json = file_get_contents('php://input');
        $input_data = json_decode($input_json, true) ?? [];
    }

    // Prepare Request Data
    $requestData = [
        'room_type_id' => (int)($input_data['room_type_id'] ?? 0),
        'check_in' => sanitize($input_data['check_in_date'] ?? $input_data['check_in'] ?? ''),
        'check_out' => sanitize($input_data['check_out_date'] ?? $input_data['check_out'] ?? ''),
        'num_adults' => (int)($input_data['num_adults'] ?? $input_data['adults'] ?? 2),
        'num_children' => (int)($input_data['num_children'] ?? 0),
        'num_nights' => (int)($input_data['num_nights'] ?? 1),
        'extra_beds' => (int)($input_data['extra_beds'] ?? 0),
        'stay_type' => sanitize($input_data['booking_type'] ?? $input_data['stay_type'] ?? 'standard'),
        'booking_type' => sanitize($input_data['booking_type'] ?? 'standard'),
        'inquiry_message' => sanitize($input_data['inquiry_message'] ?? ''),
        'duration_type' => sanitize($input_data['duration_type'] ?? ''),
        'guest_name' => sanitize($input_data['guest_name'] ?? ''),
        'guest_phone' => sanitize($input_data['guest_phone'] ?? ''),
        'guest_email' => sanitize($input_data['guest_email'] ?? ''),
        'special_requests' => sanitize($input_data['special_requests'] ?? ''),
        'payment_method' => sanitize($input_data['payment_method'] ?? 'cash'),
        'user_id' => $_SESSION['user_id'] ?? null,
        'extra_guests' => []
    ];

    // Parse extra guests from JSON if provided
    $extraGuestsData = $input_data['extra_guests_data'] ?? null;
    if ($extraGuestsData) {
        $extraGuestsJson = is_string($extraGuestsData) ? json_decode($extraGuestsData, true) : $extraGuestsData;
        if (is_array($extraGuestsJson)) {
            $requestData['extra_guests'] = $extraGuestsJson;
        }
    }

    // Execute Booking Process
    $result = $bookingService->createBooking($requestData);

    echo json_encode([
        'success' => true,
        'message' => $result['message'] ?? __('booking_success'),
        'booking_code' => $result['booking_code'] ?? '',
        'booking_id' => $result['booking_id'] ?? 0,
        'booking_type' => $result['booking_type'] ?? 'instant',
        'pricing' => $result['pricing'] ?? [],
        'redirect' => '../confirmation.php?booking_code=' . ($result['booking_code'] ?? '')
    ]);

} catch (Throwable $e) {
    error_log("Booking API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
