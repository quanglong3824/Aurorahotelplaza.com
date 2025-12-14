<?php
/**
 * Session Helper Functions
 * Quản lý session một cách an toàn và nhất quán
 */

/**
 * Xóa hoàn toàn session hiện tại
 * Sử dụng khi logout hoặc trước khi đăng nhập user mới
 * 
 * @param bool $start_new Có tạo session mới sau khi xóa không
 * @return void
 */
function destroySessionCompletely($start_new = false) {
    // Đảm bảo session đã được start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Xóa tất cả session variables
    $_SESSION = array();
    
    // Xóa session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    // Xóa PHPSESSID cookie trực tiếp (backup)
    if (isset($_COOKIE['PHPSESSID'])) {
        setcookie('PHPSESSID', '', time() - 42000, '/');
    }
    
    // Destroy session
    session_destroy();
    
    // Unset session cookie trong $_COOKIE array
    unset($_COOKIE[session_name()]);
    
    // Tạo session mới nếu cần
    if ($start_new) {
        session_start();
        session_regenerate_id(true);
    }
}

/**
 * Đăng nhập user mới - xóa session cũ và tạo session mới
 * 
 * @param array $user_data Thông tin user từ database
 * @param string|null $intended_url URL để redirect sau khi login
 * @return void
 */
function loginUser($user_data, $intended_url = null) {
    // Xóa session cũ hoàn toàn và tạo mới
    destroySessionCompletely(true);
    
    // Set session data cho user mới
    $_SESSION['user_id'] = $user_data['user_id'];
    $_SESSION['user_email'] = $user_data['email'];
    $_SESSION['user_name'] = $user_data['full_name'];
    $_SESSION['user_role'] = $user_data['user_role'];
    $_SESSION['login_time'] = time();
    
    // Lưu intended_url nếu có
    if ($intended_url) {
        $_SESSION['intended_url'] = $intended_url;
    }
}

/**
 * Kiểm tra user đã đăng nhập chưa
 * 
 * @return bool
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Lấy thông tin user hiện tại từ session
 * 
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'customer'
    ];
}
