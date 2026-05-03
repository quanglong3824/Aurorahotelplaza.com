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
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'X-No-Cache: true',
                'X-With-Generated-Alt: true'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || empty($response)) {
            throw new Exception("Không thể thu thập dữ liệu từ Jina Reader (HTTP $http_code)");
        }
        
        // Làm sạch Markdown sơ bộ (loại bỏ header/footer dư thừa nếu có thể)
        return mb_substr($response, 0, 15000); // Giới hạn 15k ký tự để tiết kiệm token
    }

    /**
     * Phân tích nội dung Markdown bằng Gemini 1.5 Pro
     */
    public static function analyze($markdown_content) {
        $db = getDB();
        
        $system_instruction = "Bạn là một chuyên gia phân tích dữ liệu và chiến lược gia bất động sản/kinh doanh cấp cao. 
Nhiệm vụ của bạn là tiếp nhận dữ liệu Markdown từ website đối thủ và thực hiện phân tích đa tầng:
- Tầng 1 (Dữ liệu): Trích xuất chính xác các thông số kỹ thuật, giá cả, và các ưu đãi.
- Tầng 2 (Tri thức): So sánh dữ liệu này với xu hướng thị trường chung, đánh giá tâm lý khách hàng mà đối thủ đang nhắm tới.
- Tầng 3 (Trí tuệ): Dự báo bước đi tiếp theo của đối thủ và đề xuất hành động cụ thể để chúng ta chiếm ưu thế cạnh tranh. 

YÊU CẦU PHẢN HỒI: Chỉ trả về JSON duy nhất, không kèm giải thích. Cấu trúc JSON:
{
    \"summary\": { \"name\": \"string\", \"price_range\": \"string\", \"usp\": [\"string\"] },
    \"structural_extraction\": { \"specs\": [], \"pricing_details\": [], \"promotions\": [] },
    \"semantic_analysis\": { \"positioning\": \"string\", \"target_audience\": \"string\", \"sentiment\": \"string\" },
    \"predictive_strategy\": { \"next_moves\": [], \"counter_strategies\": [] }
}";

        $prompt = "Dữ liệu Markdown của đối thủ:\n\n" . $markdown_content;
        
        // Sử dụng hàm call_gemini_sync từ ai-helper.php
        $response_text = call_gemini_sync($prompt, $db, null, $system_instruction);
        
        // Làm sạch JSON từ phản hồi (đôi khi AI bọc trong ```json)
        $clean_json = preg_replace('/^```json\s*|\s*```$/i', '', trim($response_text));
        $data = json_decode($clean_json, true);
        
        if (!$data) {
            throw new Exception("AI không trả về định dạng JSON hợp lệ: " . substr($response_text, 0, 200));
        }
        
        return $data;
    }

    /**
     * Xử lý 1 đối thủ trong hàng đợi
     */
    public static function processOne($competitor_id) {
        $db = getDB();
        
        try {
            // Cập nhật trạng thái sang processing
            $db->prepare("UPDATE competitor_intelligence SET status = 'processing' WHERE id = ?")->execute([$competitor_id]);
            
            $stmt = $db->prepare("SELECT url FROM competitor_intelligence WHERE id = ?");
            $stmt->execute([$competitor_id]);
            $comp = $stmt->fetch();
            
            if (!$comp) return;

            // 1. Fetch Markdown
            $markdown = self::fetchMarkdown($comp['url']);
            
            // 2. Analyze
            $analysis = self::analyze($markdown);
            
            // 3. Save result
            $stmtUpdate = $db->prepare("
                UPDATE competitor_intelligence 
                SET raw_markdown = ?, analysis_data = ?, status = 'completed', last_analyzed = NOW() 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$markdown, json_encode($analysis, JSON_UNESCAPED_UNICODE), $competitor_id]);
            
            return true;
        } catch (Exception $e) {
            $db->prepare("UPDATE competitor_intelligence SET status = 'error', error_message = ? WHERE id = ?")
               ->execute([$e->getMessage(), $competitor_id]);
            return false;
        }
    }
}
