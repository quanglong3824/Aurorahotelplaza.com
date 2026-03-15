<?php

class FrontRoomsController {
    public function getData() {
        try {
            $db = getDB();

            // Chỉ lấy phòng (không lấy căn hộ)
            $stmt = $db->prepare("
                SELECT * FROM room_types 
                WHERE status = 'active' AND category = 'room'
                ORDER BY sort_order ASC, type_name ASC
            ");

            $stmt->execute();
            $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'room_types' => $room_types
            ];
        } catch (Exception $e) {
            error_log("Rooms page error: " . $e->getMessage());
            return [
                'room_types' => []
            ];
        }
    }
}
