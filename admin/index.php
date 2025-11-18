<?php
session_start();

// Check if user is logged in and has admin/staff role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Redirect to dashboard
header('Location: dashboard.php');
exit;