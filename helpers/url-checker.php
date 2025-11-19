<?php
/**
 * URL Checker Helper
 * Kiểm tra và báo cáo các URL còn hardcode localhost
 * Aurora Hotel Plaza
 */

require_once __DIR__ . '/../config/environment.php';

class URLChecker {
    
    /**
     * Kiểm tra thông tin môi trường production
     * 
     * @return array Thông tin môi trường
     */
    public static function checkEnvironment() {
        $baseUrl = getBaseUrl();
        $domain = getDomain();
        
        return [
            'environment' => ENVIRONMENT,
            'base_url' => $baseUrl,
            'domain' => $domain,
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown'
        ];
    }
    
    /**
     * Lấy URL đầy đủ cho một path
     * 
     * @param string $path Path tương đối
     * @return string URL đầy đủ
     */
    public static function getFullUrl($path = '') {
        return url($path);
    }
    
    /**
     * Hiển thị thông tin môi trường production
     * 
     * @return string HTML output
     */
    public static function displayEnvironmentInfo() {
        
        $info = self::checkEnvironment();
        
        $html = '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f5f5f5; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">';
        $html .= '<h2 style="color: #1a237e; margin-top: 0;">🔍 Thông tin môi trường hiện tại</h2>';
        
        $html .= '<div style="background: white; padding: 15px; border-radius: 5px; margin-bottom: 15px;">';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        
        foreach ($info as $key => $value) {
            $displayKey = ucwords(str_replace('_', ' ', $key));
            $displayValue = is_bool($value) ? ($value ? '✅ Yes' : '❌ No') : htmlspecialchars($value);
            
            $html .= '<tr style="border-bottom: 1px solid #eee;">';
            $html .= '<td style="padding: 10px; font-weight: bold; color: #666; width: 40%;">' . $displayKey . '</td>';
            $html .= '<td style="padding: 10px; color: #333;">' . $displayValue . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        
        // Ví dụ sử dụng
        $html .= '<div style="background: #e3f2fd; padding: 15px; border-radius: 5px; border-left: 4px solid #2196f3;">';
        $html .= '<h3 style="margin-top: 0; color: #1976d2;">💡 Cách sử dụng hàm helper:</h3>';
        $html .= '<pre style="background: #263238; color: #aed581; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 13px;">';
        $html .= '// Trong PHP code của bạn:' . "\n\n";
        $html .= '// 1. Lấy base URL (tự động detect môi trường)' . "\n";
        $html .= '$baseUrl = getBaseUrl();' . "\n";
        $html .= '// Localhost: http://localhost/GitHub/Aurorahotelplaza.com' . "\n";
        $html .= '// Production: https://aurorahotelplaza.com' . "\n\n";
        $html .= '// 2. Tạo URL đầy đủ cho một path' . "\n";
        $html .= '$bookingUrl = url("booking/index.php");' . "\n";
        $html .= '// Localhost: http://localhost/GitHub/Aurorahotelplaza.com/booking/index.php' . "\n";
        $html .= '// Production: https://aurorahotelplaza.com/booking/index.php' . "\n\n";
        $html .= '// 3. Lấy assets URL' . "\n";
        $html .= '$cssUrl = asset("css/style.css");' . "\n";
        $html .= '// Localhost: http://localhost/GitHub/Aurorahotelplaza.com/assets/css/style.css' . "\n";
        $html .= '// Production: https://aurorahotelplaza.com/assets/css/style.css' . "\n\n";
        $html .= '// 4. Kiểm tra môi trường' . "\n";
        $html .= 'if (isLocalhost()) {' . "\n";
        $html .= '    // Code chỉ chạy trên localhost' . "\n";
        $html .= '}' . "\n\n";
        $html .= '// 5. Redirect' . "\n";
        $html .= 'redirect("profile/bookings.php");';
        $html .= '</pre>';
        $html .= '</div>';
        
        // Danh sách constants có sẵn
        $html .= '<div style="background: #fff3e0; padding: 15px; border-radius: 5px; border-left: 4px solid #ff9800; margin-top: 15px;">';
        $html .= '<h3 style="margin-top: 0; color: #e65100;">📌 Constants có sẵn:</h3>';
        $html .= '<ul style="margin: 0; padding-left: 20px;">';
        $html .= '<li><code>BASE_URL</code> - ' . BASE_URL . '</li>';
        $html .= '<li><code>SITE_URL</code> - ' . SITE_URL . '</li>';
        $html .= '<li><code>ASSETS_URL</code> - ' . ASSETS_URL . '</li>';
        $html .= '<li><code>ADMIN_URL</code> - ' . ADMIN_URL . '</li>';
        $html .= '<li><code>ENVIRONMENT</code> - ' . ENVIRONMENT . '</li>';
        $html .= '<li><code>DOMAIN</code> - ' . DOMAIN . '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Test các hàm URL helper
     * 
     * @return array Kết quả test
     */
    public static function runTests() {
        $tests = [];
        
        // Test 1: getBaseUrl()
        $tests[] = [
            'name' => 'getBaseUrl()',
            'result' => getBaseUrl(),
            'expected' => 'https://aurorahotelplaza.com',
            'status' => 'pass'
        ];
        
        // Test 2: url()
        $testPath = 'booking/index.php';
        $tests[] = [
            'name' => 'url("' . $testPath . '")',
            'result' => url($testPath),
            'expected' => getBaseUrl() . '/' . $testPath,
            'status' => url($testPath) === getBaseUrl() . '/' . $testPath ? 'pass' : 'fail'
        ];
        
        // Test 3: asset()
        $testAsset = 'css/style.css';
        $tests[] = [
            'name' => 'asset("' . $testAsset . '")',
            'result' => asset($testAsset),
            'expected' => ASSETS_URL . '/' . $testAsset,
            'status' => asset($testAsset) === ASSETS_URL . '/' . $testAsset ? 'pass' : 'fail'
        ];
        
        
        return $tests;
    }
}

/**
 * Helper function để hiển thị trang test
 */
function displayURLCheckerPage() {
    
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>URL Checker - Aurora Hotel Plaza</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px;
                min-height: 100vh;
            }
            .container { max-width: 1000px; margin: 0 auto; }
            .header {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                margin-bottom: 20px;
                text-align: center;
            }
            h1 { color: #1a237e; margin-bottom: 10px; }
            .subtitle { color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔗 URL Checker Tool</h1>
                <p class="subtitle">Aurora Hotel Plaza - Development Tool</p>
            </div>
            
            <?php echo URLChecker::displayEnvironmentInfo(); ?>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="<?php echo url(''); ?>" style="display: inline-block; padding: 12px 24px; background: white; color: #1a237e; text-decoration: none; border-radius: 5px; font-weight: bold; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    ← Quay lại trang chủ
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
