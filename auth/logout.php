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

// Load session helper
require_once '../helpers/session-helper.php';

// Xóa hoàn toàn session
destroySessionCompletely(false);

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    unset($_COOKIE['remember_token']);
}

// Clear any other potential cookies
$cookies_to_clear = ['PHPSESSID', 'remember_token', 'user_session'];
foreach ($cookies_to_clear as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        setcookie($cookie_name, '', time() - 3600, '/');
        setcookie($cookie_name, '', time() - 3600, '/', '', false, true);
        unset($_COOKIE[$cookie_name]);
    }
}

// Redirect to login page with success message
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Location: login.php?logged_out=1');
exit;
?>
