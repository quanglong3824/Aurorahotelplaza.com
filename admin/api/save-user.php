<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_POST['user_id'] ?? null;
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$user_role = $_POST['user_role'] ?? '';
$status = $_POST['status'] ?? 'active';
$password = $_POST['password'] ?? '';

if (empty($full_name) || empty($email) || empty($user_role)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
    exit;
}

try {
    $db = getDB();

    // Check duplicate email
    if ($user_id) {
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :id");
        $stmt->execute([':email' => $email, ':id' => $user_id]);
    } else {
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
    }

    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email đã tồn tại']);
        exit;
    }

    if ($user_id) {
        // Update existing user
        $is_new = false;
        $stmt = $db->prepare("
            UPDATE users SET
                full_name = :full_name,
                email = :email,
                phone = :phone,
                user_role = :user_role,
                status = :status,
                updated_at = NOW()
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            ':full_name' => $full_name,
            ':email' => $email,
            ':phone' => $phone,
            ':user_role' => $user_role,
            ':status' => $status,
            ':user_id' => $user_id
        ]);

        $message = 'Cập nhật nhân viên thành công';

    } else {
        // Insert new user
        $is_new = true;
        if (empty($password) || strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
            exit;
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (
                email, password_hash, full_name, phone, user_role, 
                status, email_verified, created_at
            ) VALUES (
                :email, :password_hash, :full_name, :phone, :user_role,
                :status, 1, NOW()
            )
        ");

        $stmt->execute([
            ':email' => $email,
            ':password_hash' => $password_hash,
            ':full_name' => $full_name,
            ':phone' => $phone,
            ':user_role' => $user_role,
            ':status' => $status
        ]);

        $user_id = $db->lastInsertId();
        $message = 'Thêm nhân viên thành công';
    }

    // Log activity (không để crash user creation)
    try {
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at)
            VALUES (:user_id, :action, 'user', :entity_id, :description, :ip_address, NOW())
        ");
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':action' => $is_new ? 'create_user' : 'update_user',
            ':entity_id' => $user_id,
            ':description' => "$message: $full_name",
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $logErr) {
        error_log("Activity log error: " . $logErr->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'user_id' => $user_id
    ]);

} catch (Exception $e) {
    error_log("Save user error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
