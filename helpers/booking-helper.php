<?php
/**
 * Booking Helper
 * Xử lý mã booking thông minh
 */

class BookingHelper {
    
    /**
     * Parse smart booking code
     * Chuyển đổi mã ngắn thành mã đầy đủ
     * 
     * VD: 6C320B -> BK202511196C320B (nếu hôm nay là 19/11/2025)
     * VD: BK202511196C320B -> BK202511196C320B (giữ nguyên nếu đã đầy đủ)
     * 
     * @param string $input Mã booking người dùng nhập
     * @return array Danh sách các mã booking có thể
     */
    public static function parseSmartCode($input) {
        $input = strtoupper(trim($input));
        $possible_codes = [];
        
        // Nếu đã là mã đầy đủ (BK + 8 số + 6 ký tự)
        if (preg_match('/^BK\d{8}[A-Z0-9]{6}$/', $input)) {
            $possible_codes[] = $input;
            return $possible_codes;
        }
        
        // Nếu chỉ là 6 ký tự cuối (VD: 6C320B)
        if (preg_match('/^[A-Z0-9]{6}$/', $input)) {
            // Thử với ngày hôm nay
            $today = date('Ymd');
            $possible_codes[] = "BK{$today}{$input}";
            
            // Thử với hôm qua (phòng trường hợp booking tạo gần nửa đêm)
            $yesterday = date('Ymd', strtotime('-1 day'));
            $possible_codes[] = "BK{$yesterday}{$input}";
            
            // Thử với ngày mai (phòng trường hợp múi giờ)
            $tomorrow = date('Ymd', strtotime('+1 day'));
            $possible_codes[] = "BK{$tomorrow}{$input}";
            
            return $possible_codes;
        }
        
        // Nếu có BK + ngày + 6 ký tự (VD: BK202511196C320B)
        if (preg_match('/^BK\d{8}[A-Z0-9]{6}$/', $input)) {
            $possible_codes[] = $input;
            return $possible_codes;
        }
        
        // Nếu có BK + ngày (VD: BK20251119) - tìm tất cả booking trong ngày đó
        if (preg_match('/^BK\d{8}$/', $input)) {
            $possible_codes[] = $input . '%'; // Wildcard để tìm tất cả
            return $possible_codes;
        }
        
        // Nếu chỉ có số (VD: 20251119) - thêm BK vào
        if (preg_match('/^\d{8}$/', $input)) {
            $possible_codes[] = "BK{$input}%";
            return $possible_codes;
        }
        
        // Nếu có BK + 1 phần ngày (VD: BK2025, BK202511)
        if (preg_match('/^BK\d{4,7}$/', $input)) {
            $possible_codes[] = $input . '%';
            return $possible_codes;
        }
        
        // Fallback: tìm kiếm gần đúng
        $possible_codes[] = "%{$input}%";
        
        return $possible_codes;
    }
    
    /**
     * Search bookings by smart code
     * Tìm booking theo mã thông minh
     * 
     * @param PDO $db Database connection
     * @param string $search_term Mã tìm kiếm
     * @return array Danh sách booking tìm thấy
     */
    public static function searchBySmartCode($db, $search_term) {
        $possible_codes = self::parseSmartCode($search_term);
        
        if (empty($possible_codes)) {
            return [];
        }
        
        // Build query với nhiều điều kiện OR
        $conditions = [];
        $params = [];
        
        foreach ($possible_codes as $index => $code) {
            if (strpos($code, '%') !== false) {
                // Wildcard search
                $conditions[] = "b.booking_code LIKE :code{$index}";
                $params[":code{$index}"] = $code;
            } else {
                // Exact match
                $conditions[] = "b.booking_code = :code{$index}";
                $params[":code{$index}"] = $code;
            }
        }
        
        $where_clause = implode(' OR ', $conditions);
        
        $sql = "
            SELECT 
                b.*,
                u.full_name,
                u.email,
                rt.type_name,
                r.room_number
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            JOIN room_types rt ON b.room_type_id = rt.room_type_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE {$where_clause}
            ORDER BY b.created_at DESC
            LIMIT 50
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Format booking code for display
     * Hiển thị mã booking với highlight
     * 
     * @param string $booking_code Mã booking đầy đủ
     * @param bool $show_short Hiển thị cả mã ngắn
     * @return string HTML formatted code
     */
    public static function formatBookingCode($booking_code, $show_short = false) {
        // BK20251119 6C320B
        //   ^^^^^^^^ ^^^^^^
        //   Date     Short
        
        if (preg_match('/^(BK\d{8})([A-Z0-9]{6})$/', $booking_code, $matches)) {
            $prefix = $matches[1]; // BK20251119
            $short = $matches[2];   // 6C320B
            
            if ($show_short) {
                return "<span class='text-gray-500'>{$prefix}</span><span class='font-bold' style='color: #d4af37;'>{$short}</span>";
            }
            
            return $booking_code;
        }
        
        return $booking_code;
    }
    
    /**
     * Get short code from full booking code
     * Lấy mã ngắn từ mã đầy đủ
     * 
     * @param string $booking_code Mã booking đầy đủ
     * @return string Mã ngắn (6 ký tự cuối)
     */
    public static function getShortCode($booking_code) {
        if (preg_match('/^BK\d{8}([A-Z0-9]{6})$/', $booking_code, $matches)) {
            return $matches[1];
        }
        return $booking_code;
    }
    
    /**
     * Get date from booking code
     * Lấy ngày từ mã booking
     * 
     * @param string $booking_code Mã booking đầy đủ
     * @return string|null Ngày (Y-m-d) hoặc null
     */
    public static function getDateFromCode($booking_code) {
        if (preg_match('/^BK(\d{4})(\d{2})(\d{2})[A-Z0-9]{6}$/', $booking_code, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            
            return "{$year}-{$month}-{$day}";
        }
        return null;
    }
    
    /**
     * Validate booking code format
     * Kiểm tra định dạng mã booking
     * 
     * @param string $booking_code Mã booking
     * @return bool
     */
    public static function isValidFormat($booking_code) {
        return preg_match('/^BK\d{8}[A-Z0-9]{6}$/', $booking_code) === 1;
    }
    
    /**
     * Generate example search hints
     * Tạo gợi ý tìm kiếm
     * 
     * @return array
     */
    public static function getSearchHints() {
        $today = date('Ymd');
        $short_example = strtoupper(substr(uniqid(), -6));
        
        return [
            'full' => "BK{$today}{$short_example}",
            'short' => $short_example,
            'date' => "BK{$today}",
            'year' => "BK" . date('Y'),
            'month' => "BK" . date('Ym')
        ];
    }
}
