<?php
session_start();

// Nếu đang có session cũ và đang bắt đầu flow OAuth mới (không có code), xóa session cũ
if (!isset($_GET['code']) && !isset($_GET['error'])) {
    // Bắt đầu flow mới - xóa session cũ để tránh lẫn lộn
    session_unset();
    session_regenerate_id(true);
}

// Redirect if already logged in (chỉ khi không phải callback)
if (isset($_SESSION['user_id']) && !isset($_GET['code'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';

// Load Google OAuth configuration
$google_config_file = __DIR__ . '/key-google-web-app.json';
if (!file_exists($google_config_file)) {
    die('Google OAuth configuration file not found');
}

$google_config = json_decode(file_get_contents($google_config_file), true);
if (!$google_config || !isset($google_config['web'])) {
    die('Invalid Google OAuth configuration');
}

$client_id = $google_config['web']['client_id'];
$client_secret = $google_config['web']['client_secret'];

// Tự động chọn redirect URI dựa trên môi trường
$is_localhost = (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
    $_SERVER['SERVER_ADDR'] === '::1'
);

$redirect_uri = null;
foreach ($google_config['web']['redirect_uris'] as $uri) {
    if ($is_localhost && strpos($uri, 'localhost') !== false) {
        $redirect_uri = $uri;
        break;
    } elseif (!$is_localhost && strpos($uri, 'localhost') === false) {
        $redirect_uri = $uri;
        break;
    }
}

// Fallback to first URI if no match
if (!$redirect_uri) {
    $redirect_uri = $google_config['web']['redirect_uris'][0];
}

// Handle OAuth callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Exchange authorization code for access token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirect_uri
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $token_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        header('Location: login.php?error=google_auth_failed');
        exit;
    }
    
    $token_data = json_decode($token_response, true);
    if (!$token_data || !isset($token_data['access_token'])) {
        header('Location: login.php?error=google_token_failed');
        exit;
    }
    
    // Get user info from Google
    $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_data['access_token'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_info_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $user_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        header('Location: login.php?error=google_userinfo_failed');
        exit;
    }
    
    $user_info = json_decode($user_response, true);
    if (!$user_info || !isset($user_info['email'])) {
        header('Location: login.php?error=google_userinfo_invalid');
        exit;
    }
    
    try {
        $db = getDB();
        
        // Check if user exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$user_info['email']]);
        $user = $stmt->fetch();
        
        // QUAN TRỌNG: Xóa toàn bộ session cũ trước khi set session mới
        // Lưu lại intended_url nếu có
        $intended_url = $_SESSION['intended_url'] ?? null;
        
        // Xóa session cũ hoàn toàn
        session_unset();
        session_regenerate_id(true);
        
        // Khôi phục intended_url
        if ($intended_url) {
            $_SESSION['intended_url'] = $intended_url;
        }
        
        if ($user) {
            // User exists, log them in
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['user_role'];
            
            // Update last login
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            // Log login
            try {
                require_once '../helpers/logger.php';
                $logger = getLogger();
                $logger->logUserLogin($user['user_id'], [
                    'email' => $user['email'],
                    'user_name' => $user['full_name'],
                    'role' => $user['user_role'],
                    'login_method' => 'google'
                ]);
            } catch (Exception $logError) {
                error_log("Logger failed: " . $logError->getMessage());
            }
            
        } else {
            // User doesn't exist, create new account
            $full_name = $user_info['name'] ?? $user_info['email'];
            $avatar = $user_info['picture'] ?? null;
            
            // Generate a random password (user won't use it for Google login)
            $random_password = bin2hex(random_bytes(16));
            $password_hash = password_hash($random_password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (email, password_hash, full_name, avatar, user_role, status, email_verified, created_at) 
                VALUES (?, ?, ?, ?, 'customer', 'active', 1, NOW())
            ");
            $stmt->execute([$user_info['email'], $password_hash, $full_name, $avatar]);
            
            $user_id = $db->lastInsertId();
            
            // Create loyalty record for new user
            $stmt = $db->prepare("
                INSERT INTO user_loyalty (user_id, current_points, lifetime_points, created_at) 
                VALUES (?, 0, 0, NOW())
            ");
            $stmt->execute([$user_id]);
            
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_email'] = $user_info['email'];
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_role'] = 'customer';
            
            // Log registration
            try {
                require_once '../helpers/logger.php';
                $logger = getLogger();
                $logger->logUserRegistration($user_id, [
                    'email' => $user_info['email'],
                    'user_name' => $full_name,
                    'registration_method' => 'google'
                ]);
            } catch (Exception $logError) {
                error_log("Logger failed: " . $logError->getMessage());
            }
        }
        
        // Redirect to intended page or home
        $redirect = $_SESSION['intended_url'] ?? '../index.php';
        unset($_SESSION['intended_url']);
        header('Location: ' . $redirect);
        exit;
        
    } catch (Exception $e) {
        error_log("Google login error: " . $e->getMessage());
        header('Location: login.php?error=database_error');
        exit;
    }
}

// Handle error from Google OAuth
if (isset($_GET['error'])) {
    $error_message = 'Đăng nhập Google thất bại';
    switch ($_GET['error']) {
        case 'access_denied':
            $error_message = 'Bạn đã từ chối quyền truy cập';
            break;
        case 'invalid_request':
            $error_message = 'Yêu cầu không hợp lệ';
            break;
    }
    header('Location: login.php?google_error=' . urlencode($error_message));
    exit;
}

// Redirect to Google OAuth
$auth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'scope' => 'email profile',
    'response_type' => 'code',
    'access_type' => 'online',
    'prompt' => 'select_account'
]);

header('Location: ' . $auth_url);
exit;
?>