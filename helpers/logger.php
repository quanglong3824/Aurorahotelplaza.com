<?php
/**
 * Activity Logger Helper
 * Logs user activities to database
 */

require_once __DIR__ . '/../config/database.php';

class Logger {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Log user registration
     */
    public function logUserRegister($userId, $data = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
                VALUES (?, 'register', 'user', ?, ?, ?, ?)
            ");
            
            $description = json_encode($data);
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->execute([$userId, $userId, $description, $ipAddress, $userAgent]);
            return true;
        } catch (Exception $e) {
            error_log("Logger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log user login
     */
    public function logUserLogin($userId, $data = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
                VALUES (?, 'login', 'user', ?, ?, ?, ?)
            ");
            
            $description = json_encode($data);
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->execute([$userId, $userId, $description, $ipAddress, $userAgent]);
            return true;
        } catch (Exception $e) {
            error_log("Logger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log user logout
     */
    public function logUserLogout($userId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent)
                VALUES (?, 'logout', 'user', ?, ?, ?)
            ");
            
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->execute([$userId, $userId, $ipAddress, $userAgent]);
            return true;
        } catch (Exception $e) {
            error_log("Logger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log booking action
     */
    public function logBooking($userId, $bookingId, $action, $data = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
                VALUES (?, ?, 'booking', ?, ?, ?, ?)
            ");
            
            $description = json_encode($data);
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->execute([$userId, $action, $bookingId, $description, $ipAddress, $userAgent]);
            return true;
        } catch (Exception $e) {
            error_log("Logger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log payment action
     */
    public function logPayment($userId, $paymentId, $action, $data = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
                VALUES (?, ?, 'payment', ?, ?, ?, ?)
            ");
            
            $description = json_encode($data);
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->execute([$userId, $action, $paymentId, $description, $ipAddress, $userAgent]);
            return true;
        } catch (Exception $e) {
            error_log("Logger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log admin action
     */
    public function logAdminAction($userId, $action, $entityType, $entityId, $data = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $description = json_encode($data);
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->execute([$userId, $action, $entityType, $entityId, $description, $ipAddress, $userAgent]);
            return true;
        } catch (Exception $e) {
            error_log("Logger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return trim($ip);
    }
}

/**
 * Helper function to get logger instance
 */
function getLogger() {
    return new Logger();
}
