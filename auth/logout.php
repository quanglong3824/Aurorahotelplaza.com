<?php
session_start();

// Log logout
try {
    if (isset($_SESSION['user_id'])) {
        require_once '../config/database.php';
        require_once '../helpers/logger.php';
        
        $logger = getLogger();
        $logger->logUserLogout($_SESSION['user_id']);
    }
} catch (Exception $e) {
    error_log("Logout logging error: " . $e->getMessage());
}

// Destroy session
session_destroy();

// Clear cookies
setcookie('remember_token', '', time() - 3600, '/');

// Redirect to home
header('Location: ../index.php');
exit;
