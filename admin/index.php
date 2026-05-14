<?php
/**
 * Aurora Hotel Plaza - Admin Secure Router
 * Cửa ngõ duy nhất truy cập trang quản trị
 */

// 1. Khởi động cấu hình & CSDL trước tiên
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/security-guard.php';

// 2. Kích hoạt bảo mật (Cần CSDL để kiểm tra Blacklist)
SecurityGuard::protect();

// 3. Xác định trang cần truy cập
$hashed_page = $_GET['p'] ?? '';

// Nếu truy cập root admin mà không có tham số, mặc định vào dashboard
if (empty($hashed_page)) {
    $dashboard_hash = Security::hashAdminPage('dashboard');
    header("Location: index.php?p=$dashboard_hash");
    exit;
}

// 3. Danh sách ánh xạ các trang Admin (Để giải mã hash)
$admin_pages = [
    'dashboard', 'bookings', 'booking-detail', 'create-booking', 'apartment-inquiries',
    'room-types', 'room-type-form', 'rooms', 'room-form', 'room-map', 'pricing', 'pricing-detailed',
    'customers', 'customer-detail', 'ai-leads', 'loyalty', 'reviews', 'contacts',
    'chat', 'chat-settings', 'service-packages', 'services', 'service-bookings',
    'ai-assistant', 'competitor-intelligence', 'ai-stats', 'ai-bug',
    'promotions', 'banners', 'blog', 'blog-form', 'blog-comments', 'gallery', 'faqs', 'seo',
    'users', 'permissions', 'activity-logs', 'security-center', 'traffic-stats', 'traffic-logs',
    'reports', 'notifications', 'test-messenger-2way', 'settings', 'backup-database', 'reset-database'
];

$target_file = '';
foreach ($admin_pages as $page) {
    if (Security::hashAdminPage($page) === $hashed_page) {
        $target_file = $page . '.php';
        break;
    }
}

// 4. Kiểm tra file và thực thi
if ($target_file && file_exists(__DIR__ . '/' . $target_file)) {
    // Để cho luồng PHP tự nhiên, chúng ta include file đó vào đây
    // Lưu ý: Các file admin cần sử dụng $current_page để biết mình đang ở đâu
    include __DIR__ . '/' . $target_file;
} else {
    // Nếu hash sai hoặc không tìm thấy trang
    header('HTTP/1.1 404 Not Found');
    die("<h1>404 Security Error</h1><p>The requested secure path is invalid or expired.</p>");
}
