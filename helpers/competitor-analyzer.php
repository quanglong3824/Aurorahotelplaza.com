<?php
/**
 * Aurora Hotel Plaza - Competitor Analyzer Helper
 * Xử lý việc thu thập và phân tích dữ liệu đối thủ qua Jina Reader và Gemini API
 */

require_once __DIR__ . '/ai-helper.php';

class CompetitorAnalyzer {
    private static $jina_base_url = "https://r.jina.ai/";
    
    /**
     * Thu thập Markdown từ URL đối thủ sử dụng Jina.ai
     */
    public static function fetchMarkdown($url) {
        $jina_url = self::$jina_base_url . $url;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $jina_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 45, // Tăng lên 45s cho hosting yếu
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'X-No-Cache: true',
                'X-With-Generated-Alt: true',
                'Accept: text/event-stream'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            throw new Exception("Lỗi kết nối mạng: " . $curl_error);
        }

        if ($http_code !== 200 || empty($response)) {
            throw new Exception("Jina Reader không thể truy cập URL này (HTTP $http_code). Website đối thủ có thể đang chặn Proxy.");
        }
        
        return mb_substr($response, 0, 20000); // Tăng giới hạn lên 20k ký tự
    }

    /**
     * Phân tích nội dung Markdown bằng Gemini 1.5 Pro
     */
    public static function analyze($markdown_content, $custom_instruction = '') {
        $db = getDB();
        if (!$db) throw new Exception("Không thể kết nối CSDL để phân tích.");
        
        $system_instruction = "Bạn là một chuyên gia phân tích dữ liệu và chiến lược gia bất động sản/kinh doanh cấp cao. 
Nhiệm vụ của bạn là tiếp nhận dữ liệu Markdown từ website đối thủ và thực hiện phân tích đa tầng:
- Tầng 1 (Dữ liệu): Trích xuất chính xác các thông số kỹ thuật, giá cả, và các ưu đãi.
- Tầng 2 (Tri thức): So sánh dữ liệu này với xu hướng thị trường chung, đánh giá tâm lý khách hàng mà đối thủ đang nhắm tới.
- Tầng 3 (Trí tuệ): Dự báo bước đi tiếp theo của đối thủ và đề xuất hành động cụ thể để chúng ta chiếm ưu thế cạnh tranh. ";

        if (!empty($custom_instruction)) {
            $system_instruction .= "\n\nYÊU CẦU ĐẶC BIỆT TỪ NGƯỜI DÙNG: " . $custom_instruction;
        }

        $system_instruction .= "\n\nYÊU CẦU PHẢN HỒI: Chỉ trả về JSON duy nhất, không kèm giải thích. Cấu trúc JSON:
{
    \"summary\": { \"name\": \"string\", \"price_range\": \"string\", \"usp\": [\"string\"] },
    \"structural_extraction\": { \"specs\": [], \"pricing_details\": [], \"promotions\": [] },
    \"semantic_analysis\": { \"positioning\": \"string\", \"target_audience\": \"string\", \"sentiment\": \"string\" },
    \"predictive_strategy\": { \"next_moves\": [], \"counter_strategies\": [] }
}";

        $prompt = "Dữ liệu Markdown của đối thủ:\n\n" . $markdown_content;
        
        // Sử dụng hàm call_gemini_sync
        try {
            $response_text = call_gemini_sync($prompt, $db, null, $system_instruction);
        } catch (Throwable $t) {
            throw new Exception("Lỗi gọi API AI: " . $t->getMessage());
        }
        
        if (empty($response_text) || str_contains($response_text, 'Lỗi:')) {
            throw new Exception("AI không trả về kết quả: " . $response_text);
        }

        // Làm sạch JSON
        $clean_json = preg_replace('/^```json\s*|\s*```$/i', '', trim($response_text));
        $data = json_decode($clean_json, true);
        
        if (!$data) {
            throw new Exception("AI trả về dữ liệu không đúng định dạng JSON. Phản hồi thô: " . substr($response_text, 0, 100));
        }
        
        return $data;
    }

    /**
     * Tự động khám phá các đối thủ lân cận dựa trên trí tuệ nhân tạo (Mở rộng tối đa)
     */
    public static function discoverNearbyCompetitors() {
        $db = getDB();
        
        $system_instruction = "Bạn là một trinh sát tình báo thị trường chuyên nghiệp. 
Nhiệm vụ của bạn là liệt kê TẤT CẢ các đối thủ có cung cấp dịch vụ lưu trú tại thành phố Biên Hòa, Đồng Nai.
KHÔNG GIỚI HẠN phân khúc: Bao gồm khách sạn (1-5 sao), nhà khách, căn hộ dịch vụ, nhà nghỉ lớn, và các Homestay nổi tiếng.
YÊU CẦU: Tìm ít nhất 15-20 đối thủ. Nếu bạn biết tên nhưng không chắc URL, hãy cung cấp URL khả thi nhất của họ trên các trang đặt phòng hoặc website chính thức.

YÊU CẦU PHẢN HỒI: Chỉ trả về JSON duy nhất. Cấu trúc JSON:
{
    \"discovered\": [
        { \"name\": \"Tên cơ sở kinh doanh\", \"url\": \"URL website hoặc trang booking của họ\" }
    ]
}";

        $prompt = "Hãy quét dữ liệu và liệt kê danh sách toàn bộ các đối thủ cạnh tranh về lưu trú tại khu vực Biên Hòa cho tôi. Càng nhiều càng tốt.";
        
        try {
            $response_text = call_gemini_sync($prompt, $db, null, $system_instruction);
            // Loại bỏ các đoạn text rác nếu có
            $clean_json = preg_replace('/^.*?\{/s', '{', $response_text);
            $clean_json = preg_replace('/\}.*?$/s', '}', $clean_json);
            $data = json_decode($clean_json, true);
            
            if (!$data || empty($data['discovered'])) {
                error_log("AI Discovery returned no data or invalid JSON. Response: " . substr($response_text, 0, 500));
                return 0;
            }

            $new_count = 0;
            foreach ($data['discovered'] as $item) {
                $url = trim($item['url']);
                if (empty($url)) continue;

                // Kiểm tra xem đã tồn tại URL này chưa (chuẩn hóa URL để tránh trùng)
                $check = $db->prepare("SELECT id FROM competitor_intelligence WHERE url LIKE ? OR url LIKE ?");
                $url_pattern = '%' . parse_url($url, PHP_URL_HOST) . '%';
                $check->execute([$url_pattern, $url]);
                
                if (!$check->fetch()) {
                    $stmt = $db->prepare("INSERT INTO competitor_intelligence (name, url, instruction, status) VALUES (?, ?, ?, 'pending')");
                    $stmt->execute([$item['name'], $url, 'Phân tích tổng quan và báo cáo điểm mạnh/yếu so với Aurora.']);
                    $new_count++;
                }
            }
            return $new_count;
        } catch (Throwable $t) {
            error_log("AI Discovery Error: " . $t->getMessage());
            return 0;
        }
    }

    /**
     * CHIẾN DỊCH QUÉT QUY MÔ LỚN (Massive Strategic Scout)
     * Tận dụng tối đa mô hình AI cao cấp (GLM-5/Gemini Pro) để càn quét thị trường
     */
    public static function runMassiveStrategicScout() {
        $db = getDB();
        
        $scout_prompt = "Bạn là một CHUYÊN GIA TÌNH BÁO CẠNH TRANH cấp cao. 
Nhiệm vụ của bạn là thực hiện một cuộc càn quét (Massive Recon) toàn bộ thị trường lưu trú tại Biên Hòa.

BƯỚC 1: Xác định 10 đối thủ đáng gờm nhất của Aurora Hotel Plaza (khách sạn, căn hộ, đơn vị phá giá).
BƯỚC 2: Phân tích chiến thuật của họ (điểm mạnh, điểm yếu).
BƯỚC 3: Tổng hợp một 'Hồ sơ Tình báo Thị trường' (Market Intelligence Dossier).

YÊU CẦU PHẢN HỒI JSON DUY NHẤT:
{
    \"market_overview\": \"Tóm tắt tình hình thị trường Biên Hòa hiện tại\",
    \"targets\": [
        {
            \"name\": \"Tên đối thủ\",
            \"url\": \"URL khả thi nhất\",
            \"threat_level\": \"high|medium|low\",
            \"usp_discovery\": \"Điểm đặc biệt nhất của họ\",
            \"weakness_to_exploit\": \"Điểm yếu chúng ta có thể tận dụng\"
        }
    ],
    \"strategic_recommendations\": [\"Khuyến nghị 1\", \"Khuyến nghị 2\"]
}";

        try {
            require_once __DIR__ . '/ai-helper.php';
            $response_text = call_ai_sync($scout_prompt, $db, null, "Bạn là Tổng tư lệnh Tình báo Thị trường.");
            
            $clean_json = preg_replace('/^.*?\{/s', '{', $response_text);
            $clean_json = preg_replace('/\}.*?$/s', '}', $clean_json);
            $data = json_decode($clean_json, true);

            if (!$data) return null;

            foreach ($data['targets'] as $target) {
                $url = trim($target['url'] ?? '');
                if (empty($url)) continue;

                $check = $db->prepare("SELECT id FROM competitor_intelligence WHERE url = ?");
                $check->execute([$url]);
                if (!$check->fetch()) {
                    $stmt = $db->prepare("INSERT INTO competitor_intelligence (name, url, instruction, status) VALUES (?, ?, ?, 'pending')");
                    $stmt->execute([$target['name'], $url, "AI RECON: " . ($target['weakness_to_exploit'] ?? 'Phân tích dịch vụ')]);
                }
            }

            require_once __DIR__ . '/activity-logger.php';
            ActivityLogger::log(null, 'ai_recon', 'massive_report', 0, "AI MASSIVE SCOUT HOÀN TẤT: " . $data['market_overview']);

            return $data;
        } catch (Throwable $t) {
            error_log("Massive Recon Error: " . $t->getMessage());
            return null;
        }
    }

    /**
     * Xử lý 1 đối thủ trong hàng đợi
     */
    public static function processOne($competitor_id) {
        $db = getDB();
        
        try {
            // Đánh dấu đang xử lý
            $db->prepare("UPDATE competitor_intelligence SET status = 'processing', error_message = NULL WHERE id = ?")->execute([$competitor_id]);
            
            $stmt = $db->prepare("SELECT url, instruction FROM competitor_intelligence WHERE id = ?");
            $stmt->execute([$competitor_id]);
            $comp = $stmt->fetch();
            
            if (!$comp) return false;

            // 1. Fetch Markdown (Có cơ chế tự bỏ qua nếu website chặn proxy)
            try {
                $markdown = self::fetchMarkdown($comp['url']);
            } catch (Throwable $e) {
                // Nếu bị chặn, ghi lỗi và chuyển trạng thái để bỏ qua sang web khác
                $db->prepare("UPDATE competitor_intelligence SET status = 'error', error_message = ? WHERE id = ?")
                   ->execute(["[BỎ QUA] Website chặn truy cập hoặc lỗi mạng: " . $e->getMessage(), $competitor_id]);
                return false; 
            }
            
            // 2. Analyze
            $analysis = self::analyze($markdown, $comp['instruction'] ?? '');
            
            // 3. Save result
            $stmtUpdate = $db->prepare("
                UPDATE competitor_intelligence 
                SET raw_markdown = ?, analysis_data = ?, status = 'completed', last_analyzed = NOW() 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$markdown, json_encode($analysis, JSON_UNESCAPED_UNICODE), $competitor_id]);
            
            return true;
        } catch (Throwable $e) {
            $db->prepare("UPDATE competitor_intelligence SET status = 'error', error_message = ? WHERE id = ?")
               ->execute($e->getMessage(), $competitor_id);
            return false;
        }
    }
}
