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

/**
 * Kiểm tra xem session có hợp lệ không
 * Session không hợp lệ nếu user_id = 0 hoặc không tồn tại
 * 
 * @return bool
 */
function isValidSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Kiểm tra user_id tồn tại và khác 0
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || $_SESSION['user_id'] == 0) {
        return false;
    }
    
    // Kiểm tra các thông tin cần thiết khác
    if (empty($_SESSION['user_email'])) {
        return false;
    }
    
    return true;
}

/**
 * Xóa session nếu không hợp lệ (user_id = 0)
 * Gọi function này ở đầu các trang cần authentication
 * 
 * @return bool True nếu session hợp lệ, False nếu đã xóa session
 */
function validateAndCleanSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Nếu có session nhưng user_id = 0, xóa session
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0) {
        error_log("Invalid session detected: user_id = 0, clearing session");
        destroySessionCompletely(false);
        return false;
    }
    
    return isValidSession();
}

/**
 * Refresh session data từ database
 * Sử dụng khi cần đảm bảo session data đồng bộ với database
 * 
 * @param PDO $db Database connection
 * @return bool
 */
function refreshSessionFromDB($db)
{
    if (!isLoggedIn()) {
        return false;
    }

    try {
        $stmt = $db->prepare("SELECT user_id, email, full_name, user_role, status FROM users WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['user_role'];
            return true;
        } else {
            // User không tồn tại hoặc bị banned, xóa session
            destroySessionCompletely(false);
            return false;
        }
    } catch (Exception $e) {
        error_log("refreshSessionFromDB error: " . $e->getMessage());
        return false;
    }
}

/**
 * Kiểm tra user còn tồn tại và active trong database không
 * Nếu không, tự động xóa session và redirect về login
 * 
 * @param string|null $redirect_url URL để redirect nếu user không hợp lệ
 * @return bool True nếu user hợp lệ
 */
function verifyUserExists($redirect_url = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Nếu chưa đăng nhập, không cần kiểm tra
    if (!isset($_SESSION['user_id'])) {
        return true;
    }

    // Kiểm tra user_id = 0 (database lỗi)
    if ($_SESSION['user_id'] == 0) {
        destroySessionCompletely(false);
        if ($redirect_url) {
            header('Location: ' . $redirect_url . '?error=session_invalid');
            exit;
        }
        return false;
    }

    try {
        require_once __DIR__ . '/../config/database.php';
        $db = getDB();

        $stmt = $db->prepare("SELECT user_id, status FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // User không tồn tại (đã bị xóa)
        if (!$user) {
            destroySessionCompletely(false);
            if ($redirect_url) {
                header('Location: ' . $redirect_url . '?error=account_deleted');
                exit;
            }
            return false;
        }

        // User bị banned
        if ($user['status'] === 'banned') {
            destroySessionCompletely(false);
            if ($redirect_url) {
                header('Location: ' . $redirect_url . '?error=account_banned');
                exit;
            }
            return false;
        }

        // User inactive
        if ($user['status'] === 'inactive') {
            destroySessionCompletely(false);
            if ($redirect_url) {
                header('Location: ' . $redirect_url . '?error=account_inactive');
                exit;
            }
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log("verifyUserExists error: " . $e->getMessage());
        return true; // Không xóa session nếu có lỗi database
    }
}

/**
 * Middleware để kiểm tra authentication
 * Gọi ở đầu các trang cần đăng nhập
 * 
 * @param string $login_url URL trang login
 * @param bool $verify_db Có kiểm tra database không
 * @return void
 */
function requireAuth($login_url = '/auth/login.php', $verify_db = true)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Kiểm tra session hợp lệ
    if (!validateAndCleanSession()) {
        header('Location: ' . $login_url . '?error=session_expired');
        exit;
    }

    // Kiểm tra user còn tồn tại trong database
    if ($verify_db) {
        verifyUserExists($login_url);
    }
}
