<?php
/**
 * Script tạo tài khoản admin
 * Chạy file này một lần để tạo tài khoản admin đầu tiên
 * Sau khi tạo xong, nên xóa hoặc đổi tên file này để bảo mật
 */

require_once '../config/database.php';

// Thông tin admin mặc định
$admin_data = [
    'email' => 'admin@aurorahotelplaza.com',
    'password' => 'Admin@123456', // Đổi password này sau khi đăng nhập
    'full_name' => 'Administrator',
    'phone' => '0123456789',
    'user_role' => 'admin',
    'status' => 'active',
    'email_verified' => 1
];

try {
    $db = getDB();
    
    // Kiểm tra email đã tồn tại chưa
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
    $stmt->execute([':email' => $admin_data['email']]);
    
    if ($stmt->fetch()) {
        echo "<div style='padding: 20px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; margin: 20px;'>";
        echo "<h2 style='color: #92400e; margin: 0 0 10px 0;'>⚠️ Tài khoản đã tồn tại</h2>";
        echo "<p style='margin: 0;'>Email <strong>{$admin_data['email']}</strong> đã có trong hệ thống.</p>";
        echo "<p style='margin: 10px 0 0 0;'>Nếu quên mật khẩu, vui lòng sử dụng chức năng <a href='../auth/forgot-password.php'>Quên mật khẩu</a></p>";
        echo "</div>";
        exit;
    }
    
    // Hash password
    $password_hash = password_hash($admin_data['password'], PASSWORD_DEFAULT);
    
    // Insert admin user
    $stmt = $db->prepare("
        INSERT INTO users (
            email, password_hash, full_name, phone, user_role, 
            status, email_verified, created_at
        ) VALUES (
            :email, :password_hash, :full_name, :phone, :user_role,
            :status, :email_verified, NOW()
        )
    ");
    
    $stmt->execute([
        ':email' => $admin_data['email'],
        ':password_hash' => $password_hash,
        ':full_name' => $admin_data['full_name'],
        ':phone' => $admin_data['phone'],
        ':user_role' => $admin_data['user_role'],
        ':status' => $admin_data['status'],
        ':email_verified' => $admin_data['email_verified']
    ]);
    
    $user_id = $db->lastInsertId();
    
    // Tạo loyalty record cho user
    $stmt = $db->prepare("
        INSERT INTO user_loyalty (user_id, current_points, lifetime_points, created_at)
        VALUES (:user_id, 0, 0, NOW())
    ");
    $stmt->execute([':user_id' => $user_id]);
    
    // Success message
    echo "<!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Tạo tài khoản Admin thành công</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 500px;
                width: 100%;
                padding: 40px;
            }
            .success-icon {
                width: 80px;
                height: 80px;
                background: #10b981;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 48px;
                color: white;
            }
            h1 {
                color: #1f2937;
                margin: 0 0 10px 0;
                text-align: center;
                font-size: 24px;
            }
            p {
                color: #6b7280;
                text-align: center;
                margin: 0 0 30px 0;
            }
            .info-box {
                background: #f3f4f6;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                margin: 10px 0;
                padding: 8px 0;
                border-bottom: 1px solid #e5e7eb;
            }
            .info-row:last-child {
                border-bottom: none;
            }
            .label {
                color: #6b7280;
                font-weight: 500;
            }
            .value {
                color: #1f2937;
                font-weight: 600;
            }
            .warning {
                background: #fef3c7;
                border: 1px solid #f59e0b;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                color: #92400e;
            }
            .warning strong {
                display: block;
                margin-bottom: 5px;
            }
            .btn {
                display: block;
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-align: center;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                transition: transform 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
            .btn-secondary {
                background: #6b7280;
                margin-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='success-icon'>✓</div>
            <h1>Tạo tài khoản Admin thành công!</h1>
            <p>Tài khoản quản trị viên đã được tạo và sẵn sàng sử dụng</p>
            
            <div class='info-box'>
                <div class='info-row'>
                    <span class='label'>Email:</span>
                    <span class='value'>{$admin_data['email']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Mật khẩu:</span>
                    <span class='value'>{$admin_data['password']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Họ tên:</span>
                    <span class='value'>{$admin_data['full_name']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Vai trò:</span>
                    <span class='value'>Administrator</span>
                </div>
            </div>
            
            <div class='warning'>
                <strong>⚠️ Quan trọng về bảo mật:</strong>
                1. Đổi mật khẩu ngay sau khi đăng nhập lần đầu<br>
                2. Xóa hoặc đổi tên file <code>create-admin.php</code> này<br>
                3. Không chia sẻ thông tin đăng nhập với người khác
            </div>
            
            <a href='../auth/login.php' class='btn'>Đăng nhập ngay</a>
            <a href='../index.php' class='btn btn-secondary'>Về trang chủ</a>
        </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<div style='padding: 20px; background: #fee; border: 1px solid #f00; border-radius: 8px; margin: 20px;'>";
    echo "<h2 style='color: #c00; margin: 0 0 10px 0;'>❌ Lỗi tạo tài khoản</h2>";
    echo "<p style='margin: 0;'><strong>Chi tiết lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='margin: 10px 0 0 0;'>Vui lòng kiểm tra:</p>";
    echo "<ul style='margin: 5px 0;'>";
    echo "<li>Database đã được import chưa?</li>";
    echo "<li>Cấu hình database trong <code>/config/database.php</code> đúng chưa?</li>";
    echo "<li>MySQL server đang chạy?</li>";
    echo "</ul>";
    echo "</div>";
    error_log("Create admin error: " . $e->getMessage());
}
