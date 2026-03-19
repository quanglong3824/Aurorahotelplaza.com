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
require_once __DIR__ . '/../../helpers/booking-validator.php';
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
    
    // Initialize OOP Services
    $roomRepo = new RoomRepository($db);
    $bookingRepo = new BookingRepository($db);
    $pricingService = new PricingService();
    $bookingService = new BookingService($roomRepo, $bookingRepo, $pricingService);

    // Get data from JSON body if POST is empty
    $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // Prepare Request Data
    $requestData = [
        'room_type_id' => (int)($inputData['room_type_id'] ?? 0),
        'check_in' => sanitize($inputData['check_in_date'] ?? $inputData['check_in'] ?? ''),
        'check_out' => sanitize($inputData['check_out_date'] ?? $inputData['check_out'] ?? ''),
        'num_adults' => (int)($inputData['num_adults'] ?? $inputData['adults'] ?? 2),
        'num_nights' => (int)($inputData['num_nights'] ?? 1),
        'extra_beds' => (int)($inputData['extra_beds'] ?? 0),
        'stay_type' => sanitize($inputData['duration_type'] ?? $inputData['stay_type'] ?? 'standard'),
        'guest_name' => sanitize($inputData['guest_name'] ?? $inputData['name'] ?? ''),
        'guest_phone' => sanitize($inputData['guest_phone'] ?? $inputData['phone'] ?? ''),
        'guest_email' => sanitize($inputData['guest_email'] ?? $inputData['email'] ?? ''),
        'special_requests' => sanitize($inputData['special_requests'] ?? $inputData['message'] ?? ''),
        'user_id' => $_SESSION['user_id'] ?? null,
        'extra_guests' => []
    ];

    // Parse extra guests from JSON if provided
    if (isset($inputData['extra_guests_data'])) {
        $extraGuestsJson = is_array($inputData['extra_guests_data']) ? $inputData['extra_guests_data'] : json_decode($inputData['extra_guests_data'], true);
        if (is_array($extraGuestsJson)) {
            $requestData['extra_guests'] = $extraGuestsJson;
        }
    }

    // Execute Booking Process
    $result = $bookingService->createBooking($requestData);

    echo json_encode([
        'success' => true,
        'message' => __('booking_success.message'),
        'booking_code' => $result['booking_code'] ?? '',
        'redirect' => '../confirmation.php?code=' . ($result['booking_code'] ?? '')
    ]);

} catch (Exception $e) {
    error_log("Booking API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
