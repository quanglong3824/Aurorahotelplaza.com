<?php
/**
 * Aurora Hotel Plaza - Room Form Controller
 * Handles data fetching and processing for room creation/editing
 */

function getRoomFormData() {
    $room_id = $_GET['id'] ?? null;
    $is_edit = !empty($room_id);

    $room = null;
    if ($is_edit) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM rooms WHERE room_id = :id");
            $stmt->execute([':id' => $room_id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$room) {
                return ['error' => 'Không tìm thấy phòng', 'redirect' => 'rooms.php'];
            }
        } catch (Exception $e) {
            error_log("Room Form Controller error: " . $e->getMessage());
            return ['error' => 'Có lỗi xảy ra', 'redirect' => 'rooms.php'];
        }
    }

    // Get room types
    $room_types = [];
    try {
        $db = getDB();
        $stmt = $db->query("SELECT room_type_id, type_name FROM room_types WHERE status = 'active' ORDER BY type_name");
        $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Room Form Controller (room_types) error: " . $e->getMessage());
    }

    return [
        'room' => $room,
        'is_edit' => $is_edit,
        'room_id' => $room_id,
        'room_types' => $room_types
    ];
}
