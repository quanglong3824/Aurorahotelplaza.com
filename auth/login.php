<?php
session_start();
require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../helpers/session-helper.php';
require_once __DIR__ . '/../helpers/language.php';
require_once __DIR__ . '/../controllers/FrontAuthController.php';

initLanguage();

// Kiểm tra và xóa session không hợp lệ (user_id = 0)
validateAndCleanSession();

// Redirect if already logged in với session hợp lệ
if (isValidSession()) {
    header('Location: ' . url('index.php'));
    exit;
}

$controller = new FrontAuthController();
$data = $controller->login();

extract($data);
include __DIR__ . '/../views/auth/login.view.php';
