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
        $intent = "Đang tìm hiểu";
        $score = 0;
        $category = "General";

        // Logic phân tích ý định (Heuristic AI)
        $has_rooms = false;
        $has_apartments = false;
        $has_booking = false;
        $has_contact = false;

        foreach ($paths as $path) {
            if (str_contains($path, '/rooms') || str_contains($path, '/phong/')) {
                $has_rooms = true;
                $score += 10;
            }
            if (str_contains($path, '/apartments') || str_contains($path, '/can-ho/')) {
                $has_apartments = true;
                $score += 10;
            }
            if (str_contains($path, '/booking') || str_contains($path, '/dat-phong')) {
                $has_booking = true;
                $score += 30;
            }
            if (str_contains($path, 'contact.php')) {
                $has_contact = true;
                $score += 20;
            }
        }

        if ($has_booking) {
            $intent = "Ý định đặt phòng rất cao";
            $category = "Hot Lead";
        } elseif ($has_rooms && $has_apartments) {
            $intent = "Đang so sánh phòng và căn hộ";
            $category = "Evaluating";
        } elseif ($has_rooms || $has_apartments) {
            $intent = "Quan tâm sâu đến dịch vụ lưu trú";
            $category = "Interested";
        }

        return [
            'intent' => $intent,
            'score' => $score,
            'category' => $category,
            'path_count' => count($paths)
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
