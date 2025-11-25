<?php
/**
 * Project Audit & Route Checker
 * Kiểm tra toàn bộ project: routes, assets, includes, performance
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/environment.php';

header('Content-Type: text/html; charset=utf-8');

// Collect audit data
$audit = [
    'routes' => [],
    'assets' => [],
    'includes' => [],
    'performance' => [],
    'errors' => [],
    'warnings' => []
];

// 1. Check all PHP files for broken includes
$phpFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/../', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$phpFiles = new RegexIterator($phpFiles, '/\.php$/');

foreach ($phpFiles as $file) {
    $filePath = $file->getPathname();
    $relativePath = str_replace(__DIR__ . '/../', '', $filePath);
    
    // Skip test and admin files
    if (strpos($relativePath, 'test/') === 0 || strpos($relativePath, 'admin/') === 0) {
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Check for broken includes
    if (preg_match_all('/(?:require|include)(?:_once)?\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
        foreach ($matches[1] as $includePath) {
            $fullPath = dirname($filePath) . '/' . $includePath;
            $fullPath = str_replace('\\', '/', $fullPath);
            $fullPath = preg_replace('#/+#', '/', $fullPath);
            
            if (!file_exists($fullPath)) {
                $audit['errors'][] = [
                    'file' => $relativePath,
                    'type' => 'Missing Include',
                    'path' => $includePath,
                    'full_path' => $fullPath
                ];
            }
        }
    }
}

// 2. Check all asset references
$assetDir = __DIR__ . '/../assets';
$cssFiles = glob($assetDir . '/css/*.css');
$jsFiles = glob($assetDir . '/js/*.js');
$imgDirs = glob($assetDir . '/img/*', GLOB_ONLYDIR);

$audit['assets']['css_count'] = count($cssFiles);
$audit['assets']['js_count'] = count($jsFiles);
$audit['assets']['img_dirs'] = count($imgDirs);

// 3. Check database connection
$db = new Database();
$conn = $db->getConnection();
if ($conn) {
    $audit['performance']['database'] = 'Connected';
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
        $result = $stmt->fetch();
        $audit['performance']['tables'] = $result['count'];
    } catch (Exception $e) {
        $audit['errors'][] = ['type' => 'Database Query Error', 'message' => $e->getMessage()];
    }
} else {
    $audit['errors'][] = ['type' => 'Database Connection Failed', 'message' => $db->getLastError()];
}

// 4. Check main routes
$mainRoutes = [
    'index.php' => 'Trang chủ',
    'rooms.php' => 'Phòng',
    'apartments.php' => 'Căn hộ',
    'services.php' => 'Dịch vụ',
    'gallery.php' => 'Thư viện ảnh',
    'blog.php' => 'Blog',
    'contact.php' => 'Liên hệ',
    'profile.php' => 'Hồ sơ',
];

foreach ($mainRoutes as $file => $name) {
    $filePath = __DIR__ . '/../' . $file;
    if (file_exists($filePath)) {
        $audit['routes'][] = [
            'name' => $name,
            'file' => $file,
            'status' => 'OK',
            'size' => filesize($filePath)
        ];
    } else {
        $audit['errors'][] = ['type' => 'Missing Route', 'file' => $file];
    }
}

// 5. Check subdirectory routes
$subdirs = ['room-details', 'apartment-details', 'auth', 'booking', 'profile', 'services-pages'];
foreach ($subdirs as $dir) {
    $dirPath = __DIR__ . '/../' . $dir;
    if (is_dir($dirPath)) {
        $files = glob($dirPath . '/*.php');
        $audit['routes'][] = [
            'name' => $dir,
            'type' => 'directory',
            'file_count' => count($files),
            'status' => 'OK'
        ];
    } else {
        $audit['warnings'][] = ['type' => 'Missing Directory', 'dir' => $dir];
    }
}

// 6. Check .htaccess
$htaccessPath = __DIR__ . '/../.htaccess';
if (file_exists($htaccessPath)) {
    $audit['performance']['htaccess'] = 'Configured';
} else {
    $audit['warnings'][] = ['type' => 'Missing .htaccess', 'impact' => 'URL rewriting may not work'];
}

// 7. Check environment configuration
$audit['performance']['environment'] = ENVIRONMENT;
$audit['performance']['base_url'] = BASE_URL;
$audit['performance']['debug_mode'] = DEBUG_MODE ? 'ON' : 'OFF';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Audit Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .section {
            margin: 30px 0;
            padding: 25px;
            border-radius: 10px;
            border-left: 5px solid #3498db;
            background: #f8f9fa;
        }
        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin: 5px;
        }
        .status-ok { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-warning { background: #fff3cd; color: #856404; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #34495e;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .error-list, .warning-list {
            list-style: none;
            padding: 0;
        }
        .error-list li, .warning-list li {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .error-list li {
            background: #ffebee;
            border-left-color: #e74c3c;
            color: #c0392b;
        }
        .warning-list li {
            background: #fff8e1;
            border-left-color: #f39c12;
            color: #d68910;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #3498db;
        }
        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .summary {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .summary strong {
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Project Audit Report</h1>
            <p>Kiểm tra toàn diện: Routes, Assets, Includes, Performance</p>
            <p style="color: #7f8c8d; margin-top: 10px;">Thời gian: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>

        <div class="content">
            <!-- Summary -->
            <div class="summary">
                <strong>📊 Tóm tắt:</strong><br>
                • Lỗi: <span class="status-badge status-error"><?php echo count($audit['errors']); ?></span><br>
                • Cảnh báo: <span class="status-badge status-warning"><?php echo count($audit['warnings']); ?></span><br>
                • Routes: <span class="status-badge status-ok"><?php echo count($audit['routes']); ?></span>
            </div>

            <!-- Errors -->
            <?php if (!empty($audit['errors'])): ?>
            <div class="section" style="border-left-color: #e74c3c;">
                <h2>❌ Lỗi Phát Hiện (<?php echo count($audit['errors']); ?>)</h2>
                <ul class="error-list">
                    <?php foreach ($audit['errors'] as $error): ?>
                    <li>
                        <strong><?php echo $error['type']; ?></strong><br>
                        <?php if (isset($error['file'])): ?>
                            File: <code><?php echo $error['file']; ?></code><br>
                        <?php endif; ?>
                        <?php if (isset($error['path'])): ?>
                            Path: <code><?php echo $error['path']; ?></code><br>
                        <?php endif; ?>
                        <?php if (isset($error['message'])): ?>
                            Message: <?php echo $error['message']; ?><br>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Warnings -->
            <?php if (!empty($audit['warnings'])): ?>
            <div class="section" style="border-left-color: #f39c12;">
                <h2>⚠️ Cảnh Báo (<?php echo count($audit['warnings']); ?>)</h2>
                <ul class="warning-list">
                    <?php foreach ($audit['warnings'] as $warning): ?>
                    <li>
                        <strong><?php echo $warning['type']; ?></strong><br>
                        <?php if (isset($warning['dir'])): ?>
                            Directory: <code><?php echo $warning['dir']; ?></code><br>
                        <?php endif; ?>
                        <?php if (isset($warning['impact'])): ?>
                            Impact: <?php echo $warning['impact']; ?><br>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Routes Status -->
            <div class="section">
                <h2>🛣️ Routes Status</h2>
                <table>
                    <tr>
                        <th>Route Name</th>
                        <th>File/Type</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                    <?php foreach ($audit['routes'] as $route): ?>
                    <tr>
                        <td><?php echo $route['name']; ?></td>
                        <td><?php echo $route['file'] ?? $route['type']; ?></td>
                        <td><span class="status-badge status-ok"><?php echo $route['status']; ?></span></td>
                        <td>
                            <?php if (isset($route['size'])): ?>
                                Size: <?php echo number_format($route['size'] / 1024, 2); ?> KB
                            <?php elseif (isset($route['file_count'])): ?>
                                Files: <?php echo $route['file_count']; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Assets Status -->
            <div class="section">
                <h2>📦 Assets Status</h2>
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $audit['assets']['css_count']; ?></div>
                        <div class="stat-label">CSS Files</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $audit['assets']['js_count']; ?></div>
                        <div class="stat-label">JS Files</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $audit['assets']['img_dirs']; ?></div>
                        <div class="stat-label">Image Directories</div>
                    </div>
                </div>
            </div>

            <!-- Performance & Configuration -->
            <div class="section">
                <h2>⚡ Performance & Configuration</h2>
                <table>
                    <tr>
                        <th>Setting</th>
                        <th>Value</th>
                    </tr>
                    <?php foreach ($audit['performance'] as $key => $value): ?>
                    <tr>
                        <td><?php echo ucfirst(str_replace('_', ' ', $key)); ?></td>
                        <td>
                            <?php if ($key === 'database'): ?>
                                <span class="status-badge status-ok"><?php echo $value; ?></span>
                            <?php elseif ($key === 'debug_mode' && $value === 'OFF'): ?>
                                <span class="status-badge status-ok"><?php echo $value; ?></span>
                            <?php elseif ($key === 'debug_mode' && $value === 'ON'): ?>
                                <span class="status-badge status-warning"><?php echo $value; ?></span>
                            <?php else: ?>
                                <code><?php echo $value; ?></code>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Recommendations -->
            <div class="section" style="border-left-color: #27ae60;">
                <h2>💡 Khuyến Nghị</h2>
                <ul style="list-style: none; padding: 0;">
                    <li style="padding: 10px 0; border-bottom: 1px solid #ddd;">
                        ✅ Sử dụng hàm <code>asset()</code> cho tất cả asset URLs
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #ddd;">
                        ✅ Sử dụng hàm <code>url()</code> cho tất cả internal links
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #ddd;">
                        ✅ Kiểm tra .htaccess được cấu hình đúng cho subdirectory
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #ddd;">
                        ✅ Bật caching headers cho static assets
                    </li>
                    <li style="padding: 10px 0;">
                        ✅ Minify CSS/JS files để tăng tốc độ tải
                    </li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
