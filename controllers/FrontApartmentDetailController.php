<?php

require_once __DIR__ . '/../src/Core/Repositories/RoomRepository.php';

use Aurora\Core\Repositories\RoomRepository;

class FrontApartmentDetailController {
    private RoomRepository $roomRepo;

    public function __construct() {
        $db = getDB();
        $this->roomRepo = new RoomRepository($db);
    }

    /**
     * Get apartment detail data
     * 
     * @param string $slug The room type slug
     * @param float $defaultPrice Default price if not found in DB
     * @return array
     */
    public function getData(string $slug, float $defaultPrice = 0): array {
        try {
            $room_data = $this->roomRepo->findBySlug($slug);
            $room_price = $room_data ? $room_data['base_price'] : $defaultPrice;

            return [
                'room_slug' => $slug,
                'room_price' => $room_price,
                'room_data' => $room_data
            ];
        } catch (Exception $e) {
            error_log("Apartment detail error: " . $e->getMessage());
            return [
                'room_slug' => $slug,
                'room_price' => $defaultPrice,
                'room_data' => null
            ];
        }
    }
}
