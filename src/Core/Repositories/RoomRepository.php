<?php
namespace Aurora\Core\Repositories;

use PDO;

/**
 * RoomRepository - Đóng gói các truy vấn SQL liên quan đến phòng
 */
class RoomRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findRoomTypeById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM room_types WHERE room_type_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM room_types WHERE slug = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function checkAvailability(int $roomTypeId, string $checkIn, string $checkOut): bool {
        // Logic kiểm tra phòng trống thực tế dựa trên lịch đặt
        // Tạm thời trả về true để focus vào cấu trúc OOP
        return true; 
    }
}
