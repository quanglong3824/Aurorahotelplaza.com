<?php
/**
 * Authentication Middleware
 * Kiểm tra đăng nhập và phân quyền
 */

require_once __DIR__ . '/permissions.php';

class AuthMiddleware {
    /**
     * Require login
     */
    public static function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            if (self::isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
                exit;
            } else {
                header('Location: /auth/login.php');
                exit;
            }
        }
        
        // Check if user must change password
        if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password']) {
            if (self::isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Bạn phải đổi mật khẩu trước', 'redirect' => '/auth/change-password.php']);
                exit;
            } else {
                header('Location: /auth/change-password.php');
                exit;
            }
        }
    }
    
    /**
     * Require staff role (admin, receptionist, sale)
     */
    public static function requireStaff() {
        self::requireLogin();
        
        if (!in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale'])) {
            if (self::isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập']);
                exit;
            } else {
                die('<h1>403 Forbidden</h1><p>Bạn không có quyền truy cập trang này</p>');
            }
        }
    }
    
    /**
     * Require admin role
     */
    public static function requireAdmin() {
        self::requireLogin();
        
        if ($_SESSION['user_role'] !== 'admin') {
            if (self::isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Chỉ admin mới có quyền này']);
                exit;
            } else {
                die('<h1>403 Forbidden</h1><p>Chỉ admin mới có quyền truy cập</p>');
            }
        }
    }
    
    /**
     * Require permission
     */
    public static function requirePermission($module, $action) {
        self::requireLogin();
        Permissions::require($module, $action);
    }
    
    /**
     * Check if request is AJAX
     */
    private static function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get current user info
     */
    public static function user() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'user_role' => $_SESSION['user_role'] ?? 'customer'
        ];
    }
    
    /**
     * Check if user is staff
     */
    public static function isStaff() {
        return isset($_SESSION['user_role']) && 
               in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale']);
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
