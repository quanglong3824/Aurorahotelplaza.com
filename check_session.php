<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_email' => $_SESSION['user_email'] ?? null,
    'cookie_params' => session_get_cookie_params(),
    'server_name' => $_SERVER['SERVER_NAME']
]);
