<?php
/**
 * Security & Performance Check Tool
 * Aurora Hotel Plaza
 * 
 * IMPORTANT: This file should be deleted or protected in production!
 * Only use for testing and development.
 */

// Prevent direct access in production
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    die('Access denied. This tool is only available in development environment.');
}

require_once __DIR__ . '/helpers/security.php';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security & Performance Check - Aurora Hotel Plaza</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        
        h1 {
            color: #1A237E;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        
        h2 {
            color: #1A237E;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .check-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .check-item.pass {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
        }
        
        .check-item.fail {
            background: #ffebee;
            border-left: 4px solid #f44336;
        }
        
        .check-item.warning {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
        }
        
        .status {
            font-weight: bold;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .status.pass {
            background: #4caf50;
            color: white;
        }
        
        .status.fail {
            background: #f44336;
            color: white;
        }
        
        .status.warning {
            background: #ff9800;
            color: white;
        }
        
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
            border-left: 4px solid #2196f3;
        }
        
        .code {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 10px;
            overflow-x: auto;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .metric {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 32px;
            font-weight: bold;
            color: #1A237E;
            margin: 10px 0;
        }
        
        .metric-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Security & Performance Check</h1>
            <p class="subtitle">Aurora Hotel Plaza - Development Tool</p>
        </div>

        <!-- Security Checks -->
        <div class="section">
            <h2>🛡️ Security Checks</h2>
            
            <?php
            $security_checks = [];
            
            // Check 1: HTTPS
            $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            $security_checks[] = [
                'name' => 'HTTPS Enabled',
                'status' => $is_https ? 'pass' : 'warning',
                'message' => $is_https ? 'HTTPS is enabled' : 'HTTPS should be enabled in production'
            ];
            
            // Check 2: Security Headers
            $headers_to_check = [
                'X-Content-Type-Options',
                'X-Frame-Options',
                'X-XSS-Protection',
                'Strict-Transport-Security'
            ];
            
            $headers_set = 0;
            foreach ($headers_to_check as $header) {
                if (isset(apache_response_headers()[$header])) {
                    $headers_set++;
                }
            }
            
            $security_checks[] = [
                'name' => 'Security Headers',
                'status' => $headers_set >= 3 ? 'pass' : 'warning',
                'message' => "$headers_set out of " . count($headers_to_check) . " security headers are set"
            ];
            
            // Check 3: Session Security
            $session_secure = ini_get('session.cookie_httponly') && ini_get('session.cookie_secure');
            $security_checks[] = [
                'name' => 'Secure Session Configuration',
                'status' => $session_secure ? 'pass' : 'fail',
                'message' => $session_secure ? 'Session cookies are secure' : 'Session cookies should be HttpOnly and Secure'
            ];
            
            // Check 4: Error Display
            $error_display = ini_get('display_errors');
            $security_checks[] = [
                'name' => 'Error Display',
                'status' => !$error_display ? 'pass' : 'warning',
                'message' => !$error_display ? 'Error display is disabled (good for production)' : 'Error display should be disabled in production'
            ];
            
            // Check 5: File Permissions
            $sensitive_files = [
                __DIR__ . '/config/database.php',
                __DIR__ . '/.htaccess',
                __DIR__ . '/helpers/security.php'
            ];
            
            $permissions_ok = true;
            foreach ($sensitive_files as $file) {
                if (file_exists($file)) {
                    $perms = substr(sprintf('%o', fileperms($file)), -4);
                    if ($perms > '0644') {
                        $permissions_ok = false;
                        break;
                    }
                }
            }
            
            $security_checks[] = [
                'name' => 'File Permissions',
                'status' => $permissions_ok ? 'pass' : 'warning',
                'message' => $permissions_ok ? 'Sensitive files have correct permissions' : 'Some files have overly permissive permissions'
            ];
            
            // Check 6: .htaccess exists
            $htaccess_exists = file_exists(__DIR__ . '/.htaccess');
            $security_checks[] = [
                'name' => '.htaccess Protection',
                'status' => $htaccess_exists ? 'pass' : 'fail',
                'message' => $htaccess_exists ? '.htaccess file exists' : '.htaccess file is missing'
            ];
            
            // Check 7: Security Helper
            $security_helper_exists = class_exists('Security');
            $security_checks[] = [
                'name' => 'Security Helper Class',
                'status' => $security_helper_exists ? 'pass' : 'fail',
                'message' => $security_helper_exists ? 'Security helper class is available' : 'Security helper class is missing'
            ];
            
            // Display security checks
            foreach ($security_checks as $check) {
                echo '<div class="check-item ' . $check['status'] . '">';
                echo '<span>' . $check['name'] . ': ' . $check['message'] . '</span>';
                echo '<span class="status ' . $check['status'] . '">' . strtoupper($check['status']) . '</span>';
                echo '</div>';
            }
            ?>
            
            <div class="info">
                <strong>💡 Security Recommendations:</strong><br>
                • Enable HTTPS in production<br>
                • Set all security headers in .htaccess<br>
                • Use prepared statements for all database queries<br>
                • Implement CSRF protection on all forms<br>
                • Validate and sanitize all user inputs<br>
                • Keep PHP and all dependencies updated
            </div>
        </div>

        <!-- Performance Checks -->
        <div class="section">
            <h2>⚡ Performance Checks</h2>
            
            <?php
            $performance_checks = [];
            
            // Check 1: Gzip Compression
            $gzip_enabled = extension_loaded('zlib');
            $performance_checks[] = [
                'name' => 'Gzip Compression',
                'status' => $gzip_enabled ? 'pass' : 'warning',
                'message' => $gzip_enabled ? 'Gzip compression is available' : 'Gzip compression is not available'
            ];
            
            // Check 2: OPcache
            $opcache_enabled = function_exists('opcache_get_status') && opcache_get_status() !== false;
            $performance_checks[] = [
                'name' => 'OPcache',
                'status' => $opcache_enabled ? 'pass' : 'warning',
                'message' => $opcache_enabled ? 'OPcache is enabled' : 'OPcache should be enabled for better performance'
            ];
            
            // Check 3: Memory Limit
            $memory_limit = ini_get('memory_limit');
            $memory_ok = (int)$memory_limit >= 128;
            $performance_checks[] = [
                'name' => 'PHP Memory Limit',
                'status' => $memory_ok ? 'pass' : 'warning',
                'message' => "Memory limit is set to $memory_limit"
            ];
            
            // Check 4: Max Execution Time
            $max_execution = ini_get('max_execution_time');
            $execution_ok = (int)$max_execution >= 30;
            $performance_checks[] = [
                'name' => 'Max Execution Time',
                'status' => $execution_ok ? 'pass' : 'warning',
                'message' => "Max execution time is {$max_execution}s"
            ];
            
            // Check 5: Image Optimization
            $image_extensions = ['gd', 'imagick'];
            $image_support = false;
            foreach ($image_extensions as $ext) {
                if (extension_loaded($ext)) {
                    $image_support = true;
                    break;
                }
            }
            $performance_checks[] = [
                'name' => 'Image Processing',
                'status' => $image_support ? 'pass' : 'warning',
                'message' => $image_support ? 'Image processing extensions available' : 'No image processing extensions found'
            ];
            
            // Display performance checks
            foreach ($performance_checks as $check) {
                echo '<div class="check-item ' . $check['status'] . '">';
                echo '<span>' . $check['name'] . ': ' . $check['message'] . '</span>';
                echo '<span class="status ' . $check['status'] . '">' . strtoupper($check['status']) . '</span>';
                echo '</div>';
            }
            ?>
            
            <div class="info">
                <strong>💡 Performance Recommendations:</strong><br>
                • Enable OPcache for PHP code caching<br>
                • Use CDN for static assets<br>
                • Implement lazy loading for images<br>
                • Minify CSS and JavaScript files<br>
                • Enable browser caching via .htaccess<br>
                • Optimize and compress images (WebP format)<br>
                • Use database query caching
            </div>
        </div>

        <!-- PHP Configuration -->
        <div class="section">
            <h2>⚙️ PHP Configuration</h2>
            
            <div class="grid">
                <div class="metric">
                    <div class="metric-label">PHP Version</div>
                    <div class="metric-value"><?php echo phpversion(); ?></div>
                </div>
                <div class="metric">
                    <div class="metric-label">Memory Limit</div>
                    <div class="metric-value"><?php echo ini_get('memory_limit'); ?></div>
                </div>
                <div class="metric">
                    <div class="metric-label">Max Upload Size</div>
                    <div class="metric-value"><?php echo ini_get('upload_max_filesize'); ?></div>
                </div>
                <div class="metric">
                    <div class="metric-label">Max POST Size</div>
                    <div class="metric-value"><?php echo ini_get('post_max_size'); ?></div>
                </div>
                <div class="metric">
                    <div class="metric-label">Max Execution Time</div>
                    <div class="metric-value"><?php echo ini_get('max_execution_time'); ?>s</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Loaded Extensions</div>
                    <div class="metric-value"><?php echo count(get_loaded_extensions()); ?></div>
                </div>
            </div>
        </div>

        <!-- SEO Checks -->
        <div class="section">
            <h2>🔍 SEO Checks</h2>
            
            <?php
            $seo_checks = [];
            
            // Check 1: robots.txt
            $robots_exists = file_exists(__DIR__ . '/robots.txt');
            $seo_checks[] = [
                'name' => 'robots.txt',
                'status' => $robots_exists ? 'pass' : 'fail',
                'message' => $robots_exists ? 'robots.txt file exists' : 'robots.txt file is missing'
            ];
            
            // Check 2: sitemap.xml
            $sitemap_exists = file_exists(__DIR__ . '/sitemap.xml');
            $seo_checks[] = [
                'name' => 'sitemap.xml',
                'status' => $sitemap_exists ? 'pass' : 'fail',
                'message' => $sitemap_exists ? 'sitemap.xml file exists' : 'sitemap.xml file is missing'
            ];
            
            // Check 3: SEO Helper
            $seo_helper_exists = file_exists(__DIR__ . '/helpers/seo.php');
            $seo_checks[] = [
                'name' => 'SEO Helper Class',
                'status' => $seo_helper_exists ? 'pass' : 'warning',
                'message' => $seo_helper_exists ? 'SEO helper class exists' : 'SEO helper class is missing'
            ];
            
            // Display SEO checks
            foreach ($seo_checks as $check) {
                echo '<div class="check-item ' . $check['status'] . '">';
                echo '<span>' . $check['name'] . ': ' . $check['message'] . '</span>';
                echo '<span class="status ' . $check['status'] . '">' . strtoupper($check['status']) . '</span>';
                echo '</div>';
            }
            ?>
            
            <div class="info">
                <strong>💡 SEO Recommendations:</strong><br>
                • Add meta descriptions to all pages<br>
                • Use semantic HTML5 tags<br>
                • Implement structured data (JSON-LD)<br>
                • Optimize images with alt tags<br>
                • Create XML sitemap and submit to search engines<br>
                • Use canonical URLs<br>
                • Implement breadcrumb navigation
            </div>
        </div>

        <!-- File Structure -->
        <div class="section">
            <h2>📁 Important Files Check</h2>
            
            <?php
            $important_files = [
                '.htaccess' => 'Security configuration',
                'robots.txt' => 'Search engine directives',
                'sitemap.xml' => 'Site structure for search engines',
                'helpers/security.php' => 'Security helper functions',
                'helpers/seo.php' => 'SEO helper functions',
                'assets/js/performance.js' => 'Performance optimization script',
                'config/database.php' => 'Database configuration'
            ];
            
            foreach ($important_files as $file => $description) {
                $exists = file_exists(__DIR__ . '/' . $file);
                $status = $exists ? 'pass' : 'fail';
                
                echo '<div class="check-item ' . $status . '">';
                echo '<span><strong>' . $file . '</strong>: ' . $description . '</span>';
                echo '<span class="status ' . $status . '">' . ($exists ? 'EXISTS' : 'MISSING') . '</span>';
                echo '</div>';
            }
            ?>
        </div>

        <div class="section">
            <h2>⚠️ Important Notice</h2>
            <div class="info">
                <strong>🔒 Security Warning:</strong><br>
                This security check tool should be <strong>DELETED or PROTECTED</strong> before deploying to production!<br>
                It exposes sensitive information about your server configuration.<br><br>
                
                <strong>To protect this file:</strong><br>
                1. Delete it: <code>rm security-check.php</code><br>
                2. Or add to .htaccess:<br>
                <div class="code">
&lt;Files "security-check.php"&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;Require ip 127.0.0.1<br>
&nbsp;&nbsp;&nbsp;&nbsp;Require ip YOUR_IP_ADDRESS<br>
&lt;/Files&gt;
                </div>
            </div>
        </div>
    </div>
</body>
</html>
