<?php
/**
 * Aurora Hotel Plaza - AI Traffic Analyzer
 * Phân tích hành vi chuyên sâu bằng AI để nhận diện ý định khách hàng
 */

class AITrafficAnalyzer {
    private static $db = null;

    /**
     * Phân tích hành vi của một Visitor dựa trên chuỗi log gần đây
     */
    public static function analyzeVisitorIntent($ip) {
        $db = getDB();
        
        // Lấy 10 hành động gần nhất của IP này trong 30 phút qua
        $stmt = $db->prepare("
            SELECT page_url, visit_time 
            FROM traffic_logs 
            WHERE ip_address = ? AND visit_time > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            ORDER BY visit_time ASC
        ");
        $stmt->execute([$ip]);
        $history = $stmt->fetchAll();

        if (count($history) < 2) return null;

        $paths = array_column($history, 'page_url');
        $intent = "Đang tìm hiểu chung";
        $score = 0;
        $category = "General";

        // Logic phân tích ý định cực kỳ chuyên sâu
        $indicators = [
            'stay' => 0,      // Quan tâm lưu trú
            'booking' => 0,   // Ý định đặt phòng
            'event' => 0,     // Sự kiện/Tiệc cưới
            'promo' => 0,     // Ưu đãi
            'logistics' => 0, // Vị trí/Liên hệ
            'trust' => 0,     // Chính sách/Uy tín
            'dining' => 0,    // Nhà hàng/Ẩm thực
            'wellness' => 0,  // Spa/Gym/Hồ bơi
            'family' => 0,    // Khách gia đình (căn hộ to)
            'business' => 0,  // Khách công vụ (phòng đơn/hội nghị)
            'engagement' => count($paths)
        ];

        // Đếm số lần xem cùng một trang để phát hiện phân vân
        $path_counts = array_count_values($paths);
        $max_repeats = !empty($path_counts) ? max($path_counts) : 0;

        foreach ($paths as $path) {
            $path = strtolower($path);
            
            // 1. Lưu trú & Phân loại Persona
            if (str_contains($path, 'family') || str_contains($path, 'apartment') || str_contains($path, 'can-ho')) {
                $indicators['stay'] += 10;
                $indicators['family'] += 15;
            }
            if (str_contains($path, 'premium') || str_contains($path, 'suite') || str_contains($path, 'deluxe')) {
                $indicators['stay'] += 10;
                $indicators['business'] += 10;
            }
            if (str_contains($path, 'studio') || str_contains($path, 'standard')) {
                $indicators['stay'] += 10;
                $indicators['business'] += 5;
            }

            // 2. Đặt phòng
            if (str_contains($path, '/booking') || str_contains($path, '/dat-phong')) {
                $indicators['booking'] += 35;
            }

            // 3. Sự kiện
            if (str_contains($path, 'wedding') || str_contains($path, 'conference') || str_contains($path, 'tiec-cuoi') || str_contains($path, 'hoi-nghi')) {
                $indicators['event'] += 25;
            }

            // 4. Ẩm thực & Thư giãn
            if (str_contains($path, 'restaurant') || str_contains($path, 'cuisine') || str_contains($path, 'nha-hang')) {
                $indicators['dining'] += 20;
            }
            if (str_contains($path, 'spa') || str_contains($path, 'gym') || str_contains($path, 'pool') || str_contains($path, 'ho-boi')) {
                $indicators['wellness'] += 20;
            }

            // 5. Khác
            if (str_contains($path, 'promotion') || str_contains($path, 'khuyen-mai')) $indicators['promo'] += 15;
            if (str_contains($path, 'contact') || str_contains($path, 'map')) $indicators['logistics'] += 10;
            if (str_contains($path, 'policy') || str_contains($path, 'about')) $indicators['trust'] += 5;
        }

        // PHÂN LOẠI PERSONA & Ý ĐỊNH CHI TIẾT
        $score = array_sum($indicators);

        if ($indicators['booking'] > 0) {
            $intent = "KHÁCH MỤC TIÊU: Đang chốt đơn đặt phòng";
            $category = "Hot Lead (Booking)";
        } elseif ($indicators['event'] >= 25) {
            $intent = "KHÁCH SỰ KIỆN: Đang lên kế hoạch tổ chức tiệc/hội nghị";
            $category = "Event Planner";
        } elseif ($max_repeats >= 3) {
            $intent = "KHÁCH PHÂN VÂN: Đang xem đi xem lại một hạng phòng nhất định";
            $category = "Indecisive Lead";
        } elseif ($indicators['family'] >= 30) {
            $intent = "KHÁCH GIA ĐÌNH: Quan tâm không gian lưu trú rộng rãi/căn hộ";
            $category = "Family Traveler";
        } elseif ($indicators['business'] >= 30 && $indicators['event'] > 0) {
            $intent = "KHÁCH DOANH NGHIỆP: Tìm phòng cao cấp kết hợp hội họp";
            $category = "Business/Corporate";
        } elseif ($indicators['dining'] >= 20 && $indicators['wellness'] >= 20) {
            $intent = "KHÁCH NGHỈ DƯỠNG: Tập trung vào ẩm thực và dịch vụ thư giãn";
            $category = "Leisure/Relax";
        } elseif ($indicators['promo'] >= 15 && $indicators['stay'] >= 10) {
            $intent = "KHÁCH SĂN DEAL: Đang chờ giá tốt để đặt phòng";
            $category = "Price Sensitive";
        } elseif ($indicators['logistics'] >= 20) {
            $intent = "KHÁCH SẮP ĐẾN: Kiểm tra vị trí và cách liên hệ cuối cùng";
            $category = "Imminent Arrival";
        } elseif ($indicators['engagement'] >= 12) {
            $intent = "FAN CỨNG: Đang nghiên cứu cực kỳ kỹ mọi ngóc ngách website";
            $category = "Super Engaged";
        }

        return [
            'intent' => $intent,
            'score' => $score,
            'category' => $category,
            'path_count' => count($paths),
            'indicators' => $indicators,
            'is_indecisive' => ($max_repeats >= 3)
        ];
    }

    /**
     * Tổng hợp báo cáo động thái hàng ngày vào hệ thống Activity Logs
     */
    public static function logDailyInsight() {
        $db = getDB();
        $today = date('Y-m-d');

        try {
            // Thống kê nhanh
            $stats = $db->query("
                SELECT 
                    COUNT(DISTINCT ip_address) as unique_v,
                    COUNT(*) as total_hits,
                    SUM(CASE WHEN device_type = 'bot' THEN 1 ELSE 0 END) as bot_hits
                FROM traffic_logs 
                WHERE DATE(visit_time) = CURDATE()
            ")->fetch();

            $human_hits = $stats['total_hits'] - $stats['bot_hits'];
            
            // Tìm Visitor tiềm năng nhất (Hot Lead)
            $stmtLead = $db->query("
                SELECT ip_address, COUNT(*) as pages 
                FROM traffic_logs 
                WHERE DATE(visit_time) = CURDATE() AND device_type != 'bot'
                GROUP BY ip_address 
                HAVING pages >= 5
                ORDER BY pages DESC 
                LIMIT 3
            ");
            $leads = $stmtLead->fetchAll();

            $insight_desc = "Báo cáo AI: Hôm nay có {$stats['unique_v']} khách truy cập ({$human_hits} lượt xem thực). ";
            
            if (!empty($leads)) {
                $insight_desc .= "Phát hiện " . count($leads) . " khách hàng tiềm năng đang xem nhiều trang dịch vụ. ";
                foreach ($leads as $index => $lead) {
                    $analysis = self::analyzeVisitorIntent($lead['ip_address']);
                    $insight_desc .= "Khách #" . ($index+1) . " ({$lead['ip_address']}): {$analysis['intent']}. ";
                }
            } else {
                $insight_desc .= "Chưa phát hiện hành vi đặt phòng nổi bật.";
            }

            // Ghi vào Activity Logs của hệ thống
            require_once __DIR__ . '/activity-logger.php';
            ActivityLogger::log(null, 'ai_insight', 'traffic_report', 0, $insight_desc);

            return true;
        } catch (Exception $e) {
            error_log("AI Insight Error: " . $e->getMessage());
            return false;
        }
    }
}
