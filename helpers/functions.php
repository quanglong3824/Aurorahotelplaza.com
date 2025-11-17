<?php
/**
 * Helper Functions
 */

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone (Vietnamese format)
 */
function validatePhone($phone) {
    $phone = preg_replace('/\s+/', '', $phone);
    return preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone);
}

/**
 * Format currency VND
 */
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

/**
 * Format date Vietnamese
 */
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)))), 1, $length);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Check user role
 */
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

/**
 * Redirect
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * JSON response
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Calculate loyalty points
 */
function calculateLoyaltyPoints($amount) {
    return floor($amount / 10000); // 1 point per 10,000 VND
}

/**
 * Send email (placeholder - implement with PHPMailer)
 */
function sendEmail($to, $subject, $body, $template = null) {
    // TODO: Implement with PHPMailer
    return true;
}

/**
 * Generate QR Code (placeholder)
 */
function generateQRCode($data) {
    // TODO: Implement QR code generation
    return 'qr_code_placeholder.png';
}

/**
 * Log activity
 */
function logActivity($action, $entity_type = null, $entity_id = null, $description = null) {
    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$user_id, $action, $entity_type, $entity_id, $description, $ip_address, $user_agent]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>
