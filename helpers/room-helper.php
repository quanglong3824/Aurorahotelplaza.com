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
function getRandomRooms($currentRoomTypeId = null, $limit = 3) {
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
        
        $sql .= " ORDER BY RAND() LIMIT :limit";
        
        $stmt = $conn->prepare($sql);
        
        if ($currentRoomTypeId) {
            $stmt->bindParam(':current_id', $currentRoomTypeId, PDO::PARAM_INT);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
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
function getRoomBySlug($slug) {
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
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

/**
 * Lấy đường dẫn thumbnail
 * @param string $thumbnail Tên file thumbnail
 * @param string $category Loại phòng (room/apartment)
 * @return string Đường dẫn đầy đủ
 */
function getThumbnailPath($thumbnail, $category = 'room') {
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
function getRoomDetailUrl($slug, $category = 'room') {
    if ($category === 'apartment') {
        return '../apartment-details/' . $slug . '.php';
    }
    return '../room-details/' . $slug . '.php';
}
?>
