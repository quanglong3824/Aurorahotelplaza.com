<?php
session_start();

// Log the logout activity if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        require_once '../config/database.php';
        require_once '../helpers/logger.php';
        
        $logger = getLogger();
        $logger->logActivity($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], 'User logged out');
    } catch (Exception $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page with success message
header('Location: login.php?logged_out=1');
exit;
?>