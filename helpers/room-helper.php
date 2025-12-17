<?php
/**
 * Room Helper Functions
 * Các hàm hỗ trợ lấy thông tin phòng từ database
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Lấy danh sách phòng ngẫu nhiên (không bao gồm phòng hiện tại)
 * @param int $currentRoomTypeId ID của loại phòng hiện tại
 * @param int $limit Số lượng phòng cần lấy
 * @return array Danh sách phòng
 */
function getRandomRooms($currentRoomTypeId = null, $limit = 3, $category = null)
{
    try {
        $conn = getDB();
        if (!$conn) {
            return [];
        }

        $sql = "SELECT 
                    rt.room_type_id as id,
                    rt.type_name as name,
                    rt.slug,
                    rt.base_price,
                    rt.thumbnail,
                    rt.category
                FROM room_types rt
                WHERE rt.status = 'active'";

        if ($currentRoomTypeId) {
            $sql .= " AND rt.room_type_id != :current_id";
        }

        if ($category) {
            $sql .= " AND rt.category = :category";
        }

        $sql .= " ORDER BY RAND() LIMIT :limit";

        $stmt = $conn->prepare($sql);

        if ($currentRoomTypeId) {
            $stmt->bindValue(':current_id', $currentRoomTypeId, PDO::PARAM_INT);
        }
        if ($category) {
            $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error in getRandomRooms: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy thông tin chi tiết phòng theo slug
 * @param string $slug Slug của phòng
 * @return array|null Thông tin phòng hoặc null nếu không tìm thấy
 */
function getRoomBySlug($slug)
{
    try {
        $conn = getDB();
        if (!$conn) {
            return null;
        }

        $sql = "SELECT 
                    room_type_id as id,
                    type_name as name,
                    slug,
                    category,
                    base_price,
                    thumbnail
                FROM room_types 
                WHERE slug = :slug AND status = 'active' 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error in getRoomBySlug: " . $e->getMessage());
        return null;
    }
}

/**
 * Format giá tiền
 * @param float $price Giá
 * @return string Giá đã format
 */
function formatPrice($price)
{
    return number_format($price, 0, ',', '.') . 'đ';
}

/**
 * Lấy đường dẫn thumbnail
 * @param string $thumbnail Tên file thumbnail
 * @param string $category Loại phòng (room/apartment)
 * @return string Đường dẫn đầy đủ
 */
function getThumbnailPath($thumbnail, $category = 'room')
{
    if (empty($thumbnail)) {
        return '../assets/img/placeholder.jpg';
    }

    // Nếu đã có đường dẫn tương đối đúng
    if (strpos($thumbnail, '../assets/img/') === 0) {
        return $thumbnail;
    }

    // Nếu bắt đầu bằng /assets/img/ thì thêm .. vào đầu
    if (strpos($thumbnail, '/assets/img/') === 0) {
        return '..' . $thumbnail;
    }

    // Nếu chỉ là tên file thì thêm đường dẫn đầy đủ
    return '../assets/img/' . $thumbnail;
}

/**
 * Lấy đường dẫn chi tiết phòng
 * @param string $slug Slug của phòng
 * @param string $category Loại phòng (room/apartment)
 * @return string Đường dẫn
 */
function getRoomDetailUrl($slug, $category = 'room')
{
    if ($category === 'apartment') {
        return '../apartment-details/' . $slug . '.php';
    }
    return '../room-details/' . $slug . '.php';
}

/**
 * Kiểm tra số lượng phòng trống
 * @param int $roomTypeId ID loại phòng
 * @param string $checkIn Ngày nhận phòng (Y-m-d)
 * @param string $checkOut Ngày trả phòng (Y-m-d)
 * @return int Số lượng phòng trống
 */
function checkRoomAvailability($roomTypeId, $checkIn, $checkOut)
{
    try {
        $conn = getDB();
        if (!$conn) {
            return 0;
        }

        // Đếm tổng số phòng active của loại này
        $sqlTotal = "SELECT COUNT(*) FROM rooms WHERE room_type_id = :room_type_id AND status = 'available'";
        $stmtTotal = $conn->prepare($sqlTotal);
        $stmtTotal->execute([':room_type_id' => $roomTypeId]);
        $totalRooms = $stmtTotal->fetchColumn();

        if ($totalRooms == 0) {
            return 0;
        }

        // Đếm số phòng đã được đặt trong khoảng thời gian này
        // Booking trùng là booking có thời gian giao nhau với khoảng [checkIn, checkOut]
        // (start1 < end2) AND (end1 > start2)
        $sqlBooked = "SELECT COUNT(DISTINCT room_id) 
                      FROM bookings 
                      WHERE room_type_id = :room_type_id 
                      AND status IN ('confirmed', 'checked_in')
                      AND room_id IS NOT NULL
                      AND check_in_date < :check_out 
                      AND check_out_date > :check_in";

        $stmtBooked = $conn->prepare($sqlBooked);
        $stmtBooked->execute([
            ':room_type_id' => $roomTypeId,
            ':check_in' => $checkIn,
            ':check_out' => $checkOut
        ]);
        $bookedRooms = $stmtBooked->fetchColumn();

        return max(0, $totalRooms - $bookedRooms);

    } catch (PDOException $e) {
        error_log("Error in checkRoomAvailability: " . $e->getMessage());
        return 0;
    }
}
?>