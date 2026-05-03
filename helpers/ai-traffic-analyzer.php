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

        // Logic phân tích ý định chuyên sâu
        $indicators = [
            'stay' => 0,      // Quan tâm lưu trú (phòng/căn hộ)
            'booking' => 0,   // Ý định đặt phòng
            'event' => 0,     // Quan tâm sự kiện/tiệc cưới/hội nghị
            'promo' => 0,     // Tìm kiếm ưu đãi
            'logistics' => 0, // Tìm đường/liên hệ/vị trí
            'trust' => 0,      // Kiểm tra uy tín (về chúng tôi/chính sách)
            'engagement' => count($paths) // Mức độ tương tác
        ];

        foreach ($paths as $path) {
            $path = strtolower($path);
            
            // 1. Lưu trú
            if (str_contains($path, '/rooms') || str_contains($path, '/phong/') || str_contains($path, 'rooms.php')) {
                $indicators['stay'] += 10;
            }
            if (str_contains($path, '/apartments') || str_contains($path, '/can-ho/') || str_contains($path, 'apartments.php')) {
                $indicators['stay'] += 10;
            }

            // 2. Đặt phòng
            if (str_contains($path, '/booking') || str_contains($path, '/dat-phong') || str_contains($path, 'confirmation.php')) {
                $indicators['booking'] += 30;
            }

            // 3. Sự kiện & Tiệc cưới
            if (str_contains($path, 'wedding') || str_contains($path, 'conference') || str_contains($path, 'tiec-cuoi') || str_contains($path, 'hoi-nghi')) {
                $indicators['event'] += 20;
            }

            // 4. Ưu đãi
            if (str_contains($path, 'promotion') || str_contains($path, 'khuyen-mai') || str_contains($path, 'offers')) {
                $indicators['promo'] += 15;
            }

            // 5. Logistics & Liên hệ
            if (str_contains($path, 'contact') || str_contains($path, 'lien-he') || str_contains($path, 'map') || str_contains($path, 'location')) {
                $indicators['logistics'] += 10;
            }

            // 6. Chính sách & Uy tín
            if (str_contains($path, 'about') || str_contains($path, 'policy') || str_contains($path, 'terms') || str_contains($path, 'chinh-sach')) {
                $indicators['trust'] += 5;
            }
        }

        // PHÂN LOẠI CHI TIẾT
        $score = array_sum($indicators);

        if ($indicators['booking'] > 0) {
            $intent = "Khách hàng mục tiêu: Đang thực hiện quy trình đặt phòng";
            $category = "Hot Lead (Booking)";
        } elseif ($indicators['event'] >= 20) {
            $intent = "Khách hàng sự kiện: Quan tâm đến dịch vụ tiệc cưới/hội nghị";
            $category = "Event Lead";
        } elseif ($indicators['stay'] >= 20 && $indicators['promo'] > 0) {
            $intent = "Khách hàng săn deal: Đang tìm phòng với giá ưu đãi";
            $category = "Promotion Seeker";
        } elseif ($indicators['stay'] >= 30) {
            $intent = "Khách hàng tiềm năng: Đang xem xét kỹ các hạng phòng/căn hộ";
            $category = "Strong Interest";
        } elseif ($indicators['logistics'] >= 20) {
            $intent = "Khách sắp đến hoặc đối tác: Đang tìm vị trí và cách thức liên hệ";
            $category = "Logistics/Contact";
        } elseif ($indicators['stay'] > 0 && $indicators['trust'] > 0) {
            $intent = "Khách hàng cẩn trọng: Đang tìm hiểu kỹ uy tín và chính sách";
            $category = "Consideration";
        } elseif ($indicators['engagement'] > 8) {
            $intent = "Người xem nhiệt tình: Đang dạo quanh xem rất nhiều nội dung";
            $category = "Highly Engaged";
        }

        return [
            'intent' => $intent,
            'score' => $score,
            'category' => $category,
            'path_count' => count($paths),
            'indicators' => $indicators
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
