<?php
/**
 * Permissions Helper
 * Quản lý phân quyền chi tiết cho từng role
 */

class Permissions {
    private static $db = null;
    private static $cache = [];
    
    /**
     * Initialize database connection
     */
    private static function getDB() {
        if (self::$db === null) {
            require_once __DIR__ . '/../config/database.php';
            self::$db = getDB();
        }
        return self::$db;
    }
    
    /**
     * Check if user has permission
     * 
     * @param string $module Module name (bookings, customers, rooms, etc.)
     * @param string $action Action name (view, create, update, delete, etc.)
     * @param string $role User role (optional, uses session if not provided)
     * @return bool
     */
    public static function can($module, $action, $role = null) {
        // Get role from session if not provided
        if ($role === null) {
            if (!isset($_SESSION['user_role'])) {
                return false;
            }
            $role = $_SESSION['user_role'];
        }
        
        // Admin always has full access
        if ($role === 'admin') {
            return true;
        }
        
        // Check cache first
        $cache_key = "{$role}_{$module}_{$action}";
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }
        
        try {
            $db = self::getDB();
            
            $stmt = $db->prepare("
                SELECT allowed 
                FROM role_permissions 
                WHERE role = :role 
                AND module = :module 
                AND action = :action
                LIMIT 1
            ");
            
            $stmt->execute([
                ':role' => $role,
                ':module' => $module,
                ':action' => $action
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $allowed = $result ? (bool)$result['allowed'] : false;
            
            // Cache result
            self::$cache[$cache_key] = $allowed;
            
            return $allowed;
            
        } catch (Exception $e) {
            error_log("Permission check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Require permission or die
     */
    public static function require($module, $action, $message = 'Bạn không có quyền thực hiện hành động này') {
        if (!self::can($module, $action)) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
            } else {
                die('<h1>403 Forbidden</h1><p>' . htmlspecialchars($message) . '</p>');
            }
            exit;
        }
    }
    
    /**
     * Get all permissions for a role
     */
    public static function getRolePermissions($role) {
        try {
            $db = self::getDB();
            
            $stmt = $db->prepare("
                SELECT module, action, allowed
                FROM role_permissions
                WHERE role = :role
                ORDER BY module, action
            ");
            
            $stmt->execute([':role' => $role]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get role permissions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update permission
     */
    public static function updatePermission($role, $module, $action, $allowed) {
        try {
            $db = self::getDB();
            
            $stmt = $db->prepare("
                INSERT INTO role_permissions (role, module, action, allowed)
                VALUES (:role, :module, :action, :allowed)
                ON DUPLICATE KEY UPDATE allowed = :allowed
            ");
            
            $stmt->execute([
                ':role' => $role,
                ':module' => $module,
                ':action' => $action,
                ':allowed' => $allowed ? 1 : 0
            ]);
            
            // Clear cache
            $cache_key = "{$role}_{$module}_{$action}";
            unset(self::$cache[$cache_key]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Update permission error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get permission matrix for all roles
     */
    public static function getPermissionMatrix() {
        try {
            $db = self::getDB();
            
            $stmt = $db->query("
                SELECT role, module, action, allowed
                FROM role_permissions
                WHERE role IN ('receptionist', 'sale')
                ORDER BY role, module, action
            ");
            
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize by role and module
            $matrix = [];
            foreach ($permissions as $perm) {
                if (!isset($matrix[$perm['role']])) {
                    $matrix[$perm['role']] = [];
                }
                if (!isset($matrix[$perm['role']][$perm['module']])) {
                    $matrix[$perm['role']][$perm['module']] = [];
                }
                $matrix[$perm['role']][$perm['module']][$perm['action']] = (bool)$perm['allowed'];
            }
            
            return $matrix;
            
        } catch (Exception $e) {
            error_log("Get permission matrix error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check multiple permissions at once
     */
    public static function canAny($module, array $actions, $role = null) {
        foreach ($actions as $action) {
            if (self::can($module, $action, $role)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user can access admin panel
     */
    public static function canAccessAdmin() {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        return in_array($_SESSION['user_role'], ['admin', 'receptionist', 'sale']);
    }
    
    /**
     * Get readable permission name
     */
    public static function getPermissionLabel($module, $action) {
        $labels = [
            'bookings' => [
                'view' => 'Xem đặt phòng',
                'create' => 'Tạo đặt phòng',
                'update' => 'Cập nhật đặt phòng',
                'delete' => 'Xóa đặt phòng',
                'confirm' => 'Xác nhận đặt phòng',
                'cancel' => 'Hủy đặt phòng',
                'assign_room' => 'Phân phòng',
                'checkin' => 'Check-in',
                'checkout' => 'Check-out'
            ],
            'customers' => [
                'view' => 'Xem khách hàng',
                'create' => 'Tạo khách hàng',
                'update' => 'Cập nhật khách hàng',
                'delete' => 'Xóa khách hàng'
            ],
            'rooms' => [
                'view' => 'Xem phòng',
                'create' => 'Tạo phòng',
                'update' => 'Cập nhật phòng',
                'delete' => 'Xóa phòng'
            ],
            'pricing' => [
                'view' => 'Xem giá',
                'update' => 'Cập nhật giá'
            ],
            'promotions' => [
                'view' => 'Xem khuyến mãi',
                'create' => 'Tạo khuyến mãi',
                'update' => 'Cập nhật khuyến mãi',
                'delete' => 'Xóa khuyến mãi'
            ],
            'loyalty' => [
                'view' => 'Xem điểm thưởng',
                'update' => 'Cập nhật hạng',
                'adjust_points' => 'Điều chỉnh điểm'
            ],
            'payments' => [
                'view' => 'Xem thanh toán',
                'confirm' => 'Xác nhận thanh toán'
            ],
            'reports' => [
                'view' => 'Xem báo cáo'
            ],
            'settings' => [
                'view' => 'Xem cài đặt',
                'update' => 'Cập nhật cài đặt'
            ],
            'permissions' => [
                'manage' => 'Quản lý phân quyền'
            ]
        ];
        
        return $labels[$module][$action] ?? ucfirst($action);
    }
}
