<?php
/**
 * Router Configuration
 * Quản lý tất cả routes của ứng dụng
 * Đảm bảo không bị 404 và tối ưu hóa đường dẫn
 */

require_once __DIR__ . '/environment.php';

class Router {
    private static $routes = [];
    private static $currentPath = '';
    
    /**
     * Định nghĩa tất cả routes của ứng dụng
     */
    public static function defineRoutes() {
        // Main pages
        self::$routes = [
            // Frontend Routes
            'index' => ['file' => 'index.php', 'name' => 'Trang chủ'],
            'rooms' => ['file' => 'rooms.php', 'name' => 'Phòng'],
            'apartments' => ['file' => 'apartments.php', 'name' => 'Căn hộ'],
            'services' => ['file' => 'services.php', 'name' => 'Dịch vụ'],
            'service-detail' => ['file' => 'service-detail.php', 'name' => 'Chi tiết dịch vụ'],
            'gallery' => ['file' => 'gallery.php', 'name' => 'Thư viện ảnh'],
            'blog' => ['file' => 'blog.php', 'name' => 'Blog'],
            'blog-detail' => ['file' => 'blog-detail.php', 'name' => 'Chi tiết bài viết'],
            'contact' => ['file' => 'contact.php', 'name' => 'Liên hệ'],
            'about' => ['file' => 'about.php', 'name' => 'Về chúng tôi'],
            'profile' => ['file' => 'profile.php', 'name' => 'Hồ sơ'],
            'explore' => ['file' => 'explore.php', 'name' => 'Khám phá'],
            'room-map' => ['file' => 'room-map-user.php', 'name' => 'Bản đồ phòng'],
            
            // Room Details
            'room-details/deluxe' => ['file' => 'room-details/deluxe.php', 'name' => 'Phòng Deluxe'],
            'room-details/premium-deluxe' => ['file' => 'room-details/premium-deluxe.php', 'name' => 'Premium Deluxe'],
            'room-details/premium-twin' => ['file' => 'room-details/premium-twin.php', 'name' => 'Premium Twin'],
            'room-details/vip-suite' => ['file' => 'room-details/vip-suite.php', 'name' => 'VIP Suite'],
            
            // Apartment Details
            'apartment-details/studio' => ['file' => 'apartment-details/studio.php', 'name' => 'Studio'],
            'apartment-details/premium' => ['file' => 'apartment-details/premium.php', 'name' => 'Premium'],
            'apartment-details/family' => ['file' => 'apartment-details/family.php', 'name' => 'Family'],
            'apartment-details/modern-studio' => ['file' => 'apartment-details/modern-studio.php', 'name' => 'Modern Studio'],
            'apartment-details/modern-premium' => ['file' => 'apartment-details/modern-premium.php', 'name' => 'Modern Premium'],
            'apartment-details/indochine-studio' => ['file' => 'apartment-details/indochine-studio.php', 'name' => 'Indochine Studio'],
            'apartment-details/indochine-family' => ['file' => 'apartment-details/indochine-family.php', 'name' => 'Indochine Family'],
            'apartment-details/classical-premium' => ['file' => 'apartment-details/classical-premium.php', 'name' => 'Classical Premium'],
            'apartment-details/classical-family' => ['file' => 'apartment-details/classical-family.php', 'name' => 'Classical Family'],
            
            // Auth Routes
            'auth/login' => ['file' => 'auth/login.php', 'name' => 'Đăng nhập'],
            'auth/register' => ['file' => 'auth/register.php', 'name' => 'Đăng ký'],
            'auth/logout' => ['file' => 'auth/logout.php', 'name' => 'Đăng xuất'],
            'auth/forgot-password' => ['file' => 'auth/forgot-password.php', 'name' => 'Quên mật khẩu'],
            
            // Booking Routes
            'booking' => ['file' => 'booking/index.php', 'name' => 'Đặt phòng'],
            'booking/confirm' => ['file' => 'booking/confirm.php', 'name' => 'Xác nhận đặt phòng'],
            'booking/success' => ['file' => 'booking/success.php', 'name' => 'Đặt phòng thành công'],
            
            // Payment Routes
            'payment/process' => ['file' => 'payment/process.php', 'name' => 'Xử lý thanh toán'],
            'payment/callback' => ['file' => 'payment/callback.php', 'name' => 'Callback thanh toán'],
            
            // Admin Routes
            'admin' => ['file' => 'admin/index.php', 'name' => 'Admin Dashboard'],
            'admin/dashboard' => ['file' => 'admin/dashboard.php', 'name' => 'Dashboard'],
            'admin/bookings' => ['file' => 'admin/bookings.php', 'name' => 'Quản lý đặt phòng'],
            'admin/rooms' => ['file' => 'admin/rooms.php', 'name' => 'Quản lý phòng'],
            'admin/users' => ['file' => 'admin/users.php', 'name' => 'Quản lý người dùng'],
            
            // Test Routes
            'test/connection' => ['file' => 'test/test-connection.php', 'name' => 'Test Connection'],
            'test/audit' => ['file' => 'test/audit-project.php', 'name' => 'Project Audit'],
        ];
    }
    
    /**
     * Lấy tất cả routes
     */
    public static function getRoutes() {
        if (empty(self::$routes)) {
            self::defineRoutes();
        }
        return self::$routes;
    }
    
    /**
     * Kiểm tra route có tồn tại không
     */
    public static function routeExists($route) {
        $routes = self::getRoutes();
        return isset($routes[$route]);
    }
    
    /**
     * Lấy file path của route
     */
    public static function getRoutePath($route) {
        $routes = self::getRoutes();
        if (isset($routes[$route])) {
            return $routes[$route]['file'];
        }
        return null;
    }
    
    /**
     * Lấy tên của route
     */
    public static function getRouteName($route) {
        $routes = self::getRoutes();
        if (isset($routes[$route])) {
            return $routes[$route]['name'];
        }
        return null;
    }
    
    /**
     * Tạo URL cho route
     */
    public static function url($route, $params = []) {
        if (!self::routeExists($route)) {
            return '#';
        }
        
        $url = BASE_URL . '/' . $route;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Kiểm tra route hiện tại
     */
    public static function getCurrentRoute() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace(dirname(BASE_URL), '', $path);
        $path = ltrim($path, '/');
        $path = rtrim($path, '/');
        
        // Remove .php extension if present
        if (substr($path, -4) === '.php') {
            $path = substr($path, 0, -4);
        }
        
        return $path ?: 'index';
    }
    
    /**
     * Kiểm tra route có phải hiện tại không
     */
    public static function isCurrentRoute($route) {
        return self::getCurrentRoute() === $route;
    }
}

// Initialize routes
Router::defineRoutes();

/**
 * Helper function để tạo URL cho route
 */
function route($routeName, $params = []) {
    return Router::url($routeName, $params);
}

/**
 * Helper function để kiểm tra route hiện tại
 */
function isRoute($routeName) {
    return Router::isCurrentRoute($routeName);
}

/**
 * Helper function để lấy tên route hiện tại
 */
function currentRoute() {
    return Router::getCurrentRoute();
}
