<?php
/**
 * Simple Logger Helper for Aurora Hotel
 */

class Logger {
    private $db;
    
    public function __construct($database = null) {
        if ($database) {
            $this->db = $database;
        } else {
            require_once __DIR__ . '/../config/database.php';
            $this->db = getDB();
        }
    }
    
    /**
     * Log user login activity
     */
    public function logUserLogin($user_id, $details = []) {
        try {
            $description = 'User logged in';
            if (isset($details['login_method'])) {
                $description .= ' via ' . $details['login_method'];
            }
            
            $this->logActivity($user_id, 'login', 'user', $user_id, $description, $details);
        } catch (Exception $e) {
            error_log("Logger error in logUserLogin: " . $e->getMessage());
        }
    }
    
    /**
     * Log user registration activity
     */
    public function logUserRegistration($user_id, $details = []) {
        try {
            $description = 'New user registered';
            if (isset($details['registration_method'])) {
                $description .= ' via ' . $details['registration_method'];
            }
            
            $this->logActivity($user_id, 'register', 'user', $user_id, $description, $details);
        } catch (Exception $e) {
            error_log("Logger error in logUserRegistration: " . $e->getMessage());
        }
    }
    
    /**
     * Log booking activity
     */
    public function logBooking($user_id, $booking_id, $action, $details = []) {
        try {
            $description = "Booking {$action}";
            $this->logActivity($user_id, $action, 'booking', $booking_id, $description, $details);
        } catch (Exception $e) {
            error_log("Logger error in logBooking: " . $e->getMessage());
        }
    }
    
    /**
     * Log payment success
     */
    public function logPaymentSuccess($transaction_id, $details = [], $user_id = null) {
        try {
            $description = "Payment successful - Transaction: {$transaction_id}";
            $this->logActivity($user_id, 'payment_success', 'payment', null, $description, $details);
        } catch (Exception $e) {
            error_log("Logger error in logPaymentSuccess: " . $e->getMessage());
        }
    }
    
    /**
     * Generic activity logging
     */
    public function logActivity($user_id, $action, $entity_type, $entity_id, $description, $details = []) {
        if (!$this->db) {
            throw new Exception("Database connection not available");
        }
        
        $ip_address = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Add details as JSON if provided
        if (!empty($details)) {
            $description .= ' - Details: ' . json_encode($details);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $entity_type,
            $entity_id,
            $description,
            $ip_address,
            $user_agent
        ]);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * Get logger instance
 */
function getLogger($database = null) {
    return new Logger($database);
}
?>