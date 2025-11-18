<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_POST['user_id'] ?? null;
$new_password = $_POST['new_password'] ?? '';

if (!$user_id || strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
    exit;
}

try {
    $db = getDB();
    
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE user_id = :user_id");
    $stmt->execute([
        ':password_hash' => $password_hash,
        ':user_id' => $user_id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Reset mật khẩu thành công']);
    
} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
