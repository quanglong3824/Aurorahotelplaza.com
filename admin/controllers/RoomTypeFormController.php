<?php
/**
 * Aurora Hotel Plaza - Room Type Form Controller
 * Handles data fetching and processing for room type creation/editing
 */

function getRoomTypeFormData() {
    $room_type_id = $_GET['id'] ?? null;
    $is_edit = !empty($room_type_id);

    $room_type = null;
    if ($is_edit) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM room_types WHERE room_type_id = :id");
            $stmt->execute([':id' => $room_type_id]);
            $room_type = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$room_type) {
                return ['error' => 'Không tìm thấy loại phòng', 'redirect' => 'room-types.php'];
            }
        } catch (Exception $e) {
            error_log("Room Type Form Controller error: " . $e->getMessage());
            return ['error' => 'Có lỗi xảy ra', 'redirect' => 'room-types.php'];
        }
    }

    return [
        'room_type' => $room_type,
        'is_edit' => $is_edit,
        'room_type_id' => $room_type_id
    ];
}
