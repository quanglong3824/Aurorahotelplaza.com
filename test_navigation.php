<?php
/**
 * Test Navigation Links
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Navigation - Aurora Hotel Plaza</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1, h2 {
            color: #333;
        }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .link-list {
            list-style: none;
            padding: 0;
        }
        .link-list li {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .link-list a {
            color: #d4af37;
            text-decoration: none;
            font-weight: bold;
        }
        .link-list a:hover {
            text-decoration: underline;
        }
        .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
        .status.ok {
            background: #d4edda;
            color: #155724;
        }
        .status.new {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <h1>🧪 Test Navigation Links - Aurora Hotel Plaza</h1>
    
    <div class="section">
        <h2>📝 Authentication Pages</h2>
        <ul class="link-list">
            <li>
                <a href="auth/login.php">Đăng nhập</a>
                <span class="status new">NEW</span>
            </li>
            <li>
                <a href="auth/register.php">Đăng ký</a>
                <span class="status new">NEW</span>
            </li>
            <li>
                <a href="auth/forgot-password.php">Quên mật khẩu</a>
                <span class="status new">NEW</span>
            </li>
            <li>
                <a href="auth/test_auth.php">Test Auth System (Create test users)</a>
                <span class="status new">NEW</span>
            </li>
        </ul>
    </div>

    <div class="section">
        <h2>🏨 Booking System</h2>
        <ul class="link-list">
            <li>
                <a href="booking/index.php">Đặt phòng</a>
                <span class="status new">NEW</span>
            </li>
            <li>
                <a href="booking/test_db.php">Test Booking System (Setup sample data)</a>
                <span class="status new">NEW</span>
            </li>
        </ul>
    </div>

    <div class="section">
        <h2>🏠 Main Pages</h2>
        <ul class="link-list">
            <li>
                <a href="index.php">Trang chủ</a>
                <span class="status ok">OK</span>
            </li>
            <li>
                <a href="rooms.php">Phòng</a>
                <span class="status ok">OK</span>
            </li>
            <li>
                <a href="apartments.php">Căn hộ</a>
                <span class="status ok">OK</span>
            </li>
            <li>
                <a href="about.php">Giới thiệu</a>
                <span class="status ok">OK</span>
            </li>
            <li>
                <a href="contact.php">Liên hệ</a>
                <span class="status ok">OK</span>
            </li>
        </ul>
    </div>

    <div class="section">
        <h2>🛠️ Setup Instructions</h2>
        <ol>
            <li><strong>Import Database:</strong> Import file <code>docs/DATABASE_SCHEMA.sql</code> vào MySQL</li>
            <li><strong>Configure Database:</strong> Kiểm tra <code>config/database.php</code></li>
            <li><strong>Setup Test Data:</strong> 
                <ul>
                    <li>Chạy <a href="booking/test_db.php">booking/test_db.php</a> để tạo room types và rooms</li>
                    <li>Chạy <a href="auth/test_auth.php">auth/test_auth.php</a> để tạo test users</li>
                </ul>
            </li>
            <li><strong>Configure VNPay:</strong> Cập nhật <code>payment/config.php</code> với thông tin VNPay thật</li>
            <li><strong>Test Login:</strong> Sử dụng test accounts từ auth/test_auth.php</li>
            <li><strong>Test Booking:</strong> Đăng nhập và thử đặt phòng</li>
        </ol>
    </div>

    <div class="section">
        <h2>📚 Documentation</h2>
        <ul class="link-list">
            <li>
                <a href="booking/README.md" target="_blank">Booking System Documentation</a>
            </li>
            <li>
                <a href="auth/README.md" target="_blank">Authentication System Documentation</a>
            </li>
            <li>
                <a href="docs/DATABASE_SCHEMA.sql" target="_blank">Database Schema</a>
            </li>
        </ul>
    </div>

    <div class="section">
        <h2>✅ Test Checklist</h2>
        <ul>
            <li>☐ Database imported successfully</li>
            <li>☐ Database connection working</li>
            <li>☐ Room types and rooms created</li>
            <li>☐ Test users created</li>
            <li>☐ Can login with test account</li>
            <li>☐ Header shows user menu when logged in</li>
            <li>☐ Can access booking page</li>
            <li>☐ Can select room and dates</li>
            <li>☐ Can fill guest information</li>
            <li>☐ Can create booking (without payment)</li>
            <li>☐ VNPay configured (optional)</li>
        </ul>
    </div>

    <div class="section">
        <h2>🔐 Test Accounts (After running test_auth.php)</h2>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-top: 10px;">
            <p><strong>Customer Account:</strong><br>
            Email: test@aurorahotel.com<br>
            Password: test123</p>
            
            <p><strong>Admin Account:</strong><br>
            Email: admin@aurorahotel.com<br>
            Password: admin123</p>
        </div>
    </div>

</body>
</html>
