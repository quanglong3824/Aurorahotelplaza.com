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
require_once __DIR__ . '/../../src/Core/Repositories/UserRepository.php';
require_once __DIR__ . '/../../src/Core/Services/PricingService.php';
require_once __DIR__ . '/../../src/Core/Services/BookingService.php';

use Aurora\Core\Repositories\RoomRepository;
use Aurora\Core\Repositories\BookingRepository;
use Aurora\Core\Repositories\UserRepository;
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
    $userRepo = new UserRepository($db);
    $pricingService = new PricingService();
    $bookingService = new BookingService($roomRepo, $bookingRepo, $userRepo, $pricingService);

    // Prepare Request Data
    $requestData = [
        'room_type_id' => (int)($_POST['room_type_id'] ?? 0),
        'check_in' => sanitize($_POST['check_in'] ?? ''),
        'check_out' => sanitize($_POST['check_out'] ?? ''),
        'num_adults' => (int)($_POST['adults'] ?? 2),
        'num_nights' => (int)($_POST['num_nights'] ?? 1),
        'extra_beds' => (int)($_POST['extra_beds'] ?? 0),
        'stay_type' => sanitize($_POST['stay_type'] ?? 'standard'),
        'guest_name' => sanitize($_POST['guest_name'] ?? ''),
        'guest_phone' => sanitize($_POST['guest_phone'] ?? ''),
        'guest_email' => sanitize($_POST['guest_email'] ?? ''),
        'special_requests' => sanitize($_POST['special_requests'] ?? ''),
        'user_id' => $_SESSION['user_id'] ?? null,
        'extra_guests' => [] // Will be populated below
    ];

    // Parse extra guests from JSON if provided
    if (isset($_POST['extra_guests_data'])) {
        $extraGuestsJson = json_decode($_POST['extra_guests_data'], true);
        if (is_array($extraGuestsJson)) {
            $requestData['extra_guests'] = $extraGuestsJson;
        }
    }

    // Execute Booking Process
    $result = $bookingService->createBooking($requestData);

    echo json_encode([
        'success' => true,
        'message' => $result['message'] ?? 'Đặt phòng thành công!',
        'booking_code' => $result['booking_code'] ?? '',
        'redirect' => '../confirmation.php?booking_code=' . ($result['booking_code'] ?? '')
    ]);

} catch (Exception $e) {
    error_log("Booking API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
