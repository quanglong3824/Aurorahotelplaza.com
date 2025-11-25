<?php
/**
 * Test Kết Nối Database - Local & Host
 * Kiểm tra kết nối song song giữa Local (XAMPP) và Host (Production)
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Kết Nối Database</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            border-radius: 15px 15px 0 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .header p {
            color: #7f8c8d;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .env-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
        }
        .env-local {
            background: #3498db;
            color: white;
        }
        .env-production {
            background: #e74c3c;
            color: white;
        }
        .test-section {
            margin: 30px 0;
            padding: 25px;
            border-radius: 10px;
            border-left: 5px solid #3498db;
        }
        .test-local {
            background: #e3f2fd;
            border-left-color: #2196F3;
        }
        .test-host {
            background: #fce4ec;
            border-left-color: #E91E63;
        }
        .test-current {
            background: #f1f8e9;
            border-left-color: #8BC34A;
        }
        .status {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            margin: 10px 0;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status-icon {
            font-size: 20px;
            margin-right: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .info-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .info-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .note strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Test Kết Nối Database</h1>
            <p>Kiểm tra kết nối song song: Local (XAMPP) & Host (Production)</p>
            <span class="env-badge <?php echo $isLocal ? 'env-local' : 'env-production'; ?>">
                <?php echo $isLocal ? '💻 ĐANG CHẠY LOCAL' : '🌐 ĐANG CHẠY TRÊN HOST'; ?>
            </span>
        </div>

        <div class="content">
            <!-- Môi trường hiện tại -->
            <div class="test-section test-current">
                <h2>🎯 Môi Trường Hiện Tại: <?php echo DB_ENVIRONMENT; ?></h2>
                
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label">🖥️ Server Name</div>
                        <div class="info-value"><?php echo $_SERVER['SERVER_NAME']; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">📍 Server IP</div>
                        <div class="info-value"><?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">💾 Database</div>
                        <div class="info-value"><?php echo DB_NAME; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">👤 User</div>
                        <div class="info-value"><?php echo DB_USER; ?></div>
                    </div>
                </div>

                <?php
                // Test kết nối hiện tại
                $database = new Database();
                $conn = $database->getConnection();
                
                if ($conn) {
                    echo '<div class="status status-success">';
                    echo '<span class="status-icon">✅</span>';
                    echo '<span>Kết nối thành công!</span>';
                    echo '</div>';
                    
                    // Lấy thông tin server
                    try {
                        $stmt = $conn->query("SELECT VERSION() as version, DATABASE() as db, NOW() as time");
                        $info = $stmt->fetch();
                        
                        echo '<div class="info-grid">';
                        echo '<div class="info-card">';
                        echo '<div class="info-label">📌 MySQL Version</div>';
                        echo '<div class="info-value">' . $info['version'] . '</div>';
                        echo '</div>';
                        echo '<div class="info-card">';
                        echo '<div class="info-label">💾 Database Active</div>';
                        echo '<div class="info-value">' . $info['db'] . '</div>';
                        echo '</div>';
                        echo '<div class="info-card">';
                        echo '<div class="info-label">🕐 Server Time</div>';
                        echo '<div class="info-value">' . $info['time'] . '</div>';
                        echo '</div>';
                        echo '</div>';
                        
                        // Đếm số bảng
                        $stmt = $conn->query("SHOW TABLES");
                        $tableCount = $stmt->rowCount();
                        echo '<p style="margin-top: 15px;"><strong>📊 Tổng số bảng:</strong> ' . $tableCount . '</p>';
                        
                    } catch (Exception $e) {
                        echo '<p style="color: #e74c3c;">Lỗi truy vấn: ' . $e->getMessage() . '</p>';
                    }
                } else {
                    echo '<div class="status status-error">';
                    echo '<span class="status-icon">❌</span>';
                    echo '<span>Kết nối thất bại!</span>';
                    echo '</div>';
                    echo '<p><strong>Lỗi:</strong> ' . $database->getLastError() . '</p>';
                }
                ?>
            </div>

            <!-- Test Local -->
            <div class="test-section test-local">
                <h2>💻 Test Kết Nối LOCAL (XAMPP)</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label">Host</div>
                        <div class="info-value"><?php echo DB_LOCAL_HOST; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Database</div>
                        <div class="info-value"><?php echo DB_LOCAL_NAME; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">User</div>
                        <div class="info-value"><?php echo DB_LOCAL_USER; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Password</div>
                        <div class="info-value"><?php echo empty(DB_LOCAL_PASSWORD) ? '(trống)' : '***'; ?></div>
                    </div>
                </div>

                <?php
                // Test kết nối Local
                try {
                    $localConn = new PDO(
                        "mysql:host=localhost;port=3306;dbname=" . DB_LOCAL_NAME . ";charset=utf8",
                        DB_LOCAL_USER,
                        DB_LOCAL_PASSWORD
                    );
                    $localConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    echo '<div class="status status-success">';
                    echo '<span class="status-icon">✅</span>';
                    echo '<span>Kết nối LOCAL thành công!</span>';
                    echo '</div>';
                    
                    $stmt = $localConn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_LOCAL_NAME . "'");
                    $result = $stmt->fetch();
                    echo '<p><strong>📊 Số bảng trong database:</strong> ' . $result['count'] . '</p>';
                    
                } catch (PDOException $e) {
                    echo '<div class="status status-error">';
                    echo '<span class="status-icon">❌</span>';
                    echo '<span>Kết nối LOCAL thất bại!</span>';
                    echo '</div>';
                    echo '<p><strong>Lỗi:</strong> ' . $e->getMessage() . '</p>';
                    echo '<p><strong>Gợi ý:</strong> Kiểm tra XAMPP MySQL đã chạy chưa, database đã import chưa.</p>';
                }
                ?>
            </div>

            <!-- Test Host -->
            <div class="test-section test-host">
                <h2>🌐 Test Kết Nối HOST (Production)</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label">Host</div>
                        <div class="info-value"><?php echo DB_HOST_HOST; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Database</div>
                        <div class="info-value"><?php echo DB_HOST_NAME; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">User</div>
                        <div class="info-value"><?php echo DB_HOST_USER; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Password</div>
                        <div class="info-value">***</div>
                    </div>
                </div>

                <?php
                // Test kết nối Host
                try {
                    $hostConn = new PDO(
                        "mysql:host=localhost;port=3306;dbname=" . DB_HOST_NAME . ";charset=utf8",
                        DB_HOST_USER,
                        DB_HOST_PASSWORD
                    );
                    $hostConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    echo '<div class="status status-success">';
                    echo '<span class="status-icon">✅</span>';
                    echo '<span>Kết nối HOST thành công!</span>';
                    echo '</div>';
                    
                    $stmt = $hostConn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_HOST_NAME . "'");
                    $result = $stmt->fetch();
                    echo '<p><strong>📊 Số bảng trong database:</strong> ' . $result['count'] . '</p>';
                    
                } catch (PDOException $e) {
                    echo '<div class="status status-error">';
                    echo '<span class="status-icon">❌</span>';
                    echo '<span>Kết nối HOST thất bại!</span>';
                    echo '</div>';
                    echo '<p><strong>Lỗi:</strong> ' . $e->getMessage() . '</p>';
                    echo '<p><strong>Gợi ý:</strong> Nếu chạy từ local, đây là bình thường (không thể kết nối remote). Upload file lên host để test.</p>';
                }
                ?>
            </div>

            <!-- Tóm tắt -->
            <div class="note">
                <strong>📝 Tóm Tắt:</strong><br>
                • Hệ thống tự động chuyển đổi giữa Local và Host dựa trên môi trường<br>
                • Khi chạy trên localhost → dùng cấu hình LOCAL (root/trống)<br>
                • Khi chạy trên domain → dùng cấu hình HOST (auroraho_longdev)<br>
                • File config: <code>config/database.php</code><br>
                • Thời gian test: <?php echo date('d/m/Y H:i:s'); ?>
            </div>
        </div>
    </div>
</body>
</html>
