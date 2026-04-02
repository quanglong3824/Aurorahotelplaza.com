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
            // ── Frontend Routes ──────────────────────────────────────
            ''                          => ['file' => 'index.php',              'name' => 'Trang chủ'],
            'phong-khach-san'           => ['file' => 'rooms.php',              'name' => 'Phòng khách sạn'],
            'can-ho'                    => ['file' => 'apartments.php',         'name' => 'Căn hộ'],
            'dich-vu'                   => ['file' => 'services.php',           'name' => 'Dịch vụ'],
            'thu-vien-anh'              => ['file' => 'gallery.php',            'name' => 'Thư viện ảnh'],
            'tin-tuc'                   => ['file' => 'blog.php',               'name' => 'Tin tức'],
            'lien-he'                   => ['file' => 'contact.php',            'name' => 'Liên hệ'],
            'gioi-thieu'                => ['file' => 'about.php',              'name' => 'Giới thiệu'],
            'kham-pha'                  => ['file' => 'explore.php',            'name' => 'Khám phá'],
            'ban-do-phong'              => ['file' => 'room-map-user.php',      'name' => 'Bản đồ phòng'],
            'chinh-sach-huy'            => ['file' => 'cancellation-policy.php','name' => 'Chính sách hủy'],
            'chinh-sach-bao-mat'        => ['file' => 'privacy.php',           'name' => 'Chính sách bảo mật'],
            'dieu-khoan'                => ['file' => 'terms.php',              'name' => 'Điều khoản'],

            // ── Dynamic Detail Routes (Base keys for URL generation) ──
            'phong'                     => ['file' => 'room-details/{slug}.php',     'name' => 'Chi tiết phòng'],
            'chi-tiet-can-ho'           => ['file' => 'apartment-details/{slug}.php','name' => 'Chi tiết căn hộ'],
            'chi-tiet-dich-vu'          => ['file' => 'service-detail.php',          'name' => 'Chi tiết dịch vụ'],
            'chi-tiet-tin-tuc'          => ['file' => 'blog-detail.php',             'name' => 'Chi tiết tin tức'],

            // ── Auth Routes ─────────────────────────────────────────
            'dang-nhap'                 => ['file' => 'auth/login.php',             'name' => 'Đăng nhập'],
            'dang-ky'                   => ['file' => 'auth/register.php',          'name' => 'Đăng ký'],
            'dang-xuat'                 => ['file' => 'auth/logout.php',            'name' => 'Đăng xuất'],
            'quen-mat-khau'             => ['file' => 'auth/forgot-password.php',   'name' => 'Quên mật khẩu'],
            'dat-lai-mat-khau'          => ['file' => 'auth/reset-password.php',    'name' => 'Đặt lại mật khẩu'],
            'doi-mat-khau'              => ['file' => 'auth/change-password.php',   'name' => 'Đổi mật khẩu'],
            'dang-nhap-google'          => ['file' => 'auth/login-google.php',      'name' => 'Đăng nhập Google'],

            // ── Booking Routes ──────────────────────────────────────
            'dat-phong'                 => ['file' => 'booking/index.php',          'name' => 'Đặt phòng'],
            'dat-phong/xac-nhan'        => ['file' => 'booking/confirmation.php',   'name' => 'Xác nhận đặt phòng'],
            'dat-phong/thanh-toan'      => ['file' => 'booking/vnpay_return.php',   'name' => 'Thanh toán VNPay'],

            // ── Profile Routes ──────────────────────────────────────
            'ho-so'                     => ['file' => 'profile/index.php',          'name' => 'Hồ sơ'],
            'ho-so/dat-phong'           => ['file' => 'profile/bookings.php',       'name' => 'Đặt phòng của tôi'],
            'ho-so/tich-diem'           => ['file' => 'profile/loyalty.php',        'name' => 'Tích điểm'],
            'ho-so/chinh-sua'           => ['file' => 'profile/edit.php',           'name' => 'Chỉnh sửa hồ sơ'],
            'ho-so/chi-tiet-dat-phong'  => ['file' => 'profile/booking-detail.php', 'name' => 'Chi tiết đặt phòng'],
            'ho-so/ma-qr'               => ['file' => 'profile/view-qrcode.php',    'name' => 'Mã QR đặt phòng'],

            // ── Admin Routes ────────────────────────────────────────
            'admin'                     => ['file' => 'admin/index.php',            'name' => 'Admin Dashboard'],
            'admin/dashboard'           => ['file' => 'admin/dashboard.php',        'name' => 'Dashboard'],
            'admin/bookings'            => ['file' => 'admin/bookings.php',         'name' => 'Quản lý đặt phòng'],
            'admin/rooms'               => ['file' => 'admin/rooms.php',            'name' => 'Quản lý phòng'],
            'admin/users'               => ['file' => 'admin/users.php',            'name' => 'Quản lý người dùng'],
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
            // Check if it's a dynamic route manually if not found in exact keys
            return BASE_URL . '/' . ltrim($route, '/');
        }
        
        $url = BASE_URL;
        $slug = isset($params['slug']) ? $params['slug'] : '';
        unset($params['slug']); // Remove slug from query params
        
        switch ($route) {
            case 'phong':
                $url .= '/phong/' . $slug;
                break;
            case 'chi-tiet-can-ho':
                $url .= '/can-ho/' . $slug;
                break;
            case 'chi-tiet-dich-vu':
                $url .= '/dich-vu/' . $slug;
                break;
            case 'chi-tiet-tin-tuc':
                $url .= '/tin-tuc/' . $slug;
                break;
            case '':
                $url .= '/';
                break;
            default:
                $url .= '/' . $route;
                break;
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return rtrim($url, '/');
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
