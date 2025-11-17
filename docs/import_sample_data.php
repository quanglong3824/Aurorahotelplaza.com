<?php
/**
 * Import Sample Data Script
 * Run this file once to populate database with test data
 */

require_once '../config/database.php';

echo "<h1>Aurora Hotel - Import Sample Data</h1>";

// Check if user wants to clear existing data
$clearData = isset($_GET['clear']) && $_GET['clear'] === 'yes';

if (!$clearData) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;'>";
    echo "<h3 style='margin-top: 0; color: #856404;'>⚠️ Cảnh báo</h3>";
    echo "<p>Script này sẽ <strong>XÓA TẤT CẢ</strong> dữ liệu mẫu cũ và import dữ liệu mới.</p>";
    echo "<p>Các dữ liệu sau sẽ bị xóa:</p>";
    echo "<ul>";
    echo "<li>Tất cả bookings và payments</li>";
    echo "<li>Tất cả room_types và rooms</li>";
    echo "<li>Tất cả services và promotions</li>";
    echo "<li>User test (admin@aurorahotel.com, receptionist@aurorahotel.com, customer@test.com)</li>";
    echo "</ul>";
    echo "<p><strong>Lưu ý:</strong> Dữ liệu thật của khách hàng sẽ KHÔNG bị ảnh hưởng.</p>";
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='?clear=yes' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;'>✅ Đồng ý, Import ngay</a>";
    echo "<a href='../booking/' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>❌ Hủy bỏ</a>";
    echo "</div>";
    echo "</div>";
    exit;
}

echo "<pre>";

try {
    $db = getDB();
    
    if (!$db) {
        die("❌ Không thể kết nối database!\n");
    }
    
    echo "✅ Kết nối database thành công!\n\n";
    echo "🗑️  Đang xóa dữ liệu cũ...\n";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/INSERT_SAMPLE_DATA.sql';
    
    if (!file_exists($sqlFile)) {
        die("❌ Không tìm thấy file INSERT_SAMPLE_DATA.sql\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        // Skip comments and empty lines
        if (empty(trim($statement))) continue;
        if (preg_match('/^(--|\/\*)/', trim($statement))) continue;
        
        try {
            $db->exec($statement);
            $success++;
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            // Ignore duplicate entry and foreign key errors during cleanup
            if (strpos($errorMsg, 'Duplicate entry') === false && 
                strpos($errorMsg, 'foreign key constraint') === false &&
                strpos($errorMsg, 'Cannot delete or update a parent row') === false) {
                echo "⚠️  Warning: " . $errorMsg . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n📊 Kết quả import:\n";
    echo "   ✅ Thành công: $success câu lệnh\n";
    echo "   ⚠️  Lỗi: $errors câu lệnh\n\n";
    
    // Verify data
    echo "📋 Kiểm tra dữ liệu đã import:\n\n";
    
    $tables = [
        'room_types' => 'Loại phòng',
        'rooms' => 'Phòng',
        'users' => 'Người dùng',
        'membership_tiers' => 'Hạng thành viên',
        'services' => 'Dịch vụ',
        'promotions' => 'Khuyến mãi',
        'system_settings' => 'Cài đặt hệ thống'
    ];
    
    foreach ($tables as $table => $label) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
        $result = $stmt->fetch();
        echo sprintf("   %-20s: %d bản ghi\n", $label, $result['count']);
    }
    
    echo "\n📦 Chi tiết loại phòng:\n\n";
    $stmt = $db->query("
        SELECT 
            rt.type_name as 'Loại phòng',
            rt.base_price as 'Giá cơ bản',
            rt.max_occupancy as 'Số khách',
            COUNT(r.room_id) as 'Số phòng trống'
        FROM room_types rt
        LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id AND r.status = 'available'
        WHERE rt.status = 'active'
        GROUP BY rt.room_type_id
        ORDER BY rt.sort_order
    ");
    
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rooms as $room) {
        echo sprintf(
            "   %-25s: %s VNĐ/đêm | %d khách | %d phòng trống\n",
            $room['Loại phòng'],
            number_format($room['Giá cơ bản']),
            $room['Số khách'],
            $room['Số phòng trống']
        );
    }
    
    echo "\n👥 Tài khoản test:\n\n";
    $stmt = $db->query("SELECT email, full_name, user_role FROM users ORDER BY user_role");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo sprintf(
            "   %-30s | %-20s | %s\n",
            $user['email'],
            $user['full_name'],
            $user['user_role']
        );
    }
    
    echo "\n🔑 Mật khẩu mặc định cho tất cả tài khoản: admin123\n";
    
    echo "\n✅ HOÀN TẤT! Bạn có thể test booking tại:\n";
    echo "   http://localhost/GitHub/Aurorahotelplaza.com/booking/\n\n";
    
    echo "</pre>";
    
    // Action buttons
    echo "<div style='margin-top: 20px; text-align: center;'>";
    echo "<a href='../booking/' style='background: #d4af37; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 0 10px; font-weight: bold;'>🏨 Test Booking</a>";
    echo "<a href='?clear=yes' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 0 10px;'>🔄 Import lại</a>";
    echo "<a href='../index.php' style='background: #6c757d; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 0 10px;'>🏠 Trang chủ</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    echo "</pre>";
}

echo "<style>
body { font-family: 'Courier New', monospace; padding: 20px; background: #f5f5f5; }
h1 { color: #d4af37; text-align: center; }
pre { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); line-height: 1.6; }
</style>";
?>
